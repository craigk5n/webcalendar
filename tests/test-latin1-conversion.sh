#!/bin/bash
#
# Integration test for tools/convert_latin1_to_utf8.php (issue #649).
#
# Spins up a throwaway MariaDB container, seeds webcal_entry with a MIX of
# encodings in latin1 columns (genuine latin1 bytes, double-encoded UTF-8,
# pure ASCII, already-valid UTF-8), runs the converter, and asserts every
# value ends up as the correct UTF-8 bytes.
#
# Requires: docker, php (cli, mysqli + mbstring). No host MySQL port needed --
# we connect to the container's IP on 3306 directly (Linux bridge network).
#
# Usage: tests/test-latin1-conversion.sh

set -u

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONTAINER="wc-latin1-test-$$"
ROOT_PW="testpw"
DB="calendar_test"
IMAGE="mariadb:10.5"

red()   { printf '\033[31m%s\033[0m\n' "$*"; }
green() { printf '\033[32m%s\033[0m\n' "$*"; }

cleanup() {
  docker rm -f "$CONTAINER" >/dev/null 2>&1 || true
}
trap cleanup EXIT

echo "=== latin1->utf8 converter integration test ==="
echo "Starting MariaDB container ($IMAGE)..."
docker run -d --name "$CONTAINER" \
  -e MYSQL_ROOT_PASSWORD="$ROOT_PW" \
  -e MYSQL_DATABASE="$DB" \
  "$IMAGE" >/dev/null || { red "Failed to start container"; exit 1; }

mysql_exec() {
  docker exec -i "$CONTAINER" mariadb -uroot -p"$ROOT_PW" "$@"
}

echo -n "Waiting for MariaDB to accept connections"
for i in $(seq 1 60); do
  if mysql_exec -e "SELECT 1" >/dev/null 2>&1; then
    ready=1; break
  fi
  echo -n "."; sleep 1
done
echo
[ "${ready:-0}" = 1 ] || { red "MariaDB never came up"; docker logs "$CONTAINER" | tail; exit 1; }

# Seed mixed-encoding data into latin1 columns using raw byte (hex) literals.
echo "Seeding mixed-encoding data..."
mysql_exec "$DB" <<'SQL'
CREATE TABLE webcal_entry (
  cal_id INT PRIMARY KEY,
  cal_name VARCHAR(80) CHARACTER SET latin1,
  cal_description TEXT CHARACTER SET latin1,
  cal_location VARCHAR(100) CHARACTER SET latin1
);
INSERT INTO webcal_entry (cal_id, cal_name, cal_description) VALUES
  (1, 0x54657374,                               NULL),                 -- "Test" ASCII
  (2, 0xE4,                                     0xC3A4),               -- latin1 'ä' ; desc already-UTF-8 'ä'
  (3, 0x5363686C6FDF,                           0xF6),                 -- latin1 'Schloß' ; desc latin1 'ö'
  (4, 0xC3A4,                                   NULL),                 -- already-UTF-8 'ä'  (must be kept)
  (5, 0x457669202854696572E4727A74696E29,       NULL),                -- 'Evi (Tierärztin)' latin1
  (6, 0x636166C3A9,                             NULL),                 -- already-UTF-8 'café' (kept)
  (7, 0x4DFC6C6C6572,                           NULL);                 -- latin1 'Müller'
SQL
[ $? -eq 0 ] || { red "Seed failed"; exit 1; }

CONTAINER_IP="$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$CONTAINER")"
[ -n "$CONTAINER_IP" ] || { red "Could not determine container IP"; exit 1; }
echo "Container IP: $CONTAINER_IP"

run_converter() {
  WEBCALENDAR_DB_TYPE=mysql \
  WEBCALENDAR_DB_HOST="$CONTAINER_IP" \
  WEBCALENDAR_DB_DATABASE="$DB" \
  WEBCALENDAR_DB_LOGIN=root \
  WEBCALENDAR_DB_PASSWORD="$ROOT_PW" \
    php "$REPO_ROOT/tools/convert_latin1_to_utf8.php" "$@"
}

