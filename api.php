<?php

declare(strict_types=1);

const API_NOT_FOUND = 404;
const API_UNAVAILABLE = 503;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (getenv('WEBCALENDAR_API_ENABLED') !== 'true') {
  http_response_code(API_NOT_FOUND);
  echo json_encode(['message' => 'API is not enabled.']);
  exit;
}

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
  http_response_code(API_UNAVAILABLE);
  echo json_encode(['message' => 'API dependencies are not installed.']);
  exit;
}

require __DIR__ . '/vendor/autoload.php';

try {
  define('WEBCALENDAR_API_BOOTSTRAP', true);
  require __DIR__ . '/includes/api-bootstrap.php';
  $app = require __DIR__ . '/app/Api/bootstrap.php';
  $app->handleRequest(Illuminate\Http\Request::capture());
} catch (Throwable $exception) {
  error_log('WebCalendar API startup failed: ' . get_class($exception));
  http_response_code(API_UNAVAILABLE);
  echo json_encode(['message' => 'API is unavailable. Check the installation and API configuration.']);
}
