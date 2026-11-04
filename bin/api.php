<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit;
}

require dirname(__DIR__) . '/vendor/autoload.php';
$apiEnvironment = WebCalendar\Api\Environment::load(dirname(__DIR__));
define('WEBCALENDAR_API_BOOTSTRAP', true);
require dirname(__DIR__) . '/includes/api-bootstrap.php';
$app = require dirname(__DIR__) . '/app/Api/bootstrap.php';
exit($app->handleCommand(new Symfony\Component\Console\Input\ArgvInput()));
