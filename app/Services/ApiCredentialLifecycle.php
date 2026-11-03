<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use RuntimeException;

final class ApiCredentialLifecycle
{
  public static function revoke(string $username): void
  {
    $catalog = [
      'sqlite3' => "SELECT name FROM sqlite_master WHERE type = 'table' AND name IN (?, ?)",
      'mysqli' => 'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN (?, ?)',
      'postgresql' => 'SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name IN (?, ?)',
    ][$GLOBALS['db_type']] ?? null;
    if ($catalog === null) {
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
      return;
    }
    if (count($tables) !== 2) {
      throw new RuntimeException('API credential tables are incomplete. Complete the API migrations before deleting the account.');
    }
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
  }
}
