<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";

/**
 * Regression tests for account-lifecycle, registration and redirect fixes.
 *
 * Covers security-audit run-1:
 *   includes/user.php:user_delete_user:stale-nonuser-admin-and-create_by (medium)
 *   register.php:client-controlled-control-bypasses-SELF_REGISTRATION_FULL (low)
 *   register.php:self-registration-reserved-nonuser-login (low)
 *   nulogin.php:return_path:open-redirect (low)
 *
 * @covers ::safe_return_path
 */
final class AccountLifecycleTest extends TestCase
{
  /** @dataProvider unsafePaths */
  public function test_open_redirect_values_fall_back_to_index(string $path): void
  {
    self::assertSame('index.php', safe_return_path($path),
      'unsafe return_path must fall back: ' . addslashes($path));
  }

  public static function unsafePaths(): array
  {
    return [
      'absolute-url'   => ['http://evil.example/'],
      'js-scheme'      => ['javascript:alert(1)'],
      'protocol-rel'   => ['//evil.example/'],
      'backslash'      => ['/\\evil.example'],
      'leading-slash'  => ['/etc'],
      'crlf'           => ["month.php\r\nSet-Cookie: x=1"],
      'empty'          => [''],
    ];
  }

  public function test_relative_pages_are_preserved(): void
  {
    self::assertSame('month.php', safe_return_path('month.php'));
    self::assertSame('week.php?date=20260101',
      safe_return_path('week.php?date=20260101'));
  }

  public function test_nulogin_validates_return_path(): void
  {
    $src = file_get_contents(__DIR__ . '/../nulogin.php');
    self::assertStringContainsString('safe_return_path', $src);
  }

  public function test_user_add_user_rejects_reserved_logins(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/user.php');
    self::assertStringContainsString('$NONUSER_PREFIX', $src);
    self::assertMatchesRegularExpression(
      '/strncmp \( \$user, \$NONUSER_PREFIX/',
      $src,
      'user_add_user must reject nonuser-prefixed logins.'
    );
  }

  public function test_user_delete_clears_stale_references(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/user.php');
    self::assertStringContainsString(
      'UPDATE webcal_nonuser_cals SET cal_admin', $src);
    self::assertStringContainsString(
      'UPDATE webcal_entry SET cal_create_by', $src);
  }

  public function test_register_uses_server_control_mode(): void
  {
    $src = file_get_contents(__DIR__ . '/../register.php');
    self::assertMatchesRegularExpression(
      '/\$control = \$form_control/',
      $src,
      'registration mode must come from the server policy, not the client.'
    );
  }

  public function test_reject_entry_requires_participant(): void
  {
    $src = file_get_contents(__DIR__ . '/../reject_entry.php');
    self::assertMatchesRegularExpression(
      '/user_is_participant \( \$id, \$app_user \)/',
      $src,
      'reject_entry must confirm the user is a participant.'
    );
  }
}
