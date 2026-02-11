<?php
/**
 * Comprehensive unit tests for repeating event functions in includes/functions.php
 *
 * Tests cover:
 * - get_all_dates() - main repeat date generation
 * - get_byday() - BYDAY RRULE processing
 * - get_bymonthday() - BYMONTHDAY RRULE processing
 *
 * Edge cases include:
 * - All repeat types (daily, weekly, monthly, yearly)
 * - RRULE components (BYMONTH, BYDAY, BYMONTHDAY, BYSETPOS, etc.)
 * - Exception dates (EXDATE) and inclusion dates (RDATE)
 * - DST transitions
 * - Month/year boundaries
 * - Leap years
 * - COUNT and UNTIL termination
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";

final class RepeatingEventsTest extends TestCase
{
  /**
   * Set up global variables required by repeat functions
   */
  protected function setUp(): void
  {
    global $byday_names, $byday_values, $CONFLICT_REPEAT_MONTHS;

    // These are normally set in WebCalendar.php
    $byday_names = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    $byday_values = array_flip($byday_names);
    $CONFLICT_REPEAT_MONTHS = 6;

    // Use a consistent timezone for all tests
    date_default_timezone_set("America/New_York");
  }

  // =========================================================================
  // DAILY REPETITION TESTS
  // =========================================================================

  /**
   * Test simple daily repetition
   */
  public function test_daily_simple()
  {
    // Start date: Jan 1, 2024 at 10:00 AM
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,        // interval
      '',       // ByMonth
      '',       // ByWeekNo
      '',       // ByYearDay
      '',       // ByMonthDay
      '',       // ByDay
      '',       // BySetPos
      5         // Count
    );

    $this->assertCount(5, $dates);
    $this->assertEquals('20240101', date('Ymd', $dates[0]));
    $this->assertEquals('20240102', date('Ymd', $dates[1]));
    $this->assertEquals('20240103', date('Ymd', $dates[2]));
    $this->assertEquals('20240104', date('Ymd', $dates[3]));
    $this->assertEquals('20240105', date('Ymd', $dates[4]));
  }

  /**
   * Test daily repetition with interval (every 3 days)
   */
  public function test_daily_with_interval()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      3,        // every 3 days
      '',
      '',
      '',
      '',
      '',
      '',
      5
    );

    $this->assertCount(5, $dates);
    $this->assertEquals('20240101', date('Ymd', $dates[0]));
    $this->assertEquals('20240104', date('Ymd', $dates[1]));
    $this->assertEquals('20240107', date('Ymd', $dates[2]));
    $this->assertEquals('20240110', date('Ymd', $dates[3]));
    $this->assertEquals('20240113', date('Ymd', $dates[4]));
  }

  /**
   * Test daily repetition with BYMONTH filter (only in January and March)
   */
  public function test_daily_with_bymonth()
  {
    $startDate = mktime(10, 0, 0, 1, 28, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '1,3',    // January and March only
      '',
      '',
      '',
      '',
      '',
      10
    );

    // Should get Jan 28-31 (4 days), skip Feb entirely, then Mar 1-5 (6 days)
    $this->assertCount(10, $dates);
    $this->assertEquals('20240128', date('Ymd', $dates[0]));
    $this->assertEquals('20240131', date('Ymd', $dates[3]));
    $this->assertEquals('20240301', date('Ymd', $dates[4])); // Jumps to March
    $this->assertEquals('20240306', date('Ymd', $dates[9]));
  }

  /**
   * Test daily repetition with BYDAY filter (only Mon, Wed, Fri)
   *
   * NOTE: The get_byday() function uses ">" not ">=" when comparing to start date,
   * so the start date itself is excluded from BYDAY matching even if it matches.
   * This is current behavior - the first occurrence comes from the simple daily
   * iteration, BYDAY filters subsequent dates.
   */
  public function test_daily_with_byday()
  {
    // Jan 1, 2024 is a Monday
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      'MO,WE,FR',  // Monday, Wednesday, Friday
      '',
      6
    );

    $this->assertCount(6, $dates);
    // Current behavior: BYDAY excludes dates <= start date
    // So first matching BYDAY is Wed Jan 3, not Mon Jan 1
    $this->assertEquals('20240103', date('Ymd', $dates[0])); // Wed (first BYDAY match after start)
    $this->assertEquals('20240105', date('Ymd', $dates[1])); // Fri
    $this->assertEquals('20240108', date('Ymd', $dates[2])); // Mon
    $this->assertEquals('20240110', date('Ymd', $dates[3])); // Wed
    $this->assertEquals('20240112', date('Ymd', $dates[4])); // Fri
    $this->assertEquals('20240115', date('Ymd', $dates[5])); // Mon
  }

  /**
   * Test daily repetition with BYMONTHDAY filter
   */
  public function test_daily_with_bymonthday()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '1,15',   // 1st and 15th of each month
      '',
      '',
      6
    );

    $this->assertCount(6, $dates);
    $this->assertEquals('20240101', date('Ymd', $dates[0]));
    $this->assertEquals('20240115', date('Ymd', $dates[1]));
    $this->assertEquals('20240201', date('Ymd', $dates[2]));
    $this->assertEquals('20240215', date('Ymd', $dates[3]));
    $this->assertEquals('20240301', date('Ymd', $dates[4]));
    $this->assertEquals('20240315', date('Ymd', $dates[5]));
  }

  // =========================================================================
  // WEEKLY REPETITION TESTS
  // =========================================================================

  /**
   * Test simple weekly repetition
   */
  public function test_weekly_simple()
  {
    // Jan 1, 2024 is a Monday
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      5
    );

    $this->assertCount(5, $dates);
    $this->assertEquals('20240101', date('Ymd', $dates[0])); // Mon
    $this->assertEquals('20240108', date('Ymd', $dates[1])); // Mon +1 week
    $this->assertEquals('20240115', date('Ymd', $dates[2])); // Mon +2 weeks
    $this->assertEquals('20240122', date('Ymd', $dates[3])); // Mon +3 weeks
    $this->assertEquals('20240129', date('Ymd', $dates[4])); // Mon +4 weeks
  }

  /**
   * Test weekly with BYDAY (multiple days per week: MO, WE, FR)
   */
  public function test_weekly_with_byday_multiple()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      1,
      '',
      '',
      '',
      '',
      'MO,WE,FR',
      '',
      9
    );

    $this->assertCount(9, $dates);
    // Week 1: Mon 1, Wed 3, Fri 5
    $this->assertEquals('20240101', date('Ymd', $dates[0]));
    $this->assertEquals('20240103', date('Ymd', $dates[1]));
    $this->assertEquals('20240105', date('Ymd', $dates[2]));
    // Week 2: Mon 8, Wed 10, Fri 12
    $this->assertEquals('20240108', date('Ymd', $dates[3]));
    $this->assertEquals('20240110', date('Ymd', $dates[4]));
    $this->assertEquals('20240112', date('Ymd', $dates[5]));
    // Week 3: Mon 15, Wed 17, Fri 19
    $this->assertEquals('20240115', date('Ymd', $dates[6]));
    $this->assertEquals('20240117', date('Ymd', $dates[7]));
    $this->assertEquals('20240119', date('Ymd', $dates[8]));
  }

  /**
   * Test bi-weekly repetition
   */
  public function test_weekly_biweekly()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      2,        // every 2 weeks
      '',
      '',
      '',
      '',
      '',
      '',
      4
    );

    $this->assertCount(4, $dates);
    $this->assertEquals('20240101', date('Ymd', $dates[0]));
    $this->assertEquals('20240115', date('Ymd', $dates[1])); // +2 weeks
    $this->assertEquals('20240129', date('Ymd', $dates[2])); // +4 weeks
    $this->assertEquals('20240212', date('Ymd', $dates[3])); // +6 weeks
  }

  /**
   * Test weekly with different week start (Sunday)
   */
  public function test_weekly_with_wkst_sunday()
  {
    // Jan 3, 2024 is a Wednesday
    $startDate = mktime(10, 0, 0, 1, 3, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      1,
      '',
      '',
      '',
      '',
      'TU,TH',  // Tuesday, Thursday
      '',
      4,
      null,
      'SU'      // Week starts on Sunday
    );

    $this->assertCount(4, $dates);
    // First week (starting Sun Dec 31): Tue Jan 2 is before start, Thu Jan 4
    $this->assertEquals('20240104', date('Ymd', $dates[0])); // Thu
    // Second week: Tue Jan 9, Thu Jan 11
    $this->assertEquals('20240109', date('Ymd', $dates[1])); // Tue
    $this->assertEquals('20240111', date('Ymd', $dates[2])); // Thu
    $this->assertEquals('20240116', date('Ymd', $dates[3])); // Tue
  }

  // =========================================================================
  // MONTHLY REPETITION TESTS
  // =========================================================================

  /**
   * Test monthly repetition by date (15th of each month)
   */
  public function test_monthly_by_date()
  {
    $startDate = mktime(10, 0, 0, 1, 15, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      6
    );

    $this->assertCount(6, $dates);
    $this->assertEquals('20240115', date('Ymd', $dates[0]));
    $this->assertEquals('20240215', date('Ymd', $dates[1]));
    $this->assertEquals('20240315', date('Ymd', $dates[2]));
    $this->assertEquals('20240415', date('Ymd', $dates[3]));
    $this->assertEquals('20240515', date('Ymd', $dates[4]));
    $this->assertEquals('20240615', date('Ymd', $dates[5]));
  }

  /**
   * Test monthly with BYMONTHDAY (multiple days)
   *
   * NOTE: get_bymonthday() uses ">" not ">=" for start date comparison,
   * so start date itself is excluded. Jan 1 is excluded, first match is Jan 15.
   * Additionally, when the base iteration lands on Jan 1, Feb 1, Mar 1 etc,
   * it generates both BYMONTHDAY matches for each month visited.
   */
  public function test_monthly_with_bymonthday_multiple()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '1,15',   // 1st and 15th
      '',
      '',
      6
    );

    // Current behavior: Jan 1 excluded (start date), so we get:
    // Jan 15, Feb 1, Feb 15, Mar 1, Mar 15, Apr 1
    $this->assertGreaterThanOrEqual(6, count($dates));
    $this->assertEquals('20240115', date('Ymd', $dates[0])); // First match after start
    $this->assertEquals('20240201', date('Ymd', $dates[1]));
    $this->assertEquals('20240215', date('Ymd', $dates[2]));
    $this->assertEquals('20240301', date('Ymd', $dates[3]));
    $this->assertEquals('20240315', date('Ymd', $dates[4]));
    $this->assertEquals('20240401', date('Ymd', $dates[5]));
  }

  /**
   * Test monthly with BYDAY (2nd Tuesday of each month)
   *
   * NOTE: get_byday() uses ">" not ">=" for start date comparison, so the
   * start date's month is effectively skipped. First result is Feb.
   */
  public function test_monthly_second_tuesday()
  {
    $startDate = mktime(10, 0, 0, 1, 9, 2024); // Jan 9, 2024 is 2nd Tuesday

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '2TU',    // 2nd Tuesday
      '',
      6
    );

    // Current behavior: start month skipped, first result is Feb
    $this->assertCount(6, $dates);
    $this->assertEquals('20240213', date('Ymd', $dates[0])); // Feb 2nd Tue
    $this->assertEquals('20240312', date('Ymd', $dates[1])); // Mar 2nd Tue
    $this->assertEquals('20240409', date('Ymd', $dates[2])); // Apr 2nd Tue
    $this->assertEquals('20240514', date('Ymd', $dates[3])); // May 2nd Tue
    $this->assertEquals('20240611', date('Ymd', $dates[4])); // Jun 2nd Tue
    $this->assertEquals('20240709', date('Ymd', $dates[5])); // Jul 2nd Tue
  }

  /**
   * Test monthly with negative BYDAY (last Friday of each month)
   *
   * NOTE: Start month is skipped due to ">" comparison in get_byday().
   */
  public function test_monthly_last_friday()
  {
    $startDate = mktime(10, 0, 0, 1, 26, 2024); // Jan 26, 2024 is last Friday

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '-1FR',   // Last Friday
      '',
      6
    );

    // Current behavior: start month skipped
    $this->assertCount(6, $dates);
    $this->assertEquals('20240223', date('Ymd', $dates[0])); // Feb last Fri
    $this->assertEquals('20240329', date('Ymd', $dates[1])); // Mar last Fri
    $this->assertEquals('20240426', date('Ymd', $dates[2])); // Apr last Fri
    $this->assertEquals('20240531', date('Ymd', $dates[3])); // May last Fri
    $this->assertEquals('20240628', date('Ymd', $dates[4])); // Jun last Fri
    $this->assertEquals('20240726', date('Ymd', $dates[5])); // Jul last Fri
  }

  /**
   * Test monthly with BYSETPOS (last weekday via BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1)
   *
   * NOTE: Start month is skipped due to ">" comparison.
   */
  public function test_monthly_last_weekday_with_bysetpos()
  {
    $startDate = mktime(10, 0, 0, 1, 31, 2024); // Jan 31, 2024 is last weekday

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      'MO,TU,WE,TH,FR', // All weekdays
      '-1',             // Last one (BYSETPOS)
      6
    );

    // Current behavior: start month skipped
    $this->assertCount(6, $dates);
    $this->assertEquals('20240229', date('Ymd', $dates[0])); // Feb 29 (Thu) - leap year!
    $this->assertEquals('20240329', date('Ymd', $dates[1])); // Mar 29 (Fri)
    $this->assertEquals('20240430', date('Ymd', $dates[2])); // Apr 30 (Tue)
    $this->assertEquals('20240531', date('Ymd', $dates[3])); // May 31 (Fri)
    $this->assertEquals('20240628', date('Ymd', $dates[4])); // Jun 28 (Fri)
    $this->assertEquals('20240731', date('Ymd', $dates[5])); // Jul 31 (Wed)
  }

  /**
   * Test monthly with negative BYMONTHDAY (last day of month)
   *
   * NOTE: Start month is skipped due to ">" comparison in get_bymonthday().
   */
  public function test_monthly_last_day_of_month()
  {
    $startDate = mktime(10, 0, 0, 1, 31, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '-1',     // Last day of month
      '',
      '',
      6
    );

    // Current behavior: start month skipped
    $this->assertCount(6, $dates);
    $this->assertEquals('20240229', date('Ymd', $dates[0])); // 29 days (leap year)
    $this->assertEquals('20240331', date('Ymd', $dates[1])); // 31 days
    $this->assertEquals('20240430', date('Ymd', $dates[2])); // 30 days
    $this->assertEquals('20240531', date('Ymd', $dates[3])); // 31 days
    $this->assertEquals('20240630', date('Ymd', $dates[4])); // 30 days
    $this->assertEquals('20240731', date('Ymd', $dates[5])); // 31 days
  }

  /**
   * Test quarterly repetition (every 3 months)
   */
  public function test_monthly_quarterly()
  {
    $startDate = mktime(10, 0, 0, 1, 15, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      3,        // every 3 months
      '',
      '',
      '',
      '',
      '',
      '',
      4
    );

    $this->assertCount(4, $dates);
    $this->assertEquals('20240115', date('Ymd', $dates[0])); // Jan
    $this->assertEquals('20240415', date('Ymd', $dates[1])); // Apr
    $this->assertEquals('20240715', date('Ymd', $dates[2])); // Jul
    $this->assertEquals('20241015', date('Ymd', $dates[3])); // Oct
  }

  // =========================================================================
  // YEARLY REPETITION TESTS
  // =========================================================================

  /**
   * Test simple yearly repetition
   */
  public function test_yearly_simple()
  {
    $startDate = mktime(10, 0, 0, 3, 15, 2024);

    $dates = get_all_dates(
      $startDate,
      'yearly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      4
    );

    $this->assertCount(4, $dates);
    $this->assertEquals('20240315', date('Ymd', $dates[0]));
    $this->assertEquals('20250315', date('Ymd', $dates[1]));
    $this->assertEquals('20260315', date('Ymd', $dates[2]));
    $this->assertEquals('20270315', date('Ymd', $dates[3]));
  }

  /**
   * Test yearly with BYMONTH (different month than start)
   */
  public function test_yearly_with_bymonth()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'yearly',
      1,
      '7',      // July only
      '',
      '',
      '',
      '',
      '',
      3
    );

    $this->assertCount(3, $dates);
    $this->assertEquals('20240701', date('Ymd', $dates[0])); // July 2024
    $this->assertEquals('20250701', date('Ymd', $dates[1])); // July 2025
    $this->assertEquals('20260701', date('Ymd', $dates[2])); // July 2026
  }

  /**
   * Test Mother's Day (2nd Sunday in May)
   *
   * NOTE: Start year is skipped due to ">" comparison in get_byday().
   */
  public function test_yearly_mothers_day()
  {
    $startDate = mktime(10, 0, 0, 5, 12, 2024); // Mother's Day 2024

    $dates = get_all_dates(
      $startDate,
      'yearly',
      1,
      '5',      // May
      '',
      '',
      '',
      '2SU',    // 2nd Sunday
      '',
      5
    );

    // Current behavior: start year skipped
    $this->assertCount(5, $dates);
    $this->assertEquals('20250511', date('Ymd', $dates[0])); // 2025
    $this->assertEquals('20260510', date('Ymd', $dates[1])); // 2026
    $this->assertEquals('20270509', date('Ymd', $dates[2])); // 2027
    $this->assertEquals('20280514', date('Ymd', $dates[3])); // 2028
    $this->assertEquals('20290513', date('Ymd', $dates[4])); // 2029

    // Verify they're all Sundays
    foreach ($dates as $date) {
      $this->assertEquals(0, date('w', $date), "Date should be Sunday: " . date('Y-m-d', $date));
    }
  }

  /**
   * Test Thanksgiving (4th Thursday in November)
   *
   * NOTE: Start year is skipped due to ">" comparison in get_byday().
   */
  public function test_yearly_thanksgiving()
  {
    $startDate = mktime(10, 0, 0, 11, 28, 2024); // Thanksgiving 2024

    $dates = get_all_dates(
      $startDate,
      'yearly',
      1,
      '11',     // November
      '',
      '',
      '',
      '4TH',    // 4th Thursday
      '',
      5
    );

    // Current behavior: start year skipped
    $this->assertCount(5, $dates);
    $this->assertEquals('20251127', date('Ymd', $dates[0])); // 2025
    $this->assertEquals('20261126', date('Ymd', $dates[1])); // 2026
    $this->assertEquals('20271125', date('Ymd', $dates[2])); // 2027
    $this->assertEquals('20281123', date('Ymd', $dates[3])); // 2028
    $this->assertEquals('20291122', date('Ymd', $dates[4])); // 2029

    // Verify they're all Thursdays
    foreach ($dates as $date) {
      $this->assertEquals(4, date('w', $date), "Date should be Thursday: " . date('Y-m-d', $date));
    }
  }

  /**
   * Test yearly with BYYEARDAY (100th day of year)
   */
  public function test_yearly_with_byyearday()
  {
    $startDate = mktime(10, 0, 0, 4, 9, 2024); // Day 100 of 2024 (leap year)

    $dates = get_all_dates(
      $startDate,
      'yearly',
      1,
      '',
      '',
      '100',    // 100th day of year
      '',
      '',
      '',
      4
    );

    $this->assertCount(4, $dates);
    $this->assertEquals('20240409', date('Ymd', $dates[0])); // 2024 (leap year)
    $this->assertEquals('20250410', date('Ymd', $dates[1])); // 2025 (not leap)
    $this->assertEquals('20260410', date('Ymd', $dates[2])); // 2026
    $this->assertEquals('20270410', date('Ymd', $dates[3])); // 2027
  }

  /**
   * Test leap year - Feb 29 event
   */
  public function test_yearly_leap_day()
  {
    $startDate = mktime(10, 0, 0, 2, 29, 2024);

    $dates = get_all_dates(
      $startDate,
      'yearly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      3
    );

    // Note: This tests how the system handles Feb 29
    // Behavior may vary - some systems skip non-leap years
    $this->assertGreaterThanOrEqual(1, count($dates));
    $this->assertEquals('20240229', date('Ymd', $dates[0]));
  }

  /**
   * Test biennial repetition (every 2 years)
   */
  public function test_yearly_biennial()
  {
    $startDate = mktime(10, 0, 0, 6, 15, 2024);

    $dates = get_all_dates(
      $startDate,
      'yearly',
      2,        // every 2 years
      '',
      '',
      '',
      '',
      '',
      '',
      4
    );

    $this->assertCount(4, $dates);
    $this->assertEquals('20240615', date('Ymd', $dates[0]));
    $this->assertEquals('20260615', date('Ymd', $dates[1]));
    $this->assertEquals('20280615', date('Ymd', $dates[2]));
    $this->assertEquals('20300615', date('Ymd', $dates[3]));
  }

  // =========================================================================
  // EXCEPTION (EXDATE) TESTS
  // =========================================================================

  /**
   * Test daily with exception dates
   */
  public function test_daily_with_exceptions()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      10,
      null,
      'MO',
      ['20240103', '20240105']  // Exclude Jan 3 and Jan 5
    );

    $this->assertCount(8, $dates); // 10 - 2 exceptions
    $this->assertNotContains('20240103', array_map(function($d) {
      return date('Ymd', $d);
    }, $dates));
    $this->assertNotContains('20240105', array_map(function($d) {
      return date('Ymd', $d);
    }, $dates));
  }

  /**
   * Test weekly with exception - skip specific occurrence
   */
  public function test_weekly_with_exception()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      5,
      null,
      'MO',
      ['20240115']  // Skip the 3rd Monday
    );

    $this->assertCount(4, $dates); // 5 - 1 exception
    $this->assertNotContains('20240115', array_map(function($d) {
      return date('Ymd', $d);
    }, $dates));
  }

  /**
   * Test monthly with multiple exceptions
   */
  public function test_monthly_with_multiple_exceptions()
  {
    $startDate = mktime(10, 0, 0, 1, 15, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      6,
      null,
      'MO',
      ['20240215', '20240415']  // Skip Feb and Apr
    );

    $this->assertCount(4, $dates);
    $dateStrings = array_map(function($d) { return date('Ymd', $d); }, $dates);
    $this->assertNotContains('20240215', $dateStrings);
    $this->assertNotContains('20240415', $dateStrings);
    $this->assertContains('20240115', $dateStrings);
    $this->assertContains('20240315', $dateStrings);
  }

  // =========================================================================
  // INCLUSION (RDATE) TESTS
  // =========================================================================

  /**
   * Test weekly with inclusion dates (extra dates added)
   */
  public function test_weekly_with_inclusions()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      3,
      null,
      'MO',
      '',                        // No exceptions
      ['20240110', '20240117']   // Add extra dates (Wed and Wed)
    );

    $this->assertCount(5, $dates); // 3 weekly + 2 inclusions
    $dateStrings = array_map(function($d) { return date('Ymd', $d); }, $dates);
    $this->assertContains('20240110', $dateStrings);
    $this->assertContains('20240117', $dateStrings);
  }

  /**
   * Test with both exceptions and inclusions
   */
  public function test_with_exceptions_and_inclusions()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      5,
      null,
      'MO',
      ['20240103'],              // Skip Jan 3
      ['20240110']               // Add Jan 10
    );

    $this->assertCount(5, $dates); // 5 - 1 + 1 = 5
    $dateStrings = array_map(function($d) { return date('Ymd', $d); }, $dates);
    $this->assertNotContains('20240103', $dateStrings);
    $this->assertContains('20240110', $dateStrings);
  }

  // =========================================================================
  // TERMINATION TESTS
  // =========================================================================

  /**
   * Test UNTIL termination
   */
  public function test_until_termination()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);
    $untilDate = mktime(23, 59, 59, 1, 10, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      999,      // High count (effectively unlimited)
      $untilDate
    );

    $this->assertCount(10, $dates);
    $this->assertEquals('20240101', date('Ymd', $dates[0]));
    $this->assertEquals('20240110', date('Ymd', $dates[9]));
  }

  /**
   * Test COUNT termination
   */
  public function test_count_termination()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      7
    );

    $this->assertCount(7, $dates);
  }

  /**
   * Test COUNT=1 (single occurrence)
   */
  public function test_count_one()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      1
    );

    $this->assertCount(1, $dates);
    $this->assertEquals('20240101', date('Ymd', $dates[0]));
  }

  // =========================================================================
  // DST TRANSITION TESTS
  // =========================================================================

  /**
   * Test daily repetition crossing DST spring forward
   */
  public function test_daily_across_dst_spring_forward()
  {
    date_default_timezone_set("America/New_York");

    // March 10, 2024 is when DST starts (spring forward)
    $startDate = mktime(10, 0, 0, 3, 8, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      5
    );

    $this->assertCount(5, $dates);
    // Verify dates are correct despite DST
    $this->assertEquals('20240308', date('Ymd', $dates[0]));
    $this->assertEquals('20240309', date('Ymd', $dates[1]));
    $this->assertEquals('20240310', date('Ymd', $dates[2])); // DST starts
    $this->assertEquals('20240311', date('Ymd', $dates[3]));
    $this->assertEquals('20240312', date('Ymd', $dates[4]));

    // Verify hour is preserved
    foreach ($dates as $date) {
      $this->assertEquals('10', date('H', $date),
        "Hour should be 10 for " . date('Y-m-d', $date));
    }
  }

  /**
   * Test daily repetition crossing DST fall back
   */
  public function test_daily_across_dst_fall_back()
  {
    date_default_timezone_set("America/New_York");

    // November 3, 2024 is when DST ends (fall back)
    $startDate = mktime(10, 0, 0, 11, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      5
    );

    $this->assertCount(5, $dates);
    $this->assertEquals('20241101', date('Ymd', $dates[0]));
    $this->assertEquals('20241102', date('Ymd', $dates[1]));
    $this->assertEquals('20241103', date('Ymd', $dates[2])); // DST ends
    $this->assertEquals('20241104', date('Ymd', $dates[3]));
    $this->assertEquals('20241105', date('Ymd', $dates[4]));

    // Verify hour is preserved
    foreach ($dates as $date) {
      $this->assertEquals('10', date('H', $date),
        "Hour should be 10 for " . date('Y-m-d', $date));
    }
  }

  /**
   * Test weekly repetition across DST transition
   */
  public function test_weekly_across_dst()
  {
    date_default_timezone_set("America/New_York");

    $startDate = mktime(9, 0, 0, 3, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      4
    );

    $this->assertCount(4, $dates);
    $this->assertEquals('20240301', date('Ymd', $dates[0]));
    $this->assertEquals('20240308', date('Ymd', $dates[1]));
    $this->assertEquals('20240315', date('Ymd', $dates[2])); // After DST
    $this->assertEquals('20240322', date('Ymd', $dates[3]));

    // Verify hour preserved
    foreach ($dates as $date) {
      $this->assertEquals('09', date('H', $date));
    }
  }

  // =========================================================================
  // BOUNDARY AND EDGE CASE TESTS
  // =========================================================================

  /**
   * Test year boundary crossing
   */
  public function test_daily_year_boundary()
  {
    $startDate = mktime(10, 0, 0, 12, 29, 2023);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      5
    );

    $this->assertCount(5, $dates);
    $this->assertEquals('20231229', date('Ymd', $dates[0]));
    $this->assertEquals('20231230', date('Ymd', $dates[1]));
    $this->assertEquals('20231231', date('Ymd', $dates[2]));
    $this->assertEquals('20240101', date('Ymd', $dates[3])); // New year
    $this->assertEquals('20240102', date('Ymd', $dates[4]));
  }

  /**
   * Test month boundary with 31st
   */
  public function test_monthly_31st_edge_case()
  {
    // Event on 31st - some months don't have 31 days
    $startDate = mktime(10, 0, 0, 1, 31, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      6
    );

    // Note: PHP's mktime normalizes dates, so Feb 31 becomes Mar 2/3
    // The behavior here depends on implementation
    $this->assertGreaterThanOrEqual(1, count($dates));
  }

  /**
   * Test February edge cases in non-leap year
   */
  public function test_monthly_feb_non_leap_year()
  {
    // 2025 is not a leap year
    $startDate = mktime(10, 0, 0, 1, 29, 2025);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      3
    );

    // Check how Feb 29 is handled in non-leap year
    $this->assertGreaterThanOrEqual(1, count($dates));
    $this->assertEquals('20250129', date('Ymd', $dates[0]));
  }

  /**
   * Test empty/null parameters don't crash
   */
  public function test_empty_parameters()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      null,     // ByMonth
      null,     // ByWeekNo
      null,     // ByYearDay
      null,     // ByMonthDay
      null,     // ByDay
      null,     // BySetPos
      3
    );

    $this->assertCount(3, $dates);
  }

  // =========================================================================
  // GET_BYDAY TESTS
  // =========================================================================

  /**
   * Test get_byday with simple weekday names
   */
  public function test_get_byday_simple_weekdays()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024); // First day of Jan 2024
    $date = $cdate - 1; // Just before

    $result = get_byday(['MO', 'FR'], $cdate, 'month', $date);

    // Should return all Mondays and Fridays in January 2024
    $this->assertNotEmpty($result);

    foreach ($result as $timestamp) {
      $dow = date('w', $timestamp);
      $this->assertTrue(
        $dow == 1 || $dow == 5,
        "Day should be Monday (1) or Friday (5), got $dow"
      );
    }
  }

  /**
   * Test get_byday with positive offset (2nd Monday)
   */
  public function test_get_byday_second_monday()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;

    $result = get_byday(['2MO'], $cdate, 'month', $date);

    $this->assertCount(1, $result);
    $this->assertEquals('20240108', date('Ymd', $result[0])); // Jan 8, 2024
  }

  /**
   * Test get_byday with negative offset (last Monday)
   */
  public function test_get_byday_last_monday()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;

    $result = get_byday(['-1MO'], $cdate, 'month', $date);

    $this->assertCount(1, $result);
    $this->assertEquals('20240129', date('Ymd', $result[0])); // Jan 29, 2024
  }

  /**
   * Test get_byday with second-to-last Friday
   */
  public function test_get_byday_second_to_last()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;

    $result = get_byday(['-2FR'], $cdate, 'month', $date);

    $this->assertCount(1, $result);
    $this->assertEquals('20240119', date('Ymd', $result[0])); // Jan 19, 2024
  }

  /**
   * Test get_byday for daily type
   */
  public function test_get_byday_daily_type()
  {
    // Jan 1, 2024 is a Monday
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;

    // Should match - it's a Monday
    $result = get_byday(['MO'], $cdate, 'daily', $date);
    $this->assertCount(1, $result);

    // Should not match - it's Monday, not Tuesday
    $result = get_byday(['TU'], $cdate, 'daily', $date);
    $this->assertEmpty($result);
  }

  /**
   * Test get_byday for year type
   */
  public function test_get_byday_year_type()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;

    // Get all Mondays in 2024
    $result = get_byday(['MO'], $cdate, 'year', $date);

    // 2024 has 52 Mondays
    $this->assertGreaterThanOrEqual(52, count($result));

    // Verify they're all Mondays
    foreach ($result as $timestamp) {
      $this->assertEquals(1, date('w', $timestamp));
    }
  }

  // =========================================================================
  // GET_BYMONTHDAY TESTS
  // =========================================================================

  /**
   * Test get_bymonthday with positive day
   */
  public function test_get_bymonthday_positive()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;
    $realend = mktime(0, 0, 0, 12, 31, 2024);

    $result = get_bymonthday(['15'], $cdate, $date, $realend);

    $this->assertCount(1, $result);
    $this->assertEquals('15', date('d', $result[0]));
    $this->assertEquals('01', date('m', $result[0]));
  }

  /**
   * Test get_bymonthday with multiple days
   */
  public function test_get_bymonthday_multiple()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;
    $realend = mktime(0, 0, 0, 12, 31, 2024);

    $result = get_bymonthday(['1', '15'], $cdate, $date, $realend);

    $this->assertCount(2, $result);
    $days = array_map(function($t) { return (int)date('d', $t); }, $result);
    sort($days);
    $this->assertEquals([1, 15], $days);
  }

  /**
   * Test get_bymonthday with negative day (last day)
   */
  public function test_get_bymonthday_negative()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;
    $realend = mktime(0, 0, 0, 12, 31, 2024);

    $result = get_bymonthday(['-1'], $cdate, $date, $realend);

    $this->assertCount(1, $result);
    $this->assertEquals('31', date('d', $result[0])); // Jan has 31 days
  }

  /**
   * Test get_bymonthday negative in February
   */
  public function test_get_bymonthday_negative_february()
  {
    // Feb 2024 (leap year)
    $cdate = mktime(10, 0, 0, 2, 1, 2024);
    $date = $cdate - 1;
    $realend = mktime(0, 0, 0, 12, 31, 2024);

    $result = get_bymonthday(['-1'], $cdate, $date, $realend);

    $this->assertCount(1, $result);
    $this->assertEquals('29', date('d', $result[0])); // Feb 2024 has 29 days
  }

  /**
   * Test get_bymonthday with -2 (second to last)
   */
  public function test_get_bymonthday_second_to_last()
  {
    $cdate = mktime(10, 0, 0, 1, 1, 2024);
    $date = $cdate - 1;
    $realend = mktime(0, 0, 0, 12, 31, 2024);

    $result = get_bymonthday(['-2'], $cdate, $date, $realend);

    $this->assertCount(1, $result);
    $this->assertEquals('30', date('d', $result[0])); // 31 - 2 + 1 = 30
  }

  // =========================================================================
  // COMPLEX RRULE COMBINATION TESTS
  // =========================================================================

  /**
   * Test BYDAY and BYMONTHDAY together (intersection)
   */
  public function test_monthly_byday_and_bymonthday()
  {
    // This should find days that are BOTH in BYDAY AND BYMONTHDAY
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '1,8,15,22,29',  // 1st, 8th, 15th, 22nd, 29th
      'MO',             // Mondays only
      '',
      6
    );

    // Only dates that are BOTH a Monday AND one of those day numbers
    foreach ($dates as $date) {
      $this->assertEquals(1, date('w', $date), "Should be Monday");
      $this->assertContains(
        (int)date('d', $date),
        [1, 8, 15, 22, 29],
        "Should be 1st, 8th, 15th, 22nd, or 29th"
      );
    }
  }

  /**
   * Test yearly with BYMONTH and BYDAY (specific holiday-like pattern)
   */
  public function test_yearly_bymonth_byday_combination()
  {
    // Labor Day: First Monday of September
    $startDate = mktime(10, 0, 0, 9, 2, 2024); // Labor Day 2024

    $dates = get_all_dates(
      $startDate,
      'yearly',
      1,
      '9',      // September
      '',
      '',
      '',
      '1MO',    // First Monday
      '',
      5
    );

    $this->assertCount(5, $dates);

    // Verify all are first Mondays of September
    foreach ($dates as $date) {
      $this->assertEquals(1, date('w', $date), "Should be Monday");
      $this->assertEquals(9, date('n', $date), "Should be September");
      $this->assertLessThanOrEqual(7, (int)date('d', $date), "Should be in first week");
    }
  }

  // =========================================================================
  // PERFORMANCE/JUMP OPTIMIZATION TESTS
  // =========================================================================

  /**
   * Test that jump parameter works for daily events
   *
   * When jump is provided, the function:
   * 1. Skips forward to the jump date for efficiency
   * 2. Returns dates as YYYYMMDD strings instead of timestamps
   */
  public function test_daily_with_jump()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2020);
    $jumpDate = mktime(0, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      5,
      null,
      'MO',
      '',
      '',
      $jumpDate
    );

    // With jump parameter, dates are returned as YYYYMMDD strings
    $this->assertCount(5, $dates);
    $this->assertIsString($dates[0]);
    // The first date should be at or after the start date (2020-01-01)
    // Jump optimizes iteration but doesn't change the sequence
    $this->assertEquals('20200101', $dates[0]);
  }

  // =========================================================================
  // REGRESSION TESTS
  // =========================================================================

  /**
   * Regression: Ensure interval=0 doesn't cause infinite loop
   * (should be treated as interval=1)
   */
  public function test_interval_zero_safety()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    // This should not hang - use a timeout or count limit
    $dates = get_all_dates(
      $startDate,
      'daily',
      0,        // Invalid interval - should not cause issues
      '',
      '',
      '',
      '',
      '',
      '',
      3
    );

    // Just verify it returns without hanging
    // The actual behavior with interval=0 may vary
    $this->assertIsArray($dates);
  }

  /**
   * Regression: Large count should still work
   */
  public function test_large_count()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      100
    );

    $this->assertCount(100, $dates);
  }

  // =========================================================================
  // TIMES_OVERLAP TESTS
  // =========================================================================

  /**
   * Test times_overlap with no overlap
   */
  public function test_times_overlap_no_overlap()
  {
    // 9AM-10AM and 11AM-12PM - no overlap
    $this->assertFalse(times_overlap(90000, 60, 110000, 60));

    // 9AM-10AM and 10:30AM-11:30AM - no overlap
    $this->assertFalse(times_overlap(90000, 60, 103000, 60));
  }

  /**
   * Test times_overlap with adjacent times (should NOT overlap)
   */
  public function test_times_overlap_adjacent()
  {
    // 9AM-10AM and 10AM-11AM - adjacent, should NOT overlap
    // (duration is reduced by 1 minute internally)
    $this->assertFalse(times_overlap(90000, 60, 100000, 60));

    // 2PM-3PM and 3PM-4PM - adjacent
    $this->assertFalse(times_overlap(140000, 60, 150000, 60));
  }

  /**
   * Test times_overlap with actual overlap
   */
  public function test_times_overlap_yes()
  {
    // 9AM-10AM and 9:30AM-10:30AM - overlap
    $this->assertTrue(times_overlap(90000, 60, 93000, 60));

    // 9AM-11AM and 10AM-12PM - overlap
    $this->assertTrue(times_overlap(90000, 120, 100000, 120));

    // Event completely inside another
    // 9AM-12PM and 10AM-11AM
    $this->assertTrue(times_overlap(90000, 180, 100000, 60));
  }

  /**
   * Test times_overlap with same start time
   */
  public function test_times_overlap_same_start()
  {
    // Both start at 9AM
    $this->assertTrue(times_overlap(90000, 60, 90000, 30));
    $this->assertTrue(times_overlap(90000, 30, 90000, 60));
  }

  /**
   * Test times_overlap with zero duration
   */
  public function test_times_overlap_zero_duration()
  {
    // Zero duration events
    $this->assertFalse(times_overlap(90000, 0, 90000, 0));
    $this->assertFalse(times_overlap(90000, 60, 100000, 0));
  }

  /**
   * Test times_overlap edge case: exact boundary
   *
   * After -1 minute adjustment:
   * - time1: 9:00-9:59 (540-599)
   * - time2: 9:59-10:58 (599-658)
   * Since 599 >= 599, no overlap (boundary is exclusive)
   */
  public function test_times_overlap_exact_boundary()
  {
    // 9AM-10AM and 9:59AM-10:59AM - exactly touching, no overlap
    $this->assertFalse(times_overlap(90000, 60, 95900, 60));

    // 9AM-10AM and 9:58AM-10:58AM - one minute overlap
    $this->assertTrue(times_overlap(90000, 60, 95800, 60));
  }

  // =========================================================================
  // TIME_TO_MINUTES TESTS
  // =========================================================================

  /**
   * Test time_to_minutes conversion
   */
  public function test_time_to_minutes()
  {
    $this->assertEquals(0, time_to_minutes(0));           // Midnight
    $this->assertEquals(60, time_to_minutes(10000));      // 1:00 AM
    $this->assertEquals(540, time_to_minutes(90000));     // 9:00 AM
    $this->assertEquals(570, time_to_minutes(93000));     // 9:30 AM
    $this->assertEquals(720, time_to_minutes(120000));    // 12:00 PM
    $this->assertEquals(1020, time_to_minutes(170000));   // 5:00 PM
    $this->assertEquals(1439, time_to_minutes(235900));   // 11:59 PM
  }

  // =========================================================================
  // DATE_TO_EPOCH TESTS
  // =========================================================================

  /**
   * Test date_to_epoch with YYYYMMDD format
   */
  public function test_date_to_epoch_yyyymmdd()
  {
    date_default_timezone_set("UTC");

    $epoch = date_to_epoch('20240101', true);
    $this->assertEquals('2024', date('Y', $epoch));
    $this->assertEquals('01', date('m', $epoch));
    $this->assertEquals('01', date('d', $epoch));
    $this->assertEquals('00', date('H', $epoch));
  }

  /**
   * Test date_to_epoch with YYYYMMDDHHIISS format
   */
  public function test_date_to_epoch_with_time()
  {
    date_default_timezone_set("UTC");

    $epoch = date_to_epoch('20240115143000', true);
    $this->assertEquals('2024', date('Y', $epoch));
    $this->assertEquals('01', date('m', $epoch));
    $this->assertEquals('15', date('d', $epoch));
    $this->assertEquals('14', date('H', $epoch));
    $this->assertEquals('30', date('i', $epoch));
  }

  /**
   * Test date_to_epoch with single-digit hour (13 char format)
   */
  public function test_date_to_epoch_single_digit_hour()
  {
    date_default_timezone_set("UTC");

    // Format: YYYYMMDD + H + MMSS (13 chars total)
    $epoch = date_to_epoch('2024011593000', true);
    $this->assertEquals('09', date('H', $epoch));
    $this->assertEquals('30', date('i', $epoch));
  }

  /**
   * Test date_to_epoch with empty/zero input
   */
  public function test_date_to_epoch_empty()
  {
    $this->assertEquals(0, date_to_epoch(0));
    $this->assertEquals(0, date_to_epoch(''));
  }

  /**
   * Test date_to_epoch with local time (not GMT)
   */
  public function test_date_to_epoch_local()
  {
    date_default_timezone_set("America/New_York");

    $epochGmt = date_to_epoch('20240101120000', true);
    $epochLocal = date_to_epoch('20240101120000', false);

    // They should differ by the timezone offset
    $this->assertNotEquals($epochGmt, $epochLocal);
  }

  // =========================================================================
  // ADDITIONAL EDGE CASE TESTS
  // =========================================================================

  /**
   * Test monthly repetition spanning year boundary
   */
  public function test_monthly_year_boundary()
  {
    $startDate = mktime(10, 0, 0, 11, 15, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      4
    );

    $this->assertCount(4, $dates);
    $this->assertEquals('20241115', date('Ymd', $dates[0]));
    $this->assertEquals('20241215', date('Ymd', $dates[1]));
    $this->assertEquals('20250115', date('Ymd', $dates[2])); // Year change
    $this->assertEquals('20250215', date('Ymd', $dates[3]));
  }

  /**
   * Test weekly with weekend days (SA, SU)
   */
  public function test_weekly_weekend()
  {
    // Jan 6, 2024 is a Saturday
    $startDate = mktime(10, 0, 0, 1, 6, 2024);

    $dates = get_all_dates(
      $startDate,
      'weekly',
      1,
      '',
      '',
      '',
      '',
      'SA,SU',
      '',
      6
    );

    $this->assertCount(6, $dates);
    // Verify all are weekends
    foreach ($dates as $date) {
      $dow = date('w', $date);
      $this->assertTrue($dow == 0 || $dow == 6, "Should be weekend: " . date('Y-m-d D', $date));
    }
  }

  /**
   * Test that exception on non-existent date doesn't crash
   */
  public function test_exception_nonexistent_date()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    // Exception for a date that won't be in the series
    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      5,
      null,
      'MO',
      ['20241231']  // Far future exception
    );

    $this->assertCount(5, $dates); // Should have all 5 dates
  }

  /**
   * Test yearly with multiple BYMONTH values
   *
   * BEHAVIOR NOTE: With BYMONTH=1,7 starting on Jan 15, the function only
   * returns July dates. This appears to be because:
   * 1. The yearly loop iterates year by year (not month by month)
   * 2. For each year, it generates dates for all BYMONTH months
   * 3. But the January date equals the start date and is filtered out
   *
   * This may be intentional or a bug - documenting current behavior.
   */
  public function test_yearly_multiple_bymonth()
  {
    $startDate = mktime(10, 0, 0, 1, 15, 2024);

    $dates = get_all_dates(
      $startDate,
      'yearly',
      1,
      '1,7',    // January and July
      '',
      '',
      '',
      '',
      '',
      4
    );

    // Current behavior: only July dates are returned
    $this->assertCount(4, $dates);
    foreach ($dates as $d) {
      $this->assertEquals(7, date('n', $d), "Expected July, got " . date('F', $d));
    }
  }

  /**
   * Test that UNTIL before start date returns empty
   */
  public function test_until_before_start()
  {
    $startDate = mktime(10, 0, 0, 6, 1, 2024);
    $untilDate = mktime(23, 59, 59, 1, 1, 2024); // Before start

    $dates = get_all_dates(
      $startDate,
      'daily',
      1,
      '',
      '',
      '',
      '',
      '',
      '',
      999,
      $untilDate
    );

    $this->assertCount(0, $dates);
  }

  /**
   * Test first Monday in month (1MO)
   */
  public function test_monthly_first_monday()
  {
    // Dec 2, 2024 is first Monday
    $startDate = mktime(10, 0, 0, 12, 2, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '1MO',
      '',
      3
    );

    // Due to > comparison, Dec 2024 is skipped
    $this->assertCount(3, $dates);
    // All should be Mondays and in first week of month
    foreach ($dates as $date) {
      $this->assertEquals(1, date('w', $date)); // Monday
      $this->assertLessThanOrEqual(7, (int)date('d', $date)); // First week
    }
  }

  // =========================================================================
  // DOCUMENTED BEHAVIOR TESTS (for refactoring reference)
  // =========================================================================

  /**
   * BEHAVIOR DOCUMENTATION: Start date excluded from BYDAY/BYMONTHDAY matching
   *
   * This test documents that get_byday() and get_bymonthday() use ">" instead
   * of ">=" when comparing against the start date. This means if you create
   * an event starting on the 2nd Tuesday of January with RRULE BYDAY=2TU,
   * the January occurrence is skipped.
   *
   * This may or may not be intentional RFC 5545 behavior. During refactoring,
   * verify whether this is correct iCalendar semantics.
   */
  public function test_documented_behavior_start_date_excluded()
  {
    // Create event on Jan 9, 2024 (2nd Tuesday) with BYDAY=2TU
    $startDate = mktime(10, 0, 0, 1, 9, 2024);

    $dates = get_all_dates(
      $startDate,
      'monthly',
      1,
      '',
      '',
      '',
      '',
      '2TU',
      '',
      3
    );

    // CURRENT BEHAVIOR: January is skipped, first result is February
    // If this is wrong, change the comparison in get_byday() from > to >=
    $this->assertEquals('20240213', date('Ymd', $dates[0])); // Feb, not Jan
  }

  /**
   * BEHAVIOR DOCUMENTATION: Interval=0 behavior
   *
   * Tests what happens with interval=0. This should probably be treated
   * as interval=1 to avoid infinite loops.
   */
  public function test_documented_behavior_interval_zero()
  {
    $startDate = mktime(10, 0, 0, 1, 1, 2024);

    // With interval=0, add_dstfree_time() with interval 0 may cause issues
    $dates = get_all_dates(
      $startDate,
      'daily',
      0,
      '',
      '',
      '',
      '',
      '',
      '',
      3
    );

    // Document current behavior - this test will show if it hangs or returns something
    $this->assertIsArray($dates);
    // If this test times out, interval=0 causes an infinite loop
  }

  // ===========================================================================
  // COMPREHENSIVE CONCERNS DOCUMENTATION FOR REFACTORING
  // ===========================================================================

  /**
   * @test
   * @group documentation
   *
   * This "test" documents all concerns found during test development.
   * These should be reviewed and addressed during the refactoring of functions.php.
   *
   * ============================================================================
   * CRITICAL CONCERNS (May cause incorrect behavior)
   * ============================================================================
   *
   * 1. START DATE EXCLUSION BUG (get_byday, get_bymonthday)
   *    Location: includes/functions.php lines 2394, 2402, 2406, 2412, 2447
   *    Issue: Uses ">" instead of ">=" when comparing dates
   *    Impact: Events with BYDAY or BYMONTHDAY rules skip the first occurrence
   *            if it matches the start date. For example:
   *            - Event starts Jan 9 (2nd Tuesday) with BYDAY=2TU
   *            - Expected: First occurrence is Jan 9
   *            - Actual: First occurrence is Feb 13 (January skipped)
   *    Fix: Change comparisons from "$byxxxDay > $date" to "$byxxxDay >= $date"
   *    Tests: test_monthly_second_tuesday, test_monthly_last_friday, etc.
   *
   * 2. YEARLY BYMONTH ONLY RETURNS ONE MONTH
   *    Location: includes/functions.php lines 2230-2250
   *    Issue: When yearly event has BYMONTH=1,7, it only returns July dates
   *    Impact: Multi-month yearly patterns (like semi-annual events) don't work
   *    Root cause: Likely related to the start date exclusion bug above
   *    Tests: test_yearly_multiple_bymonth
   *
   * 3. INTERVAL=0 NOT VALIDATED
   *    Location: includes/functions.php throughout get_all_dates()
   *    Issue: If interval=0 is passed, add_dstfree_time() gets 0 multiplier
   *    Impact: Could potentially cause issues (though currently seems to work)
   *    Fix: Validate interval >= 1 at function start
   *    Tests: test_documented_behavior_interval_zero
   *
   * ============================================================================
   * MODERATE CONCERNS (May cause edge case issues)
   * ============================================================================
   *
   * 4. DST HANDLING IN WEEKLY EVENTS
   *    Location: includes/functions.php line 2124
   *    Issue: Weekly with BYDAY uses simple $cdate + ($i * 86400) calculation
   *    Impact: May cause 1-hour drift during DST transitions for some edge cases
   *    Note: The function does call add_dstfree_time() for main iteration,
   *          but inner BYDAY loop uses raw arithmetic
   *
   * 5. MONTH BOUNDARY HANDLING
   *    Location: includes/functions.php lines 2196-2198
   *    Issue: Monthly events on 31st when next month has 30 days
   *    Impact: PHP's mktime normalizes dates, so behavior may be unexpected
   *            (e.g., Jan 31 + 1 month = Mar 2 or 3, not "Feb 31")
   *    Note: This is standard PHP behavior, but may surprise users
   *
   * 6. LEAP YEAR FEBRUARY 29 HANDLING
   *    Location: get_all_dates() yearly logic
   *    Issue: Events on Feb 29 in leap year - what happens in non-leap years?
   *    Impact: Yearly event on Feb 29 may skip non-leap years entirely
   *    Tests: test_yearly_leap_day (behavior observed but not fully tested)
   *
   * ============================================================================
   * CODE QUALITY CONCERNS (Should be improved during refactoring)
   * ============================================================================
   *
   * 7. FUNCTION LENGTH
   *    Issue: get_all_dates() is ~300 lines with deeply nested logic
   *    Recommendation: Extract daily/weekly/monthly/yearly handling into
   *                    separate functions for testability and readability
   *
   * 8. GLOBAL VARIABLE DEPENDENCIES
   *    Issue: Functions depend on globals: $byday_names, $byday_values,
   *           $CONFLICT_REPEAT_MONTHS, $jumpdate, $max_until
   *    Recommendation: Pass as parameters or use dependency injection
   *
   * 9. INCONSISTENT DATE FORMATS
   *    Issue: Some functions expect timestamps, others expect YYYYMMDD strings
   *    Impact: Easy to pass wrong format, causing silent failures
   *    Recommendation: Standardize on one format or use DateTime objects
   *
   * 10. MAGIC NUMBERS
   *     Issue: 86400, 604800, etc. used throughout without constants
   *     Recommendation: Define SECONDS_PER_DAY, SECONDS_PER_WEEK constants
   *
   * 11. RETURN VALUE INCONSISTENCY
   *     Issue: get_all_dates() returns timestamps normally, but YYYYMMDD
   *            strings when $jump parameter is provided
   *     Impact: Caller must know about this behavior
   *     Recommendation: Always return same format; let caller convert
   *
   * ============================================================================
   * POTENTIAL RFC 5545 (iCalendar) COMPLIANCE ISSUES
   * ============================================================================
   *
   * 12. BYDAY WITH NUMERIC PREFIX
   *     Issue: Handling of BYDAY=1MO (first Monday) vs BYDAY=MO (every Monday)
   *     Question: Does current implementation match RFC 5545 semantics?
   *     Tests: test_monthly_second_tuesday, test_monthly_last_friday
   *
   * 13. BYSETPOS IMPLEMENTATION
   *     Issue: BYSETPOS should filter results from other BYxxx rules
   *     Question: Is the interaction between BYSETPOS and other rules correct?
   *     Tests: test_monthly_last_weekday_with_bysetpos
   *
   * 14. WKST (Week Start) HANDLING
   *     Issue: WKST affects week calculations for BYWEEKNO
   *     Question: Is WKST properly applied in all relevant calculations?
   *     Tests: test_weekly_with_wkst_sunday
   *
   * ============================================================================
   * RECOMMENDED REFACTORING APPROACH
   * ============================================================================
   *
   * Phase 1: Fix Critical Bugs
   *   - Fix start date exclusion (>= instead of >)
   *   - Add interval validation
   *   - Verify yearly BYMONTH behavior
   *
   * Phase 2: Extract Functions
   *   - get_daily_dates($start, $interval, $filters, $count, $until)
   *   - get_weekly_dates($start, $interval, $byday, $wkst, $count, $until)
   *   - get_monthly_dates($start, $interval, $byday, $bymonthday, $bysetpos, ...)
   *   - get_yearly_dates($start, $interval, $bymonth, $byday, ...)
   *
   * Phase 3: Standardize
   *   - Use DateTime objects throughout
   *   - Define constants for magic numbers
   *   - Remove global variable dependencies
   *
   * Phase 4: Add Integration Tests
   *   - Test against known iCalendar RRULE examples
   *   - Compare with PHP's DatePeriod class for simple cases
   *   - Test import/export round-trip with ical files
   *
   * ============================================================================
   * ADDITIONAL CONCERNS FROM CODE REVIEW (2026-02-05)
   * ============================================================================
   *
   * VERIFICATION OF EXISTING CONCERNS:
   * - Concerns #1, #2, #3, #4, #6, #7, #8, #9, #10, #11 are ACCURATE and
   *   verified by code inspection in includes/functions.php
   *
   * ============================================================================
   * NEW CONCERNS IDENTIFIED
   * ============================================================================
   *
   * 15. BYMONTHDAY RETURN FORMAT INCONSISTENCY
   *     Location: includes/functions.php line 2447
   *     Issue: get_bymonthday() docblock says it returns dates in YYYYMMDD format,
   *            but actually returns Unix timestamps (see line 2448: $ret[] = $byxxxDay)
   *     Impact: Documentation is misleading, though callers may handle both formats
   *     Tests: None specifically test the return format
   *
   * 16. DAILY TYPE BYDAY HANDLING INCONSISTENT WITH RFC 5545
   *     Location: includes/functions.php lines 2405-2407
   *     Issue: For daily events, BYDAY only matches the current day ($cdate),
   *            not all matching days in the repetition period
   *     Impact: Daily event with BYDAY=MO,WE,FR only gets one day per interval,
   *            not all matching weekdays
   *     RFC 5545: BYDAY in daily context should probably be a no-op or filter
   *
   * 17. MISSING BYWEEKNO IMPLEMENTATION
   *     Location: includes/functions.php get_all_dates()
   *     Issue: ByWeekNo parameter is accepted but never used in the function
   *     Impact: Week number filtering (e.g., "every year in week 20") doesn't work
   *     RFC 5545: BYWEEKNO is used with yearly frequency to specify week numbers
   *
   * 18. YEAR TYPE WITHOUT BYMONTH MAY NOT HANDLE LEAP DAY CORRECTLY
   *     Location: includes/functions.php lines 2208-2217
   *     Issue: Yearly event on Feb 29 - no explicit handling for non-leap years
   *     Expected: Should probably skip non-leap years or use Feb 28
   *     Actual: Likely returns Mar 1 due to mktime normalization
   *
   * 19. WEEKLY BYDAY LOOP USES $wkst BUT DOESN'T ADJUST CALCULATION
   *     Location: includes/functions.php lines 2124-2136
   *     Issue: Weekly BYDAY uses raw day arithmetic without considering week start
   *     Impact: Events with non-standard week start may have wrong occurrences
   *
   * 20. COUNT LIMITS MAY BE INCONSISTENT WITH EXDATE/RDATE
   *     Location: includes/functions.php lines 2298-2305, 2318-2323
   *     Issue: COUNT is checked before applying EXDATE/RDATE, so final count may
   *            be less than requested if exceptions are removed
   *     RFC 5545: COUNT specifies the number of occurrences, not iterations
   *
   * 21. INTERACTION BETWEEN BYDAY AND BYMONTHDAY NOT FULLY RFC-COMPLIANT
   *     Location: includes/functions.php lines 2164-2174
   *     Issue: When both BYDAY and BYMONTHDAY are specified, the code unions them
   *            (any matching day works), but RFC 5545 says they should intersect
   *            (only days matching both rules)
   *     Actual: Code appears to intersect via $byday AND $bymonthday checks
   *     Verification needed: Confirm current behavior matches RFC semantics
   *
   * 22. MISSING BYHOUR, BYMINUTE, BYSECOND SUPPORT
   *     Location: includes/functions.php get_all_dates() signature
   *     Issue: Function doesn't accept BYHOUR, BYMINUTE, BYSECOND parameters
   *     Impact: Cannot create rules like "every hour on the hour" or "every 15 min"
   *     RFC 5545: These are valid RRULE components for advanced scheduling
   *
   * 23. DATE STRING VS TIMESTAMP CONFUSION IN JUMP PARAMETER
   *     Location: includes/functions.php lines 2039-2074
   *     Issue: When $jump is provided, dates are returned as YYYYMMDD strings
   *            instead of timestamps, but the array may mix formats
   *     Impact: Hard to use consistently; callers must check is_string() or is_int()
   *
   * 24. RECURSION DEPTH RISK IN UNTIL CALCULATION
   *     Location: includes/functions.php lines 2286-2295
   *     Issue: No maximum date limit for UNTIL calculations
   *     Impact: With large intervals or distant UNTIL, could generate many dates
   *
   * 25. TIMEZONE HANDLING INCONSISTENT
   *     Location: Throughout get_all_dates()
   *     Issue: Uses date_default_timezone_get() but doesn't explicitly set it
   *     Impact: Test behavior may differ from production depending on timezone
   *
   * ============================================================================
   * RECOMMENDATIONS FOR TEST IMPROVEMENT
   * ============================================================================
   *
   * - Add tests for BYWEEKNO to verify it works or document that it doesn't
   * - Add tests for leap year Feb 29 behavior in yearly events
   * - Add tests confirming COUNT behavior with EXDATE (does removing exceptions
   *   reduce the total count or does the count include exceptions?)
   * - Add tests for edge cases like monthly events on 31st in months with 30 days
   * - Add tests comparing behavior against PHP's DatePeriod for simple rules
   *
   * ============================================================================
   * DISAGREEMENT WITH EXISTING CONCERNS
   * ============================================================================
   *
   * None of the existing concerns appear incorrect based on code review.
   * However, the severity of #5 (month boundary handling) may be overstated -
   * PHP's mktime() normalization is actually well-defined behavior (dates wrap
   * to the next month), though it may surprise users expecting "last day of month"
   * semantics. This is more of a UX issue than a bug.
   */
  public function test_concerns_documentation()
  {
    // This test serves as documentation - it always passes
    $this->assertTrue(true, 'See docblock above for comprehensive concerns list');
  }
}
