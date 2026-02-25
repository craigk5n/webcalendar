#!/bin/bash

# WebCalendar SQLite Installer Test Runner
# Runs both new installation and upgrade tests using Docker

set -e

# Configuration
COMPOSE_FILE="docker/docker compose-test-sqlite.yml"

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
