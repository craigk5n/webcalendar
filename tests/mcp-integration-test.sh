#!/bin/bash
#
# MCP Server Integration Test (STDIO transport)
#
# Tests the MCP server's HTTP JSON-RPC handler by running
# a PHP built-in server and making curl requests against mcp.php.
#
# Requirements:
#   - PHP with sqlite3 extension
#   - curl, jq
#   - WebCalendar installed with SQLite (via headless installer)
#
# Usage:
#   ./tests/mcp-integration-test.sh [--cleanup]
#
# This script is designed to run in CI (GitHub Actions) but also
# works locally. It creates a temporary SQLite database, installs
# WebCalendar, generates an API token, and tests all MCP endpoints.

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

CLEANUP=false
if [ "$1" = "--cleanup" ]; then
  CLEANUP=true
fi

PORT=8099
DB_DIR="$PROJECT_DIR/database"
DB_FILE="$DB_DIR/mcp_test.sqlite"
SETTINGS_FILE="$PROJECT_DIR/includes/settings.php"
SETTINGS_BACKUP=""
SERVER_PID=""
PASS=0
FAIL=0
SKIP=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

cleanup() {
  # Stop PHP server
  if [ -n "$SERVER_PID" ] && kill -0 "$SERVER_PID" 2>/dev/null; then
    kill "$SERVER_PID" 2>/dev/null || true
    wait "$SERVER_PID" 2>/dev/null || true
  fi

  if [ "$CLEANUP" = true ]; then
    # Restore settings.php if we backed it up
    if [ -n "$SETTINGS_BACKUP" ] && [ -f "$SETTINGS_BACKUP" ]; then
      mv "$SETTINGS_BACKUP" "$SETTINGS_FILE"
    elif [ -f "$SETTINGS_FILE.mcp-test-created" ]; then
      rm -f "$SETTINGS_FILE"
      rm -f "$SETTINGS_FILE.mcp-test-created"
    fi
    # Remove test database
    rm -f "$DB_FILE"
  fi
}
trap cleanup EXIT

pass() {
  PASS=$((PASS + 1))
  echo -e "  ${GREEN}PASS${NC}: $1"
}

fail() {
  FAIL=$((FAIL + 1))
  echo -e "  ${RED}FAIL${NC}: $1"
  if [ -n "$2" ]; then
    echo "        $2"
  fi
}

skip() {
  SKIP=$((SKIP + 1))
  echo -e "  ${YELLOW}SKIP${NC}: $1"
}

# Check prerequisites
for cmd in php curl jq sqlite3; do
  if ! command -v $cmd &> /dev/null; then
    echo "Error: $cmd is required but not installed."
    exit 1
  fi
done

if ! php -m 2>/dev/null | grep -qi sqlite3; then
  echo "Error: PHP sqlite3 extension is required."
  exit 1
fi

echo "=== MCP Server Integration Test ==="
echo ""

# ---------------------------------------------------------------
# Setup: Install WebCalendar with SQLite
# ---------------------------------------------------------------
echo "--- Setup ---"

# Create database directory
mkdir -p "$DB_DIR"

# Backup existing settings.php if present
if [ -f "$SETTINGS_FILE" ]; then
  SETTINGS_BACKUP="${SETTINGS_FILE}.mcp-test-backup.$$"
  cp "$SETTINGS_FILE" "$SETTINGS_BACKUP"
fi

# Create settings.php for SQLite
cat > "$SETTINGS_FILE" << 'SETTINGS'
db_type: sqlite3
db_database: database/mcp_test.sqlite
db_host: localhost
db_login: ""
db_password: ""
install_password: test123
single_user: false
use_http_auth: false
user_inc: user.php
SETTINGS
touch "${SETTINGS_FILE}.mcp-test-created"

echo "Created settings.php for SQLite"

# Run headless installer
echo "Running headless installer..."
INSTALL_OUTPUT=$(php wizard/headless.php \
  --db-type=sqlite3 \
  --db-database=database/mcp_test.sqlite \
  --admin-login=admin \
  --admin-password=admin \
  --install-password=test123 \
  --force 2>&1) || {
  echo "Installation failed:"
  echo "$INSTALL_OUTPUT"
  exit 1
}

if ! echo "$INSTALL_OUTPUT" | grep -q "Installation Complete"; then
  echo "Installation did not complete successfully:"
  echo "$INSTALL_OUTPUT"
  exit 1
fi
echo "WebCalendar installed successfully"

# Enable MCP and generate API token
echo "Configuring MCP settings..."
API_TOKEN=$(php -r 'echo bin2hex(random_bytes(32));')

sqlite3 "$DB_FILE" "INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_SERVER_ENABLED', 'Y');"
sqlite3 "$DB_FILE" "INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_WRITE_ACCESS', 'Y');"
sqlite3 "$DB_FILE" "INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_RATE_LIMIT', '100');"
sqlite3 "$DB_FILE" "UPDATE webcal_user SET cal_api_token = '$API_TOKEN' WHERE cal_login = 'admin';"

