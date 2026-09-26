<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * The activity log's cal_type letters have to be reachable from every entry
 * point that writes a log row.
 *
 * They were defined inline in WebCalendar::_initFunctions(), which the web
 * pages and the scripts under tools/ run and bin/webcal.php does not. The
 * first command to reach import_data() died with `Undefined constant
 * "LOG_CREATE"` after parsing the file and inserting the import row, and
 * `user reset-password` had already worked around the same gap by passing the
 * literal 'u'. includes/activity-log-constants.php is now the one copy.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ActivityLogConstantsTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';
  private const SHARED = __DIR__ . '/../includes/activity-log-constants.php';

  /**
   * Names the codebase uses, minus the ones PHP defines itself: LOG_ERR,
   * LOG_USER and the rest of the syslog family would otherwise look like
   * ours.
   *
   * @return list<string>
   */
  private function referencedConstants(): array
  {
    $files = [];
    $status = 0;
    @exec('git -C ' . escapeshellarg(self::ROOT) . ' ls-files "*.php" 2>/dev/null',
      $files, $status);

    if ($status !== 0 || $files === []) {
      self::markTestSkipped('git ls-files unavailable.');
    }

    $names = [];
    foreach ($files as $relative) {
      if (str_starts_with($relative, 'includes/classes/phpmailer/')) {
        continue;
      }
      $source = @file_get_contents(self::ROOT . '/' . $relative);
      if ($source === false) {
        continue;
      }
      // Executable tokens only. A docblock listing the names is prose, and
      // one of them was misspelled there -- a build should not fail over a
      // comment.
      $code = SourceText::php($source);

      if (!preg_match_all('/\b(LOG_[A-Z_]+|SECURITY_VIOLATION)\b/', $code,
        $matches)) {
        continue;
      }
      foreach ($matches[1] as $name) {
        // Already defined at this point means PHP owns the name.
        if (!defined($name)) {
          $names[$name] = true;
        }
      }
    }

    return array_keys($names);
  }

  public function testTheSharedFileDefinesEveryConstantTheCodebaseUses(): void
  {
    $referenced = $this->referencedConstants();
    self::assertNotEmpty($referenced, 'no constants found to check');

    require_once self::SHARED;

    $undefined = [];
    foreach ($referenced as $name) {
      if (!defined($name)) {
        $undefined[] = $name;
      }
    }
    sort($undefined);

    self::assertSame([], $undefined, 'Constant(s) used in the tree that '
      . "includes/activity-log-constants.php does not define. A command line "
      . "entry point reaching one of these dies at runtime:\n  "
      . implode("\n  ", $undefined));
  }

  /**
   * Two copies of a value that is already written into every existing row is
   * how the letters drift apart.
   */
  public function testWebCalendarNoLongerDefinesThemItself(): void
  {
    $source = file_get_contents(self::ROOT . '/includes/classes/WebCalendar.php');
    self::assertIsString($source);

    self::assertDoesNotMatchRegularExpression(
      "/define\s*\(\s*'(LOG_[A-Z_]+|SECURITY_VIOLATION)'/",
      $source,
      'WebCalendar.php must include includes/activity-log-constants.php '
      . 'rather than defining the activity log constants again'
    );
    self::assertStringContainsString('activity-log-constants.php', $source,
      'WebCalendar.php must still load them for the web pages');
  }

  /**
   * _initFunctions() runs after a command line entry point has already loaded
   * the file, so a second include must not warn about redefinition.
   */
  public function testIncludingItTwiceIsHarmless(): void
  {
    require self::SHARED;
    require self::SHARED;

    self::assertSame('C', LOG_CREATE);
    self::assertSame('u', LOG_USER_UPDATE);
    self::assertSame('Z', SECURITY_VIOLATION);
  }

  /**
   * The letters are the stored column values, so they are not free to change.
   */
  public function testTheStoredLettersAreUnchanged(): void
  {
    require_once self::SHARED;

    self::assertSame('A', LOG_APPROVE);
    self::assertSame('C', LOG_CREATE);
    self::assertSame('D', LOG_DELETE);
    self::assertSame('R', LOG_REMINDER);
    self::assertSame('U', LOG_UPDATE);
    self::assertSame('a', LOG_USER_ADD);
    self::assertSame('d', LOG_USER_DELETE);
    self::assertSame('u', LOG_USER_UPDATE);
    self::assertSame('x', LOG_LOGIN_FAILURE);
    self::assertSame('Y', LOG_SYSTEM);
  }
}
