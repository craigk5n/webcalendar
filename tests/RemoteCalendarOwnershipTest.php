<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/SourceText.php";

/**
 * Regression tests for remote/nonuser calendar ownership enforcement.
 *
 * edit_remotes_handler.php ran for any logged-in user with no ownership
 * check: delete/Save/reload acted on any nid, and Add trusted a client
 * 'nadmin', letting a user self-grant nonuser-admin on an arbitrary calendar
 * (first half of the edit_remotes->pref privilege chain). users_ajax.php's
 * save_remote_calendar() UPDATE likewise repointed any calendar's URL with no
 * owner check (cron then wipes and re-imports it). Both now authorize through
 * remote_calendar_edit_allowed(), and non-admins can only ever set themselves
 * as the calendar admin.
 *
 * Covers security-audit run-1:
 *   edit_remotes_handler.php:nid:missing-owner-check (high)
 *   users_ajax.php:save_remote_calendar:update-missing-owner-check (medium)
 *   chain:edit_remotes_handler.php-selfgrant-nonuser-admin::... (high, edit_remotes half)
 *
 * @covers ::remote_calendar_edit_allowed
 */
final class RemoteCalendarOwnershipTest extends TestCase
{
  public function test_admin_may_edit_any_calendar(): void
  {
    $this->assertTrue(remote_calendar_edit_allowed(true, 'someoneelse', 'bob'));
    $this->assertTrue(remote_calendar_edit_allowed(true, null, 'bob'));
  }

  public function test_owner_may_edit_own_calendar(): void
  {
    $this->assertTrue(remote_calendar_edit_allowed(false, 'bob', 'bob'));
  }

  public function test_non_owner_is_rejected(): void
  {
    $this->assertFalse(remote_calendar_edit_allowed(false, 'alice', 'bob'));
  }

  public function test_missing_or_empty_owner_is_rejected(): void
  {
    // A non-existent calendar (nonuser_load_variables returned no admin).
    $this->assertFalse(remote_calendar_edit_allowed(false, null, 'bob'));
    $this->assertFalse(remote_calendar_edit_allowed(false, '', 'bob'));
  }

  public function test_edit_remotes_handler_checks_ownership(): void
  {
    $src = SourceText::php(
      file_get_contents(__DIR__ . '/../edit_remotes_handler.php')
    );
    $this->assertStringContainsString('remote_calendar_edit_allowed', $src);
    // A non-admin can never name an arbitrary calendar admin (self-grant).
    $this->assertMatchesRegularExpression(
      '/!\s*\$is_admin.*\n.*\$nadmin\s*=\s*\$login/',
      $src,
      'non-admins must be forced to administer only their own calendar'
    );
  }

  public function test_save_remote_calendar_checks_ownership(): void
  {
    $body = SourceText::phpFunction(
      file_get_contents(__DIR__ . '/../users_ajax.php'),
      'save_remote_calendar'
    );
    $this->assertNotNull($body);
    $this->assertStringContainsString('remote_calendar_edit_allowed', $body);
  }
}
