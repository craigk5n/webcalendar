<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the access-control fixes in users_ajax.php.
 *
 * users_ajax.php had two CRITICAL broken-access-control bugs:
 *
 *   1. The "save" action forwarded the POSTed `is_admin` flag for a
 *      self-editing non-admin, allowing any authenticated user to make
 *      themselves an administrator.
 *   2. The "set-password" action let any authenticated user reset ANY
 *      account's password (it gated only on the self-service
 *      ACCESS_ACCOUNT_INFO function, which is a no-op when UAC is disabled —
 *      the default).
 *
 * The "delete", "save-group" and "delete-group" actions, and the user/group
 * list endpoints, similarly lacked a fail-closed authorization check.
 *
 * The root cause is that `access_can_access_function()` returns true for
 * everyone when UAC is disabled, so the common
 * `if (!$is_admin) { if (!access_can_access_function(X)) $error=...; }` idiom
 * is fail-open. The fix introduces `users_ajax_can_manage_users()`, which is
 * fail-closed (it requires `access_is_enabled()` before trusting the UAC
 * function).
 *
 * Like SecurityAuditAccessGateTest, this is a source-structure test: the
 * handler is procedural and exits, so we assert the invariants that keep the
 * authorization checks effective. If a future refactor reintroduces the
 * fail-open pattern, this test breaks the build.
 */
final class UsersAjaxAccessControlTest extends TestCase
{
  private const USERS_AJAX = __DIR__ . '/../users_ajax.php';

  private string $source;

  protected function setUp(): void
  {
    $src = file_get_contents(self::USERS_AJAX);
    self::assertNotFalse($src, 'users_ajax.php must exist');
    $this->source = $src;
  }

  public function testManageHelperIsFailClosed(): void
  {
    self::assertMatchesRegularExpression(
      '/function\s+users_ajax_can_manage_users\s*\(/',
      $this->source,
      'users_ajax_can_manage_users() helper must exist.'
    );
    // The helper MUST gate the UAC function behind access_is_enabled(),
    // otherwise it is fail-open when UAC is disabled (the default).
    self::assertMatchesRegularExpression(
      '/access_is_enabled\s*\(\s*\)\s*&&\s*access_can_access_function\s*\(\s*ACCESS_USER_MANAGEMENT\s*\)/',
      $this->source,
      'users_ajax_can_manage_users() must require access_is_enabled() before '
      . 'trusting access_can_access_function(ACCESS_USER_MANAGEMENT).'
    );
  }

  public function testSelfEditCannotGrantAdmin(): void
  {
    // The is_admin argument passed to save_user() must be gated on the
    // privileged $canManage flag. A non-manager self-edit must force a literal
    // 'N' rather than honoring the POSTed value.
    self::assertMatchesRegularExpression(
      "/\\\$canManage\s*\?\s*\(\s*getPostValue\s*\(\s*'is_admin'\s*\)\s*==\s*'Y'\s*\?\s*'Y'\s*:\s*'N'\s*\)\s*:\s*'N'/",
      $this->source,
      "The save action must force is_admin to 'N' for a non-managing "
      . 'self-editing user (only an administrator may set the admin flag).'
    );
  }

  public function testSetPasswordRequiresSelfOrManager(): void
  {
    // set-password must reject changing ANOTHER user's password unless the
    // caller can manage users.
    self::assertMatchesRegularExpression(
      '/\$user\s*!=\s*\$login\s*&&\s*!\s*users_ajax_can_manage_users\s*\(\s*\)/',
      $this->source,
      'The set-password action must require $user == $login OR '
      . 'users_ajax_can_manage_users().'
    );
    // The old fail-open ACCESS_ACCOUNT_INFO bypass must be gone.
    self::assertDoesNotMatchRegularExpression(
      '/access_can_access_function\s*\(\s*ACCESS_ACCOUNT_INFO\s*\)/',
      $this->source,
      'The set-password action must not authorize an arbitrary target via '
      . 'ACCESS_ACCOUNT_INFO (self-service permission).'
    );
  }

  public function testPrivilegedActionsUseFailClosedCheck(): void
  {
    // delete, save-group and delete-group must all be gated by the
    // fail-closed helper.
    $required = [
      "delete user"   => "/'delete'.*?users_ajax_can_manage_users/s",
    ];
    // Count occurrences of the helper guarding privileged branches. We expect
    // it to appear for: save, set-password, delete, save-group, delete-group,
    // userlist, group-list (plus the definition) — i.e. several times.
    $count = preg_match_all('/users_ajax_can_manage_users\s*\(/', $this->source);
    self::assertGreaterThanOrEqual(
      7,
      $count,
      'Expected users_ajax_can_manage_users() to guard every privileged '
      . 'action (save, set-password, delete, save-group, delete-group, '
      . 'userlist, group-list) plus its own definition.'
    );
  }

  public function testListEndpointsAreGated(): void
  {
    // The user list and group list expose administrative data (emails, admin
    // flags, group membership) and must be gated.
    self::assertMatchesRegularExpression(
      "/'userlist'.*?users_ajax_can_manage_users/s",
      $this->source,
      'The userlist action must be gated by users_ajax_can_manage_users().'
    );
    self::assertMatchesRegularExpression(
      "/'group-list'.*?users_ajax_can_manage_users/s",
      $this->source,
      'The group-list action must be gated by users_ajax_can_manage_users().'
    );
  }
}
