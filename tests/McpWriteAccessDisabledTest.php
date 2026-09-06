<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Regression guard for the MCP_WRITE_ACCESS admin setting: when write access
 * is NOT enabled, every write tool must refuse and change nothing, while read
 * tools still work. Runs a real server (headless-installed schema) with MCP
 * enabled but MCP_WRITE_ACCESS left OFF.
 */
final class McpWriteAccessDisabledTest extends TestCase
{
    private static $db_file = null;
    private static $api_token = null;
    private static $server_pid = null;
    private static $server_port = 8104;

    public static function setUpBeforeClass(): void
    {
        self::$db_file = sys_get_temp_dir() . '/mcp_write_disabled_test.sqlite';
        if (file_exists(self::$db_file)) {
            unlink(self::$db_file);
        }

        $project_dir = __DIR__ . '/..';
        $cmd = sprintf(
            'php %s/wizard/headless.php --use-env --db-type=sqlite3 --db-database=%s --admin-login=admin --admin-password=admin --install-password=test123 --force',
            $project_dir,
            self::$db_file
        );
        $output = [];
        $return_var = null;
        exec($cmd, $output, $return_var);
        if ($return_var !== 0) {
            throw new RuntimeException("Headless installer failed: " . implode("\n", $output));
        }

        self::$api_token = bin2hex(random_bytes(32));
        $db = new SQLite3(self::$db_file);
        // MCP enabled, but write access deliberately NOT set to 'Y'.
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_SERVER_ENABLED', 'Y');");
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_WRITE_ACCESS', 'N');");
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_RATE_LIMIT', '1000');");
        $db->exec(sprintf("UPDATE webcal_user SET cal_api_token = '%s' WHERE cal_login = 'admin';", self::$api_token));
        $db->close();

        $env = sprintf(
            'MCP_TOKEN= WEBCALENDAR_USE_ENV=true WEBCALENDAR_DB_TYPE=sqlite3 WEBCALENDAR_DB_DATABASE=%s',
            self::$db_file
        );
        $cmd = sprintf('%s php -S localhost:%d -t %s > /tmp/mcp-write-disabled-server.log 2>&1 & echo $!', $env, self::$server_port, $project_dir);
        $out = [];
        exec($cmd, $out);
        self::$server_pid = (int)$out[0];
        sleep(2);
    }

    public static function tearDownAfterClass(): void
    {
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
            // best-effort
        }
        if (file_exists(self::$db_file)) {
            unlink(self::$db_file);
        }
    }

    private function callTool(string $name, array $arguments): array
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
                'params' => ['name' => $name, 'arguments' => $arguments],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-MCP-Token: ' . self::$api_token,
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    private function entryCount(): int
    {
        $db = new SQLite3(self::$db_file);
        $n = (int)$db->querySingle('SELECT COUNT(*) FROM webcal_entry');
        $db->close();
        return $n;
    }

    public function test_add_event_is_blocked_and_creates_nothing(): void
    {
        $before = $this->entryCount();
        $resp = $this->callTool('add_event', [
            'name' => 'Should Not Persist',
            'date' => '20260803',
        ]);
        $result = $resp['result'] ?? [];
        $this->assertArrayHasKey('error', $result, json_encode($resp));
        $this->assertStringContainsStringIgnoringCase('write access', $result['error']);
        $this->assertSame($before, $this->entryCount(), 'no event may be created when write access is off');
    }

    public function test_add_recurring_event_is_blocked_and_creates_nothing(): void
    {
        $before = $this->entryCount();
        $resp = $this->callTool('add_recurring_event', [
            'name' => 'Should Not Persist',
            'date' => '20260803',
            'rrule' => 'FREQ=WEEKLY;BYDAY=MO',
        ]);
        $result = $resp['result'] ?? [];
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsStringIgnoringCase('write access', $result['error']);
        $this->assertSame($before, $this->entryCount(), 'no event may be created when write access is off');
    }

    public function test_update_event_is_blocked(): void
    {
        $resp = $this->callTool('update_event', ['event_id' => 1, 'name' => 'x']);
        $result = $resp['result'] ?? [];
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsStringIgnoringCase('write access', $result['error']);
    }

    public function test_delete_event_is_blocked(): void
    {
        $resp = $this->callTool('delete_event', ['event_id' => 1]);
        $result = $resp['result'] ?? [];
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsStringIgnoringCase('write access', $result['error']);
    }

    public function test_read_tools_still_work_when_write_disabled(): void
    {
        // get_availability is a read tool and must remain usable.
        $resp = $this->callTool('get_availability', ['start_date' => '20260801', 'end_date' => '20260831']);
        $result = $resp['result'] ?? [];
        $this->assertArrayNotHasKey('error', $result, json_encode($resp));
        $this->assertArrayHasKey('busy', $result);
    }
}
