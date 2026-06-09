<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/dbi4php.php";
require_once __DIR__ . "/../includes/functions.php";

/**
 * Test for MCP-2: rate limit timestamp comparison.
 *
 * check_mcp_rate_limit() counts a user's MCP actions in webcal_entry_log
 * within the last hour. These tests run against the installer-created
 * production schema (where the log time is stored as the integer columns
 * cal_date (YYYYMMDD) and cal_time (HHMMSS), both in GMT) and verify the
 * window, per-user scoping, and the "0 means unlimited" rule.
 */
final class McpRateLimitTest extends TestCase
{
    private static $db_dir = null;
    private static $db_file = null;
    private static $api_token = null;
    private static $server_pid = null;
    private static $server_port = 8101;

    public static function setUpBeforeClass(): void
    {
        self::$db_dir = sys_get_temp_dir();
        self::$db_file = self::$db_dir . '/mcp_rate_test.sqlite';

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

        // Enable MCP server with a low rate limit for testing
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_SERVER_ENABLED', 'Y');");
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_WRITE_ACCESS', 'Y');");
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_RATE_LIMIT', '2');"); // Very low limit for testing

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
        $cmd = sprintf('%s php -S localhost:%d -t %s > /tmp/mcp-rate-test-server.log 2>&1 & echo $!', $env, self::$server_port, $project_dir);

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
     * Per-test isolation: re-read settings from this class's database so the
     * configured MCP_RATE_LIMIT (2) is used regardless of what an earlier test
     * class may have cached in load_settings().
     */
    protected function setUp(): void
    {
        load_settings(true);
    }

    /**
     * Insert one MCP action row into webcal_entry_log for $login at the given
     * GMT date/time. Uses MAX(cal_log_id)+1 for the primary key (the production
     * schema has no auto-increment) and writes the GMT cal_date/cal_time columns
     * that activity_log() populates and check_mcp_rate_limit() reads.
     */
    private function seedLogRow(SQLite3 $db, string $login, int $calDate, int $calTime): void
    {
        $next = (int)$db->querySingle('SELECT MAX(cal_log_id) FROM webcal_entry_log') + 1;
        $stmt = $db->prepare(
            "INSERT INTO webcal_entry_log
               (cal_log_id, cal_entry_id, cal_login, cal_type, cal_text, cal_date, cal_time)
             VALUES (:id, 0, :login, 'M', 'MCP: Test', :d, :t)"
        );
        $stmt->bindValue(':id', $next, SQLITE3_INTEGER);
        $stmt->bindValue(':login', $login, SQLITE3_TEXT);
        $stmt->bindValue(':d', $calDate, SQLITE3_INTEGER);
        $stmt->bindValue(':t', $calTime, SQLITE3_INTEGER);
        $stmt->execute();
    }

    /**
     * TEST: actions older than one hour must not count toward the limit.
     *
     * Seeds MCP_RATE_LIMIT (2) entries timestamped two hours ago. If the time
     * window were ignored these would trip the limit; with the fix they do not.
     */
    public function test_rate_limit_should_reset_after_time_window(): void
    {
        $db = new SQLite3(self::$db_file);
        $db->exec("DELETE FROM webcal_entry_log WHERE cal_login = 'admin'");

        $old = time() - 7200; // 2 hours ago
        $oldDate = (int)gmdate('Ymd', $old);
        $oldTime = (int)gmdate('Gis', $old);
        $this->seedLogRow($db, 'admin', $oldDate, $oldTime);
        $this->seedLogRow($db, 'admin', $oldDate, $oldTime);
        $db->close();

        $this->assertFalse(
            check_mcp_rate_limit('admin'),
            'Rate limit should not trip when all activity is older than one hour'
        );
    }

    /**
     * TEST: rate limits are tracked per user.
     */
    public function test_different_users_should_have_separate_rate_limits(): void
    {
        $db = new SQLite3(self::$db_file);
        $db->exec("DELETE FROM webcal_entry_log WHERE cal_login IN ('admin', 'testuser')");

        // Ensure the second user exists.
        $token = bin2hex(random_bytes(32));
        $db->exec("INSERT OR IGNORE INTO webcal_user
                     (cal_login, cal_firstname, cal_lastname, cal_email, cal_enabled, cal_api_token)
                   VALUES ('testuser', 'Test', 'User', 'test@example.com', 'Y', '$token')");

        $now = time();
        $d = (int)gmdate('Ymd', $now);
        $t = (int)gmdate('Gis', $now);

        // admin reaches the limit (2 recent actions); testuser has only 1.
        $this->seedLogRow($db, 'admin', $d, $t);
        $this->seedLogRow($db, 'admin', $d, $t);
        $this->seedLogRow($db, 'testuser', $d, $t);
        $db->close();

        $this->assertTrue(check_mcp_rate_limit('admin'), 'Admin should be rate limited at the limit');
        $this->assertFalse(check_mcp_rate_limit('testuser'), 'Test user should be under the limit');
    }

    /**
     * TEST: a rate limit of 0 disables limiting entirely.
     */
    public function test_zero_rate_limit_should_mean_no_limiting(): void
    {
        $db = new SQLite3(self::$db_file);
        $db->exec("DELETE FROM webcal_entry_log WHERE cal_login = 'admin'");

        // Seed more than the normal limit so limiting WOULD trip if enabled.
        $now = time();
        $d = (int)gmdate('Ymd', $now);
        $t = (int)gmdate('Gis', $now);
        for ($i = 0; $i < 3; $i++) {
            $this->seedLogRow($db, 'admin', $d, $t);
        }
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_RATE_LIMIT', '0')");
        $db->close();

        // Pick up the changed config (load_settings caches within the process).
        load_settings(true);

        $this->assertFalse(
            check_mcp_rate_limit('admin'),
            'Rate limit should not be enforced when MCP_RATE_LIMIT is 0'
        );

        // Restore the limit so the change does not leak to other tests/classes.
        $db = new SQLite3(self::$db_file);
        $db->exec("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('MCP_RATE_LIMIT', '2')");
        $db->close();
        load_settings(true);
    }
}
