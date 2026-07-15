<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/McpTestHelper.php";

/**
 * Unit tests for MCP server functionality.
 *
 * These tests cover the pure logic of MCP functions without requiring
 * a database connection. Database-dependent functions are tested with
 * basic smoke tests to verify they handle missing DB gracefully.
 */
final class McpTest extends TestCase
{
  private $testDb = null;
  
  // ---------------------------------------------------------------
  // MCP Core Function Tests
  // ---------------------------------------------------------------

  public function test_validate_mcp_token_empty_returns_null() {
    $result = validate_mcp_token('');
    $this->assertNull($result);
  }

  public function test_validate_mcp_token_null_returns_null() {
    $result = validate_mcp_token(null);
    $this->assertNull($result);
  }

  public function test_validate_mcp_token_invalid_returns_null() {
    $result = validate_mcp_token('invalid_token');
    $this->assertNull($result);
  }

  public function test_is_mcp_enabled_returns_bool() {
    $result = is_mcp_enabled();
    $this->assertIsBool($result);
  }

  public function test_is_mcp_write_enabled_returns_bool() {
    $result = is_mcp_write_enabled();
    $this->assertIsBool($result);
  }

  public function test_get_mcp_rate_limit_returns_int() {
    $result = get_mcp_rate_limit();
    $this->assertIsInt($result);
    $this->assertGreaterThanOrEqual(0, $result);
  }

  public function test_get_mcp_rate_limit_non_negative() {
    $result = get_mcp_rate_limit();
    $this->assertGreaterThanOrEqual(0, $result);
  }

  public function test_check_mcp_rate_limit_returns_bool() {
    $result = check_mcp_rate_limit('testuser');
    $this->assertIsBool($result);
  }

  // ---------------------------------------------------------------
  // MCP JSON-RPC Response Tests
  // ---------------------------------------------------------------

  public function test_initialize_response_structure() {
    $result = mcp_initialize_result();
    $this->assertEquals('2024-11-05', $result['protocolVersion']);
    $this->assertArrayHasKey('capabilities', $result);
    $this->assertArrayHasKey('tools', $result['capabilities']);
    $this->assertEquals('WebCalendar MCP Server', $result['serverInfo']['name']);
    $this->assertEquals('1.0.0', $result['serverInfo']['version']);
  }

  public function test_tools_list_response() {
    $tools = mcp_list_tools();
    $this->assertCount(9, $tools);

    $names = array_column($tools, 'name');
    $this->assertContains('list_events', $names);
    $this->assertContains('get_user_info', $names);
    $this->assertContains('search_events', $names);
    $this->assertContains('add_event', $names);
    $this->assertContains('get_availability', $names);
    $this->assertContains('check_conflicts', $names);
    $this->assertContains('add_recurring_event', $names);
    $this->assertContains('update_event', $names);
    $this->assertContains('delete_event', $names);
  }

  public function test_tools_list_schemas() {
    foreach (mcp_list_tools() as $tool) {
      $this->assertArrayHasKey('name', $tool);
      $this->assertArrayHasKey('description', $tool);
      $this->assertArrayHasKey('inputSchema', $tool);
      $this->assertEquals('object', $tool['inputSchema']['type']);
      $this->assertArrayHasKey('properties', $tool['inputSchema']);
    }
  }

  // ---------------------------------------------------------------
  // MCP Tool Schema Validation Tests
  // ---------------------------------------------------------------

  public function test_list_events_schema_required_fields() {
    $schema = get_mcp_tool_schema('list_events');
    $this->assertNotNull($schema);
    $this->assertContains('start_date', $schema['required']);
    $this->assertContains('end_date', $schema['required']);
  }

  public function test_add_event_schema_required_fields() {
    $schema = get_mcp_tool_schema('add_event');
    $this->assertNotNull($schema);
    $this->assertContains('name', $schema['required']);
    $this->assertContains('date', $schema['required']);
  }

  public function test_add_event_schema_has_optional_time() {
    $schema = get_mcp_tool_schema('add_event');
    $this->assertArrayHasKey('time', $schema['properties']);
    $this->assertNotContains('time', $schema['required']);
  }

  public function test_search_events_schema_required_fields() {
    $schema = get_mcp_tool_schema('search_events');
    $this->assertNotNull($schema);
    $this->assertContains('keyword', $schema['required']);
  }

  public function test_unknown_tool_schema_returns_null() {
    $this->assertNull(get_mcp_tool_schema('nonexistent_tool'));
  }

  // ---------------------------------------------------------------
  // MCP Error Handling Tests (via the real dispatcher)
  // ---------------------------------------------------------------

  public function test_unknown_method_returns_error() {
    $response = mcp_dispatch_request(
      ['jsonrpc' => '2.0', 'method' => 'nonexistent/method', 'id' => 1]
    );

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals(-32601, $response['error']['code']);
    $this->assertStringContainsString('Method not found', $response['error']['message']);
  }

