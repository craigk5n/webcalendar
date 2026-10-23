<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for event edit/attachment authorization.
 *
 *   edit_entry_handler.php trusted a POST 'parent' value as the cal_group_id
 *   used for repeats_not exclusions, so a non-owner could write exclusions
 *   onto another user's repeating event. It must accept only the stored group
 *   id of the event actually being edited.
 *
 *   docadd.php checked UAC with access_user_calendar('edit', $user) where
 *   $user came from the request, so a caller could name themselves and bypass
 *   the participant-only comment/attachment policy. It must check the event's
 *   own owner.
 *
 * Procedural pages -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   edit_entry_handler.php:parent-old_id:repeats_not (medium)
 *   docadd.php:uac-edit-request-user (low)
 */
final class EventEditAuthzTest extends TestCase
{
  public function test_edit_entry_handler_binds_parent_to_edited_event(): void
  {
    $src = file_get_contents(__DIR__ . '/../edit_entry_handler.php');
    self::assertStringContainsString(
      'SELECT cal_group_id FROM webcal_entry',
      $src,
      'parent must be resolved from the edited event, not trusted from POST.'
    );
  }

  public function test_docadd_checks_uac_against_event_owner(): void
  {
    $src = file_get_contents(__DIR__ . '/../docadd.php');
    self::assertMatchesRegularExpression(
      "/access_user_calendar\s*\(\s*'edit'\s*,\s*\\\$event_owner\s*\)/",
      $src,
      'docadd UAC must check the event owner, never the request user.'
    );
    self::assertDoesNotMatchRegularExpression(
      "/access_user_calendar\s*\(\s*'edit'\s*,\s*\\\$user\s*\)/",
      $src,
      'docadd must not pass the request user to the UAC edit check.'
    );
  }
}
