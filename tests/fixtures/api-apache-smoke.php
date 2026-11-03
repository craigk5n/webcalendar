<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit;
}

$fixtureDatabase = '/tmp/webcalendar-api-smoke/calendar.sqlite';
if (getenv('WEBCALENDAR_DB_TYPE') !== 'sqlite3'
    || getenv('WEBCALENDAR_DB_DATABASE') !== $fixtureDatabase) {
  throw new RuntimeException('Run this check only in the disposable Apache API fixture');
}
$db = new PDO('sqlite:' . $fixtureDatabase, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('TIMEZONE', 'UTC')");
foreach ([1 => 20261026, 2 => 20261005] as $id => $date) {
  $statement = $db->prepare("INSERT INTO webcal_entry (cal_id, cal_name, cal_description, cal_date, cal_time, cal_duration, cal_access, cal_type, cal_create_by, cal_priority, cal_mod_date, cal_mod_time) VALUES (?, 'Recurring smoke fixture', '', ?, 100000, 60, 'P', 'M', 'api-smoke', 5, 20261001, 120000)");
  $statement->execute([$id, $date]);
  $statement = $db->prepare("INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status, cal_percent) VALUES (?, 'api-smoke', 'A', 0)");
  $statement->execute([$id]);
  $statement = $db->prepare("INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_end, cal_endtime, cal_byday, cal_wkst) VALUES (?, ?, 1, 20261130, 235959, ?, 'MO')");
  $statement->execute([$id, $id === 1 ? 'weekly' : 'monthlyByDay', $id === 1 ? 'MO,WE,FR' : '1MO']);
}

function api_smoke_request(string $method, string $path, ?string $token = null, ?array $body = null): array
{
  $headers = "Accept: application/json\r\nContent-Type: application/json\r\n";
  if ($token !== null) {
    $headers .= "Authorization: Bearer $token\r\n";
  }
  $context = stream_context_create(['http' => [
    'method' => $method, 'header' => $headers, 'ignore_errors' => true,
    'timeout' => 15, 'content' => $body === null ? '' : json_encode($body),
  ]]);
  $stream = fopen('http://127.0.0.1/api.php/v1' . $path, 'rb', false, $context);
  if ($stream === false) {
    throw new RuntimeException("$method $path could not connect");
  }
  $responseHeaders = stream_get_meta_data($stream)['wrapper_data'];
  $raw = stream_get_contents($stream);
  fclose($stream);
  preg_match('/\s(\d{3})\s/', $responseHeaders[0], $matches);
  $decoded = json_decode((string) $raw, true);
  if (!is_array($decoded)) {
    throw new RuntimeException("$method $path did not return JSON");
  }
  return [(int) $matches[1], $decoded];
}

function api_smoke_expect_status(int $expected, array $response, string $operation): void
{
  if ($response[0] !== $expected) {
    throw new RuntimeException("$operation: expected HTTP $expected, received HTTP {$response[0]}");
  }
}

api_smoke_expect_status(401, api_smoke_request('GET', '/users/current'), 'Missing token');
api_smoke_expect_status(401, api_smoke_request('GET', '/users/current', 'synthetic-invalid-token'), 'Invalid token');
$signIn = api_smoke_request('POST', '/tokens', null, [
  'username' => 'api-smoke', 'password' => 'api-smoke-password', 'device_name' => 'Apache smoke fixture',
]);
api_smoke_expect_status(201, $signIn, 'Sign-in');
$token = $signIn[1]['data']['token'];
$profile = api_smoke_request('GET', '/users/current', $token);
api_smoke_expect_status(200, $profile, 'Profile with newly issued bearer token');
if ($profile[1]['data']['username'] !== 'api-smoke') {
  throw new RuntimeException('Profile returned the wrong fixture account');
}
$events = api_smoke_request('GET', '/events?start_date=2026-11-01&end_date=2026-11-07', $token);
api_smoke_expect_status(200, $events, 'Recurring events');
$occurrences = array_map(static fn (array $event): array => [$event['id'], $event['date']], $events[1]['data']);
if ($occurrences !== [[1, '2026-11-02'], [2, '2026-11-02'], [1, '2026-11-04'], [1, '2026-11-06']]) {
  throw new RuntimeException('Weekly/monthly recurrence returned unexpected fixture occurrences');
}
$revocation = api_smoke_request('DELETE', '/tokens/current', $token);
api_smoke_expect_status(200, $revocation, 'Revocation');
if ($revocation[1]['data']['revoked'] !== true) {
  throw new RuntimeException('Revocation was not confirmed');
}
api_smoke_expect_status(401, api_smoke_request('GET', '/users/current', $token), 'Revoked token');
$mode = 'server environment';
if (getenv('API_SMOKE_PRIVATE_ENV') === 'true') {
  $mode = 'private .env';
  if (getenv('WEBCALENDAR_API_ENABLED') !== false || getenv('WEBCALENDAR_API_STORAGE') !== false) {
    throw new RuntimeException('The private-file fixture must not rely on API server environment settings');
  }
  if (!glob('/var/www/private/html/storage/framework/cache/data/*/*/*')) {
    throw new RuntimeException('HTTP did not use the storage configured by the private file');
  }
}
echo "Apache API smoke passed ($mode): token issuance, profile, weekly/monthly recurrence, revocation, and missing/invalid/revoked token rejection.\n";
