<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Test for MCP-3: add_event id generation.
 *
 * add_event() assigns new event ids as MAX(cal_id) + 1 because
 * webcal_entry.cal_id is a plain INT PRIMARY KEY with no auto-increment on
 * any supported backend. These tests exercise the real production code path
 * (HTTP -> mcp.php -> add_event) against the production schema created by the
 * headless installer, and guard against:
 *   - a double INSERT that made every add_event fail on a duplicate key, and
 *   - id generation that violated the NOT NULL cal_id column (e.g. relying on
 *     auto-increment / INSERT ... RETURNING that the schema does not provide).
 */
final class McpAddEventRaceConditionTest extends TestCase
{
    private static $db_dir = null;
    private static $db_file = null;
    private static $api_token = null;
    private static $server_pid = null;
    private static $server_port = 8102;

    public static function setUpBeforeClass(): void
    {
        self::$db_dir = sys_get_temp_dir();
        self::$db_file = self::$db_dir . '/mcp_race_test.sqlite';

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

        // Set up database connection
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

        // Enable MCP server with write access
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
            'WEBCALENDAR_USE_ENV=true WEBCALENDAR_DB_TYPE=sqlite3 WEBCALENDAR_DB_DATABASE=%s',
            self::$db_file
        );
        $cmd = sprintf('%s php -S localhost:%d -t %s > /tmp/mcp-race-test-server.log 2>&1 & echo $!', $env, self::$server_port, $project_dir);

        exec($cmd, $output);
        self::$server_pid = (int)$output[0];

        // Wait for server to start
        sleep(2);
    }

    private static function setupDatabaseConnection(): void
    {
        // Set up global database connection
        global $db_type, $db_host, $db_database, $db_login, $db_password;

        $db_type = 'sqlite3';
        $db_host = 'localhost';
        $db_database = self::$db_file;
        $db_login = '';
        $db_password = '';

        dbi_connect($db_host, $db_login, $db_password, $db_database);
    }

    /**
     * TEST: add_event persists each event and assigns a unique id.
     *
     * Issues several sequential add_event calls over HTTP and asserts that
     * each one succeeds (returns an event_id, not an error) and that all ids
     * are unique. This is the regression guard for the double-INSERT bug:
     * when add_event re-inserted with an already-used cal_id, every call
     * returned result.error instead of result.event_id, which this test
     * fails on (rather than passing vacuously on zero results).
     */
    public function test_add_event_creates_unique_sequential_ids(): void
    {
        $event_ids = [];

        for ($i = 0; $i < 5; $i++) {
            $data = $this->callAddEvent("Sequential Event $i", '20260610');
            $result = $data['result'] ?? [];

            $this->assertArrayNotHasKey(
                'error',
                $result,
                'add_event returned an error: ' . json_encode($data)
            );
            $this->assertArrayHasKey(
                'event_id',
                $result,
                'add_event did not return an event_id: ' . json_encode($data)
            );

            $event_ids[] = $result['event_id'];
        }

        $this->assertCount(5, $event_ids, 'All 5 add_event calls should succeed');
        $this->assertCount(
            5,
            array_unique($event_ids),
            'All event IDs should be unique: ' . implode(', ', $event_ids)
        );
    }

    /**
     * TEST: concurrent add_event calls all succeed with unique ids.
     *
     * NOTE: PHP's built-in server (php -S) is single-threaded unless
     * PHP_CLI_SERVER_WORKERS is set, so these requests are effectively
     * serialized. This test therefore verifies end-to-end success and id
     * uniqueness rather than proving true concurrency safety (the
     * retry-on-collision loop in add_event provides the latter). We still
     * assert that every request returned an event_id so a globally-failing
     * add_event cannot pass vacuously.
     */
    public function test_concurrent_add_event_calls(): void
    {
        $request_count = 3;
        $requests = [];

        for ($i = 0; $i < $request_count; $i++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $i + 1,
                    'method' => 'tools/call',
                    'params' => [
                        'name' => 'add_event',
                        'arguments' => [
                            'name' => "Concurrent Event $i",
                            'date' => '20260610',
                            'description' => "Created concurrently $i",
                            'duration' => 60
                        ]
                    ]
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-MCP-Token: ' . self::$api_token
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            $requests[] = $ch;
        }

        // Execute all requests concurrently
        $multi_handle = curl_multi_init();
        foreach ($requests as $ch) {
            curl_multi_add_handle($multi_handle, $ch);
        }

        $active = null;
        do {
            $status = curl_multi_exec($multi_handle, $active);
            if ($active) {
                curl_multi_select($multi_handle);
            }
        } while ($active && $status == CURLM_OK);

        // Collect results
        $results = [];
        foreach ($requests as $ch) {
            $response = curl_multi_getcontent($ch);
            $data = json_decode($response, true);
            if (isset($data['result']['event_id'])) {
                $results[] = $data['result']['event_id'];
            }
            curl_multi_remove_handle($multi_handle, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi_handle);

        $this->assertCount(
            $request_count,
            $results,
            'Every concurrent add_event call should return an event_id'
        );
        $this->assertCount(
            $request_count,
            array_unique($results),
            'All concurrent calls should return unique event IDs: ' . implode(', ', $results)
        );
    }

    /**
     * Helper: issue a single add_event JSON-RPC call over HTTP and return the
     * decoded response array.
     */
    private function callAddEvent(string $name, string $date): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://localhost:" . self::$server_port . "/mcp.php",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'add_event',
                    'arguments' => [
                        'name' => $name,
                        'date' => $date,
                        'description' => 'Test event',
                        'duration' => 60
                    ]
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-MCP-Token: ' . self::$api_token
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}