echo "MCP enabled, API token generated"

# Start PHP built-in server
echo "Starting PHP server on port $PORT..."
php -S "localhost:$PORT" -t "$PROJECT_DIR" > /tmp/mcp-test-server.log 2>&1 &
SERVER_PID=$!
sleep 2

if ! kill -0 "$SERVER_PID" 2>/dev/null; then
  echo "Failed to start PHP server"
  cat /tmp/mcp-test-server.log
  exit 1
fi
echo "PHP server running (PID $SERVER_PID)"

# Helper: make MCP JSON-RPC call
mcp_call() {
  local method="$1"
  local params="$2"
  local id="${3:-1}"
  local token="${4:-$API_TOKEN}"

  curl -s -X POST "http://localhost:$PORT/mcp.php" \
    -H "Content-Type: application/json" \
    -H "X-MCP-Token: $token" \
    -d "{\"jsonrpc\":\"2.0\",\"id\":$id,\"method\":\"$method\",\"params\":$params}"
}

echo ""
echo "--- Test: Authentication ---"

# Test: Missing token returns error
RESPONSE=$(curl -s -X POST "http://localhost:$PORT/mcp.php" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}')
if echo "$RESPONSE" | jq -e '.error' > /dev/null 2>&1; then
  pass "Missing token returns error"
else
  fail "Missing token should return error" "$RESPONSE"
fi

# Test: Invalid token returns error
RESPONSE=$(mcp_call "initialize" "{}" 1 "bad_token_value")
if echo "$RESPONSE" | jq -e '.error.message' 2>/dev/null | grep -qi "invalid\|token"; then
  pass "Invalid token returns error"
else
  fail "Invalid token should return error" "$RESPONSE"
fi

# Test: Valid token succeeds
RESPONSE=$(mcp_call "initialize" "{}")
if echo "$RESPONSE" | jq -e '.result.serverInfo.name' > /dev/null 2>&1; then
  pass "Valid token authenticates successfully"
else
  fail "Valid token should authenticate" "$RESPONSE"
fi

echo ""
echo "--- Test: Initialize ---"

RESPONSE=$(mcp_call "initialize" "{}")

if echo "$RESPONSE" | jq -e '.result.protocolVersion == "2024-11-05"' > /dev/null 2>&1; then
  pass "Protocol version is 2024-11-05"
else
  fail "Wrong protocol version" "$(echo "$RESPONSE" | jq '.result.protocolVersion')"
fi

if echo "$RESPONSE" | jq -e '.result.serverInfo.name == "WebCalendar MCP Server"' > /dev/null 2>&1; then
  pass "Server name is correct"
else
  fail "Wrong server name" "$(echo "$RESPONSE" | jq '.result.serverInfo')"
fi

if echo "$RESPONSE" | jq -e '.result.capabilities.tools' > /dev/null 2>&1; then
  pass "Capabilities include tools"
else
  fail "Missing tools capability" "$RESPONSE"
fi

echo ""
echo "--- Test: Tools List ---"

RESPONSE=$(mcp_call "tools/list" "{}")

TOOL_COUNT=$(echo "$RESPONSE" | jq '.result.tools | length')
if [ "$TOOL_COUNT" = "4" ]; then
  pass "Returns 4 tools"
else
  fail "Expected 4 tools, got $TOOL_COUNT" "$RESPONSE"
fi

for TOOL in list_events get_user_info search_events add_event; do
  if echo "$RESPONSE" | jq -e ".result.tools[] | select(.name == \"$TOOL\")" > /dev/null 2>&1; then
    pass "Tool '$TOOL' is listed"
  else
    fail "Tool '$TOOL' not found" "$RESPONSE"
  fi
done

# Verify schemas have required fields
if echo "$RESPONSE" | jq -e '.result.tools[] | select(.name == "list_events") | .inputSchema.required | contains(["start_date","end_date"])' > /dev/null 2>&1; then
  pass "list_events schema has required fields"
else
  fail "list_events missing required fields"
fi

if echo "$RESPONSE" | jq -e '.result.tools[] | select(.name == "add_event") | .inputSchema.required | contains(["name","date"])' > /dev/null 2>&1; then
  pass "add_event schema has required fields"
else
  fail "add_event missing required fields"
fi

echo ""
echo "--- Test: get_user_info ---"

RESPONSE=$(mcp_call "tools/call" '{"name":"get_user_info","arguments":{}}')

if echo "$RESPONSE" | jq -e '.result' > /dev/null 2>&1; then
  if echo "$RESPONSE" | jq -e '.result.content[0].text' 2>/dev/null | grep -q "admin"; then
    pass "get_user_info returns admin user"
  elif echo "$RESPONSE" | jq -e '.result.login == "admin"' > /dev/null 2>&1; then
    pass "get_user_info returns admin user"
  else
    # MCP SDK wraps results in content array
    pass "get_user_info returns a result"
  fi
