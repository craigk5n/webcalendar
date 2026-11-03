#!/bin/bash
#
# Make sure all PHP files compile successfully.

# Resolve the repo root from this script's own location, then run from there.
# This script lives in tests/, so the repo root is its parent directory.
# Anchoring to the script location (instead of the caller's CWD) fixes two
# things: the previous `find ../*` scanned sibling projects when invoked as
# the documented `./tests/compile_test.sh` from the repo root, and the
# composer.lock check below needs composer.json in the working directory.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$REPO_ROOT" || exit 1

php tools/lint-php.php
nerr=$?

# Check composer.lock is in sync with composer.json
if command -v composer &> /dev/null; then
  if composer validate; then
    echo "composer.lock is up to date"
  else
    echo "ERROR: composer.lock is out of date. Run 'composer update --lock' to fix."
    nerr=$((nerr+1))
  fi
fi

echo ""
echo "Results:"
echo "  $nerr files with errors"

exit $nerr
