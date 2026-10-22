<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Unit tests for mcp_validate_rrule(): server-side, defense-in-depth
 * validation that a client-supplied RRULE conforms to the subset WebCalendar
 * can store and correctly expand.
 *
 * The supported subset and its caveats are fixed by the scheduling-agent
 * schema audit (docs/SCHEMA_AUDIT.md), derived from webcal_entry_repeats and
 * the xcal.php builder/parser:
 *   - FREQ in {DAILY, WEEKLY, MONTHLY, YEARLY} -- no sub-daily.
 *   - Parts: INTERVAL, COUNT, UNTIL (COUNT/UNTIL mutually exclusive),
 *     BYMONTH, BYMONTHDAY, BYDAY (+offsets), BYSETPOS, BYWEEKNO, BYYEARDAY, WKST.
 *   - Rejected: BYHOUR/BYMINUTE/BYSECOND (WebCalendar ignores them),
 *     the COUNT=999 "infinite" sentinel, and BY* values that would overflow
 *     the varchar columns.
 *
 * mcp_validate_rrule($rrule) returns:
 *   ['valid' => true,  'parts' => [KEY => VALUE, ...]]   (normalized, uppercased)
 *   ['valid' => false, 'error' => 'human-readable reason']
 */
final class McpRruleValidationTest extends TestCase
{
  private function assertValid($rrule) {
    $r = mcp_validate_rrule($rrule);
    $this->assertTrue($r['valid'], 'expected valid, got: ' . ($r['error'] ?? ''));
    return $r;
  }

  private function assertInvalid($rrule, $needle = null) {
    $r = mcp_validate_rrule($rrule);
    $this->assertFalse($r['valid'], "expected invalid for: $rrule");
    if ($needle !== null) {
      $this->assertStringContainsStringIgnoringCase($needle, $r['error']);
    }
    return $r;
  }

  // --- happy paths --------------------------------------------------------

  public function test_simple_weekly_byday_is_valid() {
    $r = $this->assertValid('FREQ=WEEKLY;BYDAY=MO,WE,FR');
    $this->assertEquals('WEEKLY', $r['parts']['FREQ']);
    $this->assertEquals('MO,WE,FR', $r['parts']['BYDAY']);
  }

  public function test_rrule_prefix_and_lowercase_are_normalized() {
    $r = $this->assertValid('rrule:freq=daily;interval=2');
    $this->assertEquals('DAILY', $r['parts']['FREQ']);
    $this->assertEquals('2', $r['parts']['INTERVAL']);
    $this->assertArrayNotHasKey('RRULE', $r['parts']);
  }

  public function test_monthly_by_setpos_is_valid() {
    $this->assertValid('FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1');
  }

  public function test_monthly_by_monthday_with_negative_is_valid() {
    $this->assertValid('FREQ=MONTHLY;BYMONTHDAY=1,15,-1');
  }

  public function test_byday_with_offsets_is_valid() {
    $this->assertValid('FREQ=MONTHLY;BYDAY=2MO,-1FR');
  }

  public function test_yearly_with_until_is_valid() {
    $this->assertValid('FREQ=YEARLY;BYMONTH=6;UNTIL=20301231T000000Z');
  }

  public function test_wkst_sunday_is_valid() {
    $this->assertValid('FREQ=WEEKLY;BYDAY=MO;WKST=SU');
  }

  // --- FREQ rules ---------------------------------------------------------

  public function test_missing_freq_is_invalid() {
    $this->assertInvalid('BYDAY=MO,WE', 'FREQ');
  }

  public function test_subdaily_freq_is_invalid() {
    $this->assertInvalid('FREQ=HOURLY', 'HOURLY');
    $this->assertInvalid('FREQ=MINUTELY');
    $this->assertInvalid('FREQ=SECONDLY');
  }

  public function test_unknown_freq_is_invalid() {
    $this->assertInvalid('FREQ=FORTNIGHTLY', 'FREQ');
  }

  public function test_empty_rrule_is_invalid() {
    $this->assertInvalid('', 'required');
    $this->assertInvalid('   ');
  }

  // --- unsupported / conflicting parts ------------------------------------

  public function test_byhour_byminute_bysecond_are_rejected() {
    $this->assertInvalid('FREQ=DAILY;BYHOUR=9', 'BYHOUR');
    $this->assertInvalid('FREQ=DAILY;BYMINUTE=30', 'BYMINUTE');
    $this->assertInvalid('FREQ=DAILY;BYSECOND=0', 'BYSECOND');
  }

  public function test_count_and_until_are_mutually_exclusive() {
    $this->assertInvalid('FREQ=DAILY;COUNT=5;UNTIL=20301231T000000Z', 'COUNT');
  }

  public function test_count_999_sentinel_is_rejected() {
    // 999 is WebCalendar's "infinite" sentinel; a literal COUNT=999 would
    // collide with it, so the agent must never emit it.
    $this->assertInvalid('FREQ=DAILY;COUNT=999', '999');
  }

  public function test_unknown_rule_part_is_rejected() {
    $this->assertInvalid('FREQ=DAILY;FOO=BAR', 'FOO');
  }

  public function test_duplicate_part_is_rejected() {
    $this->assertInvalid('FREQ=DAILY;FREQ=WEEKLY', 'duplicate');
  }

  // --- numeric part validation --------------------------------------------

  public function test_non_positive_interval_is_invalid() {
    $this->assertInvalid('FREQ=DAILY;INTERVAL=0', 'INTERVAL');
    $this->assertInvalid('FREQ=DAILY;INTERVAL=-1');
    $this->assertInvalid('FREQ=DAILY;INTERVAL=abc');
  }

  public function test_non_positive_count_is_invalid() {
    $this->assertInvalid('FREQ=DAILY;COUNT=0', 'COUNT');
    $this->assertInvalid('FREQ=DAILY;COUNT=-3');
  }

  public function test_bymonth_out_of_range_is_invalid() {
    $this->assertInvalid('FREQ=YEARLY;BYMONTH=13', 'BYMONTH');
    $this->assertInvalid('FREQ=YEARLY;BYMONTH=0');
  }

  public function test_bymonthday_zero_is_invalid() {
    $this->assertInvalid('FREQ=MONTHLY;BYMONTHDAY=0', 'BYMONTHDAY');
    $this->assertInvalid('FREQ=MONTHLY;BYMONTHDAY=32');
  }

  public function test_bysetpos_zero_is_invalid() {
    $this->assertInvalid('FREQ=MONTHLY;BYDAY=MO;BYSETPOS=0', 'BYSETPOS');
  }

  public function test_invalid_byday_token_is_invalid() {
    $this->assertInvalid('FREQ=WEEKLY;BYDAY=XY', 'BYDAY');
  }

  public function test_invalid_wkst_is_invalid() {
    $this->assertInvalid('FREQ=WEEKLY;BYDAY=MO;WKST=XX', 'WKST');
  }

  public function test_invalid_until_format_is_invalid() {
    $this->assertInvalid('FREQ=DAILY;UNTIL=next-year', 'UNTIL');
  }

  // --- column width bounds (from the schema audit) ------------------------

  public function test_byday_overflowing_column_width_is_invalid() {
    // cal_byday is VARCHAR(100); a list longer than that must be rejected,
    // not silently truncated.
    $long = 'FREQ=WEEKLY;BYDAY=' . implode(',', array_fill(0, 40, 'MO'));
    $this->assertInvalid($long, 'BYDAY');
  }
}
