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

echo ""
echo "Results:"
echo "  $nok files ok"
echo "  $nerr files with errors"

exit 0

