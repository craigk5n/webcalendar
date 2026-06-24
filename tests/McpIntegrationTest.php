<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * MCP Integration Tests with real SQLite database
 *
 * These tests use a temporary SQLite database created on-the-fly,
 * run the headless installer, and test MCP functionality with
 * real data. This enables proper TDD for MCP bugs and features.
 *
 * Tests are designed to be fast and self-contained.
 */
final class McpIntegrationTest extends TestCase
{
    private static $db_dir = null;
    private static $db_file = null;
    private static $api_token = null;
    private static $server_pid = null;
    private static $server_port = 8099;

    // ---------------------------------------------------------------
    // Static setup/teardown for the entire test suite
    // ---------------------------------------------------------------

    public static function setUpBeforeClass(): void
    {
        self::$db_dir = sys_get_temp_dir();
        self::$db_file = self::$db_dir . '/mcp_test.sqlite';

        // Create database directory
        if (!file_exists(self::$db_dir)) {
            mkdir(self::$db_dir, 0755, true);
        }

        // Start from a clean database file.
        if (file_exists(self::$db_file)) {
            unlink(self::$db_file);
        }

        // Install the schema and run the server in environment-variable mode
        // (WEBCALENDAR_USE_ENV) so the live includes/settings.php is never read
        // or written by these tests.
        self::runHeadlessInstaller();

        // Enable MCP and generate API token
        self::setupMcpServer();

        // Start PHP built-in server
        self::startPhpServer();

        // Set up database connection for tests
        self::setupDatabaseConnection();
    }

    public static function tearDownAfterClass(): void
    {
        // Stop the PHP server (best-effort). SIGTERM/SIGKILL are only defined
        // when the pcntl extension is loaded, which is not guaranteed in every
        // CLI environment, so fall back to their POSIX integer values and
        // never let a failure here abort the rest of teardown — restoring
        // settings.php below is the part that must always run.
        try {
            if (self::$server_pid && function_exists('posix_kill') && posix_kill(self::$server_pid, 0)) {
                $sigterm = defined('SIGTERM') ? SIGTERM : 15;
                $sigkill = defined('SIGKILL') ? SIGKILL : 9;
                posix_kill(self::$server_pid, $sigterm);
                sleep(1);
                if (posix_kill(self::$server_pid, 0)) {
                    posix_kill(self::$server_pid, $sigkill);
                }
            }
        } catch (\Throwable $e) {
            // Ignore: server cleanup is best-effort.
        }

        // Remove test database
        if (file_exists(self::$db_file)) {
            unlink(self::$db_file);
        }
    }

    // ---------------------------------------------------------------
    // Individual test setup/teardown
    // ---------------------------------------------------------------

    protected function setUp(): void
    {
        // Clean up any test data from previous test
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        // Clean up test data after each test
        $this->cleanupTestData();
    }

    // ---------------------------------------------------------------
    // Helper methods
    // ---------------------------------------------------------------

    private static function runHeadlessInstaller(): void
    {
        $project_dir = __DIR__ . '/..';
        // --use-env makes the installer skip writing includes/settings.php;
        // the SQLite target is supplied via the CLI args below.
        $cmd = sprintf(
            'php %s/wizard/headless.php --use-env --db-type=sqlite3 --db-database=%s --admin-login=admin --admin-password=admin --install-password=test123 --force',
            $project_dir,
            self::$db_file
        );

        $output = [];
        $return_var = null;
        exec($cmd, $output, $return_var);

        if ($return_var !== 0 || !implode('', $output)) {
            throw new RuntimeException("Headless installer failed: " . implode("\n", $output));
        }

        if (!strpos(implode('', $output), 'Installation Complete')) {
            throw new RuntimeException("Installation did not complete successfully: " . implode("\n", $output));
        }
    }

    private static function setupMcpServer(): void
    {
        // Generate API token
        self::$api_token = bin2hex(random_bytes(32));

        // Enable MCP in database
        $db = new SQLite3(self::$db_file);
        
        // Enable MCP server
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_SERVER_ENABLED', 'Y');");
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_WRITE_ACCESS', 'Y');");
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_RATE_LIMIT', '100');");
        
        // Set API token for admin user
        $db->exec(sprintf("UPDATE webcal_user SET cal_api_token = '%s' WHERE cal_login = 'admin';", self::$api_token));
        
        $db->close();
    }

