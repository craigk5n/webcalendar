<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/SourceText.php";

/**
 * Regression tests for report.php cross-user access control.
 *
 *   - report.php?user= selected another user's calendar on ALLOW_VIEW_OTHER
 *     or is_admin with no access_user_calendar() check (per-calendar UAC view
 *     permission ignored).
 *   - event_to_text() masked only access 'R' (private); confidential ('C')
 *     events were shown in full, and private events still leaked location/URL
 *     and site extras.
 *   - HTML event descriptions were emitted without sanitize_html() (stored
 *     XSS), and location/url were not escaped.
 *
 * Procedural page -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   report.php:report_user:missing-access_user_calendar (medium)
 *   report.php:event_to_text:access-masking-disagreement (medium)
 *   report.php:event_to_text-html-description-unsanitized (medium)
 */
final class ReportAccessMaskingTest extends TestCase
{
  private string $src;
  private ?string $fn;

  protected function setUp(): void
  {
    $this->src = file_get_contents(__DIR__ . '/../report.php');
    $this->fn = SourceText::phpFunction($this->src, 'event_to_text');
  }

  public function test_cross_user_report_checks_view_access(): void
  {
    self::assertMatchesRegularExpression(
      "/access_user_calendar\s*\(\s*'view'\s*,\s*\\\$user\s*\)/",
      SourceText::php($this->src),
      'report.php must gate a cross-user report on view access.'
    );
  }

  public function test_event_to_text_masks_confidential_and_private(): void
  {
    self::assertNotNull($this->fn);
    self::assertStringContainsString('CONF_WT', $this->fn,
      'confidential events must honor the UAC CONF_WT weight.');
    self::assertStringContainsString('PRIVATE_WT', $this->fn,
      'private events must honor the UAC PRIVATE_WT weight.');
    self::assertStringContainsString('$masked', $this->fn,
      'a single masked decision must cover R and C.');
  }

  public function test_event_to_text_sanitizes_html_and_escapes_fields(): void
  {
    self::assertNotNull($this->fn);
    self::assertStringContainsString('sanitize_html', $this->fn,
      'HTML descriptions must be sanitized (stored XSS).');
    self::assertMatchesRegularExpression(
      '/htmlspecialchars\s*\(\s*\$event->getLocation/',
      $this->fn,
      'the location must be HTML-escaped before template substitution.'
    );
  }
}
