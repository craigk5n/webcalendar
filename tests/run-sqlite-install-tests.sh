#!/bin/bash

# WebCalendar SQLite Installer Test Runner
# Runs both new installation and upgrade tests using Docker

set -e

# These tests drive the web installation wizard against a container that bind
# mounts this working copy, and tests/web-install-*.py deletes
# includes/settings.php as part of its setup. On a CI checkout that costs
# nothing. On a working copy that is also a live install it removes the live
# configuration and takes the calendar down until someone restores it.
#
# CI sets WEBCAL_TEST_ALLOW_DESTRUCTIVE=1. Locally, copy settings.php
# somewhere safe and move it out of the way first, or run the tests against a
# separate clone.
if [ -f includes/settings.php ] && [ "${WEBCAL_TEST_ALLOW_DESTRUCTIVE:-}" != "1" ]; then
  echo "REFUSING: includes/settings.php exists in this working copy." >&2
  echo "" >&2
  echo "This test deletes it. If this checkout is a live WebCalendar install," >&2
  echo "that takes the site down. Move the file aside first, or export" >&2
  echo "WEBCAL_TEST_ALLOW_DESTRUCTIVE=1 if you are certain it is disposable." >&2
  exit 1
fi

# Configuration
COMPOSE_FILE="docker/docker-compose-test-sqlite.yml"

log() {
  echo "[$(date '+%H:%M:%S')] $*"
}

cleanup() {
  log "Cleaning up Docker containers..."
  docker compose -f "$COMPOSE_FILE" down -v --remove-orphans
}

# Trap cleanup on exit
trap cleanup EXIT

log "Starting Docker environment..."
docker compose -f "$COMPOSE_FILE" up -d web chrome

log "Waiting for web server to be ready..."
until docker compose -f "$COMPOSE_FILE" exec -T web curl -s http://localhost/ > /dev/null 2>&1; do
  echo -n "."
  sleep 1
done
echo ""
log "Web server is ready."

log "Running tests..."
# Run the pytest container
if ! docker compose -f "$COMPOSE_FILE" up --exit-code-from pytest pytest; then
  log "Tests failed! Showing container logs..."
  docker compose -f "$COMPOSE_FILE" logs web
  exit 1
fi

log "Tests completed successfully!"
