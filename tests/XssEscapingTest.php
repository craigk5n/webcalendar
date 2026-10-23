<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for output-encoding (XSS) fixes still owned by this branch.
 *
 *   - display_small_tasks() emitted the task name raw into a title attribute
 *     and link text (stored XSS in the task sidebar).
 *   - WebCalMailer::Body() assigned nl2br($msg) to the HTML mail body, so
 *     user-controlled event fields were emitted as live markup.
 *
 * (The js_cacher include-src and iCal-JS escaping was fixed independently on
 * master by PR #798 -- those files are taken from master in this branch, so
 * their assertions live with that PR's tests, not here.)
 *
 * Procedural code -> source-structure assertions (cf. BlobAndApprovalAccessTest).
 *
 * Covers security-audit run-1:
 *   includes/functions.php:display_small_tasks-task-name-unescaped (medium)
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
