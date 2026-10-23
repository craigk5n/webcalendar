<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/SourceText.php";

/**
 * Regression tests for access.php UAC self-grant holes.
 *
 *  - The function-permission form (auser save) had no $is_admin check, so any
 *    logged-in user could rewrite any login's webcal_access_function row and
 *    grant themselves user-management (admin takeover).
 *  - The calendar-access form (otheruser save) took BOTH identities from POST
 *    for non-admins, so an attacker could insert a webcal_access_user row
 *    granting themselves access to any victim's calendar. The owner
 *    (cal_other_user) must be the caller's own login.
 *
 * Covers security-audit run-1:
 *   access.php:auser-save:missing-admin-check (high)
 *   access.php:otheruser-save:unbound-guser (high)
 *
 * @covers ::access_grant_pair
 */
final class AccessManagementAuthTest extends TestCase
{
  public function test_admin_grant_pair_is_taken_verbatim(): void
  {
    // (cal_login = accessor, cal_other_user = owner)
    $this->assertSame(
      ['grantee', 'owner'],
      access_grant_pair(true, 'grantee', 'owner', 'adminuser')
    );
  }

  public function test_non_admin_owner_is_always_the_caller(): void
  {
    // The grantee comes from 'otheruser'; the owner (index 1) is forced to
    // $login regardless of what the request supplied.
    $this->assertSame(
      ['bob', 'alice'],
      access_grant_pair(false, 'bob', 'bob', 'alice'),
      'non-admin: grantee from otheruser, owner forced to caller'
    );
  }

  public function test_non_admin_cannot_forge_access_to_a_victim_calendar(): void
  {
    // Attacker tries guser=victim, otheruser=attacker while logged in as
    // attacker: the owner must still be the attacker, never the victim.
    [$calLogin, $calOtherUser] =
      access_grant_pair(false, 'victim', 'attacker', 'attacker');
    $this->assertSame('attacker', $calOtherUser,
      'cal_other_user (owner) must be the caller, never a POST value');
  }

  public function test_auser_function_save_requires_admin(): void
  {
    $src = SourceText::php(file_get_contents(__DIR__ . '/../access.php'));
    $this->assertStringContainsString('access_grant_pair', $src);
    $this->assertMatchesRegularExpression(
      "/\\\$is_admin\\s*&&\\s*getPostValue\\(\\s*'auser'\\s*\\)/",
      $src,
      'the function-permission save must be gated on $is_admin'
    );
  }
}