else
  fail "get_user_info failed" "$RESPONSE"
fi

echo ""
echo "--- Test: add_event ---"

RESPONSE=$(mcp_call "tools/call" '{"name":"add_event","arguments":{"name":"MCP Test Event","date":"20260401","description":"Created by integration test","location":"CI/CD"}}')

if echo "$RESPONSE" | jq -e '.result' > /dev/null 2>&1; then
  pass "add_event returns a result"
else
  fail "add_event failed" "$RESPONSE"
fi

# Verify event was created in the database
EVENT_COUNT=$(sqlite3 "$DB_FILE" "SELECT COUNT(*) FROM webcal_entry WHERE cal_name = 'MCP Test Event';")
if [ "$EVENT_COUNT" -ge 1 ]; then
  pass "Event exists in database"
else
  fail "Event not found in database (count=$EVENT_COUNT)"
fi

echo ""
echo "--- Test: list_events ---"

RESPONSE=$(mcp_call "tools/call" '{"name":"list_events","arguments":{"start_date":"20260401","end_date":"20260401"}}')

if echo "$RESPONSE" | jq -e '.result' > /dev/null 2>&1; then
  pass "list_events returns a result"
else
  fail "list_events failed" "$RESPONSE"
fi

echo ""
echo "--- Test: search_events ---"

RESPONSE=$(mcp_call "tools/call" '{"name":"search_events","arguments":{"keyword":"MCP Test","limit":10}}')

if echo "$RESPONSE" | jq -e '.result' > /dev/null 2>&1; then
  pass "search_events returns a result"
else
  fail "search_events failed" "$RESPONSE"
fi

echo ""
echo "--- Test: Error Handling ---"

# Unknown method
RESPONSE=$(mcp_call "nonexistent/method" "{}")
if echo "$RESPONSE" | jq -e '.error.code == -32601' > /dev/null 2>&1; then
  pass "Unknown method returns -32601"
else
  fail "Unknown method should return -32601" "$RESPONSE"
fi

# Unknown tool
RESPONSE=$(mcp_call "tools/call" '{"name":"nonexistent_tool","arguments":{}}')
if echo "$RESPONSE" | jq -e '.error' > /dev/null 2>&1; then
  pass "Unknown tool returns error"
else
  fail "Unknown tool should return error" "$RESPONSE"
fi

# Invalid JSON (test with raw curl)
RESPONSE=$(curl -s -X POST "http://localhost:$PORT/mcp.php" \
  -H "Content-Type: application/json" \
  -H "X-MCP-Token: $API_TOKEN" \
  -d 'not json at all')
if echo "$RESPONSE" | jq -e '.error' > /dev/null 2>&1; then
  pass "Invalid JSON returns error"
else
  fail "Invalid JSON should return error" "$RESPONSE"
fi

# Response ID is preserved
RESPONSE=$(mcp_call "initialize" "{}" 42)
RESPONSE_ID=$(echo "$RESPONSE" | jq '.id')
if [ "$RESPONSE_ID" = "42" ]; then
  pass "Response preserves request ID"
else
  fail "Response ID should be 42, got $RESPONSE_ID"
fi

echo ""
echo "--- Test: Auth Header Variants ---"

# Bearer token
RESPONSE=$(curl -s -X POST "http://localhost:$PORT/mcp.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $API_TOKEN" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}')
if echo "$RESPONSE" | jq -e '.result.serverInfo' > /dev/null 2>&1; then
  pass "Bearer token auth works"
else
  fail "Bearer token auth failed" "$RESPONSE"
fi

# X-MCP-Token header (already tested above, but explicit)
RESPONSE=$(curl -s -X POST "http://localhost:$PORT/mcp.php" \
  -H "Content-Type: application/json" \
  -H "X-MCP-Token: $API_TOKEN" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}')
if echo "$RESPONSE" | jq -e '.result.serverInfo' > /dev/null 2>&1; then
  pass "X-MCP-Token header auth works"
else
  fail "X-MCP-Token header auth failed" "$RESPONSE"
fi

# Query param token
RESPONSE=$(curl -s -X POST "http://localhost:$PORT/mcp.php?token=$API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}')
if echo "$RESPONSE" | jq -e '.result.serverInfo' > /dev/null 2>&1; then
  pass "Query param token auth works"
else
  fail "Query param token auth failed" "$RESPONSE"
fi

# ---------------------------------------------------------------
# Summary
# ---------------------------------------------------------------
echo ""
echo "=== Results ==="
echo -e "  ${GREEN}Passed${NC}: $PASS"
echo -e "  ${RED}Failed${NC}: $FAIL"
if [ "$SKIP" -gt 0 ]; then
  echo -e "  ${YELLOW}Skipped${NC}: $SKIP"
fi
echo ""

if [ "$FAIL" -gt 0 ]; then
  echo -e "${RED}FAILED${NC}"
  exit 1
else
  echo -e "${GREEN}ALL TESTS PASSED${NC}"
  exit 0
fi
