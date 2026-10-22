<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * One failing remote calendar must not take the rest of the list with it.
 *
 * parse_ical() reports failure by appending to the global $errormsg, and
 * nothing cleared it between calendars. load_remote_calendar() gates its
 * import on empty($errormsg) and derives its return value from the same
 * global, so the first unreachable URL in a run made every calendar after it
 * report an error and import nothing -- while tools/reload_remotes.php, which
 * loops over every subscription, counted them and said nothing at all.
 *
 * The function is lifted out of includes/functions.php rather than copied, so
 * this exercises the shipped code. Its collaborators are stubbed: the point
 * under test is the handling of the error global, not iCalendar parsing, and
 * stubs make "this one fails, the next one succeeds" deterministic without a
 * network or a database.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class RemoteCalendarErrorIsolationTest extends TestCase
{
  protected function setUp(): void
  {
    $GLOBALS['errormsg'] = '';
    $GLOBALS['importMd5'] = '';
    $GLOBALS['count_suc'] = 0;
    $GLOBALS['numDeleted'] = 0;
    $GLOBALS['login'] = 'admin';

    $this->defineStubs();
    $this->loadFunctionUnderTest();
  }

  /**
   * A URL containing "bad" fails the way parse_ical() fails: by appending to
   * $errormsg and returning nothing. Anything else parses to one event.
   */
  private function defineStubs(): void
  {
    if (!defined('LOG_UPDATE')) {
      define('LOG_UPDATE', 'U');
    }

    if (function_exists('parse_ical')) {
      return;
    }

    eval(<<<'PHP'
      function parse_ical($url, $source = 'file') {
        global $errormsg, $importMd5;
        if (str_contains((string) $url, 'bad')) {
          // Verbatim shape of the real failure, which uses .= not =.
          $errormsg .= "Invalid remote calendar URL: $url";
          return [];
        }
        $importMd5 = 'md5-of-' . $url;
        return [['fake parsed event']];
      }
      function get_event_count_for_user($user) { return 0; }
      function get_remote_calendar_last_md5($user) { return ''; }
      function update_import_check_date($user) { return true; }
      function activity_log($id, $login, $user, $type, $text) { return true; }
      function user_delete_events($user) { return 1; }
      function import_data(&$data, $overwrite, $type, $remote = false) {
        global $count_suc;
        $count_suc = 3;
        return true;
      }
PHP);
  }

  /**
   * includes/functions.php is 6600 lines that assume a configured
   * application, so take the one function out of it by walking its braces --
   * the technique PurgeEventSelectionTest uses on purge.php.
   */
  private function loadFunctionUnderTest(): void
  {
    if (function_exists('load_remote_calendar')) {
      return;
    }

    $src = file_get_contents(__DIR__ . '/../includes/functions.php');
    self::assertNotFalse($src);

    $fn = SourceText::phpFunction($src, 'load_remote_calendar');
    self::assertIsString($fn,
      'includes/functions.php must define load_remote_calendar()');

    eval($fn);
  }

  public function testAFailingCalendarIsReportedAsFailing(): void
  {
    $result = load_remote_calendar('nonuser_a', 'https://example.com/bad.ics');

    self::assertSame(1, $result[0], 'a bad URL must report an error');
    self::assertStringContainsString('Invalid remote calendar URL', $result[3]);
  }

  /**
   * The regression itself. Called straight after a failure, with $errormsg
   * still dirty, a good calendar has to load.
   */
  public function testAGoodCalendarLoadsAfterABadOne(): void
  {
    load_remote_calendar('nonuser_a', 'https://example.com/bad.ics');
    self::assertNotSame('', $GLOBALS['errormsg'],
      'the first calendar should have left an error behind');

    $result = load_remote_calendar('nonuser_b', 'https://example.com/good.ics');

    self::assertSame(0, $result[0], 'the second calendar must not inherit the '
      . "first one's failure");
    self::assertSame(3, $result[1], 'its events must actually be imported');
    self::assertSame('', $result[3],
      'and it must not be handed the previous error as its own message');
  }

  /**
   * Three in a row, the shape an installation with several subscriptions has.
   */
  public function testOnlyTheFailingCalendarInAListFails(): void
  {
    $outcomes = [];
    foreach (['good-1', 'bad-2', 'good-3'] as $name) {
      $outcomes[$name]
        = load_remote_calendar('nonuser', "https://example.com/$name.ics")[0];
    }

    self::assertSame(
      ['good-1' => 0, 'bad-2' => 1, 'good-3' => 0],
      $outcomes,
      'a failure in the middle of the list must not affect what follows it'
    );
  }

  /**
   * The outer half of the same bug lived in the tool: its loop required
   * empty($errormsg) before touching a calendar, so the skip happened there
   * too even once the function was fixed.
   */
  public function testTheReloadToolDoesNotGateItsLoopOnTheErrorGlobal(): void
  {
    $src = file_get_contents(__DIR__ . '/../tools/reload_remotes.php');
    self::assertNotFalse($src);

    // Comments explain the old condition, so read only executable code.
    $code = SourceText::php($src);

    self::assertDoesNotMatchRegularExpression(
      '/empty\s*\(\s*\$errormsg\s*\)/',
      $code,
      'tools/reload_remotes.php must not decide whether to refresh a '
      . 'calendar from a global left over from the previous one'
    );
  }
}
