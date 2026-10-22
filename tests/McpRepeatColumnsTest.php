<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Unit tests for mcp_rrule_to_repeat_columns(): maps validated RRULE parts to
 * webcal_entry_repeats column values, matching how xcal.php stores them
 * (see docs/SCHEMA_AUDIT.md in scheduling-agent). Pure function.
 *
 * Input is the normalized parts array from mcp_validate_rrule().
 */
final class McpRepeatColumnsTest extends TestCase
{
  private function cols($rrule) {
    $v = mcp_validate_rrule($rrule);
    $this->assertTrue($v['valid'], $v['error'] ?? '');
    return mcp_rrule_to_repeat_columns($v['parts']);
  }

  public function test_weekly_with_interval_and_byday() {
    $c = $this->cols('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE');
    $this->assertSame('weekly', $c['cal_type']);
    $this->assertSame(2, $c['cal_frequency']);
    $this->assertSame('MO,WE', $c['cal_byday']);
    $this->assertSame('MO', $c['cal_wkst']);
    $this->assertArrayNotHasKey('cal_count', $c);
    $this->assertArrayNotHasKey('cal_end', $c);
  }

  public function test_daily_defaults_frequency_to_one() {
    $c = $this->cols('FREQ=DAILY');
    $this->assertSame('daily', $c['cal_type']);
    $this->assertSame(1, $c['cal_frequency']);
  }

  public function test_monthly_by_monthday_is_monthlyByDay() {
    // Matches WebCalendar: MONTHLY defaults to monthlyByDay even with BYMONTHDAY.
    $c = $this->cols('FREQ=MONTHLY;BYMONTHDAY=1,15');
    $this->assertSame('monthlyByDay', $c['cal_type']);
    $this->assertSame('1,15', $c['cal_bymonthday']);
  }

  public function test_monthly_with_bysetpos_is_monthlyBySetPos() {
    $c = $this->cols('FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1');
    $this->assertSame('monthlyBySetPos', $c['cal_type']);
    $this->assertSame('1', $c['cal_bysetpos']);
    $this->assertSame('MO', $c['cal_byday']);
  }

  public function test_yearly_with_bymonth() {
    $c = $this->cols('FREQ=YEARLY;BYMONTH=6');
    $this->assertSame('yearly', $c['cal_type']);
    $this->assertSame('6', $c['cal_bymonth']);
  }

  public function test_count_maps_to_cal_count() {
    $c = $this->cols('FREQ=DAILY;COUNT=10');
    $this->assertSame(10, $c['cal_count']);
    $this->assertArrayNotHasKey('cal_end', $c);
  }

  public function test_until_date_only_sets_end_and_zero_endtime() {
    $c = $this->cols('FREQ=DAILY;UNTIL=20301231');
    $this->assertSame(20301231, $c['cal_end']);
    $this->assertSame(0, $c['cal_endtime']);
    $this->assertArrayNotHasKey('cal_count', $c);
  }

  public function test_until_datetime_sets_end_and_endtime() {
    $c = $this->cols('FREQ=DAILY;UNTIL=20301231T120000Z');
    $this->assertSame(20301231, $c['cal_end']);
    $this->assertSame(120000, $c['cal_endtime']);
  }

  public function test_wkst_is_carried_through() {
    $c = $this->cols('FREQ=WEEKLY;BYDAY=MO;WKST=SU');
    $this->assertSame('SU', $c['cal_wkst']);
  }
}
