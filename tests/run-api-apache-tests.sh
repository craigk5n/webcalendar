#!/bin/sh
set -eu

# Run from the repository root. Each run has its own container and tmpfs database.
project="webcalendar-api-smoke-$(id -u)-$$"
compose_file=docker/docker-compose-api-test.yml
cleanup() {
  status=$?
  trap - EXIT
  if [ "$status" -ne 0 ]; then
    docker compose -p "$project" -f "$compose_file" logs api api-private || true
  fi
  docker compose -p "$project" -f "$compose_file" down --volumes --remove-orphans
  exit "$status"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

docker compose -p "$project" -f "$compose_file" up --build -d --wait
for service in api api-private; do
  docker compose -p "$project" -f "$compose_file" exec -T -u www-data "$service" \
    php tests/fixtures/api-apache-smoke.php
done
