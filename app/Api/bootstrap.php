<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use WebCalendar\Api\ApiServiceProvider;
use WebCalendar\Api\Environment;

$root = dirname(__DIR__, 2);
$apiEnvironment ??= Environment::load($root, PHP_SAPI === 'cli' ? null : ($_SERVER['DOCUMENT_ROOT'] ?? null));
$storage = $apiEnvironment->resolvePrivatePath($apiEnvironment->storagePath(), true);
if (!@is_dir($storage) && !@mkdir($storage, 0700, true) && !@is_dir($storage)) {
  throw new RuntimeException('Cannot create API storage directory.');
}
$storage = $apiEnvironment->resolvePrivatePath($storage);
if (!@is_writable($storage)) {
  throw new RuntimeException('API storage is not writable by this user.');
}
foreach (['/bootstrap/cache', '/framework/cache/data', '/framework/sessions', '/framework/views', '/logs'] as $directory) {
  $path = $apiEnvironment->resolvePrivatePath($storage . $directory, true);
  if (!@is_dir($path) && !@mkdir($path, 0700, true) && !@is_dir($path)) {
    throw new RuntimeException('Cannot create API storage directory.');
  }
  $apiEnvironment->assertOutsideDocumentRoot($path);
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
    $exceptions->dontFlash(['password', 'token', 'refresh_token', 'rotation_secret']);
    $exceptions->render(function (WebCalendar\Api\Exceptions\EventWriteException $exception) {
      return response()->json(['message' => $exception->getMessage(), 'code' => $exception->errorCode], $exception->getStatusCode());
    });
  })
  ->create();

$app->useAppPath($root . '/app');
$app->useConfigPath(__DIR__ . '/config');
$app->useDatabasePath(__DIR__ . '/database');
$app->useStoragePath($storage);
$app->useBootstrapPath($storage . '/bootstrap');
$app->useEnvironmentPath($storage . '/environment');
$app->instance(Environment::class, $apiEnvironment);
return $app;
