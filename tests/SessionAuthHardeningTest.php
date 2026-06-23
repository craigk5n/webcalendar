<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for P1 authentication hardening:
 *
 *   AUTH-1  user_valid_crypt() accepted a cookie that equaled the stored
 *           cal_passwd hash (pass-the-hash) or matched a weak crypt()
 *           self-comparison. Only the token-based path may remain.
 *   AUTH-3  No session id rotation on authentication (session fixation).
 *   MED-2   Session and app cookies lacked SameSite; the PHP session cookie
 *           was started without hardened parameters.
 *
 * Source-structure tests over the auth code.
 */
final class SessionAuthHardeningTest extends TestCase
{
  private string $user;
  private string $functions;
  private string $login;
  private string $webcalendar;

  protected function setUp(): void
  {
    $this->user        = (string) file_get_contents(__DIR__ . '/../includes/user.php');
    $this->functions   = (string) file_get_contents(__DIR__ . '/../includes/functions.php');
    $this->login       = (string) file_get_contents(__DIR__ . '/../login.php');
    $this->webcalendar = (string) file_get_contents(__DIR__ . '/../includes/classes/WebCalendar.php');
  }

  public function testPassTheHashBranchesRemoved(): void
  {
    // The cookie must never be compared against the stored password hash.
    self::assertDoesNotMatchRegularExpression(
      '/hash_equals\s*\(\s*\$row\[1\]\s*,\s*\$crypt_password\s*\)/',
      $this->user,
      'user_valid_crypt() must not compare the cookie to the stored cal_passwd hash.'
    );
    self::assertDoesNotMatchRegularExpression(
      '/crypt\s*\(\s*\$row\[1\]\s*,\s*\$crypt_password\s*\)/',
      $this->user,
      'user_valid_crypt() must not use the legacy crypt() self-comparison.'
    );
  }

  public function testOnlyTokenCookiesAccepted(): void
  {
    self::assertMatchesRegularExpression(
      "/strpos\s*\(\s*\\\$crypt_password\s*,\s*'tok:'\s*\)\s*!==\s*0/",
      $this->user,
      'user_valid_crypt() must reject any cookie that is not a tok: token.'
    );
    // The remaining accepted path still verifies the token hash constant-time.
    self::assertMatchesRegularExpression(
      '/hash_equals\s*\(\s*\$row\[0\]\s*,\s*\$token_hash\s*\)/',
      $this->user,
      'The token path must compare hashes with hash_equals().'
    );
  }

  public function testSessionRegeneratedOnLogin(): void
  {
    self::assertStringContainsString(
      'session_regenerate_id(true)',
      $this->login,
      'login.php must regenerate the session id on successful login.'
    );
    self::assertStringContainsString(
      'session_regenerate_id ( true )',
      $this->webcalendar,
      'WebCalendar.php must rotate the session id when a remember-me cookie '
      . 'establishes the authenticated session.'
    );
  }

  public function testSessionAndCookieHardening(): void
  {
    self::assertMatchesRegularExpression(
      '/function\s+harden_php_session\s*\(/',
      $this->functions,
      'harden_php_session() must exist.'
    );
    self::assertMatchesRegularExpression(
      "/'samesite'\s*=>\s*'Lax'/",
      $this->functions,
      'sendCookie()/harden_php_session() must set SameSite=Lax.'
    );
    self::assertMatchesRegularExpression(
      "/ini_set\s*\(\s*'session.use_strict_mode'\s*,\s*'1'\s*\)/",
      $this->functions,
      'harden_php_session() must enable session.use_strict_mode.'
    );
    self::assertStringContainsString(
      'harden_php_session();',
      $this->login,
      'login.php must harden the session before session_start().'
    );
  }
}