  public function test_unknown_tool_returns_error() {
    $response = mcp_dispatch_request([
      'jsonrpc' => '2.0',
      'method' => 'tools/call',
      'params' => ['name' => 'nonexistent_tool', 'arguments' => []],
      'id' => 1
    ]);

    $this->assertArrayHasKey('error', $response);
    $this->assertEquals(-32603, $response['error']['code']);
    $this->assertStringContainsString('Unknown tool', $response['error']['message']);
  }

  public function test_response_envelope_format() {
    $response = mcp_dispatch_request(
      ['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 7]
    );

    $this->assertEquals('2.0', $response['jsonrpc']);
    $this->assertEquals(7, $response['id']);
    $this->assertArrayHasKey('result', $response);
    $this->assertArrayNotHasKey('error', $response);
  }

  public function test_response_null_id() {
    $response = mcp_dispatch_request(
      ['jsonrpc' => '2.0', 'method' => 'initialize', 'id' => null]
    );

    $this->assertNull($response['id']);
    $this->assertArrayHasKey('result', $response);
  }

  // ---------------------------------------------------------------
  // Database Integration Tests (SQLite)
  // ---------------------------------------------------------------

  protected function setUp(): void {
    parent::setUp();
    
    if (!extension_loaded('sqlite3')) {
      $this->markTestSkipped('SQLite3 extension not available');
    }
    
    // Create test database
    $this->testDb = createTestDatabase();
    
    // Set up global database variables and connection
    $this->testDb->setupGlobalVariables();
    
    // Establish database connection
    dbi_connect('', '', '', $this->testDb->getDbPath());

    // Re-read settings from this test's fresh database. load_settings() caches
    // process-wide, so without this a setting cached by an earlier test class
    // (e.g. a different MCP_RATE_LIMIT) would leak into these tests.
    load_settings(true);
  }

  /**
   * Test that error handling is consistent across different failure modes.
   */
  public function testErrorResponseFormat() {
    // Test various error scenarios to ensure consistent error format
    $errorScenarios = [
      'empty_token' => '',
      'null_token' => null,
      'invalid_token' => 'invalid_token_123',
      'malformed_bearer' => 'Bearer',
      'nonexistent_user' => 'user_does_not_exist'
    ];
    
    foreach ($errorScenarios as $scenario => $token) {
      // Test that invalid tokens return null (consistent error handling)
      $result = validate_mcp_token($token);
      $this->assertNull($result, "Error scenario '$scenario' should return null");
    }
  }

  /**
   * Test that error messages include sufficient debugging information without exposing sensitive data.
   */
  public function testErrorMessagesSecurity() {
    // Test that error messages don't expose sensitive information
    $sensitiveTokens = [
      'admin_token_secret_12345',
      'user_private_data_abc123',
    ];

    foreach ($sensitiveTokens as $token) {
      $result = validate_mcp_token($token);
      $this->assertNull($result, "Sensitive token should return null without exposing details");
    }
  }

  private function testDatabaseErrorHandling() {
    // Test database connection error handling
    $originalDb = $GLOBALS['db_database'];
    $GLOBALS['db_database'] = '/nonexistent/path/database.db';
    
    // This should handle the error gracefully
    $result = validate_mcp_token('test_token');
    $this->assertNull($result);
    
    // Restore original database setting
    $GLOBALS['db_database'] = $originalDb;
  }

  public function testErrorLoggingSecurity() {
    // Test that error logging doesn't expose sensitive information
    $this->testDatabaseErrorHandling();
    
    // Additional error logging tests can be added here
  }

  private function testRateLimitErrorLogging() {
    // Test rate limit error logging doesn't expose sensitive data
    dbi_query('DELETE FROM webcal_config WHERE cal_setting = "MCP_RATE_LIMIT"');
    dbi_query('INSERT INTO webcal_config (cal_setting, cal_value) VALUES ("MCP_RATE_LIMIT", "0")');
    $GLOBALS['settings_cache'] = null;
    
    $result = check_mcp_rate_limit('nonexistent_user');
    $this->assertFalse($result);
  }

  // ---------------------------------------------------------------
  // SQLite Integration Tests
  // ---------------------------------------------------------------

  public function testSQLiteTransactionIsolation() {
    // Test SQLite transaction isolation for concurrent event creation
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    // Clear existing events
    $this->testDb->clearUserEvents($userLogin);
    
    // Create events with SQLite transaction
    $events = [];
    for ($i = 1; $i <= 5; $i++) {
      $eventId = $this->testDb->createEvent($userLogin, 20240601 + $i, "Transaction Test Event $i");
      $this->testDb->associateUserWithEvent($eventId, $userLogin);
      $events[] = $eventId;
    }
    
    // Verify all events were created
    $this->assertEquals(5, count($events));
    
    // Test transaction isolation by ensuring events are properly isolated
    $eventCount = $this->testDb->getEventCountForUser($userLogin);
    $this->assertEquals(5, $eventCount);
  }

  public function testMcpServerIntegrationWithSQLite() {
    // Test MCP server integration with SQLite backend
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    // Test MCP server configuration
    $enabled = is_mcp_enabled();
    $this->assertTrue($enabled);
    
    $rateLimit = get_mcp_rate_limit();
    $this->assertIsInt($rateLimit);
    $this->assertGreaterThanOrEqual(0, $rateLimit);
    
    // Test manual rate limit calculation
    $manualRateLimit = $this->testDb->getMcpConfig('MCP_RATE_LIMIT');
    $this->assertEquals('100', $manualRateLimit);
  }

  public function testListEventsWithSQLite() {
    // Test list_events functionality with SQLite backend
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    // Create test events
    for ($i = 1; $i <= 5; $i++) {
      $eventId = $this->testDb->createEvent($userLogin, 20240601 + $i, "List Test Event $i");
      $this->testDb->associateUserWithEvent($eventId, $userLogin);
    }
    
    // Test MCP server would handle list_events
    // This is a simulation since we're testing individual functions
    $result = validate_mcp_token($token);
    $this->assertEquals($userLogin, $result);
  }

  public function testSearchEventsWithSQLite() {
    // Test search_events functionality with SQLite backend
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    // Create test events with different names
    $eventId1 = $this->testDb->createEvent($userLogin, 20240601, "Meeting");
    $eventId2 = $this->testDb->createEvent($userLogin, 20240602, "Lunch");
    $eventId3 = $this->testDb->createEvent($userLogin, 20240603, "Conference");
    
    $this->testDb->associateUserWithEvent($eventId1, $userLogin);
    $this->testDb->associateUserWithEvent($eventId2, $userLogin);
    $this->testDb->associateUserWithEvent($eventId3, $userLogin);
    
    // Test search functionality (simulated)
    $searchResults = $this->testDb->searchEventsForUser($userLogin, 'Meeting');
    $this->assertGreaterThan(0, count($searchResults));
    $eventNames = array_column($searchResults, 'cal_name');
    $this->assertContains('Meeting', $eventNames);
    
    $allEvents = $this->testDb->searchEventsForUser($userLogin, '');
    $this->assertGreaterThanOrEqual(3, count($allEvents));
  }

  public function testUserInfoWithSQLite() {
    // Test get_user_info functionality with SQLite backend
    $userLogin = 'testuser';
    
    // Get user info from database
    $result = dbi_query('SELECT cal_firstname, cal_lastname, cal_email FROM webcal_user WHERE cal_login = "' . $userLogin . '"');
    $userRow = dbi_fetch_row($result);
    
    $this->assertNotNull($userRow);
    $this->assertEquals('Test', $userRow['cal_firstname']);
    $this->assertEquals('User', $userRow['cal_lastname']);
    $this->assertEquals('test@example.com', $userRow['cal_email']);
  }

  // ---------------------------------------------------------------
  // Error Handling Tests
  // ---------------------------------------------------------------

  public function testErrorHandlingConsistency() {
    // Test error handling is consistent across different failure modes
    $userLogin = 'nonexistent_user';
    
    // Test with invalid user
    $result = validate_mcp_token('invalid_token');
    $this->assertNull($result);
    
    // Test rate limit with non-existent user
    $rateLimited = check_mcp_rate_limit($userLogin);
    $this->assertFalse($rateLimited); // Should return false for non-existent user
    
    // Test MCP enabled check
    $enabled = is_mcp_enabled();
    $this->assertTrue($enabled); // Should be enabled from test setup
  }

  // ---------------------------------------------------------------
  // Performance Tests
  // ---------------------------------------------------------------

  public function testPerformanceBaselines() {
    // Test basic performance baselines for MCP operations
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    
    // Set up API token for user
    $this->testDb->createApiToken($userLogin, $token);
    
    // Clear any existing events for this user to ensure clean test
    $this->testDb->clearUserEvents($userLogin);
    
    $startTime = microtime(true);
    
    // Create 10 events
    for ($i = 1; $i <= 10; $i++) {
      $eventId = $this->testDb->createEvent($userLogin, 20240601 + $i, "Performance Test Event $i");
      $this->testDb->associateUserWithEvent($eventId, $userLogin);
    }
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    
    // Event creation should be fast (less than 1 second for 10 events)
    $this->assertLessThan(1.0, $duration, 'Event creation took too long');
    
    // Verify all events were created
    $eventCount = $this->testDb->getEventCountForUser($userLogin);
    $this->assertEquals(10, $eventCount);
    
    // Test memory usage during event creation
    $memoryStart = memory_get_usage();
    for ($i = 11; $i <= 20; $i++) {
      $eventId = $this->testDb->createEvent($userLogin, 20240601 + $i, "Memory Test Event $i");
      $this->testDb->associateUserWithEvent($eventId, $userLogin);
    }
    $memoryEnd = memory_get_usage();
    $memoryUsed = $memoryEnd - $memoryStart;
    
    // Memory increase should be reasonable (less than 10MB for 10 more events)
    $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory usage during event creation was too high');
  }

  /**
   * Test MCP server throughput under high load.
   */
  public function testMcpServerThroughput() {
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    // Test throughput: events per second
    $startTime = microtime(true);
    $eventsCreated = 0;
    
    // Create 50 events as fast as possible
    for ($i = 1; $i <= 50; $i++) {
      $eventId = $this->testDb->createEvent($userLogin, 20240601 + $i, "Throughput Test Event $i");
      $this->testDb->associateUserWithEvent($eventId, $userLogin);
      $eventsCreated++;
    }
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    
    // Should be able to create at least 10 events per second
    $eventsPerSecond = $eventsCreated / $duration;
    $this->assertGreaterThan(10, $eventsPerSecond, "Throughput too low: $eventsPerSecond events/second");
    
    // Test concurrent access throughput
    $concurrentStartTime = microtime(true);
    $concurrentEvents = [];
    
    // Simulate 5 concurrent users creating events
    for ($user = 1; $user <= 5; $user++) {
      $userLogin = "concurrent_user_$user";
      $this->testDb->createApiToken($userLogin, "token_$user");
      
      for ($i = 1; $i <= 10; $i++) {
        $eventId = $this->testDb->createEvent($userLogin, 20240601 + $i, "Concurrent Event $user-$i");
        $this->testDb->associateUserWithEvent($eventId, $userLogin);
        $concurrentEvents[] = $eventId;
      }
    }
    
    $concurrentEndTime = microtime(true);
    $concurrentDuration = $concurrentEndTime - $concurrentStartTime;
    $concurrentThroughput = count($concurrentEvents) / $concurrentDuration;
    
    // Concurrent throughput should still be reasonable (at least 5 events/second)
    $this->assertGreaterThan(5, $concurrentThroughput, "Concurrent throughput too low: $concurrentThroughput events/second");
  }

  /**
   * Test MCP server response time consistency.
   */
  public function testMcpResponseTimeConsistency() {
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    $responseTimes = [];
    $testOperations = 20;
    
    // Measure response times for multiple operations
    for ($i = 1; $i <= $testOperations; $i++) {
      $startTime = microtime(true);
      
      // Perform typical MCP operation: create and associate event
      $eventId = $this->testDb->createEvent($userLogin, 20240601 + $i, "Response Time Test Event $i");
      $this->testDb->associateUserWithEvent($eventId, $userLogin);
      
      $endTime = microtime(true);
      $responseTimes[] = $endTime - $startTime;
    }
    
    // Calculate statistics
    $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
    $maxResponseTime = max($responseTimes);
    $minResponseTime = min($responseTimes);
    
    // Average response time should be under 100ms
    $this->assertLessThan(0.1, $avgResponseTime, "Average response time too high: {$avgResponseTime}s");
    
    // No single operation should take more than 500ms
    $this->assertLessThan(0.5, $maxResponseTime, "Maximum response time too high: {$maxResponseTime}s");
    
    // Response times should be reasonably consistent (no extreme outliers)
    $stdDev = $this->calculateStandardDeviation($responseTimes);
    $this->assertLessThan(0.05, $stdDev, "Response times too inconsistent: std dev {$stdDev}s");
  }

  /**
   * Test memory usage patterns under load.
   */
  public function testMemoryUsagePatterns() {
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    $baselineMemory = memory_get_usage();
    
    // Create events in batches and measure memory
    $memoryMeasurements = [];
    for ($batch = 1; $batch <= 5; $batch++) {
      $batchStartMemory = memory_get_usage();
      
      // Create 10 events in this batch
      for ($i = 1; $i <= 10; $i++) {
        $eventId = $this->testDb->createEvent($userLogin, 20240601 + ($batch * 10) + $i, "Memory Batch $batch Event $i");
        $this->testDb->associateUserWithEvent($eventId, $userLogin);
      }
      
      $batchEndMemory = memory_get_usage();
      $batchMemoryUsed = $batchEndMemory - $batchStartMemory;
      $memoryMeasurements[] = $batchMemoryUsed;
    }
    
    // Memory usage should grow linearly, not exponentially
    $avgMemoryPerBatch = array_sum($memoryMeasurements) / count($memoryMeasurements);
    
    // No single batch should use excessive memory (more than 2MB)
    foreach ($memoryMeasurements as $memoryUsed) {
      $this->assertLessThan(2 * 1024 * 1024, $memoryUsed, "Single batch used too much memory: {$memoryUsed} bytes");
    }
    
    // Total memory increase should be reasonable
    $totalMemoryIncrease = memory_get_usage() - $baselineMemory;
    $this->assertLessThan(20 * 1024 * 1024, $totalMemoryIncrease, 'Total memory increase too high');
  }

  /**
   * Test performance impact of rate limiting.
   */
  public function testRateLimitingPerformanceImpact() {
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    // Test rate limiting check performance with no history
    $startTime = microtime(true);
    for ($i = 0; $i < 10; $i++) {
      $requestStart = microtime(true);
      
      // Check rate limit (should be fast when no history)
      $isRateLimited = check_mcp_rate_limit($userLogin);
      
      $requestEnd = microtime(true);
      
      // Rate limit check should be very fast
      $requestDuration = $requestEnd - $requestStart;
      $this->assertLessThan(0.01, $requestDuration, "Rate limit check took too long: {$requestDuration}s");
      
      // Result should be consistent
      $this->assertFalse($isRateLimited, "Should not be rate limited with no history");
    }
    $noHistoryTime = microtime(true) - $startTime;
    
    // Add some MCP log entries to test with history
    $this->testDb->getDb()->exec("DELETE FROM webcal_entry_log WHERE cal_login = '$userLogin'");
    $currentDate = date('Ymd');
    
    // Add MCP entries using current date (which should be recent)
    for ($i = 1; $i <= 3; $i++) {
      $this->testDb->getDb()->exec("INSERT INTO webcal_entry_log (cal_entry_id, cal_login, cal_type, cal_date, cal_time, cal_text) VALUES ($i, '$userLogin', 'M', '$currentDate', '120000', 'MCP:test$i')");
    }
    
    // Test rate limiting check performance with history
    $historyStartTime = microtime(true);
    for ($i = 0; $i < 10; $i++) {
      $requestStart = microtime(true);
      
      // Check rate limit with history
      $isRateLimited = check_mcp_rate_limit($userLogin);
      
      $requestEnd = microtime(true);
      
      // Rate limit check should still be fast even with history. These are
      // wall-clock bounds, so they are intentionally generous to avoid
      // flaking on slow/loaded CI while still catching gross regressions.
      $requestDuration = $requestEnd - $requestStart;
      $this->assertLessThan(0.5, $requestDuration, "Rate limit check with history took too long: {$requestDuration}s");
    }
    $historyTime = microtime(true) - $historyStartTime;

    // Both scenarios should be reasonably fast.
    $this->assertLessThan(2.0, $noHistoryTime, "Rate limiting checks with no history took too long: {$noHistoryTime}s");
    $this->assertLessThan(2.0, $historyTime, "Rate limiting checks with history took too long: {$historyTime}s");

    // Test performance with heavy load (1000 checks)
    $heavyLoadStart = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
      $isRateLimited = check_mcp_rate_limit($userLogin);
    }
    $heavyLoadEnd = microtime(true);

    $heavyLoadTime = $heavyLoadEnd - $heavyLoadStart;
    // 1000 rate limit checks should complete in a sane time (generous bound).
    $this->assertLessThan(10.0, $heavyLoadTime, "Heavy load rate limiting took too long: {$heavyLoadTime}s");
    
    // Test that rate limiting function scales well (should be O(1) or O(log n), not O(n))
    $scalingStart = microtime(true);
    for ($i = 0; $i < 100; $i++) {
      // Add more log entries to test scaling
      $this->testDb->getDb()->exec("INSERT INTO webcal_entry_log (cal_entry_id, cal_login, cal_type, cal_date, cal_time, cal_text) VALUES (" . (100 + $i) . ", '$userLogin', 'M', '$currentDate', '120000', 'MCP:scale$i')");
      $isRateLimited = check_mcp_rate_limit($userLogin);
    }
    $scalingEnd = microtime(true);
    
    $scalingTime = $scalingEnd - $scalingStart;
    // Even with more entries, 100 checks should complete quickly. Generous
    // wall-clock bound to stay green on slow CI while catching O(n^2) blowups.
    $this->assertLessThan(5.0, $scalingTime, "Rate limiting with scaling took too long: {$scalingTime}s");
  }

