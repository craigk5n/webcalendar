#!/bin/bash
#
# Please add new unit test files below.

../vendor/bin/phpunit --bootstrap ../includes/functions.php functionsTest.php
../vendor/bin/phpunit --bootstrap ../includes/functions.php RepeatingEventsTest.php
../vendor/bin/phpunit EventTest.php
../vendor/bin/phpunit DocTest.php
../vendor/bin/phpunit DocListTest.php

exit 0
