WebCalendar test code
=====================

The `compile_test.sh` shell script just verifies all PHP files compile
successfully.  Please use this before pushing any commits upstream.

Any other files should be PHP unit tests based on
[PHPUnit](https://phpunit.de/index.html) version 5.7,
which is the latest version that supports PHP 5.X.  (The newer version of
PHPUnit requires PHP7.)

Example usage for a single file:

    phpunit --bootstrap ../includes/functions.php functionsTest.php