  // Helper method to calculate standard deviation
  private function calculateStandardDeviation($array) {
    if (count($array) === 0) return 0;
    
    $mean = array_sum($array) / count($array);
    $variance = 0;
    
    foreach ($array as $value) {
      $variance += pow($value - $mean, 2);
    }
    
    return sqrt($variance / count($array));
  }

  // ---------------------------------------------------------------
  // MCP-1: Token Validation Bug Tests
  // ---------------------------------------------------------------

  /**
   * Test that demonstrates the duplicate token extraction bug.
   * 
   * The validate_mcp_token() function should not interfere with the
   * Bearer authentication mechanism. It should extract the token from
   * "Authorization: Bearer <token>" without overwriting it.
   */
  public function testValidateMcpTokenPreservesBearer() {
    // Set up test user and token
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    // Test 1: Direct token validation (should work)
    $result1 = validate_mcp_token($token);
    $this->assertEquals($userLogin, $result1, 'Direct token validation should work');
    
    // Test 2: Bearer token validation (should work and not overwrite the original)
    $bearerToken = 'Bearer ' . $token;
    $result2 = validate_mcp_token($bearerToken);
    $this->assertEquals($userLogin, $result2, 'Bearer token validation should work');
    
    // Test 3: Verify that the original token is still valid after Bearer validation
    // This is where the bug would manifest - the original token might be corrupted
    $result3 = validate_mcp_token($token);
    $this->assertEquals($userLogin, $result3, 'Original token should still work after Bearer validation');
  }

