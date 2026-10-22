<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Unit tests for the pure interval/conflict/merge helpers underpinning the
 * get_availability and check_conflicts MCP tools. These have no DB or global
 * state, so they are tested directly.
 */
final class McpSchedulingHelpersTest extends TestCase
{
  // --- mcp_datetime_to_min ------------------------------------------------

  public function test_same_day_times_differ_by_minutes() {
    $a = mcp_datetime_to_min('20260611', '090000');
    $b = mcp_datetime_to_min('20260611', '103000');
    $this->assertSame(90, $b - $a); // 1h30m
  }

  public function test_day_rollover_is_1440_minutes() {
    $a = mcp_datetime_to_min('20260101', '000000');
    $b = mcp_datetime_to_min('20260102', '000000');
    $this->assertSame(1440, $b - $a);
  }

  public function test_unpadded_time_is_normalized() {
    // 80000 -> 08:00:00
    $a = mcp_datetime_to_min('20260611', 80000);
    $b = mcp_datetime_to_min('20260611', '080000');
    $this->assertSame($a, $b);
  }

  // --- mcp_intervals_overlap (half-open [start,end)) ----------------------

  public function test_overlapping_intervals_return_true() {
    $this->assertTrue(mcp_intervals_overlap(100, 200, 150, 250));
  }

  public function test_touching_intervals_do_not_overlap() {
    // [100,200) and [200,300) share only the boundary -> no conflict.
    $this->assertFalse(mcp_intervals_overlap(100, 200, 200, 300));
  }

  public function test_contained_interval_overlaps() {
    $this->assertTrue(mcp_intervals_overlap(100, 300, 150, 200));
  }

  public function test_disjoint_intervals_do_not_overlap() {
    $this->assertFalse(mcp_intervals_overlap(100, 150, 200, 250));
  }

  // --- mcp_find_conflicts -------------------------------------------------

  public function test_find_conflicts_returns_only_overlapping_events() {
    $events = [
      ['id' => 1, 'name' => 'before', 'start' => 0,   'end' => 60],
      ['id' => 2, 'name' => 'overlap', 'start' => 90,  'end' => 150],
      ['id' => 3, 'name' => 'after',  'start' => 300, 'end' => 360],
    ];
    $conflicts = mcp_find_conflicts(100, 130, $events);
    $this->assertCount(1, $conflicts);
    $this->assertSame(2, $conflicts[0]['id']);
  }

  public function test_find_conflicts_empty_when_none_overlap() {
    $events = [['id' => 1, 'name' => 'x', 'start' => 0, 'end' => 60]];
    $this->assertSame([], mcp_find_conflicts(100, 130, $events));
  }

  // --- mcp_merge_intervals ------------------------------------------------

  public function test_merge_combines_overlapping_intervals() {
    $merged = mcp_merge_intervals([[100, 200], [150, 250], [400, 500]]);
    $this->assertSame([[100, 250], [400, 500]], $merged);
  }

  public function test_merge_sorts_unordered_input() {
    $merged = mcp_merge_intervals([[400, 500], [100, 200]]);
    $this->assertSame([[100, 200], [400, 500]], $merged);
  }

  public function test_merge_leaves_touching_intervals_separate() {
    // Touching (200==200) are not merged: they represent back-to-back busy
    // blocks, which is fine to report as two.
    $merged = mcp_merge_intervals([[100, 200], [200, 300]]);
    $this->assertSame([[100, 200], [200, 300]], $merged);
  }

  public function test_merge_empty_returns_empty() {
    $this->assertSame([], mcp_merge_intervals([]));
  }
}
