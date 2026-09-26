<?php


require_once __DIR__ . '/McpServerFixture.php';
use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * End-to-end integration tests for the scheduling write tools
 * (add_recurring_event, update_event, delete_event) exercised over real HTTP
 * against the production schema built by the headless installer -- the same
 * approach as McpAddEventRaceConditionTest. This is where the tool method
 * bodies in mcp.php (which unit tests cannot include) are covered.
 */
final class McpSchedulingWriteToolsIntegrationTest extends TestCase
{
    private static $db_file = null;
    private static $api_token = null;
    private static $bob_token = null;
    private static $server_pid = null;
    private static $server_port = 0;
    private static $server_log = null;

    public static function setUpBeforeClass(): void
    {
        // Per-run port and paths; see tests/McpServerFixture.php.
        self::$server_port = McpServerFixture::freePort();
        self::$server_log = McpServerFixture::tempPath('mcp-sched-write-server', '.log');
        self::$db_file = McpServerFixture::tempPath('mcp_sched_write_test', '.sqlite');
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
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_SERVER_ENABLED', 'Y');");
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_WRITE_ACCESS', 'Y');");
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_RATE_LIMIT', '1000');");
        $db->exec(sprintf("UPDATE webcal_user SET cal_api_token = '%s' WHERE cal_login = 'admin';", self::$api_token));

        // A second, non-admin user to exercise the ownership check on
        // update_event / delete_event.
        self::$bob_token = bin2hex(random_bytes(32));
        $db->exec(sprintf(
            "INSERT INTO webcal_user (cal_login, cal_firstname, cal_lastname, cal_is_admin, cal_api_token) "
            . "VALUES ('bob', 'Bob', 'Tester', 'N', '%s');",
            self::$bob_token
        ));
        $db->close();

        $env = sprintf(
            'MCP_TOKEN= WEBCALENDAR_USE_ENV=true WEBCALENDAR_DB_TYPE=sqlite3 WEBCALENDAR_DB_DATABASE=%s',
            self::$db_file
        );
        $cmd = sprintf('%s php -S localhost:%d -t %s > ' . self::$server_log . ' 2>&1 & echo $!', $env, self::$server_port, $project_dir);
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

    /** Issue a tools/call over HTTP as the admin user. */
    private function callTool(string $name, array $arguments): array
    {
        return $this->callToolAs(self::$api_token, $name, $arguments);
    }

    /** Issue a tools/call over HTTP as the holder of $token. */
    private function callToolAs(string $token, string $name, array $arguments): array
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
                'X-MCP-Token: ' . $token,
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    private function entryRow(int $calId): ?array
    {
        $db = new SQLite3(self::$db_file);
        $stmt = $db->prepare('SELECT * FROM webcal_entry WHERE cal_id = :id');
        $stmt->bindValue(':id', $calId, SQLITE3_INTEGER);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        $db->close();
        return $row ?: null;
    }

    private function repeatRow(int $calId): ?array
    {
        $db = new SQLite3(self::$db_file);
        $stmt = $db->prepare('SELECT * FROM webcal_entry_repeats WHERE cal_id = :id');
        $stmt->bindValue(':id', $calId, SQLITE3_INTEGER);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        $db->close();
        return $row ?: null;
    }

    private function entryCount(): int
    {
        $db = new SQLite3(self::$db_file);
        $n = (int)$db->querySingle('SELECT COUNT(*) FROM webcal_entry');
        $db->close();
        return $n;
    }

    public function test_add_recurring_event_persists_entry_and_repeat_row(): void
    {
        $resp = $this->callTool('add_recurring_event', [
            'name' => 'Team Standup',
            'date' => '20260803',
            'rrule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;INTERVAL=1',
            'time' => '091500',
            'duration' => 15,
        ]);

        $result = $resp['result'] ?? [];
        $this->assertArrayNotHasKey('error', $result, 'unexpected error: ' . json_encode($resp));
        $this->assertArrayHasKey('event_id', $result);
        $this->assertSame('weekly', $result['cal_type']);

        $repeat = $this->repeatRow((int)$result['event_id']);
        $this->assertNotNull($repeat, 'a webcal_entry_repeats row must exist');
        $this->assertSame('weekly', $repeat['cal_type']);
        $this->assertSame('MO,WE,FR', $repeat['cal_byday']);
        $this->assertEquals(1, $repeat['cal_frequency']);
    }

    public function test_add_recurring_event_stores_count_and_setpos(): void
    {
        $resp = $this->callTool('add_recurring_event', [
            'name' => 'Monthly Review',
            'date' => '20260807',
            'rrule' => 'FREQ=MONTHLY;BYDAY=FR;BYSETPOS=1;COUNT=6',
        ]);
        $result = $resp['result'] ?? [];
        $this->assertArrayNotHasKey('error', $result, json_encode($resp));
        $this->assertSame('monthlyBySetPos', $result['cal_type']);

        $repeat = $this->repeatRow((int)$result['event_id']);
        $this->assertSame('monthlyBySetPos', $repeat['cal_type']);
        $this->assertSame('1', $repeat['cal_bysetpos']);
        $this->assertEquals(6, $repeat['cal_count']);
    }

    /**
     * webcal_entry.cal_type distinguishes a repeating event ('M') from a
     * one-off ('E'); edit_entry_handler.php is the authority on that and sets
     * it from whether a recurrence rule is present.
     *
     * add_recurring_event used to omit the column entirely and inherit the
     * schema default of 'E', so every recurring event MCP created was
     * mislabelled. Display tolerated it, because the queries accept both
     * values, which is why it went unnoticed.
     */
    public function test_add_recurring_event_marks_the_entry_as_repeating(): void
    {
        $resp = $this->callTool('add_recurring_event', [
            'name' => 'Repeating type check',
            'date' => '20260601',
            'time' => '120000',
            'duration' => 60,
            'rrule' => 'FREQ=DAILY;INTERVAL=1',
        ]);

        $result = $resp['result'] ?? [];
        $this->assertArrayNotHasKey('error', $result, 'unexpected error: ' . json_encode($resp));
        $this->assertArrayHasKey('event_id', $result);

        $entry = $this->entryRow((int)$result['event_id']);
        $this->assertNotNull($entry);
        $this->assertSame('M', $entry['cal_type'],
            'a recurring event must be stored as cal_type M, not the column default E');
        $this->assertNotNull($this->repeatRow((int)$result['event_id']),
            'and it must still have its recurrence row');
    }

    /**
     * The other half: a plain event must not be relabelled by that change.
     */
    public function test_add_event_stays_a_plain_event(): void
    {
        $resp = $this->callTool('add_event', [
            'name' => 'Plain type check',
            'date' => '20260601',
            'time' => '130000',
            'duration' => 30,
        ]);

        $result = $resp['result'] ?? [];
        $this->assertArrayNotHasKey('error', $result, 'unexpected error: ' . json_encode($resp));
        $this->assertArrayHasKey('event_id', $result);

        $entry = $this->entryRow((int)$result['event_id']);
        $this->assertNotNull($entry);
        $this->assertSame('E', $entry['cal_type']);
        $this->assertNull($this->repeatRow((int)$result['event_id']));
    }

    public function test_add_recurring_event_rejects_invalid_rrule_without_creating_event(): void
    {
        $before = $this->entryCount();
        $resp = $this->callTool('add_recurring_event', [
            'name' => 'Bad Rule',
            'date' => '20260803',
            'rrule' => 'FREQ=HOURLY',
        ]);

        $result = $resp['result'] ?? [];
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsStringIgnoringCase('rrule', $result['error']);
        $this->assertSame($before, $this->entryCount(), 'no event should be created for an invalid RRULE');
    }

    public function test_update_event_changes_only_provided_fields(): void
    {
        $add = $this->callTool('add_recurring_event', [
            'name' => 'Original Name',
            'date' => '20260803',
            'rrule' => 'FREQ=DAILY',
            'description' => 'keep me',
        ]);
        $id = (int)$add['result']['event_id'];

        $upd = $this->callTool('update_event', ['event_id' => $id, 'name' => 'New Name']);
        $this->assertArrayNotHasKey('error', $upd['result'] ?? [], json_encode($upd));

        $row = $this->entryRow($id);
        $this->assertSame('New Name', $row['cal_name']);
        // Unspecified field is untouched (null routing, not blanking).
        $this->assertSame('keep me', $row['cal_description']);
    }

    public function test_delete_event_removes_entry_and_repeat_rows(): void
    {
        $add = $this->callTool('add_recurring_event', [
            'name' => 'To Delete',
            'date' => '20260803',
            'rrule' => 'FREQ=WEEKLY;BYDAY=TU',
        ]);
        $id = (int)$add['result']['event_id'];
        $this->assertNotNull($this->repeatRow($id));

        $del = $this->callTool('delete_event', ['event_id' => $id]);
        $this->assertTrue($del['result']['success'] ?? false, json_encode($del));

        $this->assertNull($this->entryRow($id), 'base event should be gone');
        $this->assertNull($this->repeatRow($id), 'recurrence row should be gone');
    }

    public function test_delete_event_rejects_non_owner(): void
    {
        // admin creates an event; bob must not be able to delete it.
        $add = $this->callTool('add_recurring_event', [
            'name' => 'Admin Only',
            'date' => '20260803',
            'rrule' => 'FREQ=DAILY',
        ]);
        $id = (int)$add['result']['event_id'];

        $del = $this->callToolAs(self::$bob_token, 'delete_event', ['event_id' => $id]);
        $result = $del['result'] ?? [];
        $this->assertArrayHasKey('error', $result, 'bob must not delete admin\'s event: ' . json_encode($del));
        $this->assertStringContainsStringIgnoringCase('authorized', $result['error']);
        $this->assertNotNull($this->entryRow($id), 'the event must still exist after a rejected delete');
    }

    public function test_add_event_stores_the_given_time(): void
    {
        $resp = $this->callTool('add_event', [
            'name' => 'Timed Lunch',
            'date' => '20260717',
            'time' => '140000',
            'duration' => 60,
        ]);
        $result = $resp['result'] ?? [];
        $this->assertArrayNotHasKey('error', $result, json_encode($resp));

        $row = $this->entryRow((int)$result['event_id']);
        $this->assertEquals(140000, $row['cal_time'], 'the requested time must be stored');
        $this->assertEquals(60, $row['cal_duration']);
    }

    public function test_add_event_defaults_to_untimed(): void
    {
        $resp = $this->callTool('add_event', [
            'name' => 'All Day',
            'date' => '20260717',
        ]);
        $result = $resp['result'] ?? [];
        $this->assertArrayNotHasKey('error', $result, json_encode($resp));

        $row = $this->entryRow((int)$result['event_id']);
        $this->assertEquals(-1, $row['cal_time'], 'no time given -> untimed (-1)');
    }

    /**
     * Soft-delete an event the way the web UI does: set its per-user status to
     * 'D' rather than removing the row (see includes/xcal.php delete flow).
     */
    private function softDelete(int $calId): void
    {
        $db = new SQLite3(self::$db_file);
        $stmt = $db->prepare('UPDATE webcal_entry_user SET cal_status = :s WHERE cal_id = :id');
        $stmt->bindValue(':s', 'D', SQLITE3_TEXT);
        $stmt->bindValue(':id', $calId, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
    }

    public function test_read_tools_exclude_ui_deleted_events(): void
    {
        // A uniquely named, timed event that lands in every read tool's window.
        $unique = 'SoftDeleteMe_' . bin2hex(random_bytes(4));
        $add = $this->callTool('add_event', [
            'name' => $unique,
            'date' => '20260720',
            'time' => '150000',
            'duration' => 60,
        ]);
        $id = (int)($add['result']['event_id'] ?? 0);
        $this->assertGreaterThan(0, $id, 'setup add_event failed: ' . json_encode($add));

        // Before deletion: search_events finds it (sanity check the fixture).
        $before = $this->callTool('search_events', ['keyword' => $unique]);
        $names = array_column($before['result']['events'] ?? [], 'name');
        $this->assertContains($unique, $names, 'event should be visible before deletion');

        // Soft-delete as the UI does, then every read tool must exclude it.
        $this->softDelete($id);

        $search = $this->callTool('search_events', ['keyword' => $unique]);
        $this->assertNotContains(
            $unique,
            array_column($search['result']['events'] ?? [], 'name'),
            'search_events must not return a deleted event'
        );

        $list = $this->callTool('list_events', ['start_date' => '20260720', 'end_date' => '20260720']);
        $this->assertNotContains(
            $id,
            array_column($list['result']['events'] ?? [], 'id'),
            'list_events must not return a deleted event'
        );

        $avail = $this->callTool('get_availability', ['start_date' => '20260720', 'end_date' => '20260720']);
        $this->assertNotContains(
            $id,
            array_column($avail['result']['busy'] ?? [], 'id'),
            'get_availability must not report a deleted event as busy'
        );

        $conf = $this->callTool('check_conflicts', ['date' => '20260720', 'time' => '150000', 'duration' => 60]);
        $this->assertNotContains(
            $id,
            array_column($conf['result']['conflicts'] ?? [], 'id'),
            'check_conflicts must not flag a deleted event'
        );
    }
}
