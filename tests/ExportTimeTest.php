<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/xcal.php";

/**
 * Unit tests for ICS export timezone handling (issue #74).
 *
 * Verifies that export_time() produces correct UTC times in DTSTART/DTEND
 * lines, properly accounting for the server timezone and DST.
 */
final class ExportTimeTest extends TestCase
{
  /**
   * Test that a timed event in winter (CET = UTC+1) exports correct UTC.
   * 19:00 Berlin time in January = 18:00 UTC.
   */
  public function test_export_time_winter_cet() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    // Event on Jan 15 2018 at 19:00 local, duration 60 minutes
    $result = export_time('20180115', 60, '190000', 'ical');

    // 19:00 CET = 18:00 UTC
    $this->assertStringContainsString('DTSTART:20180115T180000Z', $result);
    $this->assertStringContainsString('DTEND:20180115T190000Z', $result);
  }

  /**
   * Test that a timed event in summer (CEST = UTC+2) exports correct UTC.
   * 19:00 Berlin time in July = 17:00 UTC.
   */
  public function test_export_time_summer_cest() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    // Event on Jul 15 2018 at 19:00 local, duration 60 minutes
    $result = export_time('20180715', 60, '190000', 'ical');

    // 19:00 CEST = 17:00 UTC
    $this->assertStringContainsString('DTSTART:20180715T170000Z', $result);
    $this->assertStringContainsString('DTEND:20180715T180000Z', $result);
  }

  /**
   * Test that winter and summer events at the same local time produce
   * different UTC times (the core of issue #74).
   */
  public function test_export_time_dst_difference() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $winter = export_time('20180115', 60, '190000', 'ical');
    $summer = export_time('20180715', 60, '190000', 'ical');

    // Extract DTSTART times
    preg_match('/DTSTART:(\d{8}T\d{6}Z)/', $winter, $winterMatch);
    preg_match('/DTSTART:(\d{8}T\d{6}Z)/', $summer, $summerMatch);

    // Winter: 19:00 CET = 18:00 UTC, Summer: 19:00 CEST = 17:00 UTC
    // The UTC hour should differ by 1 (the DST offset difference)
    $winterHour = substr($winterMatch[1], 9, 2);
    $summerHour = substr($summerMatch[1], 9, 2);
    $this->assertEquals(18, (int)$winterHour);
    $this->assertEquals(17, (int)$summerHour);
  }

  /**
   * Test with US Eastern timezone (UTC-5 winter / UTC-4 summer).
   */
  public function test_export_time_us_eastern() {
    date_default_timezone_set("America/New_York");
    $GLOBALS['TIMEZONE'] = 'America/New_York';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    // Winter: 14:00 EST = 19:00 UTC
    $winter = export_time('20180115', 60, '140000', 'ical');
    $this->assertStringContainsString('DTSTART:20180115T190000Z', $winter);

    // Summer: 14:00 EDT = 18:00 UTC
    $summer = export_time('20180715', 60, '140000', 'ical');
    $this->assertStringContainsString('DTSTART:20180715T180000Z', $summer);
  }

  /**
   * Test that untimed events use DATE format without time conversion.
   */
  public function test_export_time_untimed() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20180715', 0, '-1', 'ical');

    $this->assertStringContainsString('DTSTART;VALUE=DATE:20180715', $result);
  }

  /**
   * Test that all-day events use DATE format without time conversion.
   */
  public function test_export_time_allday() {
    date_default_timezone_set("Europe/Berlin");
    $GLOBALS['TIMEZONE'] = 'Europe/Berlin';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    // All day = time 0, duration 1440 minutes (24 hours)
    $result = export_time('20180715', 1440, '0', 'ical');

    $this->assertStringContainsString('DTSTART;VALUE=DATE:20180715', $result);
    $this->assertStringContainsString('DTEND;VALUE=DATE:', $result);
  }

  /**
   * Test UTC timezone (no offset, no DST).
   */
  public function test_export_time_utc() {
    date_default_timezone_set("UTC");
    $GLOBALS['TIMEZONE'] = 'UTC';
    $GLOBALS['use_vtimezone'] = '';
    $GLOBALS['vtimezone_data'] = '';

    $result = export_time('20180715', 60, '190000', 'ical');

    // UTC: 19:00 local = 19:00 UTC
    $this->assertStringContainsString('DTSTART:20180715T190000Z', $result);
    $this->assertStringContainsString('DTEND:20180715T200000Z', $result);
  }
}
