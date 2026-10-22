<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/SourceText.php";

/**
 * Regression test for the pref.php authorization-subject mismatch.
 *
 * pref.php set $prefuser from getGetValue('user') but authorized with the
 * init-computed global $is_nonuser_admin, which WebCalendar derives from a
 * POST-preferred 'user'. A regular user could therefore POST user=_NUC_x
 * (which they administer) to make $is_nonuser_admin true, while GET
 * user=victim set $prefuser=victim, then rewrite the victim's preferences and
 * mint the victim's MCP API token. pref_resolve_target() makes the
 * authorization subject and the write subject the same value.
 *
 * Covers security-audit run-1:
 *   pref.php:prefuser:nonuser-admin-check-uses-post-user-but-target-uses-get-user (high)
 *   chain:edit_remotes_handler.php-selfgrant-nonuser-admin::pref.php-... (high; pref half)
 *
 * @covers ::pref_resolve_target
 */
final class PrefTargetAuthTest extends TestCase
{
  public function test_empty_or_self_resolves_to_login(): void
  {
    $this->assertSame('bob', pref_resolve_target('', 'bob', false, false));
    $this->assertSame('bob', pref_resolve_target('bob', 'bob', false, false));
  }

  public function test_admin_may_target_another_user(): void
  {
    $this->assertSame('alice', pref_resolve_target('alice', 'bob', true, false));
  }

  public function test_nonuser_admin_of_the_requested_account_may_target_it(): void
  {
    // The flag MUST be computed against the requested account itself.
    $this->assertSame('_NUC_x', pref_resolve_target('_NUC_x', 'bob', false, true));
  }

  public function test_unauthorized_cross_user_request_is_refused(): void
  {
    $this->assertFalse(
      pref_resolve_target('alice', 'bob', false, false),
      'bob must not edit alice without admin or nonuser-admin of alice'
    );
  }

  public function test_pref_php_authorizes_against_the_requested_user(): void
  {
    $src = SourceText::php(file_get_contents(__DIR__ . '/../pref.php'));
    $this->assertStringContainsString('pref_resolve_target', $src);
    $this->assertMatchesRegularExpression(
      '/user_is_nonuser_admin\s*\(\s*\$login\s*,\s*\$user\s*\)/',
      $src,
      'the nonuser-admin check must be bound to the requested $user, not a global'
    );
  }
}