echo
echo "--- Dry run (must change nothing) ---"
dry_out="$(run_converter --dry-run --force)"
echo "$dry_out" | grep -q "DRY RUN MODE" || { red "dry-run banner missing"; exit 1; }
echo "$dry_out" | grep -q "would transcode" || { red "dry-run did not report transcode counts"; exit 1; }
dry_hex="$(mysql_exec -N -B "$DB" -e \
  "SELECT HEX(CAST(cal_name AS BINARY)) FROM webcal_entry WHERE cal_id=2")"
if [ "$dry_hex" = "E4" ]; then
  green "  PASS dry-run left data untouched (id=2 still E4)"
else
  red "  FAIL dry-run modified data (id=2 now $dry_hex, expected E4)"; exit 1
fi

echo
echo "--- Running converter ---"
WEBCALENDAR_DB_TYPE=mysql \
WEBCALENDAR_DB_HOST="$CONTAINER_IP" \
WEBCALENDAR_DB_DATABASE="$DB" \
WEBCALENDAR_DB_LOGIN=root \
WEBCALENDAR_DB_PASSWORD="$ROOT_PW" \
  php "$REPO_ROOT/tools/convert_latin1_to_utf8.php" --force
conv_rc=$?
echo "--- Converter exit code: $conv_rc ---"
echo

# Expected raw UTF-8 bytes (uppercase hex) after conversion.
declare -A expect_name=(
  [1]=54657374                                    # Test
  [2]=C3A4                                         # ä
  [3]=5363686C6FC39F                               # Schloß
  [4]=C3A4                                         # ä (kept)
  [5]=457669202854696572C3A4727A74696E29           # Evi (Tierärztin)
  [6]=636166C3A9                                   # café (kept)
  [7]=4DC3BC6C6C6572                               # Müller
)
declare -A expect_desc=(
  [2]=C3A4                                         # ä (kept)
  [3]=C3B6                                         # ö
)

fail=0
check() {
  local label="$1" id="$2" want="$3" got="$4"
  if [ "$got" = "$want" ]; then
    green "  PASS $label id=$id -> $got"
  else
    red   "  FAIL $label id=$id -> got=$got want=$want"
    fail=1
  fi
}

echo "--- Verifying cal_name bytes ---"
while IFS=$'\t' read -r id hex; do
  [ -n "${expect_name[$id]:-}" ] && check cal_name "$id" "${expect_name[$id]}" "$hex"
done < <(mysql_exec -N -B "$DB" -e \
  "SELECT cal_id, HEX(CAST(cal_name AS BINARY)) FROM webcal_entry ORDER BY cal_id")

echo "--- Verifying cal_description bytes ---"
while IFS=$'\t' read -r id hex; do
  [ -n "${expect_desc[$id]:-}" ] && check cal_description "$id" "${expect_desc[$id]}" "$hex"
done < <(mysql_exec -N -B "$DB" -e \
  "SELECT cal_id, HEX(CAST(cal_description AS BINARY)) FROM webcal_entry WHERE cal_description IS NOT NULL ORDER BY cal_id")

echo "--- Verifying column charset is now utf8mb4 ---"
charset="$(mysql_exec -N -B "$DB" -e \
  "SELECT CHARACTER_SET_NAME FROM information_schema.COLUMNS \
   WHERE TABLE_SCHEMA='$DB' AND TABLE_NAME='webcal_entry' AND COLUMN_NAME='cal_name'")"
check column_charset cal_name utf8mb4 "$charset"

echo
if [ "$fail" = 0 ] && [ "$conv_rc" = 0 ]; then
  green "=== ALL CHECKS PASSED ==="
  exit 0
else
  red "=== TEST FAILED ==="
  exit 1
fi
