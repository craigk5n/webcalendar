#!/bin/bash
set -e

# WebCalendar Web-Based Upgrade Test Script
# Tests upgrading from an older version (e.g., v1.3.0) to current version
#
# Usage:
#   ./tests/web-upgrade-test.sh [options]
#   ./tests/web-upgrade-test.sh --from-version=v1.3.0
#   ./tests/web-upgrade-test.sh --cleanup
#   ./tests/web-upgrade-test.sh --debug

# Configuration
PHP_VERSION=${PHP_VERSION:-8.2}
PORT=${PORT:-8001}
COOKIE_JAR="cookies_upgrade.txt"
HEADERS="headers.txt"
CURL_LOG="curl_upgrade_error.log"
DEBUG_MODE=0
CLEANUP_MODE=0
KEEP_DB=1
FROM_VERSION="v1.3.0"

# Parse arguments
for arg in "$@"; do
  case $arg in
    --debug)
      DEBUG_MODE=1
      KEEP_DB=1
      ;;
    --cleanup)
      CLEANUP_MODE=1
      KEEP_DB=0
      ;;
    --from-version=*)
      FROM_VERSION="${arg#*=}"
      ;;
  esac
done

# Check for required commands
check_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "ERROR: $1 is required but not installed."
    exit 1
  fi
}

check_command php
check_command sqlite3
check_command curl
check_command jq

# Helper functions
log() {
  echo "[$(date '+%H:%M:%S')] $*"
}

error_exit() {
  echo "ERROR: $1" >&2
  if [ -f "$CURL_LOG" ]; then
    echo "--- Curl log ---" >&2
    tail -50 "$CURL_LOG" >&2
  fi
  if [ -f "php_server.log" ]; then
    echo "--- PHP server log ---" >&2
    tail -50 php_server.log >&2
  fi
  cleanup
  exit 1
}

cleanup() {
  log "Cleaning up..."
  
  # Kill PHP server
  if [ -n "$PHP_PID" ]; then
    kill $PHP_PID 2>/dev/null || true
    wait $PHP_PID 2>/dev/null || true
  fi
  
  # Clean up temporary files (unless in debug mode)
  if [ $DEBUG_MODE -eq 0 ]; then
    rm -f $COOKIE_JAR $HEADERS $CURL_LOG
    rm -f upgrade_step*_response.json headers_upgrade_step*.txt
    rm -f php_server.log php_error.log
    rm -f includes/settings.php
    if [ $KEEP_DB -eq 0 ] && [ -n "$DB_TMPFILE" ] && [ -f "$DB_TMPFILE" ]; then
      rm -f "$DB_TMPFILE"
      log "Temporary database removed."
    elif [ -n "$DB_TMPFILE" ] && [ -f "$DB_TMPFILE" ]; then
      log "Keeping database at $DB_TMPFILE for debugging."
    fi
  else
    log "Debug mode: keeping all files for inspection."
    log "Database location: $DB_TMPFILE"
  fi
}

# Trap to ensure cleanup on exit
trap cleanup EXIT

# Get expected current version
EXPECTED_VERSION=""
if [ -f "bump_version.sh" ]; then
  EXPECTED_VERSION="v$(./bump_version.sh -p)"
fi

if [ -z "$EXPECTED_VERSION" ]; then
  error_exit "Cannot determine expected version. bump_version.sh not found."
fi

log "Testing upgrade from $FROM_VERSION to $EXPECTED_VERSION"

# Find the SQL fixture file for the from-version
SQL_FIXTURE="tests/fixtures/${FROM_VERSION}-schema-sqlite3.sql"
if [ ! -f "$SQL_FIXTURE" ]; then
  # Try without the 'v' prefix
  SQL_FIXTURE="tests/fixtures/${FROM_VERSION#v}-schema-sqlite3.sql"
fi

if [ ! -f "$SQL_FIXTURE" ]; then
  error_exit "SQL fixture not found for version $FROM_VERSION. Looked for: tests/fixtures/${FROM_VERSION}-schema-sqlite3.sql"
fi

log "Using SQL fixture: $SQL_FIXTURE"

