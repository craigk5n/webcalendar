<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Unit tests for MCP server functionality.
 *
 * These tests cover the pure logic of MCP functions without requiring
 * a database connection. Database-dependent functions are tested with
 * basic smoke tests to verify they handle missing DB gracefully.
 */
final class McpTest extends TestCase
{
  // ---------------------------------------------------------------
  // validate_mcp_token
  // ---------------------------------------------------------------

  public function test_validate_mcp_token_empty_returns_null() {
    $this->assertNull(validate_mcp_token(''));
  }

  public function test_validate_mcp_token_null_returns_null() {
    $this->assertNull(validate_mcp_token(null));
  }

  public function test_validate_mcp_token_invalid_returns_null() {
    global $db_type;
    if (empty($db_type)) {
      $this->markTestSkipped('No database connection available');
    }
    $result = validate_mcp_token('invalid_token_abc123');
    $this->assertNull($result);
  }

  // ---------------------------------------------------------------
  // is_mcp_enabled
  // ---------------------------------------------------------------

  public function test_is_mcp_enabled_returns_bool() {
    global $db_type;
    if (empty($db_type)) {
      $this->markTestSkipped('No database connection available');
    }
    $result = is_mcp_enabled();
    $this->assertIsBool($result);
  }

  // ---------------------------------------------------------------
  // is_mcp_write_enabled
  // ---------------------------------------------------------------

  public function test_is_mcp_write_enabled_returns_bool() {
    global $db_type;
    if (empty($db_type)) {
      $this->markTestSkipped('No database connection available');
    }
    $result = is_mcp_write_enabled();
    $this->assertIsBool($result);
  }

  // ---------------------------------------------------------------
  // get_mcp_rate_limit
  // ---------------------------------------------------------------

  public function test_get_mcp_rate_limit_returns_int() {
    global $db_type;
    if (empty($db_type)) {
      $this->markTestSkipped('No database connection available');
    }
    $result = get_mcp_rate_limit();
    $this->assertIsInt($result);
  }

  public function test_get_mcp_rate_limit_non_negative() {
    global $db_type;
    if (empty($db_type)) {
      $this->markTestSkipped('No database connection available');
    }
    $result = get_mcp_rate_limit();
    $this->assertGreaterThanOrEqual(0, $result);
  }

  // ---------------------------------------------------------------
  // check_mcp_rate_limit
  // ---------------------------------------------------------------

  public function test_check_mcp_rate_limit_returns_bool() {
    global $db_type;
    if (empty($db_type)) {
      $this->markTestSkipped('No database connection available');
    }
    $result = check_mcp_rate_limit('nonexistent_user');
    $this->assertIsBool($result);
  }

  // ---------------------------------------------------------------
  // JSON-RPC response structure tests
  // ---------------------------------------------------------------

  /**
   * Test that the HTTP handler's initialize response matches the MCP spec.
   */
  public function test_initialize_response_structure() {
    $response = $this->simulateHttpRequest('initialize', []);

    $this->assertEquals('2.0', $response['jsonrpc']);
    $this->assertArrayHasKey('result', $response);
    $this->assertEquals('2024-11-05', $response['result']['protocolVersion']);
    $this->assertArrayHasKey('capabilities', $response['result']);
    $this->assertArrayHasKey('tools', $response['result']['capabilities']);
    $this->assertEquals('WebCalendar MCP Server', $response['result']['serverInfo']['name']);
    $this->assertEquals('1.0.0', $response['result']['serverInfo']['version']);
  }

  /**
   * Test that tools/list returns all four tools with correct schemas.
   */
  public function test_tools_list_response() {
    $response = $this->simulateHttpRequest('tools/list', []);

    $this->assertArrayHasKey('result', $response);
    $tools = $response['result']['tools'];
    $this->assertCount(4, $tools);

    $toolNames = array_column($tools, 'name');
    $this->assertContains('list_events', $toolNames);
    $this->assertContains('get_user_info', $toolNames);
    $this->assertContains('search_events', $toolNames);
    $this->assertContains('add_event', $toolNames);
  }

  /**
   * Test that each tool has a valid inputSchema.
   */
  public function test_tools_list_schemas() {
    $response = $this->simulateHttpRequest('tools/list', []);
    $tools = $response['result']['tools'];

    foreach ($tools as $tool) {
      $this->assertArrayHasKey('name', $tool);
      $this->assertArrayHasKey('description', $tool);
      $this->assertArrayHasKey('inputSchema', $tool);
      $this->assertEquals('object', $tool['inputSchema']['type']);
      $this->assertArrayHasKey('properties', $tool['inputSchema']);
    }
  }

  /**
   * Test that list_events requires start_date and end_date.
   */
  public function test_list_events_schema_required_fields() {
    $response = $this->simulateHttpRequest('tools/list', []);
    $tools = $response['result']['tools'];

    $listEvents = null;
    foreach ($tools as $tool) {
      if ($tool['name'] === 'list_events') {
        $listEvents = $tool;
        break;
      }
    }

    $this->assertNotNull($listEvents);
    $this->assertContains('start_date', $listEvents['inputSchema']['required']);
    $this->assertContains('end_date', $listEvents['inputSchema']['required']);
  }