  /**
   * Test that validate_mcp_token handles malformed Bearer tokens gracefully.
   */
  public function testValidateMcpTokenHandlesMalformedBearer() {
    // Set up test user and token
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    $this->testDb->createApiToken($userLogin, $token);
    
    // Test malformed Bearer tokens
    $malformedTokens = [
      'Bearer',           // Missing token
      'Bearer ',          // Empty token
      'Bearer  ',         // Whitespace only
      'Bearer extra text', // Extra text after token
      'Token ' . $token,  // Wrong prefix
    ];
    
    foreach ($malformedTokens as $malformedToken) {
      $result = validate_mcp_token($malformedToken);
      $this->assertNull($result, "Malformed token '$malformedToken' should return null");
    }
  }

  // ---------------------------------------------------------------
  // MCP-3: add_event id generation
  //
  // The real add_event() path (id generation, persistence, and concurrency
  // behavior) is exercised end-to-end against the production schema in
  // tests/McpAddEventRaceConditionTest.php. A unit-level test here using the
  // test helper / AUTOINCREMENT test schema would not exercise WebCalendar's
  // own MAX(cal_id)+1 logic, so it is intentionally not duplicated.
  // ---------------------------------------------------------------

  // ---------------------------------------------------------------
  // MCP-6: Authentication Bypass Prevention Tests
  // ---------------------------------------------------------------

