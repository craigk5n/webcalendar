<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Verifies that mcp_dispatch_request() advertises the scheduling tools and
 * routes tools/call to the right WebCalendarMcpTools method with the right
 * arguments. A stub $tools object records the call, so this exercises the
 * dispatch wiring without loading mcp.php's transport bootstrap.
 */
final class McpToolDispatchTest extends TestCase
{
  private function stubTools() {
    return new class {
      public $calls = [];
      public function get_availability($start_date, $end_date) {
        $this->calls[] = ['get_availability', [$start_date, $end_date]];
        return ['busy' => [], 'all_day' => [], 'timezone' => 'GMT'];
      }
      public function check_conflicts($date, $time, $duration) {
        $this->calls[] = ['check_conflicts', [$date, $time, $duration]];
        return ['has_conflict' => false, 'conflicts' => []];
      }
      public function add_recurring_event($name, $date, $rrule, $time, $duration, $description, $location) {
        $this->calls[] = ['add_recurring_event',
          [$name, $date, $rrule, $time, $duration, $description, $location]];
        return ['success' => true, 'event_id' => 42, 'cal_type' => 'weekly'];
      }
      public function update_event($event_id, $name, $date, $time, $duration, $description, $location) {
        $this->calls[] = ['update_event',
          [$event_id, $name, $date, $time, $duration, $description, $location]];
        return ['success' => true, 'event_id' => $event_id];
      }
      public function delete_event($event_id) {
        $this->calls[] = ['delete_event', [$event_id]];
        return ['success' => true, 'event_id' => $event_id];
      }
    };
  }

  private function call($tools, $name, $arguments) {
    return mcp_dispatch_request([
      'jsonrpc' => '2.0',
      'method' => 'tools/call',
      'params' => ['name' => $name, 'arguments' => $arguments],
      'id' => 1,
    ], $tools);
  }

  // --- schema advertising -------------------------------------------------

  public function test_get_availability_schema_required_fields() {
    $schema = get_mcp_tool_schema('get_availability');
    $this->assertNotNull($schema);
    $this->assertContains('start_date', $schema['required']);
    $this->assertContains('end_date', $schema['required']);
  }

  public function test_check_conflicts_schema_required_fields() {
    $schema = get_mcp_tool_schema('check_conflicts');
    $this->assertNotNull($schema);
    $this->assertContains('date', $schema['required']);
    $this->assertContains('time', $schema['required']);
    $this->assertContains('duration', $schema['required']);
  }

  // --- routing ------------------------------------------------------------

  public function test_dispatch_routes_get_availability_with_args() {
    $tools = $this->stubTools();
    $resp = $this->call($tools, 'get_availability',
      ['start_date' => '20260101', 'end_date' => '20260131']);

    $this->assertSame(['get_availability', ['20260101', '20260131']], $tools->calls[0]);
    $this->assertArrayHasKey('result', $resp);
    $this->assertArrayNotHasKey('error', $resp);
  }

  public function test_dispatch_routes_check_conflicts_with_args() {
    $tools = $this->stubTools();
    $resp = $this->call($tools, 'check_conflicts',
      ['date' => '20260611', 'time' => '090000', 'duration' => 60]);

    $this->assertSame(['check_conflicts', ['20260611', '090000', 60]], $tools->calls[0]);
    $this->assertArrayHasKey('result', $resp);
    $this->assertFalse($resp['result']['has_conflict']);
  }

  public function test_add_recurring_event_schema_required_fields() {
    $schema = get_mcp_tool_schema('add_recurring_event');
    $this->assertNotNull($schema);
    $this->assertContains('name', $schema['required']);
    $this->assertContains('date', $schema['required']);
    $this->assertContains('rrule', $schema['required']);
  }

  public function test_dispatch_routes_add_recurring_event_with_args() {
    $tools = $this->stubTools();
    $resp = $this->call($tools, 'add_recurring_event', [
      'name' => 'Standup', 'date' => '20260803', 'rrule' => 'FREQ=WEEKLY;BYDAY=MO',
      'time' => '091500', 'duration' => 15, 'description' => 'daily sync', 'location' => 'Zoom',
    ]);

    $this->assertSame(
      ['add_recurring_event',
        ['Standup', '20260803', 'FREQ=WEEKLY;BYDAY=MO', '091500', 15, 'daily sync', 'Zoom']],
      $tools->calls[0]
    );
    $this->assertSame(42, $resp['result']['event_id']);
  }

  public function test_update_and_delete_schemas_require_event_id() {
    $this->assertContains('event_id', get_mcp_tool_schema('update_event')['required']);
    $this->assertContains('event_id', get_mcp_tool_schema('delete_event')['required']);
  }

  public function test_dispatch_routes_update_event_passing_null_for_absent_fields() {
    $tools = $this->stubTools();
    $this->call($tools, 'update_event', ['event_id' => 7, 'name' => 'Renamed']);
    // Absent optional fields must arrive as null so the tool leaves them
    // unchanged (not '' which would blank the column).
    $this->assertSame(
      ['update_event', [7, 'Renamed', null, null, null, null, null]],
      $tools->calls[0]
    );
  }

  public function test_dispatch_routes_delete_event_with_id() {
    $tools = $this->stubTools();
    $this->call($tools, 'delete_event', ['event_id' => 9]);
    $this->assertSame(['delete_event', [9]], $tools->calls[0]);
  }
}
