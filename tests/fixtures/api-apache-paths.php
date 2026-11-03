<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit;
}

$base = '/tmp/webcalendar-api-smoke';
$mode = getenv('API_SMOKE_PUBLIC_ALIAS');
if (getenv('WEBCALENDAR_DB_TYPE') !== 'sqlite3'
    || getenv('WEBCALENDAR_DB_DATABASE') !== $base . '/calendar.sqlite'
    || !in_array($mode, ['storage', 'configuration'], true)) {
  throw new RuntimeException('Run this check only in the disposable Apache path-policy fixture.');
}

if (($argv[1] ?? '') === 'setup') {
  foreach (['public/calendar', 'public/private', 'secure/configuration', 'secure/storage'] as $directory) {
    mkdir($base . '/' . $directory, 0700, true);
  }
  $source = dirname(__DIR__, 2);
  copy($source . '/api.php', $base . '/public/calendar/api.php');
  foreach (['app', 'vendor', 'includes'] as $directory) {
    symlink($source . '/' . $directory, $base . '/public/calendar/' . $directory);
  }
  symlink($base . '/secure/storage', $base . '/public/public-storage');
  file_put_contents($base . '/secure/storage/probe.txt', 'synthetic-storage-alias-canary');
  if ($mode === 'configuration') {
    symlink($base . '/secure/configuration', $base . '/public/private/calendar');
    file_put_contents($base . '/secure/configuration/.env', "WEBCALENDAR_API_ENABLED=true\n# synthetic-config-alias-canary\n");
  }
  exit;
}

function path_probe(string $path): array
{
  $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 15,
    'header' => "Accept: application/json\r\n"]]);
  $stream = fopen('http://127.0.0.1' . $path, 'rb', false, $context);
  if ($stream === false) {
    throw new RuntimeException('Cannot connect to the Apache path-policy fixture.');
  }
  $headers = stream_get_meta_data($stream)['wrapper_data'];
  $body = stream_get_contents($stream);
  fclose($stream);
  preg_match('/\s(\d{3})\s/', $headers[0], $matches);
  return [(int) $matches[1], $body];
}

$storageProbe = path_probe('/public-storage/probe.txt');
if ($storageProbe !== [200, 'synthetic-storage-alias-canary']) {
  throw new RuntimeException('The fixture must demonstrate that Apache follows the public storage alias.');
}
if ($mode === 'configuration') {
  [$status, $body] = path_probe('/private/calendar/.env');
  if ($status !== 200 || !str_contains($body, 'synthetic-config-alias-canary')) {
    throw new RuntimeException('The fixture must demonstrate that Apache serves the outward configuration alias.');
  }
}
[$status, $body] = path_probe('/calendar/api.php/v1/users/current');
if ($status !== 503 || json_decode($body, true) !== [
  'message' => 'API is unavailable. Check the installation and API configuration.',
]) {
  throw new RuntimeException('The API must cleanly reject the known public-facing ' . $mode . ' alias.');
}
if (scandir($base . '/secure/storage') !== ['.', '..', 'probe.txt']) {
  throw new RuntimeException('Rejected storage must not contain newly created cache or runtime files.');
}
echo "Apache path-policy smoke passed ($mode): public alias is reachable, API startup rejects it without creating runtime files.\n";
