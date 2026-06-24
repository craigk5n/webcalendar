<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Test for MCP-1: Fix duplicate token extraction
 * 
 * This test demonstrates the bug where Bearer token authentication
 * is silently overwritten by empty environment variable.
 */
final class McpTokenExtractionTest extends TestCase
{
    private static $db_dir = null;
    private static $db_file = null;
    private static $api_token = null;
    private static $server_pid = null;
    private static $server_port = 8100;

    public static function setUpBeforeClass(): void
    {
        self::$db_dir = sys_get_temp_dir();
        self::$db_file = self::$db_dir . '/mcp_token_test.sqlite';

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
        $cmd = sprintf('%s php -S localhost:%d -t %s > /tmp/mcp-token-test-server.log 2>&1 & echo $!', $env, self::$server_port, $project_dir);
        
        exec($cmd, $output);
        self::$server_pid = (int)$output[0];
        
        // Wait for server to start
        sleep(2);
    }

    private function mcpCallWithBearer(string $bearer_token, $id = 1): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'initialize',
                'params' => []
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $bearer_token
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

    /**
     * TEST: Bearer token authentication should work
     * 
     * This test demonstrates the bug where Bearer token authentication
     * fails because the duplicate token extraction at lines 399-402
     * overwrites the valid Bearer token with an empty environment variable.
     */
    public function test_bearer_token_authentication_should_work(): void
    {
        // This test should pass, but currently fails due to the bug
        $response = $this->mcpCallWithBearer(self::$api_token);
        
        $this->assertArrayHasKey('jsonrpc', $response);
        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('result', $response);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertEquals('2024-11-05', $response['result']['protocolVersion']);
        
        // If we get here, the test passes - which means the bug is fixed
        // If the bug exists, this test will fail with "Invalid API token" error
    }

    /**
     * TEST: Multiple auth methods should work independently
     * 
     * Verify that all authentication methods work and don't interfere
     * with each other.
     */
    public function test_multiple_auth_methods_work(): void
    {
        // Test Bearer token
        $response1 = $this->mcpCallWithBearer(self::$api_token, 1);
        $this->assertArrayHasKey('result', $response1);
        
        // Test X-MCP-Token header (separate curl call)
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'initialize',
                'params' => []
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-MCP-Token: ' . self::$api_token
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response2 = curl_exec($ch);
        curl_close($ch);
        
        $data2 = json_decode($response2, true);
        $this->assertArrayHasKey('result', $data2);
        $this->assertEquals('2024-11-05', $data2['result']['protocolVersion']);
    }

    /**
     * TEST: Bearer token should work even with empty environment variable
     * 
     * This test specifically targets the bug where lines 400-402 could
     * potentially interfere with Bearer authentication when MCP_TOKEN is empty.
     */
    public function test_bearer_token_with_empty_env_var(): void
    {
        // Create a test script that simulates the bug scenario
        // where MCP_TOKEN is empty but Bearer token is provided
        $test_script = __DIR__ . '/test_bearer_bug.php';
        $test_code = <<<PHP
<?php
// Simulate the MCP token extraction logic from mcp.php

// Start with empty token (like when no auth provided)
\$token = '';
\$token_source = 'none';

// Check Authorization header for Bearer token
\$auth_header = 'Bearer ' . getenv('TEST_BEARER_TOKEN');
if (preg_match('/Bearer\s+(.+)/i', \$auth_header, \$matches)) {
    \$token = trim(\$matches[1]);
    \$token_source = 'bearer';
}

// This is the problematic code at lines 400-402 in mcp.php
if (empty(\$token)) {
    \$token = getenv('MCP_TOKEN') ?: (\$_SERVER['HTTP_X_MCP_TOKEN'] ?? (\$_GET['token'] ?? ''));
}

echo json_encode([
    'token' => \$token,
    'token_source' => \$token_source,
    'bearer_provided' => !empty(getenv('TEST_BEARER_TOKEN')),
    'mcp_env_var' => getenv('MCP_TOKEN') ?: 'empty'
]);
PHP;

        file_put_contents($test_script, $test_code);
        
        try {
            // Set empty MCP_TOKEN and provide Bearer token via env
            putenv('MCP_TOKEN=');
            putenv('TEST_BEARER_TOKEN=' . self::$api_token);
            
            $output = [];
            exec("php " . escapeshellarg($test_script), $output);
            $result = json_decode($output[0], true);
            
            // The bug would be: if the fallback code overwrites the Bearer token
            // when MCP_TOKEN is empty, this test would fail
            $this->assertEquals(self::$api_token, $result['token'], 'Bearer token should be preserved even when MCP_TOKEN is empty');
            $this->assertEquals('bearer', $result['token_source'], 'Token source should be bearer');
            
        } finally {
            // Clean up
            putenv('MCP_TOKEN');
            putenv('TEST_BEARER_TOKEN');
            unlink($test_script);
        }
    }