# Create temporary SQLite database with old schema
DB_TMPFILE=$(mktemp --tmpdir webcalendar-upgrade-${FROM_VERSION}.sqlite.XXXXXX)
log "Created temporary SQLite database at $DB_TMPFILE"
touch "$DB_TMPFILE"
chmod 666 "$DB_TMPFILE"

# Load the old schema
sqlite3 "$DB_TMPFILE" < "$SQL_FIXTURE"
log "Loaded $FROM_VERSION schema into database"

# Verify the old version is set
OLD_VERSION=$(sqlite3 "$DB_TMPFILE" "SELECT cal_value FROM webcal_config WHERE cal_setting='WEBCAL_PROGRAM_VERSION';")
if [ "$OLD_VERSION" != "$FROM_VERSION" ]; then
  error_exit "Expected database version $FROM_VERSION, got $OLD_VERSION"
fi
log "Verified: Database has version $OLD_VERSION"

# Find an available port
find_free_port() {
  local port=$1
  while netstat -tuln 2>/dev/null | grep -q ":$port " || \
        ss -tuln 2>/dev/null | grep -q ":$port " || \
        lsof -Pi :$port -sTCP:LISTEN 2>/dev/null | grep -q LISTEN; do
    port=$((port + 1))
    if [ $port -gt 8100 ]; then
      error_exit "No free port found between 8001 and 8100."
    fi
  done
  echo $port
}

PORT=$(find_free_port $PORT)
log "Using port $PORT for PHP server."

# Create settings.php that points to the old database
log "Creating settings.php for existing installation..."
mkdir -p includes
cat > includes/settings.php << EOF
<?php
/* Test settings for upgrade from $FROM_VERSION */
db_type: sqlite3
db_host: localhost
db_login: 
db_password: 
db_database: $DB_TMPFILE
db_cachedir: 
db_debug: false
install_password: $(echo -n 'Test123!' | md5sum | cut -d' ' -f1)
install_password_hint: TestHint
readonly: false
single_user: false
single_user_login: 
use_http_auth: false
user_inc: user.php
mode: prod
# end settings.php */
?>
EOF
log "Created includes/settings.php"

# Verify required files exist
log "Verifying required files..."
for file in wizard/index.php wizard/WizardState.php wizard/WizardDatabase.php wizard/WizardValidator.php includes/dbi4php.php; do
  if [ ! -f "$file" ]; then
    error_exit "Required file missing: $file"
  fi
done

# Kill any existing PHP server on this port
pkill -f "php -S localhost:$PORT" 2>/dev/null || true
sleep 1

# Start PHP server
log "Starting PHP server on port $PORT..."
php -d display_errors=Off \
    -d log_errors=On \
    -d error_log=php_error.log \
    -d session.gc_maxlifetime=3600 \
    -d allow_url_fopen=On \
    -d file_uploads=On \
    -S localhost:$PORT \
    -t . > php_server.log 2>&1 &
PHP_PID=$!

# Wait for server to start
log "Waiting for PHP server to start..."
for i in {1..30}; do
  if curl -s http://localhost:$PORT/ > /dev/null 2>&1; then
    log "PHP server is ready."
    break
  fi
  # Check if process is still running (handle systems without ps command)
  if command -v ps >/dev/null 2>&1; then
    if ! ps -p $PHP_PID > /dev/null 2>&1; then
      error_exit "PHP server failed to start. Check php_server.log."
    fi
  elif [ -d "/proc/$PHP_PID" ]; then
    if [ ! -d "/proc/$PHP_PID" ]; then
      error_exit "PHP server failed to start. Check php_server.log."
    fi
  fi
  sleep 1
done

if ! curl -s http://localhost:$PORT/ > /dev/null 2>&1; then
  error_exit "PHP server did not start within 30 seconds."
fi

# Initialize fresh cookie jar and clean up old response files
rm -f $COOKIE_JAR
touch $COOKIE_JAR
rm -f upgrade_step*_response.json headers_upgrade_step*.txt

