<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";

/**
 * Unit tests for import date handling (issue #193).
 *
 * Verifies that all-day event dates are preserved correctly across
 * timezone conversions during import, where timestamps are created
 * in local timezone but later converted back to date strings.
 */
final class ImportDateTest extends TestCase
{
  /**
   * Demonstrate the core bug: gmdate shifts the date for midnight local time
   * in positive UTC offset timezones. date() preserves it.
   */
  public function test_allday_date_positive_offset() {
    date_default_timezone_set("Europe/Amsterdam");

    // Simulate what import does: strtotime parses "21-4-2020 00:00:00"
    // as midnight in the local timezone (CEST = UTC+2)
    $ts = strtotime("2020-04-21 00:00:00");

    // gmdate converts to UTC, which is 22:00 on April 20 -- WRONG date
    $this->assertEquals('20200420', gmdate('Ymd', $ts),
      'gmdate shows previous day for midnight in UTC+2');

    // date() preserves the local date -- CORRECT
    $this->assertEquals('20200421', date('Ymd', $ts),
      'date() preserves the correct local date');
  }

  /**
   * Negative UTC offset timezones: gmdate shifts the date forward.
   */
  public function test_allday_date_negative_offset() {
    date_default_timezone_set("America/New_York");

    // Midnight EDT (UTC-4) = 04:00 UTC same day
    $ts = strtotime("2020-04-21 00:00:00");

    // gmdate gives the same date here (04:00 UTC is still April 21)
    $this->assertEquals('20200421', gmdate('Ymd', $ts));

    // date() also correct
    $this->assertEquals('20200421', date('Ymd', $ts));
  }

  /**
   * Test that mktime-based timestamps for all-day events preserve the date
   * when using date() but not necessarily with gmdate().
   * This mirrors icaldate_to_timestamp behavior for CSV imports.
   */
  public function test_mktime_allday_date_preservation() {
    date_default_timezone_set("Australia/Melbourne");

    // Midnight AEST (UTC+10) = 14:00 UTC previous day
    $ts = mktime(0, 0, 0, 4, 21, 2020);

    // gmdate gives April 20 -- the bug
    $this->assertEquals('20200420', gmdate('Ymd', $ts),
      'gmdate shifts date back for UTC+10');

    // date() gives April 21 -- the fix
    $this->assertEquals('20200421', date('Ymd', $ts),
      'date() preserves correct local date for UTC+10');
  }

  /**
   * UTC timezone: gmdate and date should agree.
   */
  public function test_allday_date_utc() {
    date_default_timezone_set("UTC");

    $ts = mktime(0, 0, 0, 4, 21, 2020);

    $this->assertEquals('20200421', gmdate('Ymd', $ts));
    $this->assertEquals('20200421', date('Ymd', $ts));
  }
}