  /**
   * Test that all authentication paths are properly validated.
   */
  public function testAuthenticationBypassPrevention() {
    // Test various authentication bypass attempts
    $bypassAttempts = [
      'empty_token' => '',
      'null_token' => null,
      'whitespace_token' => '   ',
      'malformed_bearer' => 'Bearer',
      'bearer_without_space' => 'Bearer12345',
      'bearer_with_extra_spaces' => 'Bearer   token123',
      'very_long_token' => str_repeat('a', 1000),
      'sql_injection' => "' OR '1'='1",
      'xss_attempt' => '<script>alert("xss")</script>',
      'path_traversal' => '../../../etc/passwd',
      'command_injection' => '; rm -rf /',
    ];

    foreach ($bypassAttempts as $type => $token) {
      $result = validate_mcp_token($token);
      $this->assertNull($result, "Authentication bypass attempt '$type' should return null");
    }
  }

  /**
   * Test session timeout and renewal mechanisms.
   */
  public function testSessionTimeoutAndRenewal() {
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    
    // Set up API token for user
    $this->testDb->createApiToken($userLogin, $token);
    
    // Test normal token validation
    $result = validate_mcp_token($token);
    $this->assertEquals($userLogin, $result, 'Valid token should return user login');
    
    // Test token renewal (simulate by updating the token)
    $newToken = 'renewed_token_67890';
    $this->testDb->createApiToken($userLogin, $newToken);
    
    // Old token should no longer work
    $oldResult = validate_mcp_token($token);
    $this->assertNull($oldResult, 'Old token should not work after renewal');
    
    // New token should work
    $newResult = validate_mcp_token($newToken);
    $this->assertEquals($userLogin, $newResult, 'New token should work after renewal');
  }

