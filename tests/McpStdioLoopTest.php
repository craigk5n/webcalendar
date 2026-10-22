<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Verifies mcp_run_stdio_loop() frames newline-delimited JSON-RPC correctly:
 * one response line per request, no response for notifications, parse errors
 * for malformed input, and routing through mcp_dispatch_request(). Uses
 * in-memory streams so the STDIO transport is exercised without a real process.
 */
final class McpStdioLoopTest extends TestCase
{
  private function stubTools() {
    return new class {
      public $calls = [];
      public function list_events($start_date, $end_date) {
        $this->calls[] = ['list_events', [$start_date, $end_date]];
        return ['events' => []];
      }
    };
  }

  /**
   * Runs the loop over $input and returns the decoded response lines (in order).
   *
   * @return array<int, array> One decoded JSON object per output line.
   */
  private function runLoop($input, $tools = null) {
    $in = fopen('php://memory', 'r+');
    fwrite($in, $input);
    rewind($in);

    $out = fopen('php://memory', 'r+');

    mcp_run_stdio_loop($in, $out, $tools ?? $this->stubTools());

    rewind($out);
    $raw = stream_get_contents($out);
    fclose($in);
    fclose($out);

    $lines = array_values(array_filter(explode("\n", $raw), function ($l) {
      return $l !== '';
    }));
    return array_map(function ($l) {
      return json_decode($l, true);
    }, $lines);
  }

  public function test_initialize_returns_single_response() {
    $responses = $this->runLoop(
      json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize']) . "\n"
    );
    $this->assertCount(1, $responses);
    $this->assertSame('2.0', $responses[0]['jsonrpc']);
    $this->assertSame(1, $responses[0]['id']);
    $this->assertArrayHasKey('result', $responses[0]);
  }

  public function test_tools_list_is_advertised() {
    $responses = $this->runLoop(
      json_encode(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']) . "\n"
    );
    $this->assertCount(1, $responses);
    $names = array_column($responses[0]['result']['tools'], 'name');
    $this->assertContains('list_events', $names);
  }

  public function test_notification_gets_no_response() {
    // A message with no "id" is a notification and must not be answered.
    $responses = $this->runLoop(
      json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']) . "\n"
    );
    $this->assertCount(0, $responses);
  }

  public function test_malformed_json_returns_parse_error() {
    $responses = $this->runLoop("this is not json\n");
    $this->assertCount(1, $responses);
    $this->assertSame(-32700, $responses[0]['error']['code']);
    $this->assertNull($responses[0]['id']);
  }

  public function test_blank_lines_are_skipped() {
    $input = "\n" .
      json_encode(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'initialize']) . "\n" .
      "\n";
    $responses = $this->runLoop($input);
    $this->assertCount(1, $responses);
    $this->assertSame(3, $responses[0]['id']);
  }

  public function test_multiple_messages_each_get_a_response() {
    $input =
      json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize']) . "\n" .
      json_encode(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']) . "\n";
    $responses = $this->runLoop($input);
    $this->assertCount(2, $responses);
    $this->assertSame(1, $responses[0]['id']);
    $this->assertSame(2, $responses[1]['id']);
  }

  public function test_tools_call_routes_to_instance() {
    $tools = $this->stubTools();
    $responses = $this->runLoop(
      json_encode([
        'jsonrpc' => '2.0',
        'id' => 7,
        'method' => 'tools/call',
        'params' => ['name' => 'list_events',
          'arguments' => ['start_date' => '20260101', 'end_date' => '20260131']],
      ]) . "\n",
      $tools
    );
    $this->assertCount(1, $responses);
    $this->assertSame(['list_events', ['20260101', '20260131']], $tools->calls[0]);
  }
}
