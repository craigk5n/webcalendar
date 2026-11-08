<?php

declare(strict_types=1);

namespace WebCalendar\Services;

final class DatabasePasswordAuthentication
{
  private const MD5_HASH_LENGTH = 32;

  public static function authenticate(string $username, string $password, string $expectedHash): ?string
  {
    $legacy = strlen($expectedHash) === self::MD5_HASH_LENGTH && ctype_xdigit($expectedHash);
    $valid = $legacy ? hash_equals(md5($password), $expectedHash) : password_verify($password, $expectedHash);
    if (!$valid) { return null; }
    $acceptedHash = $expectedHash;
    if ($legacy || password_needs_rehash($expectedHash, PASSWORD_DEFAULT)) {
      $replacement = password_hash($password, PASSWORD_DEFAULT);
      if (self::canStore(strlen($replacement))) {
        $comparison = $GLOBALS['db_type'] === 'mysqli' ? 'BINARY cal_passwd = BINARY ?' : 'cal_passwd = ?';
        dbi_execute('UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ? AND ' . $comparison,
          [$replacement, $username, $expectedHash], false, false);
        $result = dbi_execute('SELECT cal_passwd FROM webcal_user WHERE cal_login = ?', [$username], false, false);
        if (!$result) { return null; }
        $row = dbi_fetch_row($result);
        dbi_free_result($result);
        if (!$row || !in_array($row[0], [$expectedHash, $replacement], true)) { return null; }
        $acceptedHash = $row[0];
      }
    }
    return self::subject($acceptedHash);
  }

  public static function subject(string $hash): string
  {
    return hash('sha256', 'database:' . $hash);
  }

  private static function canStore(int $length): bool
  {
    if ($GLOBALS['db_type'] !== 'mysqli') { return true; }
    // Non-strict MySQL can silently truncate a hash; avoid that write entirely.
    $result = dbi_execute("SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'webcal_user' AND COLUMN_NAME = 'cal_passwd'", [], false, false);
    if (!$result) { return false; }
    $row = dbi_fetch_row($result);
    dbi_free_result($result);
    return $row && $row[0] !== null && (int) $row[0] >= $length;
  }
}
