<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/SourceText.php";

/**
 * Regression tests for upcoming.php output.
 *
 *   - hcalendar_output=1 echoed the event description, location, url and
 *     categories for every event, ignoring Private/Confidential masking, so
 *     an anonymous viewer could read masked event details.
 *   - The hCalendar fields were echoed without escaping (stored XSS).
 *   - upcoming_title was taken from the request (and a second getGetValue
 *     override bypassed chkXSS) and echoed unescaped (reflected XSS).
 *
 * Procedural page -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   upcoming.php:print_upcoming_event:hcalendar-ignores-access-class (medium)
 *   upcoming.php:upcoming_title:raw-html-reflection (medium)
 *   upcoming.php:hcalendar-event-fields-unescaped (low)
 */
final class UpcomingOutputTest extends TestCase
{
  private string $src;

  protected function setUp(): void
  {
    $this->src = SourceText::php(file_get_contents(__DIR__ . '/../upcoming.php'));
  }

  public function test_hcalendar_block_respects_access_class(): void
  {
    self::assertMatchesRegularExpression(
      '/\$hcalendar_output\s*&&\s*!\s*\$private\s*&&\s*!\s*\$confidential/',
      $this->src,
      'hCalendar details must not be emitted for private/confidential events.'
    );
  }

  public function test_hcalendar_fields_are_escaped(): void
  {
    foreach (['getDescription', 'getLocation', 'getUrl'] as $getter) {
      self::assertMatchesRegularExpression(
        '/htmlspecialchars\s*\(\s*\$e->' . $getter . '/',
        $this->src,
        "hCalendar $getter output must be HTML-escaped."
      );
    }
  }

  public function test_request_title_is_escaped(): void
  {
    self::assertMatchesRegularExpression(
      '/htmlspecialchars\s*\(\s*stripslashes\s*\(\s*\$req_title/',
      $this->src,
      'a request-supplied upcoming_title must be HTML-escaped.'
    );
    self::assertDoesNotMatchRegularExpression(
      "/getGetValue\s*\(\s*'upcoming_title'\s*,\s*true\s*\)/",
      $this->src,
      'the chkXSS-bypassing upcoming_title override must be removed.'
    );
  }
}
