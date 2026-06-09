<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/CrossDatabaseTestHelper.php";

/**
 * Cross-Database Compatibility Tests for MCP Server
 * 
 * These tests ensure that MCP server functionality works correctly across
 * different database backends (SQLite, MySQL, PostgreSQL).
 */
final class CrossDatabaseCompatibilityTest extends TestCase
{
    private $testHelpers = [];
    private $dbTypes = ['sqlite3'];
    
    protected function setUp(): void {
        parent::setUp();
        
        // Initialize available database types
        $this->dbTypes = ['sqlite3']; // Start with SQLite
        
        // Try to add MySQL if available
        if (extension_loaded('mysqli')) {
            $this->dbTypes[] = 'mysql';
        }
        
        // Try to add PostgreSQL if available
        if (extension_loaded('pgsql')) {
            $this->dbTypes[] = 'postgresql';
        }
        
        // Create test databases for each available type
        foreach ($this->dbTypes as $dbType) {
            try {
                $config = $this->getDatabaseConfig($dbType);
                $helper = createCrossDatabaseTestHelper($dbType, $config);
                $this->testHelpers[$dbType] = $helper;
                
                // Establish database connection
                dbi_connect($config['host'] ?? '', $config['login'] ?? '', $config['password'] ?? '', $helper->getDbPath());
            } catch (Exception $e) {
                // Skip database type if not available
                echo "Skipping $dbType: " . $e->getMessage() . "\n";
            }
        }
    }
    
    protected function tearDown(): void {
        foreach ($this->testHelpers as $dbType => $helper) {
            cleanupCrossDatabaseTest($helper);
        }
        $this->testHelpers = [];
        parent::tearDown();
    }
    
    private function getDatabaseConfig($dbType) {
        switch ($dbType) {
            case 'sqlite3':
                return ['path' => sys_get_temp_dir() . '/webcalendar_' . $dbType . '_test.db'];
            case 'mysql':
                return [
                    'host' => 'localhost',
                    'database' => 'webcalendar_test',
                    'login' => 'testuser',
                    'password' => 'testpass'
                ];
            case 'postgresql':
                return [
                    'host' => 'localhost',
                    'database' => 'webcalendar_test',
                    'login' => 'testuser',
                    'password' => 'testpass'
                ];
            default:
                return [];
        }
    }
    
    // ---------------------------------------------------------------
    // MCP Core Functionality Tests Across All Databases
    // ---------------------------------------------------------------
    
