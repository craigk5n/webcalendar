<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/xcal.php";

/**
 * Unit tests for ICS export timezone handling.
 *
 * Contract: stored cal_time values are already UTC (written by
 * edit_entry_handler.php via mktime + gmdate('His', ...)). export_time()
 * must round-trip that UTC value into the ICS DTSTART/DTEND output without
 * re-applying the server timezone offset.
 *
 * Originally added for issue #74 with local-time-in semantics; rewritten
 * after that change was re-diagnosed as the cause of a constant-offset
 * export bug (Apple Calendar showing events off by the full server TZ
 * offset). The 2018 #74 symptom originated in event creation, not export.
 */
final class ExportTimeTest extends TestCase
{
  /**
   * Stored UTC value must round-trip unchanged regardless of server TZ.
   * 19:00 UTC stored -> 19:00 UTC exported, with PHP in Berlin (winter CET).
   */
  public function test_export_time_roundtrips_utc_winter_cet() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20180115', 60, '190000', 'ical');

    $this->assertStringContainsString('DTSTART:20180115T190000Z', $result);
    $this->assertStringContainsString('DTEND:20180115T200000Z', $result);
  }

  /**
   * Same round-trip with PHP in Berlin summer (CEST). Output must be
   * identical to the winter case for the same stored UTC input -- DST
   * must not affect export.
   */
  public function test_export_time_roundtrips_utc_summer_cest() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20180715', 60, '190000', 'ical');

    $this->assertStringContainsString('DTSTART:20180715T190000Z', $result);
    $this->assertStringContainsString('DTEND:20180715T200000Z', $result);
  }

  /**
   * Regression guard for the April-2026 bug: DST must NOT shift exported
   * times. Winter and summer exports for the same stored UTC value must
   * produce the same UTC hour.
   */
  public function test_export_time_is_dst_independent() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $winter = export_time('20180115', 60, '190000', 'ical');
    $summer = export_time('20180715', 60, '190000', 'ical');

    preg_match('/DTSTART:\d{8}T(\d{6})Z/', $winter, $winterMatch);
    preg_match('/DTSTART:\d{8}T(\d{6})Z/', $summer, $summerMatch);

    $this->assertSame('190000', $winterMatch[1]);
    $this->assertSame('190000', $summerMatch[1]);
  }

  /**
   * Event 846133-style scenario: 8 AM EDT (stored as 12:00 UTC) with PHP in
   * America/New_York. Exported time must be 12:00 UTC so Apple/Google
   * Calendar display it as 8 AM local to the viewer.
   */
  public function test_export_time_us_eastern_roundtrips_utc() {
    date_default_timezone_set("America/New_York");
    $GLOBALS['TIMEZONE'] = 'America/New_York';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    // 8 AM EDT == 12:00 UTC (how edit_entry_handler.php stores it).
    $result = export_time('20260428', 30, '120000', 'ical');
    $this->assertStringContainsString('DTSTART:20260428T120000Z', $result);
    $this->assertStringContainsString('DTEND:20260428T123000Z', $result);

    // Winter event: 2 PM EST == 19:00 UTC (stored).
    $winter = export_time('20180115', 60, '190000', 'ical');
    $this->assertStringContainsString('DTSTART:20180115T190000Z', $winter);
  }

  /**
   * Untimed events use DATE format and DTEND is the next day per RFC 5545.
   */
  public function test_export_time_untimed() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20180715', 0, '-1', 'ical');

    $this->assertStringContainsString('DTSTART;VALUE=DATE:20180715', $result);
    $this->assertStringContainsString('DTEND;VALUE=DATE:20180716', $result);
  }

  /**
   * Untimed event at end of month -> DTEND rolls to next month.
   */
  public function test_export_time_untimed_end_of_month() {
    date_default_timezone_set("UTC");
    $GLOBALS['TIMEZONE'] = 'UTC';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20180131', 0, '-1', 'ical');

    $this->assertStringContainsString('DTSTART;VALUE=DATE:20180131', $result);
    $this->assertStringContainsString('DTEND;VALUE=DATE:20180201', $result);
  }

  /**
   * Untimed event at end of year -> DTEND rolls to next year.
   */
  public function test_export_time_untimed_end_of_year() {
    date_default_timezone_set("UTC");
    $GLOBALS['TIMEZONE'] = 'UTC';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20181231', 0, '-1', 'ical');

    $this->assertStringContainsString('DTSTART;VALUE=DATE:20181231', $result);
    $this->assertStringContainsString('DTEND;VALUE=DATE:20190101', $result);
  }

  /**
   * All-day events use DATE format.
   */
  public function test_export_time_allday() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20180715', 1440, '0', 'ical');

    $this->assertStringContainsString('DTSTART;VALUE=DATE:20180715', $result);
    $this->assertStringContainsString('DTEND;VALUE=DATE:', $result);
  }

  /**
   * VTIMEZONE branch: when use_vtimezone is truthy AND a matching
   * VTIMEZONE row is available, DTSTART/DTEND must be tagged with TZID
   * and hold LOCAL wall time (no 'Z' suffix), per RFC 5545. We stub the
   * dbi_* helpers so get_vtimezone() returns a non-empty VTIMEZONE body
   * without needing a live DB. Regression guard for the event-846133
   * Apple Calendar bug (previously emitted a UTC value with Z under a
   * TZID label, and hard-coded T000000 for DTEND).
   */
  public function test_export_time_vtimezone_branch_emits_local_time() {
    if (!function_exists('dbi_execute')) {
      // Minimal stubs so get_vtimezone() returns a non-empty body.
      eval('
        function dbi_execute($sql, $params = []) { return new stdClass(); }
        $GLOBALS["__vtz_row_served"] = false;
        function dbi_fetch_row($res) {
          if ($GLOBALS["__vtz_row_served"]) return false;
          $GLOBALS["__vtz_row_served"] = true;
          return ["BEGIN:VTIMEZONE\r\nTZID:America/New_York\r\nEND:VTIMEZONE"];
        }
        function dbi_free_result($res) { $GLOBALS["__vtz_row_served"] = false; }
      ');
    }
    $GLOBALS["__vtz_row_served"] = false;

    date_default_timezone_set("America/New_York");
    $GLOBALS['TIMEZONE'] = 'America/New_York';
    $GLOBALS['use_vtimezone'] = true;
    $GLOBALS['vtimezone_data'] = '';

    // Event 846133 shape: stored 120000 UTC on 20260428, 30 min duration.
    $result = export_time('20260428', 30, '120000', 'ical');

    $this->assertStringContainsString(
      'DTSTART;TZID=America/New_York:20260428T080000', $result);
    $this->assertStringNotContainsString('120000Z', $result);
    $this->assertStringContainsString(
      'DTEND;TZID=America/New_York:20260428T083000', $result);
    // Prior bug: hard-coded T000000 for the timed-event DTEND. Assert
    // the emitted DTEND is NOT midnight.
    $this->assertDoesNotMatchRegularExpression(
      '/DTEND;TZID=America\/New_York:\d{8}T000000/', $result);
  }

  /**
   * UTC server: stored UTC value exports unchanged (baseline sanity check).
   */
  public function test_export_time_utc_server() {
    date_default_timezone_set("UTC");
    $GLOBALS['TIMEZONE'] = 'UTC';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20180715', 60, '190000', 'ical');

    $this->assertStringContainsString('DTSTART:20180715T190000Z', $result);
    $this->assertStringContainsString('DTEND:20180715T200000Z', $result);
  }
}
