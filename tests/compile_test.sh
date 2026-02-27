#!/bin/bash
#
# Make sure all PHP files compile successfully.

# Get list of *.php files
allfiles=`find ../* -name "*.php" -print | grep -v vendor`

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