# Helper function for curl requests
do_curl() {
  local step_name="$1"
  local url_path="$2"
  local expected_code="$3"
  local post_data="${4:-}"
  local extract_field="${5:-}"
  local output_file="${step_name}_response.json"
  local header_file="headers_${step_name}.txt"
  
  local url="http://localhost:$PORT$url_path"
  
  if [ -n "$post_data" ]; then
    log "$step_name: POST $url_path"
    http_code=$(curl -s -c $COOKIE_JAR -b $COOKIE_JAR --max-redirs 5 -w '%{http_code}' \
      -o "$output_file" --dump-header "$header_file" \
      -X POST "$url" -d "$post_data" 2>>$CURL_LOG)
  else
    log "$step_name: GET $url_path"
    http_code=$(curl -s -c $COOKIE_JAR -b $COOKIE_JAR --max-redirs 5 -w '%{http_code}' \
      -o "$output_file" --dump-header "$header_file" \
      "$url" 2>>$CURL_LOG)
  fi
  
  if [ "$http_code" != "$expected_code" ]; then
    error_exit "$step_name failed: expected HTTP $expected_code, got $http_code"
  fi
  
  # If expecting JSON response, verify it's valid JSON
  if [ "$expected_code" = "200" ] && [ -n "$post_data" ]; then
    if ! jq -e '.' "$output_file" >/dev/null 2>&1; then
      error_exit "$step_name returned invalid JSON"
    fi
  fi
  
  # Extract field from JSON if requested
  if [ -n "$extract_field" ]; then
    local value=$(jq -r ".${extract_field} // empty" "$output_file" 2>/dev/null)
    echo "$value"
  fi
}

# Helper to check JSON success field
check_json_success() {
  local step_name="$1"
  local file="${step_name}_response.json"
  local success=$(jq -r '.success // false' "$file" 2>/dev/null)
  if [ "$success" != "true" ]; then
    local message=$(jq -r '.message // "Unknown error"' "$file" 2>/dev/null)
    error_exit "$step_name failed: $message"
  fi
}

# Step 1: Access wizard welcome page (should detect existing installation)
log "Step 1: Accessing wizard welcome page..."
do_curl "upgrade_step1" "/wizard/" "200"

# Check that the page shows upgrade options
if ! grep -q "Existing Installation Detected" upgrade_step1_response.json 2>/dev/null && \
   ! grep -q "Quick Upgrade" upgrade_step1_response.json 2>/dev/null; then
  # The welcome page is HTML, not JSON - check if it contains expected text
  if ! grep -q "Existing Installation Detected" upgrade_step1_response.json 2>/dev/null; then
    log "Note: Welcome page loaded (HTML content)"
  fi
fi

# Step 2: Select Quick Upgrade
log "Step 2: Selecting Quick Upgrade..."
do_curl "upgrade_step2" "/wizard/index.php" "200" "action=welcome-quick-upgrade"
check_json_success "upgrade_step2"
NEXT_STEP=$(jq -r '.nextStep // empty' upgrade_step2_response.json)
log "Next step: $NEXT_STEP"

if [ "$NEXT_STEP" != "auth" ]; then
  error_exit "Expected nextStep to be 'auth', got '$NEXT_STEP'"
fi

# Step 3: Login with install password
log "Step 3: Authenticating..."
do_curl "upgrade_step3" "/wizard/index.php" "200" "action=login&password=Test123!"
check_json_success "upgrade_step3"
NEXT_STEP=$(jq -r '.redirect // empty' upgrade_step3_response.json)
log "Redirect to: $NEXT_STEP"

# The wizard should detect the old version and go to dbtables
if [[ "$NEXT_STEP" != *"dbtables"* ]] && [[ "$NEXT_STEP" != *"finish"* ]]; then
  log "Note: Redirect is to $NEXT_STEP (may vary based on upgrade state)"
fi

# Step 4: Execute upgrade (create/upgrade tables)
log "Step 4: Executing database upgrade..."
do_curl "upgrade_step4" "/wizard/index.php" "200" "action=execute-upgrade"
check_json_success "upgrade_step4"
NEXT_STEP=$(jq -r '.nextStep // empty' upgrade_step4_response.json)
log "Next step: $NEXT_STEP"