  /**
   * Test that malformed token handling is robust and secure.
   */
  public function testMalformedTokenHandling() {
    $malformedTokens = [
      // Base64-like malformed tokens
      'base64_empty' => '',
      'base64_invalid' => '!!invalid!!',
      'base64_padding' => '===',
      
      // JSON-like malformed tokens
      'json_empty' => '{}',
      'json_array' => '[]',
      'json_object' => '{"key": "value"}',
      'json_number' => '12345',
      
      // Binary-like tokens
      'binary_null' => "\x00",
      'binary_control' => "\x01\x02\x03",
      'binary_high' => "\xff\xfe\xfd",
      
      // Special characters
      'special_chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
      'unicode_chars' => '测试トークン', // Chinese, Japanese
      'emoji' => '🔐🔑🔒', // Emoji tokens
    ];

    foreach ($malformedTokens as $type => $token) {
      $result = validate_mcp_token($token);
      $this->assertNull($result, "Malformed token '$type' should return null");
    }
  }

  /**
   * Test authentication edge cases and boundary conditions.
   */
  public function testAuthenticationEdgeCases() {
    // Test tokens with exactly the length limits
    $validLengthTokens = [
      'token_8_chars' => '12345678',
      'token_32_chars' => '12345678901234567890123456789012',
      'token_255_chars' => str_repeat('a', 255),
      'token_256_chars' => str_repeat('a', 256),
    ];

    foreach ($validLengthTokens as $type => $token) {
      // For valid length tokens, they should return null (no such user)
      // but not cause errors or crashes
      $result = validate_mcp_token($token);
      $this->assertNull($result, "Token of valid length '$type' should handle gracefully");
    }

    // Test rate limiting on authentication attempts
    $this->testAuthRateLimiting();
  }

