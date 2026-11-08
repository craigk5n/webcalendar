<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use WebCalendar\Api\Middleware\AuthenticateIdentity;
use WebCalendar\Api\Models\ApiToken;
use WebCalendar\Api\Models\ApiUser;
use WebCalendar\Services\DeviceTokenService;
use WebCalendar\Services\RenewableSessionService;

if (PHP_SAPI !== 'cli' || getenv('WEBCALENDAR_DB_TYPE') !== 'postgresql'
    || getenv('WEBCALENDAR_DB_DATABASE') !== 'api_credential_test') {
  throw new RuntimeException('This fixture requires the disposable api_credential_test PostgreSQL database.');
}
require dirname(__DIR__, 2) . '/vendor/autoload.php';
define('WEBCALENDAR_API_BOOTSTRAP', true);
require dirname(__DIR__, 2) . '/includes/api-bootstrap.php';
$app = require dirname(__DIR__, 2) . '/app/Api/bootstrap.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
DB::statement('SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ COMMITTED');
DB::statement("SET statement_timeout = '15s'");

function barrier(): void
{
  echo json_encode(['pid' => DB::selectOne('SELECT pg_backend_pid() AS pid')->pid]) . "\n";
  fflush(STDOUT);
  if (trim(fgets(STDIN)) !== 'continue') { throw new RuntimeException('Missing credential test barrier'); }
}

$mode = $argv[1];
if (str_starts_with($mode, 'verified-issue') || str_starts_with($mode, 'rehash-')) {
  $GLOBALS['password_barrier_mode'] = $mode;
  require __DIR__ . '/api-password-barriers.php';
  try {
    $issued = (new DeviceTokenService())->issue('race-user', 'original', 'Verification fixture', $mode !== 'verified-issue-legacy');
    $result = ['status' => Response::HTTP_OK, 'credentials' => $issued];
  } catch (AuthenticationException $exception) { $result = ['status' => Response::HTTP_UNAUTHORIZED]; }
} elseif ($mode === 'sign-in-paused') {
  $paused = false;
  ApiUser::updated(function () use (&$paused): void {
    if (!$paused) { $paused = true; barrier(); }
  });
  $result = (new DeviceTokenService())->issue('race-user', 'replacement', 'Replacement', true);
} elseif ($mode === 'stale-issue') {
  $paused = false;
  DB::connection()->beforeExecuting(function ($query) use (&$paused): void {
    if (!$paused && str_contains($query, 'webcal_config') && str_contains($query, 'for update')) {
      $paused = true;
      barrier();
    }
  });
  try {
    (new DeviceTokenService())->issue('race-user', 'original', 'Stale password proof', true);
    $result = ['status' => Response::HTTP_OK];
  } catch (AuthenticationException $exception) { $result = ['status' => Response::HTTP_UNAUTHORIZED]; }
} elseif ($mode === 'renew') {
  ApiToken::creating(function (ApiToken $token): void {
    if ($token->abilities === ['events:read', 'events:write']) { barrier(); }
  });
  $result = (new RenewableSessionService())->renew($argv[2], str_repeat('a', 64));
} elseif ($mode === 'sign-in' || $mode === 'delete-recreate') {
  $pid = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
  if ($mode === 'delete-recreate') {
    $query = dbi_execute('SELECT pg_backend_pid()', [], false, false);
    $pid = dbi_fetch_row($query)[0];
    dbi_free_result($query);
  }
  echo json_encode(['pid' => $pid]) . "\n";
  fflush(STDOUT);
  if ($mode === 'delete-recreate') {
    $row = DB::table('webcal_user')->where('cal_login', 'race-user')->first();
    user_delete_user('race-user');
    DB::table('webcal_user')->insert((array) $row);
  }
  $result = (new DeviceTokenService())->issue('race-user', $mode === 'sign-in' ? 'replacement' : 'original', 'Replacement', true);
} elseif ($mode === 'issue') {
  $result = (new DeviceTokenService())->issue('race-user', 'original', 'Original', true);
} elseif ($mode === 'authenticate' || $mode === 'stale-authenticate') {
  $token = ApiToken::findToken($argv[2]);
  try {
    if (!$token || !$token->tokenable) { throw new AuthenticationException(); }
    $request = Request::create('/users/current');
    $request->setUserResolver(fn () => $token->tokenable->withAccessToken($token));
    if ($mode === 'stale-authenticate') { barrier(); }
    (new AuthenticateIdentity())->handle($request, fn () => new Response());
    $result = ['status' => Response::HTTP_OK];
  } catch (AuthenticationException $exception) { $result = ['status' => Response::HTTP_UNAUTHORIZED]; }
} elseif ($mode === 'refresh' || $mode === 'waiting-refresh') {
  if ($mode === 'waiting-refresh') {
    echo json_encode(['pid' => DB::selectOne('SELECT pg_backend_pid() AS pid')->pid]) . "\n";
    fflush(STDOUT);
  }
  try {
    (new RenewableSessionService())->renew($argv[2], str_repeat('b', 64));
    $result = ['status' => Response::HTTP_OK];
  } catch (AuthenticationException $exception) { $result = ['status' => Response::HTTP_UNAUTHORIZED]; }
} else { throw new RuntimeException('Unknown credential worker mode.'); }
echo json_encode($result);
