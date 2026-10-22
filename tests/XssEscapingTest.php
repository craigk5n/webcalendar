<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for output-encoding (XSS) fixes.
 *
 *   - display_small_tasks() emitted the task name raw into a title attribute
 *     and link text (stored XSS in the task sidebar).
 *   - print_header() wrote the request-derived js_cacher include path into a
 *     src="..." attribute unescaped (reflected XSS), and includes/js/
 *     availability.php and catsel.php echoed request-controlled $arinc
 *     segments raw into JavaScript.
 *   - WebCalMailer::Body() assigned nl2br($msg) to the HTML mail body, so
 *     user-controlled event fields were emitted as live markup.
 *
 * Procedural code -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   includes/functions.php:display_small_tasks-task-name-unescaped (medium)
 *   includes/init.php:print_header:unescaped-include-src+availability arinc-raw-js (medium)
 *   WebCalMailer.Body:nl2br-unescaped-html-notification (low)
 */
final class XssEscapingTest extends TestCase
{
  public function test_display_small_tasks_escapes_name(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/functions.php');
    self::assertMatchesRegularExpression(
      '/htmlspecialchars\(\s*\$E->getName\(\)/',
      $src,
      'display_small_tasks() must HTML-escape the task name.'
    );
  }

  public function test_print_header_escapes_include_src(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/init.php');
    self::assertMatchesRegularExpression(
      "/htmlspecialchars\(\s*'js_cacher\.php\?inc='\s*\.\s*\\\$inc/",
      $src,
      'the js_cacher include path must be escaped in the src attribute.'
    );
  }

  public function test_availability_js_casts_numeric_segments(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/js/availability.php');
    self::assertMatchesRegularExpression('/\(int\)\s*\(\s*\$arinc\[5\]/', $src);
    self::assertStringContainsString('preg_replace', $src,
      'the parent form name must be constrained to an identifier.');
  }

  public function test_catsel_js_casts_segment(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/js/catsel.php');
    self::assertMatchesRegularExpression('/\(int\)\s*\(\s*\$arinc\[3\]/', $src);
  }

  public function test_mailer_body_escapes_html(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/classes/WebCalMailer.php');
    self::assertMatchesRegularExpression(
      '/htmlspecialchars\(\s*unhtmlentities\(\s*\$msg/',
      $src,
      'HTML mail body must escape the message before linkifying it.'
    );
  }
}
