<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for events_ajax.php access control.
 *
 *   - The cross-user view check tested access_user_calendar() for truthiness,
 *     but it returns the string 'N' when UAC is off, which is truthy, so the
 *     rejection never fired.
 *   - action=addevent let an anonymous __public__ session write approved
 *     events, bypassing the moderation queue and captcha.
 *   - action=eventinfo returned participants, comments and attachments for
 *     any event id with no authorization.
 *   - debug=1 print_r'd other users' private/confidential event fields.
 *
 * Procedural endpoint -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   events_ajax.php:access_user_calendar-N-string-truthy (medium)
 *   events_ajax.php:addevent:public-approval-bypass (medium)
 *   events_ajax.php:eventinfo-missing-event-authorization (medium)
 *   events_ajax.php:get:debug-print_r-unmasked-private (medium)
 */
final class EventsAjaxAuthTest extends TestCase
{
  private string $src;

  protected function setUp(): void
  {
    $this->src = file_get_contents(__DIR__ . '/../events_ajax.php');
  }

  public function test_view_check_does_not_treat_N_as_allow(): void
  {
    self::assertStringContainsString("\$acc === 'Y'", $this->src,
      'the view check must treat only Y / positive-int as allow, not the '
        . 'truthy string N.');
  }

  public function test_public_cannot_add_events_here(): void
  {
    self::assertMatchesRegularExpression(
      "/'__public__'[\s\S]{0,400}\\\$can_edit\s*=\s*false/",
      $this->src,
      'a __public__ session must not add events through events_ajax.php.'
    );
  }

  public function test_eventinfo_authorizes_the_caller(): void
  {
    self::assertStringContainsString('cal_create_by, cal_type, cal_access',
      $this->src,
      'eventinfo must load the event and authorize the caller before '
        . 'returning participants/comments/attachments.');
  }

  public function test_debug_output_is_admin_only(): void
  {
    self::assertMatchesRegularExpression(
      '/\$debug\s*=\s*\(\s*\$is_admin/',
      $this->src,
      'debug output must be restricted to admins.'
    );
  }
}