    private static function startPhpServer(): void
    {
        $project_dir = __DIR__ . '/..';
        // Env-variable mode so the server never reads includes/settings.php.
        $env = sprintf(
            'MCP_TOKEN= WEBCALENDAR_USE_ENV=true WEBCALENDAR_DB_TYPE=sqlite3 WEBCALENDAR_DB_DATABASE=%s',
            self::$db_file
        );
        $cmd = sprintf('%s php -S localhost:%d -t %s > /tmp/mcp-test-server.log 2>&1 & echo $!', $env, self::$server_port, $project_dir);
        
        exec($cmd, $output);
        self::$server_pid = (int)$output[0];
        
        // Wait for server to start
        sleep(2);
        
        // Check if server is running by testing MCP endpoint with auth
        $output = [];
        exec(sprintf('curl -s -o /dev/null -w %%{http_code} -X POST -H "Content-Type: application/json" -H "X-MCP-Token: %s" -d \'{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}\' http://localhost:%d/mcp.php', self::$api_token, self::$server_port), $output);
        
        if ($output[0] != '200') {
            throw new RuntimeException("Failed to start PHP server. Log: " . file_get_contents('/tmp/mcp-test-server.log'));
        }
    }

    private static function setupDatabaseConnection(): void
    {
        // Set up global database connection for functions.php
        global $db_type, $db_host, $db_database, $db_login, $db_password;
        
        $db_type = 'sqlite3';
        $db_host = 'localhost';
        $db_database = self::$db_file;
        $db_login = '';
        $db_password = '';

        // Initialize database connection
        dbi_connect($db_type, $db_host, $db_database, $db_login, $db_password);
    }

    private function cleanupTestData(): void
    {
        // Clean up test events and users created during tests
        $db = new SQLite3(self::$db_file);
        
        // Remove test events (those with names starting with "MCP Test")
        $db->exec("DELETE FROM webcal_entry_user WHERE cal_id IN (SELECT cal_id FROM webcal_entry WHERE cal_name LIKE 'MCP Test%');");
        $db->exec("DELETE FROM webcal_entry WHERE cal_name LIKE 'MCP Test%';");
        
        // Remove test users (login starting with 'mcp_test_')
        $db->exec("DELETE FROM webcal_user WHERE cal_login LIKE 'mcp_test_%';");
        
        // Clean up MCP activity log entries
        $db->exec("DELETE FROM webcal_entry_log WHERE cal_text LIKE 'MCP:%';");
        
        $db->close();
    }

    // ---------------------------------------------------------------
    // HTTP helper methods for MCP testing
    // ---------------------------------------------------------------

    private function mcpCall(string $method, array $params, $id = 1, $token = null): array
    {
        $token = $token ?? self::$api_token;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => $method,
                'params' => $params
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-MCP-Token: ' . $token
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new RuntimeException("HTTP $http_code: $response");
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON response: " . json_last_error_msg());
        }
        
