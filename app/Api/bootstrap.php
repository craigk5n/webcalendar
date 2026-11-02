<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use WebCalendar\Api\ApiServiceProvider;

$root = dirname(__DIR__, 2);
$storage = getenv('WEBCALENDAR_API_STORAGE') ?: sys_get_temp_dir() . '/webcalendar-api-' . substr(hash('sha256', $root), 0, 16);
if (!is_dir($storage) && !mkdir($storage, 0700, true) && !is_dir($storage)) {
  throw new RuntimeException('Cannot create API storage directory.');
}
$storage = realpath($storage);
if ($storage === realpath($root) || str_starts_with($storage, realpath($root) . DIRECTORY_SEPARATOR)) {
  throw new RuntimeException('API storage must be outside the document root.');
}
if (!is_writable($storage)) {
  throw new RuntimeException('API storage is not writable by this user.');
}
foreach (['/bootstrap/cache', '/framework/cache/data', '/framework/sessions', '/framework/views', '/logs'] as $directory) {
  $path = $storage . $directory;
  if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
    throw new RuntimeException('Cannot create API storage directory.');
  }
}

$app = Application::configure(basePath: $root)
  ->withProviders([ApiServiceProvider::class], withBootstrapProviders: false)
  ->withRouting(api: __DIR__ . '/routes.php', apiPrefix: 'v1')
  ->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(fn () => null);
    $middleware->throttleApi('api');
    $middleware->alias([
      'api.identity' => WebCalendar\Api\Middleware\AuthenticateIdentity::class,
      'abilities' => Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
    ]);
    $middleware->trimStrings(except: ['password']);
  })
  ->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $exception): bool => true);
    $exceptions->dontFlash(['password', 'token']);
  })
  ->create();

$app->useAppPath($root . '/app');
$app->useConfigPath(__DIR__ . '/config');
$app->useDatabasePath(__DIR__ . '/database');
$app->useStoragePath($storage);
$app->useBootstrapPath($storage . '/bootstrap');
$app->useEnvironmentPath($storage . '/environment');
return $app;
