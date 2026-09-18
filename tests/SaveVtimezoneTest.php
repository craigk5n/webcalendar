<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/xcal.php";

/**
 * Unit tests for ICS import VTIMEZONE persistence (save_vtimezone).
 *
 * Regression guard: webcal_timezones has PRIMARY KEY ( tzid ), but the
 * pre-delete was keyed on ( tzid, dtstart ). Re-importing an ICS whose
 * VTIMEZONE carried a different DTSTART than the stored row left the old
 * row in place and the INSERT died with:
 *
 *   Duplicate entry 'America/New_York' for key 'webcal_timezones.PRIMARY'
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * Isolation required because the dbi_* stubs below are defined at global
 * scope and would leak into other suites. Matches ExportTimeTest.
 */
final class SaveVtimezoneTest extends TestCase
{
  protected function setUp(): void {
    if (!function_exists('dbi_execute')) {
      // Record every statement instead of touching a database.
      eval('
        function dbi_execute($sql, $params = [], $fatalOnError = true,
          $showError = true) {
          $GLOBALS["__vtz_sql"][] = [
            "sql" => preg_replace("/\s+/", " ", trim($sql)),
            "params" => $params,
            "fatalOnError" => $fatalOnError,
          ];
          return true;
        }
        function db_error() { return ""; }
      ');
    }
    $GLOBALS["__vtz_sql"] = [];
  }

  /** Statements recorded by the stub whose SQL starts with $verb. */
  private function statements($verb) {
    return array_values(array_filter($GLOBALS["__vtz_sql"],
      function ($s) use ($verb) { return stripos($s["sql"], $verb) === 0; }));
  }

  private function newYorkEvent($dtstart) {
    return [
      'tzid' => 'America/New_York',
      'tzlocation' => 'America/New_York',
      'dtstart' => $dtstart,
      'dtend' => '',
      'VTIMEZONE' => "BEGIN:VTIMEZONE\nTZID:America/New_York\nEND:VTIMEZONE",
    ];
  }

  /**
   * The pre-delete must key on tzid alone -- the table's PRIMARY KEY --
   * so an existing row with any dtstart is removed before the INSERT.
   */
  public function test_delete_keys_on_tzid_only() {
    save_vtimezone($this->newYorkEvent('19700308T020000'));

    $deletes = $this->statements('DELETE');
    $this->assertCount(1, $deletes);
    $this->assertSame('DELETE FROM webcal_timezones WHERE tzid = ?',
      $deletes[0]['sql']);
    $this->assertSame(['America/New_York'], $deletes[0]['params']);
  }

  /**
   * Re-importing the same tzid with a DIFFERENT dtstart (the reported
   * failure: a stored row from an older tzdata revision) must still
   * delete the stored row first.
   */
  public function test_reimport_with_changed_dtstart_still_deletes_row() {
    save_vtimezone($this->newYorkEvent('20070311T020000'));
    $GLOBALS["__vtz_sql"] = [];
    save_vtimezone($this->newYorkEvent('19700308T020000'));

    $deletes = $this->statements('DELETE');
    $this->assertCount(1, $deletes);
    $this->assertNotContains('19700308T020000', $deletes[0]['params'],
      'delete must not be narrowed by the incoming dtstart');
    $this->assertSame(['America/New_York'], $deletes[0]['params']);
  }

  /**
   * A timezone that cannot be cached must not abort the import with a
   * raw SQL error page, so both statements run non-fatally.
   */
  public function test_statements_are_non_fatal() {
    save_vtimezone($this->newYorkEvent('19700308T020000'));

    $this->assertNotEmpty($GLOBALS["__vtz_sql"]);
    foreach ($GLOBALS["__vtz_sql"] as $statement) {
      $this->assertFalse($statement['fatalOnError'],
        $statement['sql'] . ' must not be fatal on error');
    }
  }

  /** The INSERT still writes all four columns for a valid timezone. */
  public function test_insert_writes_timezone_row() {
    $event = $this->newYorkEvent('19700308T020000');
    save_vtimezone($event);

    $inserts = $this->statements('INSERT');
    $this->assertCount(1, $inserts);
    $this->assertSame(
      ['America/New_York', '19700308T020000', '', $event['VTIMEZONE']],
      $inserts[0]['params']);
  }

  /**
   * A VTIMEZONE with no TZID and no X-LIC-LOCATION has no primary key
   * value; writing a blank tzid only collides with the next one.
   */
  public function test_blank_tzid_writes_nothing() {
    save_vtimezone([
      'dtstart' => '19700308T020000',
      'VTIMEZONE' => "BEGIN:VTIMEZONE\nEND:VTIMEZONE",
    ]);

    $this->assertSame([], $GLOBALS["__vtz_sql"]);
  }

  /**
   * Two VTIMEZONE blocks in one file must be stored under their own
   * tzid and dtstart. Previously the second block inherited tzlocation
   * and dtstart from the first, so Europe/London was written as a second
   * America/New_York row -- another duplicate key.
   */
  public function test_multiple_vtimezones_do_not_cross_contaminate() {
    $ics = "BEGIN:VCALENDAR\r\n"
      . "BEGIN:VTIMEZONE\r\n"
      . "TZID:America/New_York\r\n"
      . "X-LIC-LOCATION:America/New_York\r\n"
      . "BEGIN:DAYLIGHT\r\n"
      . "DTSTART:19700308T020000\r\n"
      . "END:DAYLIGHT\r\n"
      . "END:VTIMEZONE\r\n"
      . "BEGIN:VTIMEZONE\r\n"
      . "TZID:Europe/London\r\n"
      . "BEGIN:STANDARD\r\n"
      . "DTSTART:19701025T020000\r\n"
      . "END:STANDARD\r\n"
      . "END:VTIMEZONE\r\n"
      . "END:VCALENDAR\r\n";

    $path = tempnam(sys_get_temp_dir(), 'vtz') . '.ics';
    file_put_contents($path, $ics);
    try {
      parse_ical($path, 'file');
    } finally {
      unlink($path);
    }

    $inserts = $this->statements('INSERT');
    $this->assertCount(2, $inserts);
    $this->assertSame('America/New_York', $inserts[0]['params'][0]);
    $this->assertSame('19700308T020000', $inserts[0]['params'][1]);
    $this->assertSame('Europe/London', $inserts[1]['params'][0]);
    $this->assertSame('19701025T020000', $inserts[1]['params'][1]);
  }
}