        return $data;
    }

    private function assertMcpSuccess(array $response): void
    {
        $this->assertArrayHasKey('jsonrpc', $response);
        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('result', $response);
        $this->assertArrayNotHasKey('error', $response);
    }

    private function assertMcpError(array $response, int $expected_code, string $expected_message): void
    {
        $this->assertArrayHasKey('jsonrpc', $response);
        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals($expected_code, $response['error']['code']);
        $this->assertStringContainsString($expected_message, $response['error']['message']);
    }

    // ---------------------------------------------------------------
    // Test MCP server availability
    // ---------------------------------------------------------------

    public function test_mcp_server_is_enabled(): void
    {
        // Test that MCP server responds correctly with valid token
        $response = $this->mcpCall('initialize', []);
        $this->assertMcpSuccess($response);
        $this->assertEquals('2024-11-05', $response['result']['protocolVersion']);
    }

    // Skip these tests for now - they require full WebCalendar setup
    // The MCP endpoint tests verify the functionality works end-to-end

    // ---------------------------------------------------------------
    // Test MCP authentication
    // ---------------------------------------------------------------

    public function test_mcp_authentication_with_valid_token(): void
    {
        $response = $this->mcpCall('initialize', []);
        $this->assertMcpSuccess($response);
        $this->assertEquals('2024-11-05', $response['result']['protocolVersion']);
    }

    public function test_mcp_authentication_with_invalid_token(): void
    {
        $response = $this->mcpCall('initialize', [], 1, 'invalid_token');
        $this->assertMcpError($response, -32600, 'Invalid API token');
    }

    public function test_mcp_authentication_with_missing_token(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => []
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        $this->assertMcpError($data, -32600, 'API token required');
    }

    // ---------------------------------------------------------------
    // Test MCP tools discovery
    // ---------------------------------------------------------------

    public function test_mcp_tools_list(): void
    {
        $response = $this->mcpCall('tools/list', []);
        $this->assertMcpSuccess($response);
        
        $tools = $response['result']['tools'];
        $this->assertCount(4, $tools, "Should have 4 tools");
        
        $tool_names = array_column($tools, 'name');
        $this->assertContains('list_events', $tool_names);
        $this->assertContains('get_user_info', $tool_names);
        $this->assertContains('search_events', $tool_names);
        $this->assertContains('add_event', $tool_names);
    }

    // ---------------------------------------------------------------
    // Test MCP tool execution (basic functionality)
    // ---------------------------------------------------------------

    public function test_get_user_info_tool(): void
    {
        $response = $this->mcpCall('tools/call', ['name' => 'get_user_info', 'arguments' => []]);
        $this->assertMcpSuccess($response);
        
        $result = $response['result'];
        $this->assertArrayHasKey('login', $result);
        $this->assertArrayHasKey('firstname', $result);
        $this->assertArrayHasKey('lastname', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('admin', $result['login']);
    }

    public function test_list_events_tool_with_no_events(): void
    {
        $response = $this->mcpCall('tools/call', [
            'name' => 'list_events',
            'arguments' => ['start_date' => '20260101', 'end_date' => '20260101']
        ]);
        $this->assertMcpSuccess($response);
        
        $result = $response['result'];
        $this->assertArrayHasKey('events', $result);
        $this->assertIsArray($result['events']);
        $this->assertCount(0, $result['events']);
    }

    // ---------------------------------------------------------------
    // Test MCP tool execution with test data
    // ---------------------------------------------------------------

    public function test_add_event_tool(): void
    {
        // First, let's add a test event
        $response = $this->mcpCall('tools/call', [
            'name' => 'add_event',
            'arguments' => [
                'name' => 'MCP Test Event',
                'date' => '20260101',
                'description' => 'Created by integration test',
                'location' => 'Test Location',
                'duration' => 60
            ]
        ]);
        
        $this->assertMcpSuccess($response);
        $this->assertArrayHasKey('event_id', $response['result']);
        $event_id = $response['result']['event_id'];
        $this->assertIsInt($event_id);
        $this->assertGreaterThan(0, $event_id);
        
        // Verify the event exists in the database
        $db = new SQLite3(self::$db_file);
        $result = $db->querySingle("SELECT COUNT(*) FROM webcal_entry WHERE cal_id = $event_id");
        $db->close();
        
        $this->assertEquals(1, $result, "Event should exist in database");
    }

    public function test_search_events_tool(): void
    {
        // Add a test event first
        $this->mcpCall('tools/call', [
            'name' => 'add_event',
            'arguments' => [
                'name' => 'MCP Test Search Event',
                'date' => '20260102',
                'description' => 'Searchable event description'
            ]
        ]);
        
        // Test search
        $response = $this->mcpCall('tools/call', [
            'name' => 'search_events',
            'arguments' => ['keyword' => 'Search', 'limit' => 10]
        ]);
        
        $this->assertMcpSuccess($response);
        $result = $response['result'];
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertIsInt($result['total']);
        $this->assertGreaterThanOrEqual(1, $result['total']);
    }

    // ---------------------------------------------------------------
    // Test error handling
    // ---------------------------------------------------------------

    public function test_unknown_method_returns_error(): void
    {
        $response = $this->mcpCall('nonexistent/method', []);
        $this->assertMcpError($response, -32601, 'Method not found');
    }

    public function test_unknown_tool_returns_error(): void
    {
        $response = $this->mcpCall('tools/call', [
            'name' => 'nonexistent_tool',
            'arguments' => []
        ]);
        $this->assertMcpError($response, -32603, 'Unknown tool');
    }

    public function test_invalid_json_returns_error(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'not json at all',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-MCP-Token: ' . self::$api_token
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        $this->assertMcpError($data, -32700, 'Invalid JSON');
    }

    // ---------------------------------------------------------------
    // Test response envelope format
    // ---------------------------------------------------------------

    public function test_response_preserves_request_id(): void
    {
        $response = $this->mcpCall('initialize', [], 42);
        $this->assertEquals(42, $response['id']);
    }

    public function test_response_null_id(): void
    {
        $response = $this->mcpCall('initialize', [], null);
        $this->assertNull($response['id']);
    }

    // ---------------------------------------------------------------
    // Test auth header variants
    // ---------------------------------------------------------------

    public function test_bearer_token_authentication(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => []
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::$api_token
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        $this->assertMcpSuccess($data);
    }

    /**
     * The ?token= query-string authentication method was removed for security
     * (tokens in URLs leak into access logs, Referer headers and history).
     * A request that supplies the token ONLY via the query string must now be
     * rejected exactly like a request with no token at all.
     */
    public function test_query_parameter_authentication_is_rejected(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php?token=" . self::$api_token,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => []
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $this->assertMcpError($data, -32600, 'API token required');
    }
}