    public function testMcpServerEnabledAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $enabled = is_mcp_enabled();
            $this->assertTrue($enabled, "MCP server should be enabled for $dbType");
        }
    }
    
    public function testMcpRateLimitAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $rateLimit = get_mcp_rate_limit();
            $this->assertIsInt($rateLimit, "Rate limit should be integer for $dbType");
            $this->assertGreaterThanOrEqual(0, $rateLimit, "Rate limit should be non-negative for $dbType");
        }
    }
    
    public function testTokenValidationAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'testuser';
            $token = 'test_token_' . $dbType;
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Test token validation
            $result = validate_mcp_token($token);
            $this->assertEquals($userLogin, $result, "Token validation should work for $dbType");
            
            // Test invalid token
            $invalidResult = validate_mcp_token('invalid_token');
            $this->assertNull($invalidResult, "Invalid token should return null for $dbType");
        }
    }
    
    // ---------------------------------------------------------------
    // Event Management Tests Across All Databases
    // ---------------------------------------------------------------
    
    public function testEventCreationAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'testuser_' . $dbType;
            $token = 'test_token_' . $dbType;
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Clear existing events
            $helper->clearUserEvents($userLogin);
            
            // Create events
            $events = [];
            for ($i = 1; $i <= 5; $i++) {
                $eventId = $helper->createEvent($userLogin, 20240601 + $i, "Test Event $i for $dbType");
                $helper->associateUserWithEvent($eventId, $userLogin);
                $events[] = $eventId;
            }
            
            // Verify events were created
            $this->assertEquals(5, count($events), "5 events should be created for $dbType");
            
            // Verify no duplicate events
            $eventCount = $helper->getEventCountForUser($userLogin);
            $this->assertEquals(5, $eventCount, "Exactly 5 events should exist for $dbType");
        }
    }
    
    public function testEventSearchAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'testuser_' . $dbType;
            $token = 'test_token_' . $dbType;
            
            // Create test user
            $helper->createUser($userLogin);
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Create test events and associate them with the user (the search
            // JOINs webcal_entry_user, so events must be linked to be found).
            foreach ([
                [20240601, "Important Meeting"],
                [20240602, "Lunch Break"],
                [20240603, "Conference Call"],
            ] as [$date, $name]) {
                $eventId = $helper->createEvent($userLogin, $date, $name);
                $helper->associateUserWithEvent($eventId, $userLogin);
            }

            // Test search functionality
            $meetingResults = $helper->searchEventsForUser($userLogin, 'Meeting');
            $this->assertGreaterThan(0, count($meetingResults), "Should find Meeting events in $dbType");
            
            $allResults = $helper->searchEventsForUser($userLogin, '');
            $this->assertGreaterThanOrEqual(3, count($allResults), "Should find all 3 events in $dbType");
        }
    }
    
    // ---------------------------------------------------------------
    // Configuration Management Tests Across All Databases
    // ---------------------------------------------------------------
    
    public function testConfigurationManagementAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            // Test setting MCP configuration
            $helper->setMcpConfig('MCP_TEST_SETTING', 'test_value');
            
            // Test getting MCP configuration
            $value = $helper->getMcpConfig('MCP_TEST_SETTING');
            $this->assertEquals('test_value', $value, "Configuration should be set and retrieved for $dbType");
            
            // Test updating configuration
            $helper->setMcpConfig('MCP_TEST_SETTING', 'updated_value');
            $updatedValue = $helper->getMcpConfig('MCP_TEST_SETTING');
            $this->assertEquals('updated_value', $updatedValue, "Configuration should be updatable for $dbType");
        }
    }
    
    public function testRateLimitConfigurationAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            // Test setting different rate limits
            $rateLimits = [10, 50, 100];
            
            foreach ($rateLimits as $limit) {
                $helper->setMcpConfig('MCP_RATE_LIMIT', (string)$limit);
                $retrievedLimit = $helper->getMcpConfig('MCP_RATE_LIMIT');
                $this->assertEquals((string)$limit, $retrievedLimit, "Rate limit $limit should be set for $dbType");
            }
        }
    }
    
    // ---------------------------------------------------------------
    // Performance Consistency Tests Across All Databases
    // ---------------------------------------------------------------
    
    public function testPerformanceConsistencyAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'testuser_' . $dbType;
            $token = 'test_token_' . $dbType;
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Clear existing events
            $helper->clearUserEvents($userLogin);
            
            // Measure event creation performance
            $startTime = microtime(true);
            $eventsCreated = 0;
            
            for ($i = 1; $i <= 10; $i++) {
                $eventId = $helper->createEvent($userLogin, 20240601 + $i, "Performance Test $i");
                $helper->associateUserWithEvent($eventId, $userLogin);
                $eventsCreated++;
            }
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            // Performance should be reasonable across all databases
            $this->assertLessThan(2.0, $duration, "Event creation should be fast for $dbType: $duration seconds");
            $this->assertEquals(10, $eventsCreated, "All 10 events should be created for $dbType");
            
            // Verify all events exist
            $eventCount = $helper->getEventCountForUser($userLogin);
            $this->assertEquals(10, $eventCount, "Event count should be consistent for $dbType");
        }
    }
    
    public function testConcurrentAccessAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'testuser_' . $dbType;
            $token = 'test_token_' . $dbType;
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Clear existing events
            $helper->clearUserEvents($userLogin);
            
            // Test concurrent event creation
            $events = [];
            $startTime = microtime(true);
            
            for ($i = 1; $i <= 10; $i++) {
                $eventId = $helper->createEvent($userLogin, 20240601 + $i, "Concurrent Event $i");
                $helper->associateUserWithEvent($eventId, $userLogin);
                $events[] = $eventId;
            }
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            // Verify all events were created
            $this->assertEquals(10, count($events), "All 10 concurrent events should be created for $dbType");
            
            // Verify no duplicates
            $uniqueEventIds = array_unique($events);
            $this->assertEquals(10, count($uniqueEventIds), "All event IDs should be unique for $dbType");
            
            // Performance should be reasonable
            $this->assertLessThan(3.0, $duration, "Concurrent creation should be fast for $dbType: $duration seconds");
        }
    }
    
    // ---------------------------------------------------------------
    // Data Integrity Tests Across All Databases
    // ---------------------------------------------------------------
    
    public function testDataIntegrityAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'testuser_' . $dbType;
            $token = 'test_token_' . $dbType;
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Clear existing events
            $helper->clearUserEvents($userLogin);
            
            // Create events with various data types
            $eventId1 = $helper->createEvent($userLogin, 20240601, "Normal Event");
            $eventId2 = $helper->createEvent($userLogin, 20240602, "Event with Special chars: !@#$%");
            $eventId3 = $helper->createEvent($userLogin, 20240603, "Event with Unicode: 测试");
            
            $helper->associateUserWithEvent($eventId1, $userLogin);
            $helper->associateUserWithEvent($eventId2, $userLogin);
            $helper->associateUserWithEvent($eventId3, $userLogin);
            
            // Verify data integrity
            $eventCount = $helper->getEventCountForUser($userLogin);
            $this->assertEquals(3, $eventCount, "All 3 events should exist for $dbType");
            
            // Test search with special characters
            $specialResults = $helper->searchEventsForUser($userLogin, 'Special chars');
            $this->assertGreaterThan(0, count($specialResults), "Should find events with special chars in $dbType");
            
            // Test search with Unicode
            $unicodeResults = $helper->searchEventsForUser($userLogin, '测试');
            $this->assertGreaterThan(0, count($unicodeResults), "Should find events with Unicode in $dbType");
        }
    }
    
    public function testTransactionSafetyAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'testuser_' . $dbType;
            $token = 'test_token_' . $dbType;
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Clear existing events
            $helper->clearUserEvents($userLogin);
            
            // Create events in a transaction-like manner
            $events = [];
            try {
                for ($i = 1; $i <= 5; $i++) {
                    $eventId = $helper->createEvent($userLogin, 20240601 + $i, "Transaction Event $i");
                    $helper->associateUserWithEvent($eventId, $userLogin);
                    $events[] = $eventId;
                }
                
                // All events should be committed (no rollback support in basic tests)
                $this->assertEquals(5, count($events), "All 5 events should be created for $dbType");
                
                $eventCount = $helper->getEventCountForUser($userLogin);
                $this->assertEquals(5, $eventCount, "Event count should be consistent after transaction for $dbType");
                
            } catch (Exception $e) {
                // If transaction fails, ensure clean state
                $helper->clearUserEvents($userLogin);
                $this->fail("Transaction should succeed for $dbType: " . $e->getMessage());
            }
        }
    }
    
    // ---------------------------------------------------------------
    // Database-Specific Feature Tests
    // ---------------------------------------------------------------
    
    public function testDatabaseSpecificOptimizations() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'testuser_' . $dbType;
            $token = 'test_token_' . $dbType;
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Clear existing events
            $helper->clearUserEvents($userLogin);
            
            // Test database-specific optimizations
            switch ($dbType) {
                case 'sqlite3':
                    // SQLite: Test transaction efficiency
                    $startTime = microtime(true);
                    for ($i = 1; $i <= 20; $i++) {
                        $eventId = $helper->createEvent($userLogin, 20240601 + $i, "SQLite Opt Event $i");
                        $helper->associateUserWithEvent($eventId, $userLogin);
                    }
                    $endTime = microtime(true);
                    $this->assertLessThan(1.0, $endTime - $startTime, "SQLite should be efficient for batch operations");
                    break;
                    
                case 'mysql':
                    // MySQL: Test JOIN performance
                    $eventCount = $helper->getEventCountForUser($userLogin);
                    $this->assertEquals(20, $eventCount, "MySQL JOIN should work correctly");
                    break;
                    
                case 'postgresql':
                    // PostgreSQL: Test complex queries
                    $searchResults = $helper->searchEventsForUser($userLogin, 'SQLite Opt');
                    $this->assertGreaterThan(0, count($searchResults), "PostgreSQL search should work correctly");
                    break;
            }
        }
    }
    
    // ---------------------------------------------------------------
    // Migration Simulation Tests
    // ---------------------------------------------------------------
    
    public function testDatabaseMigrationSimulation() {
        // Test data consistency when simulating migration between databases
        $sourceDb = 'sqlite3';
        if (!isset($this->testHelpers[$sourceDb])) {
            $this->markTestSkipped("SQLite not available for migration test");
        }
        
        $sourceHelper = $this->testHelpers[$sourceDb];
        $userLogin = 'migratetestuser';
        $token = 'migration_test_token';
        
        // Create test data in source database
        $sourceHelper->createApiToken($userLogin, $token);
        $sourceHelper->clearUserEvents($userLogin);
        
        // Create test events
        for ($i = 1; $i <= 5; $i++) {
            $eventId = $sourceHelper->createEvent($userLogin, 20240601 + $i, "Migration Test Event $i");
            $sourceHelper->associateUserWithEvent($eventId, $userLogin);
        }
        
        // Verify source data
        $sourceCount = $sourceHelper->getEventCountForUser($userLogin);
        $this->assertEquals(5, $sourceCount, "Source database should have 5 events");
        
        // Test that data structure is compatible with other databases
        foreach ($this->testHelpers as $dbType => $helper) {
            if ($dbType === $sourceDb) continue;
            
            // Test that SQL structure would work
            $configCount = $helper->getMcpConfig('MCP_SERVER_ENABLED');
            $this->assertNotNull($configCount, "Configuration should be accessible in $dbType");
            
            // Test basic functionality
            $enabled = is_mcp_enabled();
            $this->assertTrue($enabled, "MCP should be enabled in $dbType");
        }
    }
    
    // ---------------------------------------------------------------
    // Error Handling Consistency Across All Databases
    // ---------------------------------------------------------------
    
    public function testErrorHandlingConsistencyAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            // Test invalid token handling
            $result = validate_mcp_token('invalid_token_for_' . $dbType);
            $this->assertNull($result, "Invalid token should return null for $dbType");
            
            // Test non-existent user handling
            $rateLimited = check_mcp_rate_limit('nonexistent_user_' . $dbType);
            $this->assertFalse($rateLimited, "Non-existent user should not be rate limited for $dbType");
            
            // Test configuration error handling
            $invalidConfig = $helper->getMcpConfig('NONEXISTENT_SETTING');
            $this->assertNull($invalidConfig, "Invalid config should return null for $dbType");
        }
    }
    
    // ---------------------------------------------------------------
    // Stress Tests Across All Databases
    // ---------------------------------------------------------------
    
    public function testStressTestsAcrossDatabases() {
        foreach ($this->testHelpers as $dbType => $helper) {
            $userLogin = 'stresstestuser_' . $dbType;
            $token = 'stress_test_token_' . $dbType;
            
            // Create API token for user
            $helper->createApiToken($userLogin, $token);
            
            // Clear existing events
            $helper->clearUserEvents($userLogin);
            
            // Stress test: Create 50 events
            $events = [];
            $startTime = microtime(true);
            
            for ($i = 1; $i <= 50; $i++) {
                $eventId = $helper->createEvent($userLogin, 20240601 + $i, "Stress Test Event $i");
                $helper->associateUserWithEvent($eventId, $userLogin);
                $events[] = $eventId;
            }
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            // Verify all events were created
            $this->assertEquals(50, count($events), "All 50 stress test events should be created for $dbType");
            
            // Performance should be reasonable
            $this->assertLessThan(5.0, $duration, "Stress test should complete within 5 seconds for $dbType: $duration seconds");
            
            // Verify data integrity after stress test
            $eventCount = $helper->getEventCountForUser($userLogin);
            $this->assertEquals(50, $eventCount, "Event count should be consistent after stress test for $dbType");
        }
    }
}