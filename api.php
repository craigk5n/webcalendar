<?php

declare(strict_types=1);

const API_NOT_FOUND = 404;
const API_UNAVAILABLE = 503;

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/app/Api/Environment.php';

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
  $enabled = getenv('WEBCALENDAR_API_ENABLED');
  $configured = $enabled === false
    ? @is_file(WebCalendar\Api\Environment::directory(__DIR__) . '/.env')
    : filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
  http_response_code($configured ? API_UNAVAILABLE : API_NOT_FOUND);
  echo json_encode(['message' => $configured ? 'API dependencies are not installed.' : 'API is not enabled.']);
  exit;
}

try {
  require __DIR__ . '/vendor/autoload.php';
  $apiEnvironment = WebCalendar\Api\Environment::load(__DIR__, $_SERVER['DOCUMENT_ROOT'] ?? null);
  if (!$apiEnvironment->enabled()) {
    http_response_code(API_NOT_FOUND);
    echo json_encode(['message' => 'API is not enabled.']);
    exit;
  }
  define('WEBCALENDAR_API_BOOTSTRAP', true);
  require __DIR__ . '/includes/api-bootstrap.php';
  $app = require __DIR__ . '/app/Api/bootstrap.php';
  $app->handleRequest(Illuminate\Http\Request::capture());
} catch (Throwable $exception) {
  error_log('WebCalendar API startup failed: ' . get_class($exception));
  http_response_code(API_UNAVAILABLE);
  echo json_encode(['message' => 'API is unavailable. Check the installation and API configuration.']);
}