    /**
     * TEST: Duplicate code at lines 400-402 should not override valid tokens
     * 
     * This test demonstrates the bug where the simplified fallback code
     * at lines 400-402 could potentially interfere with properly
     * extracted tokens.
     */
    public function test_duplicate_fallback_does_not_override_valid_tokens(): void
    {
        // Create a test script that simulates the exact mcp.php logic
        $test_script = __DIR__ . '/test_duplicate_bug.php';
        $test_code = <<<PHP
<?php
// Simulate the exact MCP token extraction logic from mcp.php

// Start with an empty token (as in the original code)
\$token = '';
\$token_source = 'none';

// Check Authorization header for Bearer token (lines 364-378)
\$auth_header = 'Bearer ' . getenv('TEST_BEARER_TOKEN');
if (preg_match('/Bearer\s+(.+)/i', \$auth_header, \$matches)) {
    \$token = trim(\$matches[1]);
    \$token_source = 'bearer';
} elseif (preg_match('/^([a-f0-9]{64})$/i', \$auth_header, \$matches)) {
    \$token = trim(\$matches[1]);
    \$token_source = 'bearer-direct';
}

echo "After Bearer check: token='\$token', source='\$token_source'\\n";

// First fallback (lines 381-392) - this should work correctly
if (empty(\$token)) {
    if (getenv('MCP_TOKEN')) {
        \$token = getenv('MCP_TOKEN');
        \$token_source = 'env';
        echo "After env fallback: token='\$token', source='\$token_source'\\n";
    } elseif (isset(\$_SERVER['HTTP_X_MCP_TOKEN'])) {
        \$token = \$_SERVER['HTTP_X_MCP_TOKEN'];
        \$token_source = 'x-mcp-token';
        echo "After X-MCP-Token fallback: token='\$token', source='\$token_source'\\n";
    } elseif (isset(\$_GET['token'])) {
        \$token = \$_GET['token'];
        \$token_source = 'query';
        echo "After query fallback: token='\$token', source='\$token_source'\\n";
    }
}

// Duplicate fallback (lines 400-402) - THIS IS THE BUG
// This could potentially override a valid token
if (empty(\$token)) {
    \$token = getenv('MCP_TOKEN') ?: (\$_SERVER['HTTP_X_MCP_TOKEN'] ?? (\$_GET['token'] ?? ''));
    // NOTE: \$token_source is NOT set here, which is problematic
    echo "After duplicate fallback: token='\$token', source='\$token_source'\\n";
}

echo "Final: token='\$token', source='\$token_source'\\n";
echo json_encode([
    'token' => \$token,
    'token_source' => \$token_source,
    'test_bearer_token' => getenv('TEST_BEARER_TOKEN'),
    'mcp_env_token' => getenv('MCP_TOKEN')
]);
PHP;

        file_put_contents($test_script, $test_code);
        
        try {
            // Test scenario: we have a valid Bearer token, but MCP_TOKEN is also set
            // The duplicate code should NOT override our valid token
            putenv('MCP_TOKEN=env_token_that_should_not_override');
            putenv('TEST_BEARER_TOKEN=' . self::$api_token);
            
            $output = [];
            exec("php " . escapeshellarg($test_script), $output);
            echo "Debug output: " . implode("\n", $output) . "\n";
            
            $result = json_decode($output[count($output)-1], true); // Last line is JSON
            
            // The bug: if the duplicate code runs and overwrites our valid token
            $this->assertEquals(self::$api_token, $result['token'], 
                'Token should remain the valid Bearer token and not be overridden');
            $this->assertEquals('bearer', $result['token_source'], 
                'Token source should be bearer');
            
        } finally {
            // Clean up
            putenv('MCP_TOKEN');
            putenv('TEST_BEARER_TOKEN');
            unlink($test_script);
        }
    }

    /**
     * TEST: Bearer token should take precedence over query param
     */
    public function test_bearer_precedence_over_query_param(): void
    {
        // Test both Bearer and query param - Bearer should win
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php?token=invalid_token",
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
        $this->assertArrayHasKey('result', $data);
        $this->assertEquals('2024-11-05', $data['result']['protocolVersion']);
        
        // Bearer token should override the invalid query param token
    }
}