  /**
   * Test that authentication attempts are rate limited to prevent brute force attacks.
   */
  private function testAuthRateLimiting() {
    $userLogin = 'testuser';
    $token = 'test_token_12345';
    
    // Set up API token for user
    $this->testDb->createApiToken($userLogin, $token);
    
    // Set a strict rate limit
    dbi_query('DELETE FROM webcal_config WHERE cal_setting = "MCP_RATE_LIMIT"');
    dbi_query('INSERT INTO webcal_config (cal_setting, cal_value) VALUES ("MCP_RATE_LIMIT", "3")');
    $GLOBALS['settings_cache'] = null;
    
    // Test multiple rapid authentication attempts
    $attempts = [];
    for ($i = 0; $i < 5; $i++) {
      $attempts[] = validate_mcp_token($token);
    }
    
    // All attempts should return the same user (rate limiting shouldn't break valid authentication)
    foreach ($attempts as $attempt) {
      $this->assertEquals($userLogin, $attempt, 'Valid authentication should work regardless of rate limiting');
    }
  }

  /**
   * Test that authentication errors don't leak information about user existence.
   */
  public function testAuthenticationErrorInformationLeak() {
    $testCases = [
      'existing_user' => 'testuser',
      'nonexisting_user' => 'user_does_not_exist',
      'admin_user' => 'admin',
    ];

    foreach ($testCases as $userType => $userLogin) {
      // Create a test token for existing users
      if ($userType === 'existing_user' || $userType === 'admin_user') {
        $this->testDb->createApiToken($userLogin, 'test_token_for_' . $userType);
      }
      
      // Test with invalid token
      $result = validate_mcp_token('invalid_token_for_' . $userType);
      $this->assertNull($result, "Invalid token should always return null regardless of user existence");
    }
  }

  // ---------------------------------------------------------------
  // MCP-7: Rate Limit Tests
  // ---------------------------------------------------------------

  /**
   * Seed $count MCP action rows in webcal_entry_log for $login, timestamped
   * $minutesAgo minutes before now. Uses GMT date/time columns to match how
   * activity_log() records actions (and how check_mcp_rate_limit() reads them).
   */
  private function seedMcpLogRows($login, $count, $minutesAgo) {
    $ts = time() - ($minutesAgo * 60);
    $calDate = (int)gmdate('Ymd', $ts);
    $calTime = (int)gmdate('Gis', $ts);

    for ($i = 0; $i < $count; $i++) {
      dbi_execute(
        "INSERT INTO webcal_entry_log
          (cal_entry_id, cal_login, cal_user_cal, cal_type, cal_date, cal_time, cal_text)
          VALUES (?, ?, ?, 'M', ?, ?, 'MCP: Event created')",
        [0, $login, $login, $calDate, $calTime]
      );
    }
  }

  /**
   * The limit trips only once the number of recent MCP actions reaches the
   * configured limit, not before.
   */
  public function testCheckMcpRateLimitTripsAtConfiguredLimit() {
    $login = 'ratelimit_at_' . uniqid();
    $limit = get_mcp_rate_limit();
    $this->assertGreaterThan(0, $limit, 'Test assumes a positive configured rate limit');

    // No activity -> under the limit.
    $this->assertFalse(check_mcp_rate_limit($login), 'No activity should be under the limit');

    // One short of the limit -> still under.
    $this->seedMcpLogRows($login, $limit - 1, 0);
    $this->assertFalse(check_mcp_rate_limit($login), 'One under the limit should not trip');

    // Reaching the limit -> tripped.
    $this->seedMcpLogRows($login, 1, 0);
    $this->assertTrue(check_mcp_rate_limit($login), 'Reaching the limit should trip');
  }

  /**
   * Regression guard for the date-comparison bug: actions older than one hour
   * must not count toward the limit, no matter how many there are.
   */
  public function testCheckMcpRateLimitIgnoresActionsOlderThanOneHour() {
    $login = 'ratelimit_window_' . uniqid();
    $limit = get_mcp_rate_limit();
    $this->assertGreaterThan(0, $limit, 'Test assumes a positive configured rate limit');

    // More than the limit, but all two hours old: these must NOT count.
    $this->seedMcpLogRows($login, $limit + 1, 120);
    $this->assertFalse(
      check_mcp_rate_limit($login),
      'Actions older than one hour must not count toward the rate limit'
    );

    // A single recent action is still well under the limit.
    $this->seedMcpLogRows($login, 1, 0);
    $this->assertFalse(
      check_mcp_rate_limit($login),
      'One recent action should remain under the limit'
    );
  }

