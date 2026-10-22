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

# Get list of *.php files at the repo root, excluding vendor/
allfiles=`find . -name "*.php" -not -path './vendor/*' -print`

tmp=/tmp/phpcompiletest.$$

nok=0
nerr=0
# loop through them.
for f in $allfiles;
do
  echo "File: $f"
  out=`php -l $f >$tmp`
  res=`grep 'No syntax error' $tmp | wc -l | tr -d ' '`
  if [ "$res" == "1" ] ; then
    # no syntax error :-)
    nok=$((nok+1))
  else
    echo "PHP compile error in $f"
    cat $tmp
    nerr=$((nerr+1))
  fi
done

rm -f $tmp

# Check composer.lock is in sync with composer.json
if command -v composer &> /dev/null; then
  if composer update --lock --dry-run 2>&1 | grep -q "Nothing to modify in lock file"; then
    echo "composer.lock is up to date"
  else
    echo "ERROR: composer.lock is out of date. Run 'composer update --lock' to fix."
    nerr=$((nerr+1))
  fi
fi

echo ""
echo "Results:"
echo "  $nok files ok"
echo "  $nerr files with errors"

exit $nerr

