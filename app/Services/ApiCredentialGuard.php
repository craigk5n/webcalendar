<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use WebCalendar\Api\Models\ApiUser;

final class ApiCredentialGuard
{
  public const MUTEX_SETTING = 'WEBCAL_PROGRAM_VERSION';

  public static function locked(Closure $operation): mixed
  {
    return DB::transaction(function () use ($operation): mixed {
      $driver = DB::connection()->getDriverName();
      if ($driver === 'mysql') {
        $tables = DB::select("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('webcal_config', 'webcal_api_users', 'webcal_api_tokens')");
        if (count($tables) !== 3 || array_filter($tables, fn ($table) => strcasecmp($table->ENGINE ?? '', 'InnoDB') !== 0)) {
          throw new ServiceUnavailableHttpException(null, 'API credential operations require InnoDB configuration and API tables.');
        }
      }
      $mutex = DB::table('webcal_config')->where('cal_setting', self::MUTEX_SETTING);
      if ($driver === 'sqlite') {
        $exists = $mutex->update(['cal_value' => DB::raw('cal_value')]) === 1;
      } else {
        $exists = $mutex->lockForUpdate()->first() !== null;
      }
      if (!$exists) { throw new ServiceUnavailableHttpException(null, 'The API credential mutex is unavailable.'); }
      return $operation();
    }, 5);
  }

  public static function invalidate(ApiUser $user): void
  {
    $user->tokens()->delete();
    $user->forceFill(['credential_generation' => bin2hex(random_bytes(32))])->save();
  }
}
