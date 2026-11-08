<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use Closure;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/ApiCredentialGuard.php';

final class ApiCredentialLifecycle
{
  public static function revoke(string $username): void
  {
    self::deleting($username, static function (): void {});
  }

  public static function deleting(string $username, Closure $delete): void
  {
    $catalog = [
      'sqlite3' => "SELECT name FROM sqlite_master WHERE type = 'table' AND name IN (?, ?)",
      'mysqli' => 'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN (?, ?)',
      'postgresql' => 'SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name IN (?, ?)',
    ][$GLOBALS['db_type']] ?? null;
    if ($catalog === null) {
      $delete();
      return;
    }
    $result = dbi_execute($catalog, ['webcal_api_users', 'webcal_api_tokens'], false, false);
    if ($result === false) {
      throw new RuntimeException('Cannot check API credential tables before deleting the account.');
    }
    $tables = [];
    while ($row = dbi_fetch_row($result)) {
      $tables[] = $row[0];
    }
    dbi_free_result($result);
    if ($tables === []) {
      $delete();
      return;
    }
    if (count($tables) !== 2) {
      throw new RuntimeException('API credential tables are incomplete. Complete the API migrations before deleting the account.');
    }
    if (!dbi_execute($GLOBALS['db_type'] === 'sqlite3' ? 'BEGIN IMMEDIATE' : 'BEGIN', [], false, false)) {
      throw new RuntimeException('Cannot begin API credential invalidation.');
    }
    $pending = true;
    register_shutdown_function(static function () use (&$pending): void {
      if ($pending) { dbi_execute('ROLLBACK', [], false, false); }
    });
    try {
      if ($GLOBALS['db_type'] === 'mysqli') {
        $engines = dbi_execute("SELECT engine FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('webcal_config', 'webcal_user', 'webcal_api_users', 'webcal_api_tokens')", [], false, false);
        if (!$engines) { throw new RuntimeException('Cannot inspect API credential storage.'); }
        $count = 0;
        while ($row = dbi_fetch_row($engines)) {
          if (strcasecmp($row[0] ?? '', 'InnoDB') !== 0) { throw new RuntimeException('Credential deletion requires InnoDB tables.'); }
          $count++;
        }
        dbi_free_result($engines);
        if ($count !== 4) { throw new RuntimeException('Credential deletion tables are incomplete.'); }
      }
      $suffix = $GLOBALS['db_type'] === 'sqlite3' ? '' : ' FOR UPDATE';
      $mutex = dbi_execute('SELECT cal_value FROM webcal_config WHERE cal_setting = ?' . $suffix,
        [ApiCredentialGuard::MUTEX_SETTING], false, false);
      if (!$mutex || !dbi_fetch_row($mutex)) { throw new RuntimeException('The API credential mutex is unavailable.'); }
      dbi_free_result($mutex);
      // DBI removes one slash-escaping layer before binding strings.
      $tokenOwner = addslashes('WebCalendar\\Api\\Models\\ApiUser');
      foreach ([
        ['DELETE FROM webcal_api_tokens WHERE tokenable_type = ? AND tokenable_id = ?', [$tokenOwner, $username]],
        ['DELETE FROM webcal_api_users WHERE username = ?', [$username]],
      ] as [$sql, $parameters]) {
        if (!dbi_execute($sql, $parameters, false, false)) {
          throw new RuntimeException('Cannot revoke API credentials before deleting the account.');
        }
      }
      $delete();
      if (!dbi_execute('COMMIT', [], false, false)) { throw new RuntimeException('Cannot commit account deletion.'); }
      $pending = false;
    } catch (Throwable $exception) {
      dbi_execute('ROLLBACK', [], false, false);
      $pending = false;
      throw $exception;
    }
  }
}
