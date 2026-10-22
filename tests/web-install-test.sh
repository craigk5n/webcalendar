#!/bin/bash
set -e

# WebCalendar Web-Based Installer Test Script
# Tests the wizard/ installer via HTTP requests
#
# Usage:
#   ./tests/web-install-test.sh              # Run test, keep DB on success
#   ./tests/web-install-test.sh --cleanup    # Clean up database after test
#   ./tests/web-install-test.sh --debug      # Keep all files for debugging
#
# This script can run locally or in CI/GitHub Actions

# Configuration
PHP_VERSION=${PHP_VERSION:-8.2}
PORT=${PORT:-8001}
COOKIE_JAR="cookies.txt"
HEADERS="headers.txt"
CURL_LOG="curl_error.log"
DEBUG_MODE=0
CLEANUP_MODE=0
KEEP_DB=1

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
    rm -f step*_response.html headers_step*.txt
    rm -f php_server.log
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

# Create temporary SQLite database
DB_TMPFILE=$(mktemp --tmpdir webcalendar-wizard.sqlite.XXXXXX)
log "Created temporary SQLite database at $DB_TMPFILE"
touch "$DB_TMPFILE"
chmod 666 "$DB_TMPFILE"
sqlite3 "$DB_TMPFILE" "VACUUM;"

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

# Verify required files exist
log "Verifying required files..."
for file in wizard/index.php wizard/WizardState.php wizard/WizardDatabase.php wizard/WizardValidator.php includes/dbi4php.php; do
  if [ ! -f "$file" ]; then
    error_exit "Required file missing: $file"
  fi
done

# Note: The wizard will set the session cookie automatically.
# We just need to use the cookie jar to maintain the session.

# Kill any existing PHP server on this port
pkill -f "php -S localhost:$PORT" 2>/dev/null || true
sleep 1

# Start PHP server
# Note: display_errors=Off prevents PHP warnings from corrupting JSON responses
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
    # Linux proc filesystem check
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
rm -f step*_response.json headers_step*.txt

# Helper function for curl requests
# Usage: do_curl <step_name> <url_path> <expected_http_code> <post_data> <extract_json_field>
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

# Step 1: Access wizard welcome page
log "Step 1: Accessing wizard welcome page..."
do_curl "step1" "/wizard/" "200"

# Step 2: Start installation (welcome-continue)
log "Step 2: Starting installation..."
do_curl "step2" "/wizard/index.php" "200" "action=welcome-continue"
check_json_success "step2"
NEXT_STEP=$(jq -r '.nextStep // empty' step2_response.json)
log "Next step: $NEXT_STEP"
if [ "$NEXT_STEP" != "auth" ]; then
  error_exit "Expected nextStep to be 'auth', got '$NEXT_STEP'"
fi

# Step 3: Set installation password
log "Step 3: Setting installation password..."
do_curl "step3" "/wizard/index.php" "200" "action=save-install-password&password=Test123!&password2=Test123!&hint=TestPasswordHint"
check_json_success "step3"
NEXT_STEP=$(jq -r '.redirect // empty' step3_response.json)
log "Redirect to: $NEXT_STEP"

# Step 4: Skip PHP settings acknowledgment (they should already be correct)
log "Step 4: Acknowledging PHP settings..."
do_curl "step4" "/wizard/index.php" "200" "action=save-php-settings-ack"
check_json_success "step4"
NEXT_STEP=$(jq -r '.nextStep // empty' step4_response.json)
log "Next step: $NEXT_STEP"

# Step 5: Save application settings
log "Step 5: Saving application settings..."
do_curl "step5" "/wizard/index.php" "200" "action=save-app-settings&user_auth=web&user_db=user.php&readonly=false&run_mode=prod"
check_json_success "step5"
NEXT_STEP=$(jq -r '.nextStep // empty' step5_response.json)
log "Next step: $NEXT_STEP"

# Step 6: Test database connection
log "Step 6: Testing database connection..."
do_curl "step6" "/wizard/index.php" "200" \
  "action=test-db-connection&db_type=sqlite3&db_host=localhost&db_login=&db_password=&db_database=$DB_TMPFILE&db_cachedir=&db_debug=false"
check_json_success "step6"
DB_EXISTS=$(jq -r '.databaseExists // false' step6_response.json)
log "Database exists: $DB_EXISTS"

# Step 7: Save database settings
log "Step 7: Saving database settings..."
do_curl "step7" "/wizard/index.php" "200" \
  "action=save-db-settings&db_type=sqlite3&db_host=localhost&db_login=&db_password=&db_database=$DB_TMPFILE&db_cachedir=&db_debug=false"
