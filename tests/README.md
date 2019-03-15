WebCalendar test code
=====================

The `compile_test.sh` shell script just verifies all PHP files compile
successfully.  Please use this before pushing any commits upstream.

Any other files should be PHP unit tests based on
[PHPUnit](https://phpunit.de/index.html) version 8.0
which requires PHP 7.2.

Download a copy of phpunit-8.0 and place the phar file in your tools
directory:

    wget https://phar.phpunit.de/phpunit-8.0.phar

Example usage for a single file:

    php phpunit-8.0.phar --bootstrap ../includes/functions.php functionsTest.php