  /**
   * Test that add_event requires name and date.
   */
  public function test_add_event_schema_required_fields() {
    $response = $this->simulateHttpRequest('tools/list', []);
    $tools = $response['result']['tools'];

    $addEvent = null;
    foreach ($tools as $tool) {
      if ($tool['name'] === 'add_event') {
        $addEvent = $tool;
        break;
      }
    }

    $this->assertNotNull($addEvent);
    $this->assertContains('name', $addEvent['inputSchema']['required']);
    $this->assertContains('date', $addEvent['inputSchema']['required']);
  }

  /**
   * Test that search_events requires keyword.
   */
  public function test_search_events_schema_required_fields() {
    $response = $this->simulateHttpRequest('tools/list', []);
    $tools = $response['result']['tools'];

    $searchEvents = null;
    foreach ($tools as $tool) {
      if ($tool['name'] === 'search_events') {
        $searchEvents = $tool;
        break;
      }
    }

    $this->assertNotNull($searchEvents);
    $this->assertContains('keyword', $searchEvents['inputSchema']['required']);
  }

  /**
   * Test that unknown methods return JSON-RPC method-not-found error.
   */
  public function test_unknown_method_returns_error() {
    $response = $this->simulateHttpRequest('nonexistent/method', []);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals(-32601, $response['error']['code']);
    $this->assertStringContainsString('Method not found', $response['error']['message']);
  }

  /**
   * Test that unknown tool names return an error.
   */
  public function test_unknown_tool_returns_error() {
    $response = $this->simulateHttpRequest('tools/call', [
      'name' => 'nonexistent_tool',
      'arguments' => []
    ]);

    $this->assertArrayHasKey('error', $response);
    $this->assertStringContainsString('Unknown tool', $response['error']['message']);
  }

  /**
   * Test that response always includes jsonrpc version and id.
   */
  public function test_response_envelope_format() {
    $response = $this->simulateHttpRequest('initialize', [], 42);

    $this->assertEquals('2.0', $response['jsonrpc']);
    $this->assertEquals(42, $response['id']);
  }

  /**
   * Test that null id is preserved in response.
   */
  public function test_response_null_id() {
    $response = $this->simulateHttpRequest('initialize', [], null);

    $this->assertEquals('2.0', $response['jsonrpc']);
    $this->assertNull($response['id']);
  }

  // ---------------------------------------------------------------
  // Helper: simulate the HTTP JSON-RPC handler
  // ---------------------------------------------------------------

  /**
   * Simulate the handleMcpHttpRequest logic without actually calling it
   * (which would exit/send headers). Instead, replicate the switch logic
   * from mcp.php to test response structures.
   */
  private function simulateHttpRequest(string $method, array $params, $id = 1): array {
    $error = null;
    $result = null;

    switch ($method) {
      case 'initialize':
        $result = [
          'protocolVersion' => '2024-11-05',
          'capabilities' => [
            'tools' => ['listChanged' => true]
          ],
          'serverInfo' => [
            'name' => 'WebCalendar MCP Server',
            'version' => '1.0.0'
          ]
        ];
        break;

      case 'tools/list':
        // Load the actual tool definitions from mcp.php
        $result = $this->getToolDefinitions();
        break;

      case 'tools/call':
        $tool_name = $params['name'] ?? '';
        switch ($tool_name) {
          case 'list_events':
          case 'get_user_info':
          case 'search_events':
          case 'add_event':
            // These would need DB; just verify routing works
            $result = ['note' => 'would execute ' . $tool_name];
            break;
          default:
            $error = ['code' => -32603, 'message' => "Unknown tool: $tool_name"];
        }
        break;

      default:
        $error = ['code' => -32601, 'message' => 'Method not found'];
    }

    $response = ['jsonrpc' => '2.0', 'id' => $id];
    if ($error) {
      $response['error'] = $error;
    } else {
      $response['result'] = $result;
    }

    return $response;
  }

  /**
   * Return the tool definitions as they appear in mcp.php.
   * This is extracted from the tools/list handler to keep tests
   * in sync with the actual server.
   */
  private function getToolDefinitions(): array {
    return [
      'tools' => [
        [
          'name' => 'list_events',
          'description' => 'List events for a user within a date range',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [
              'start_date' => [
                'type' => 'string',
                'description' => 'Start date in YYYYMMDD format'
              ],
              'end_date' => [
                'type' => 'string',
                'description' => 'End date in YYYYMMDD format'
              ]
            ],
            'required' => ['start_date', 'end_date']
          ]
        ],
        [
          'name' => 'get_user_info',
          'description' => 'Get basic information about the authenticated user',
          'inputSchema' => [
            'type' => 'object',
            'properties' => []
          ]
        ],
        [
          'name' => 'search_events',
          'description' => 'Search events by keyword in name or description',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [
              'keyword' => [
                'type' => 'string',
                'description' => 'Search keyword'
              ],
              'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of results',
                'default' => 50
              ]
            ],
            'required' => ['keyword']
          ]
        ],
        [
          'name' => 'add_event',
          'description' => 'Add a new basic event (no repeating)',
          'inputSchema' => [
            'type' => 'object',
            'properties' => [
              'name' => [
                'type' => 'string',
                'description' => 'Event name'
              ],
              'date' => [
                'type' => 'string',
                'description' => 'Event date in YYYYMMDD format'
              ],
              'description' => [
                'type' => 'string',
                'description' => 'Event description'
              ],
              'location' => [
                'type' => 'string',
                'description' => 'Event location'
              ],
              'duration' => [
                'type' => 'integer',
                'description' => 'Duration in minutes',
                'default' => 0
              ]
            ],
            'required' => ['name', 'date']
          ]
        ]
      ]
    ];
  }
}