check_json_success "step7"
NEXT_STEP=$(jq -r '.nextStep // empty' step7_response.json)
log "Next step: $NEXT_STEP"

# Step 8: Create database (for SQLite, this is mostly a formality)
if [ "$NEXT_STEP" = "createdb" ]; then
  log "Step 8: Creating database..."
  do_curl "step8" "/wizard/index.php" "200" "action=create-database"
  check_json_success "step8"
  NEXT_STEP=$(jq -r '.nextStep // empty' step8_response.json)
  log "Next step: $NEXT_STEP"
fi

# Step 9: Execute upgrade (create tables)
if [ "$NEXT_STEP" = "dbtables" ]; then
  log "Step 9: Creating database tables..."
  do_curl "step9" "/wizard/index.php" "200" "action=execute-upgrade"
  check_json_success "step9"
  NEXT_STEP=$(jq -r '.nextStep // empty' step9_response.json)
  log "Next step: $NEXT_STEP"
fi

# Step 10: Create admin user
if [ "$NEXT_STEP" = "adminuser" ]; then
  log "Step 10: Creating admin user..."
  do_curl "step10" "/wizard/index.php" "200" \
    "action=create-admin-user&admin_login=admin&admin_password=Admin123!&admin_password2=Admin123!&admin_email=admin@example.com"
  check_json_success "step10"
  NEXT_STEP=$(jq -r '.nextStep // empty' step10_response.json)
  log "Next step: $NEXT_STEP"
fi

# Step 11: Save settings file (unless using env vars)
if [ "$NEXT_STEP" = "summary" ]; then
  log "Step 11: Saving settings file..."
  do_curl "step11" "/wizard/index.php" "200" "action=save-settings-file"
  check_json_success "step11"
  NEXT_STEP=$(jq -r '.nextStep // empty' step11_response.json)
  log "Next step: $NEXT_STEP"
fi

# Step 12: Verify installation
if [ "$NEXT_STEP" = "finish" ]; then
  log "Step 12: Installation complete! Verifying..."
  
  # Check that database tables exist
  if ! sqlite3 "$DB_TMPFILE" "SELECT name FROM sqlite_master WHERE type='table' AND name='webcal_config';" | grep -q "webcal_config"; then
    error_exit "webcal_config table not found in database"
  fi
  log "Verified: webcal_config table exists"
  
  if ! sqlite3 "$DB_TMPFILE" "SELECT name FROM sqlite_master WHERE type='table' AND name='webcal_user';" | grep -q "webcal_user"; then
    error_exit "webcal_user table not found in database"
  fi
  log "Verified: webcal_user table exists"
  
  # Check admin user exists
  ADMIN_COUNT=$(sqlite3 "$DB_TMPFILE" "SELECT COUNT(*) FROM webcal_user WHERE cal_login='admin';")
  if [ "$ADMIN_COUNT" -lt 1 ]; then
    error_exit "Admin user not found in database"
  fi
  log "Verified: Admin user exists"
  
  # Check version is set and matches expected version
  VERSION=$(sqlite3 "$DB_TMPFILE" "SELECT cal_value FROM webcal_config WHERE cal_setting='WEBCAL_PROGRAM_VERSION';")
  if [ -z "$VERSION" ]; then
    error_exit "WEBCAL_PROGRAM_VERSION not set in database"
  fi
  
  # Get expected version from bump_version.sh (format: v1.9.14)
  EXPECTED_VERSION=""
  if [ -f "bump_version.sh" ]; then
    EXPECTED_VERSION="v$(./bump_version.sh -p)"
  fi
  
  if [ -n "$EXPECTED_VERSION" ]; then
    if [ "$VERSION" != "$EXPECTED_VERSION" ]; then
      error_exit "Version mismatch: expected $EXPECTED_VERSION, got $VERSION"
    fi
    log "Verified: Version $VERSION matches expected $EXPECTED_VERSION"
  else
    log "Verified: Version $VERSION set in database (bump_version.sh not found, skipping version match check)"
  fi
  
  # Check settings.php was created
  if [ -f "includes/settings.php" ]; then
    log "Verified: includes/settings.php created"
  else
    error_exit "includes/settings.php was not created"
  fi
  
  log "SUCCESS: WebCalendar installation completed successfully!"
else
  error_exit "Unexpected final step: $NEXT_STEP (expected 'finish')"
fi

# If we get here, everything passed!
log "All tests passed."

# Don't cleanup here - let the trap do it, but control DB retention via KEEP_DB
exit 0
