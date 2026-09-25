<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * export_get_event_entry() restricts an export to publicly visible events for
 * publish.php, which is the one page that serves a user's calendar to an
 * unauthenticated visitor.
 *
 * The condition read `! empty ( $type ) && $type = 'publish'`. The inner `=`
 * is an assignment: it is always truthy, so the whole thing reduced to "any
 * non-empty $type", and it set $type to 'publish' as a side effect. Two
 * consequences, both live:
 *
 *   publish.php sets $type = 'publish' and got the filter it wanted, by luck.
 *   approve_entry.php takes $type straight from the request and attaches an
 *   ICS to the approval mail, so approving with any type parameter quietly
 *   filtered that attachment down to public events.
 *
 * The function is lifted out of includes/xcal.php rather than copied, and
 * dbi_execute() is stubbed to capture the statement, because what is being
 * tested is the SQL it decides to build.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ExportAccessFilterTest extends TestCase
{
  protected function setUp(): void
  {
    $GLOBALS['wc_captured_sql'] = '';

    $GLOBALS['login'] = 'alice';
    $GLOBALS['user'] = '';
    $GLOBALS['cat_filter'] = '';
    $GLOBALS['include_layers'] = '';
    $GLOBALS['layers'] = [];
    $GLOBALS['use_all_dates'] = true;
    $GLOBALS['startdate'] = '00000000';
    $GLOBALS['enddate'] = '99991231';
    $GLOBALS['moddate'] = '00000000';
    $GLOBALS['DISPLAY_UNAPPROVED'] = 'Y';
    $GLOBALS['USER_REMOTE_ACCESS'] = 0;
    $GLOBALS['type'] = '';

    $this->defineStubs();
    $this->loadFunctionUnderTest();
  }

  private function defineStubs(): void
  {
    if (function_exists('dbi_execute')) {
      return;
    }

    eval(<<<'PHP'
      function dbi_execute($sql, $params = [], $fatal = true, $show = true) {
        $GLOBALS['wc_captured_sql'] = $sql;
        return false;
      }
PHP);
  }

  /**
   * Taken out of includes/xcal.php by walking its braces, the technique
   * PurgeEventSelectionTest uses on purge.php.
   */
  private function loadFunctionUnderTest(): void
  {
    if (function_exists('export_get_event_entry')) {
      return;
    }

    $src = file_get_contents(__DIR__ . '/../includes/xcal.php');
    self::assertNotFalse($src);

    $start = strpos($src, 'function export_get_event_entry(');
    self::assertNotFalse($start,
      'includes/xcal.php must define export_get_event_entry()');

    $open = strpos($src, '{', $start);
    $depth = 0;
    $end = $open;
    for ($i = $open; $i < strlen($src); $i++) {
      if ($src[$i] === '{') {
        $depth++;
      }
      if ($src[$i] === '}') {
        $depth--;
        if ($depth === 0) {
          $end = $i;
          break;
        }
      }
    }

    eval(substr($src, $start, $end - $start + 1));
  }

  private function sqlFor(string $type, int $remoteAccess = 0): string
  {
    $GLOBALS['type'] = $type;
    $GLOBALS['USER_REMOTE_ACCESS'] = $remoteAccess;
    export_get_event_entry('all');

    return (string) $GLOBALS['wc_captured_sql'];
  }

  public function testPublishRestrictsToPublicEvents(): void
  {
    self::assertStringContainsString("AND we.cal_access = 'P'",
      $this->sqlFor('publish', 0),
      'publish.php serves an unauthenticated visitor and must export only '
      . 'public events when remote access is 0');
  }

  public function testPublishAllowsConfidentialWhenRemoteAccessIsOne(): void
  {
    self::assertStringContainsString("AND we.cal_access IN ('P', 'C' )",
      $this->sqlFor('publish', 1));
  }

  public function testAnOrdinaryExportIsNotFiltered(): void
  {
    self::assertStringNotContainsString('AND we.cal_access', $this->sqlFor(''),
      'an export with no type must not be restricted');
  }

  /**
   * The regression. Any non-empty value used to switch the restriction on.
   */
  public function testAnUnrelatedTypeDoesNotRestrictTheExport(): void
  {
    foreach (['icalclient', 'ical', 'vcal', 'E', 'remoteics'] as $type) {
      self::assertStringNotContainsString('AND we.cal_access',
        $this->sqlFor($type),
        "\$type = '$type' must not switch on the publish restriction");
    }
  }

  /**
   * The other half of the same `=`: the caller's variable was overwritten.
   */
  public function testTheCallerIsLeftWithTheTypeItPassedIn(): void
  {
    $this->sqlFor('icalclient');

    self::assertSame('icalclient', $GLOBALS['type'],
      'export_get_event_entry() must not assign to the global $type');
  }
}