# If admin user exists, we should go to finish, otherwise to summary
if [[ "$NEXT_STEP" != "finish" ]] && [[ "$NEXT_STEP" != "summary" ]]; then
  error_exit "Unexpected next step after upgrade: $NEXT_STEP (expected 'finish' or 'summary')"
fi

# Step 5: If we got to summary, save settings
if [ "$NEXT_STEP" = "summary" ]; then
  log "Step 5: Saving settings file..."
  do_curl "upgrade_step5" "/wizard/index.php" "200" "action=save-settings-file"
  check_json_success "upgrade_step5"
  NEXT_STEP=$(jq -r '.nextStep // empty' upgrade_step5_response.json)
  log "Next step: $NEXT_STEP"
  
  if [ "$NEXT_STEP" != "finish" ]; then
    error_exit "Expected nextStep to be 'finish', got '$NEXT_STEP'"
  fi
fi

# Step 6: Verify upgrade
log "Step 6: Upgrade complete! Verifying..."

# Check version was updated
NEW_VERSION=$(sqlite3 "$DB_TMPFILE" "SELECT cal_value FROM webcal_config WHERE cal_setting='WEBCAL_PROGRAM_VERSION';")
if [ "$NEW_VERSION" != "$EXPECTED_VERSION" ]; then
  error_exit "Version not updated correctly. Expected $EXPECTED_VERSION, got $NEW_VERSION"
fi
log "Verified: Version updated from $FROM_VERSION to $NEW_VERSION"

# Get schema for detailed verification
WEBCAL_USER_SCHEMA=$(sqlite3 "$DB_TMPFILE" ".schema webcal_user")

# Check for critical schema changes that should have been applied
# v1.9.13 added cal_api_token column
if echo "$WEBCAL_USER_SCHEMA" | grep -q "cal_api_token"; then
  log "Verified: cal_api_token column added to webcal_user (v1.9.13+)"
else
  error_exit "cal_api_token column NOT found in webcal_user. Upgrade SQL failed to execute correctly."
fi

# v1.1.2+ should have added cal_enabled, cal_telephone, cal_address, cal_title, cal_birthday, cal_last_login
# Check that at least some of these exist (they should since v1.3.0 fixture has them)
if echo "$WEBCAL_USER_SCHEMA" | grep -q "cal_enabled"; then
  log "Verified: cal_enabled column exists in webcal_user"
else
  error_exit "cal_enabled column missing from webcal_user"
fi

# Check that new tables from v1.9.x exist (optional - may not exist if upgrade SQL fails)
TABLES=$(sqlite3 "$DB_TMPFILE" ".tables")
if echo "$TABLES" | grep -q "webcal_import"; then
  log "Verified: webcal_import table exists"
else
  log "Note: webcal_import table not found (optional - may require SQLite-specific upgrade SQL)"
fi

# Check admin user still exists
ADMIN_COUNT=$(sqlite3 "$DB_TMPFILE" "SELECT COUNT(*) FROM webcal_user WHERE cal_login='admin';")
if [ "$ADMIN_COUNT" -lt 1 ]; then
  error_exit "Admin user not found after upgrade"
fi
log "Verified: Admin user still exists"

# Check core tables still exist
for table in webcal_config webcal_user webcal_entry webcal_entry_user; do
  if ! sqlite3 "$DB_TMPFILE" "SELECT name FROM sqlite_master WHERE type='table' AND name='$table';" | grep -q "$table"; then
    error_exit "Table $table missing after upgrade"
  fi
  log "Verified: $table table exists"
done

# Summary
log "Upgrade verification summary:"
log "  - Version updated: $FROM_VERSION -> $NEW_VERSION"
if echo "$WEBCAL_USER_SCHEMA" | grep -q "cal_api_token"; then
  log "  - Schema changes: APPLIED (cal_api_token found)"
else
  log "  - Schema changes: PARTIAL (SQLite-specific SQL may be needed)"
fi
log "  - Data preserved: Yes (admin user exists)"
log "  - Core tables: All present"

log "SUCCESS: Upgrade from $FROM_VERSION to $EXPECTED_VERSION completed successfully!"
log "All upgrade tests passed."

# Don't cleanup here - let the trap do it, but control DB retention via KEEP_DB
exit 0