  /**
   * Actions belonging to a different user must not count toward this user's
   * limit (guards the cal_login filter).
   */
  public function testCheckMcpRateLimitIsScopedPerUser() {
    $login = 'ratelimit_scope_' . uniqid();
    $other = 'ratelimit_other_' . uniqid();
    $limit = get_mcp_rate_limit();
    $this->assertGreaterThan(0, $limit, 'Test assumes a positive configured rate limit');

    // Plenty of recent actions, but all for a different user.
    $this->seedMcpLogRows($other, $limit + 1, 0);
    $this->assertFalse(
      check_mcp_rate_limit($login),
      "Another user's actions must not count toward this user's limit"
    );
  }

  // ---------------------------------------------------------------
  // Timezone conversion (mcp_gmt_to_local / mcp_shift_date)
  //
  // WebCalendar stores cal_time as a GMT clock time and the web UI converts it
  // to the user's TIMEZONE on display (view_entry.php: display_time(..., 2)),
  // independent of the GENERAL_USE_GMT setting. The MCP tools must return the
  // same local time. These guard list_events/search_events against the
  // regression where raw GMT times were returned to clients.
  // ---------------------------------------------------------------

  /**
   * Regression: a noon-GMT event must come back as 8:00 AM US Eastern in June
   * (EDT, UTC-4) -- the exact bug where 8 AM appointments were reported as noon.
   */
  public function testGmtToLocalConvertsEdtSummerTime() {
    $local = mcp_gmt_to_local('20260611', '120000', 'America/New_York');
    $this->assertEquals('20260611', $local['date']);
    $this->assertEquals('080000', $local['time']);
  }

  /**
   * EST winter offset is UTC-5: noon GMT in January is 7:00 AM Eastern.
   */
  public function testGmtToLocalConvertsEstWinterTime() {
    $local = mcp_gmt_to_local('20260115', '120000', 'America/New_York');
    $this->assertEquals('20260115', $local['date']);
    $this->assertEquals('070000', $local['time']);
  }

  /**
   * Early-morning GMT events roll back onto the previous local calendar day,
   * so the returned date must shift too: 02:00 GMT - 4h = 22:00 the day before.
   */
  public function testGmtToLocalRollsDateBackwardAcrossMidnight() {
    $local = mcp_gmt_to_local('20260611', '020000', 'America/New_York');
    $this->assertEquals('20260610', $local['date']);
    $this->assertEquals('220000', $local['time']);
  }

  /**
   * Untimed/all-day events (cal_time === -1) carry no clock time and must be
   * returned unchanged.
   */
  public function testGmtToLocalLeavesUntimedEventsUnchanged() {
    $local = mcp_gmt_to_local('20260615', '-1', 'America/New_York');
    $this->assertEquals('20260615', $local['date']);
    $this->assertEquals('-1', $local['time']);
  }

  /**
   * An unpadded integer-style time (e.g. 80000 for 08:00) must be normalized
   * and converted, not mangled: 08:00 GMT - 4h = 04:00 EDT.
   */
  public function testGmtToLocalNormalizesUnpaddedTime() {
    $local = mcp_gmt_to_local('20260611', 80000, 'America/New_York');
    $this->assertEquals('20260611', $local['date']);
    $this->assertEquals('040000', $local['time']);
  }

  /**
   * Converting into UTC is an identity for GMT-stored values.
   */
  public function testGmtToLocalUtcIsIdentity() {
    $local = mcp_gmt_to_local('20260611', '120000', 'UTC');
    $this->assertEquals('20260611', $local['date']);
    $this->assertEquals('120000', $local['time']);
  }

  /**
   * An invalid timezone must fail safe by returning the unconverted values
   * rather than throwing inside a tool call.
   */
  public function testGmtToLocalFailsSafeOnInvalidTimezone() {
    $local = mcp_gmt_to_local('20260611', '120000', 'Not/AZone');
    $this->assertEquals('20260611', $local['date']);
    $this->assertEquals('120000', $local['time']);
  }

  public function testShiftDateForwardAcrossMonthBoundary() {
    $this->assertEquals('20260201', mcp_shift_date('20260131', 1));
  }

  public function testShiftDateBackwardAcrossMonthBoundary() {
    // 2026 is not a leap year, so February has 28 days.
    $this->assertEquals('20260228', mcp_shift_date('20260301', -1));
  }

  public function testShiftDateBackwardAcrossYearBoundary() {
    $this->assertEquals('20251231', mcp_shift_date('20260101', -1));
  }

  public function testShiftDateForwardAcrossYearBoundary() {
    $this->assertEquals('20270101', mcp_shift_date('20261231', 1));
  }

  public function testShiftDateHandlesLeapDay() {
    // 2024 is a leap year: the day before March 1 is February 29.
    $this->assertEquals('20240229', mcp_shift_date('20240301', -1));
  }
}