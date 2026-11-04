<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/McpServerFixture.php';

final class ApiIntegrationTest extends TestCase
{
  private static string $directory;
  private static PDO $db;
  private static int $port;
  private static $server;
  private static array $environment;

  public static function setUpBeforeClass(): void
  {
    self::assertFileExists(__DIR__ . '/../bin/api.php');
    self::$directory = McpServerFixture::tempPath('webcalendar-api', '');
    mkdir(self::$directory, 0700);
    self::$environment = array_merge(getenv(), [
      'WEBCALENDAR_USE_ENV' => 'true',
      'WEBCALENDAR_DB_TYPE' => 'sqlite3',
      'WEBCALENDAR_DB_DATABASE' => self::$directory . '/calendar.sqlite',
      'WEBCALENDAR_USER_INC' => 'user.php',
      'WEBCALENDAR_API_ENABLED' => 'true',
      'WEBCALENDAR_API_STORAGE' => self::$directory . '/storage',
    ]);
    self::command([
      PHP_BINARY, 'wizard/headless.php', '--use-env', '--db-type=sqlite3',
      '--db-database=' . self::$environment['WEBCALENDAR_DB_DATABASE'],
      '--admin-login=admin', '--admin-password=admin', '--install-password=test', '--force',
    ]);
    self::command([PHP_BINARY, 'bin/api.php', 'migrate', '--force']);
    self::$db = new PDO('sqlite:' . self::$environment['WEBCALENDAR_DB_DATABASE']);
    self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    self::sql("INSERT INTO webcal_user (cal_login, cal_passwd, cal_firstname, cal_lastname, cal_is_admin, cal_enabled) VALUES (?, ?, ?, ?, ?, ?)",
      ['alice', password_hash('secret', PASSWORD_DEFAULT), 'Alice', 'Example', 'N', 'Y']);
    self::$port = McpServerFixture::freePort();
    self::$server = proc_open(
      [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, '-t', dirname(__DIR__)],
      [0 => ['pipe', 'r'], 1 => ['file', self::$directory . '/server.log', 'a'],
        2 => ['file', self::$directory . '/server.log', 'a']],
      $pipes, dirname(__DIR__), self::$environment
    );
    fclose($pipes[0]);
    for ($attempt = 0; $attempt < 50; $attempt++) {
      $socket = @fsockopen('127.0.0.1', self::$port);
      if ($socket) {
        fclose($socket);
        return;
      }
      usleep(100000);
    }
    throw new RuntimeException('API server did not start.');
  }

  public static function tearDownAfterClass(): void
  {
    if (is_resource(self::$server)) {
      proc_terminate(self::$server);
      proc_close(self::$server);
    }
    if (isset(self::$directory)) {
      (new Filesystem())->remove(self::$directory);
    }
  }

  protected function setUp(): void
  {
    self::$db->exec('DELETE FROM webcal_api_tokens');
    self::$db->exec('DELETE FROM webcal_api_users');
    self::$db->exec('DELETE FROM webcal_entry_repeats_not');
    self::$db->exec('DELETE FROM webcal_entry_repeats');
    self::$db->exec('DELETE FROM webcal_entry_user');
    self::$db->exec('DELETE FROM webcal_entry');
    self::$db->exec('DELETE FROM webcal_user_pref');
    self::$db->exec("UPDATE webcal_user SET cal_enabled = 'Y'");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('TIMEZONE', 'UTC')");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('UAC_ENABLED', 'N')");
    (new Filesystem())->remove(self::$directory . '/storage/framework/cache');
  }

  public function testDeviceTokensAreHashedAndCurrentUserContainsNoCredentials(): void
  {
    [$status, $body, $headers] = $this->request('POST', '/v1/tokens', [
      'username' => 'alice', 'password' => 'secret', 'device_name' => 'My iPhone',
    ]);
    self::assertSame(201, $status, json_encode($body));
    self::assertSame('Bearer', $body['data']['token_type']);
    self::assertNotEmpty($body['data']['expires_at']);
    self::assertStringContainsString('no-store', implode(' ', $headers));
    $token = $body['data']['token'];
    $row = self::$db->query('SELECT * FROM webcal_api_tokens')->fetch(PDO::FETCH_ASSOC);
    self::assertSame(hash('sha256', explode('|', $token, 2)[1]), $row['token']);
    self::assertSame('My iPhone', $row['name']);
    self::assertSame(['events:read'], json_decode($row['abilities'], true));
    [$status, $body] = $this->request('GET', '/v1/users/current', [], $token);
    self::assertSame(200, $status, json_encode($body));
    self::assertSame('alice', $body['data']['username']);
    self::assertSame('Alice Example', $body['data']['name']);
    self::assertArrayNotHasKey('password', $body['data']);
    self::assertArrayNotHasKey('token', $body['data']);
    self::assertArrayNotHasKey('subject_id', $body['data']);
  }

  public function testInitialMigrationCreatesTheCompleteSchemaAndCanBeReapplied(): void
  {
    $database = self::$directory . '/initial-migration.sqlite';
    copy(self::$environment['WEBCALENDAR_DB_DATABASE'], $database);
    $db = new PDO('sqlite:' . $database);
    foreach (['webcal_api_tokens', 'webcal_api_users', 'webcal_api_migrations'] as $table) {
      $db->exec('DROP TABLE ' . $table);
    }
    $migration = 'app/Api/database/migrations/2026_11_02_000001_create_api_tables.php';
    $environment = ['WEBCALENDAR_DB_DATABASE' => $database];
    for ($cycle = 0; $cycle < 2; $cycle++) {
      self::command([PHP_BINARY, 'bin/api.php', 'migrate', '--force', '--path=' . $migration], $environment);
      $columns = array_column($db->query('PRAGMA table_info(webcal_api_users)')->fetchAll(PDO::FETCH_ASSOC), 'name');
      self::assertContains('subject_id', $columns);
      self::assertSame(['2026_11_02_000001_create_api_tables'],
        $db->query('SELECT migration FROM webcal_api_migrations')->fetchAll(PDO::FETCH_COLUMN));
      self::assertSame(1, (int) $db->query("SELECT COUNT(*) FROM sqlite_master WHERE name = 'webcal_api_tokens'")->fetchColumn());
      self::command([PHP_BINARY, 'bin/api.php', 'migrate:rollback', '--force', '--path=' . $migration], $environment);
      self::assertSame(0, (int) $db->query("SELECT COUNT(*) FROM sqlite_master WHERE name IN ('webcal_api_users', 'webcal_api_tokens')")->fetchColumn());
      self::assertSame(1, (int) $db->query("SELECT COUNT(*) FROM webcal_user WHERE cal_login = 'alice'")->fetchColumn());
    }
  }

  public function testInvalidCredentialsAndDisabledAccountsCannotIssueTokens(): void
  {
    foreach ([['alice', 'wrong'], ['missing', 'wrong'], ['__public__', 'secret']] as [$username, $password]) {
      [$status] = $this->request('POST', '/v1/tokens', compact('username', 'password') + ['device_name' => 'iPhone']);
      self::assertSame(401, $status);
    }
    self::$db->exec("UPDATE webcal_user SET cal_enabled = 'N' WHERE cal_login = 'alice'");
    [$status] = $this->request('POST', '/v1/tokens', ['username' => 'alice', 'password' => 'secret', 'device_name' => 'iPhone']);
    self::assertSame(401, $status);
    self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_tokens')->fetchColumn());
  }

  public function testValidationReturnsJsonEvenWithoutAnAcceptHeader(): void
  {
    [$status, $body] = $this->request('POST', '/v1/tokens', ['username' => 'alice']);
    self::assertSame(422, $status);
    self::assertArrayHasKey('password', $body['errors']);
    self::assertArrayHasKey('device_name', $body['errors']);
  }

  public function testUnauthenticatedAndStoredHashRequestsAreRejected(): void
  {
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(401, $status);
    $this->token();
    $hash = self::$db->query('SELECT token FROM webcal_api_tokens')->fetchColumn();
    [$status] = $this->request('GET', '/v1/users/current', [], $hash);
    self::assertSame(401, $status);
  }

  public function testRevocationOnlyRevokesTheCurrentDevice(): void
  {
    $first = $this->token();
    $second = $this->token();
    [$status] = $this->request('DELETE', '/v1/tokens/current', [], $first);
    self::assertSame(200, $status);
    [$status] = $this->request('GET', '/v1/users/current', [], $first);
    self::assertSame(401, $status);
    [$status] = $this->request('GET', '/v1/users/current', [], $second);
    self::assertSame(200, $status);
  }

  public function testExpiredTokensDisabledUsersAndBackendChangesAreRejected(): void
  {
    $token = $this->token();
    self::$db->exec("UPDATE webcal_api_tokens SET expires_at = '2000-01-01 00:00:00'");
    [$status] = $this->request('GET', '/v1/users/current', [], $token);
    self::assertSame(401, $status);
    $token = $this->token();
    self::$db->exec("UPDATE webcal_user SET cal_enabled = 'N' WHERE cal_login = 'alice'");
    [$status] = $this->request('GET', '/v1/users/current', [], $token);
    self::assertSame(401, $status);
    self::$db->exec("UPDATE webcal_user SET cal_enabled = 'Y' WHERE cal_login = 'alice'");
    $token = $this->token();
    self::$db->exec("UPDATE webcal_api_users SET auth_backend = 'user-ldap.php'");
    [$status] = $this->request('GET', '/v1/users/current', [], $token);
    self::assertSame(401, $status);
    self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_tokens')->fetchColumn());
  }

  public function testDeletedUsersLoseAccess(): void
  {
    $token = $this->token();
    self::$db->exec("UPDATE webcal_user SET cal_login = 'alice_removed' WHERE cal_login = 'alice'");
    try {
      [$status] = $this->request('GET', '/v1/users/current', [], $token);
      self::assertSame(401, $status);
    } finally {
      self::$db->exec("UPDATE webcal_user SET cal_login = 'alice' WHERE cal_login = 'alice_removed'");
    }
  }

  public function testDeletingAndRecreatingAUsernameRevokesAllOriginalDeviceTokens(): void
  {
    $first = $this->token();
    $second = $this->token();
    [$status, $other] = $this->request('POST', '/v1/tokens', ['username' => 'admin', 'password' => 'admin', 'device_name' => 'Unaffected account']);
    self::assertSame(201, $status);
    try {
      self::command([PHP_BINARY, '-r', $this->legacyScript(
        'user_delete_user("alice"); user_add_user("alice", "replacement", "New", "Owner", "", "N", "Y");'
      )]);
      foreach ([$first, $second] as $token) {
        [$status] = $this->request('GET', '/v1/users/current', [], $token);
        self::assertSame(401, $status);
      }
      self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_tokens')->fetchColumn());
      self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_users')->fetchColumn());
      [$status] = $this->request('GET', '/v1/users/current', [], $other['data']['token']);
      self::assertSame(200, $status);
      [$status] = $this->request('POST', '/v1/tokens', ['username' => 'alice', 'password' => 'replacement', 'device_name' => 'Replacement']);
      self::assertSame(201, $status);
    } finally {
      self::sql('UPDATE webcal_user SET cal_passwd = ?, cal_firstname = ?, cal_lastname = ? WHERE cal_login = ?',
        [password_hash('secret', PASSWORD_DEFAULT), 'Alice', 'Example', 'alice']);
    }
  }

  public function testAccountCredentialReplacementCannotReuseAnOldToken(): void
  {
    $token = $this->token();
    try {
      self::sql('UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?', [password_hash('replacement', PASSWORD_DEFAULT), 'alice']);
      [$status] = $this->request('GET', '/v1/users/current', [], $token);
      self::assertSame(401, $status);
    } finally {
      self::sql('UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?', [password_hash('secret', PASSWORD_DEFAULT), 'alice']);
    }
  }

  public function testLdapCalendarCleanupAlsoRevokesApiCredentialsWithoutComposer(): void
  {
    $this->token();
    try {
      self::command([PHP_BINARY, '-r', $this->legacyScript('user_delete_user("alice");')], ['WEBCALENDAR_USER_INC' => 'user-ldap.php']);
      self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_tokens')->fetchColumn());
      self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_users')->fetchColumn());
    } finally {
      self::sql('INSERT OR REPLACE INTO webcal_user (cal_login, cal_passwd, cal_firstname, cal_lastname, cal_is_admin, cal_enabled) VALUES (?, ?, ?, ?, ?, ?)',
        ['alice', password_hash('secret', PASSWORD_DEFAULT), 'Alice', 'Example', 'N', 'Y']);
    }
  }

  public function testUnboundTokensRequireFreshSignIn(): void
  {
    $token = $this->token();
    self::$db->exec('UPDATE webcal_api_users SET subject_id = NULL');
    [$status] = $this->request('GET', '/v1/users/current', [], $token);
    self::assertSame(401, $status);
    self::assertNotSame($token, $this->token());
  }

  public function testDeletionWithoutOptionalTablesNeedsNoComposer(): void
  {
    $database = self::$directory . '/without-api.sqlite';
    copy(self::$environment['WEBCALENDAR_DB_DATABASE'], $database);
    $db = new PDO('sqlite:' . $database);
    $db->exec('DROP TABLE webcal_api_tokens');
    $db->exec('DROP TABLE webcal_api_users');
    self::command([PHP_BINARY, '-r', $this->legacyScript(
      'if (class_exists("Illuminate\\Foundation\\Application", false)) { exit(1); } user_delete_user("alice");'
    )], ['WEBCALENDAR_DB_DATABASE' => $database]);
    self::assertSame(0, (int) $db->query("SELECT COUNT(*) FROM webcal_user WHERE cal_login = 'alice'")->fetchColumn());
  }

  public function testFailedRevocationPreventsAccountDeletion(): void
  {
    $this->token();
    self::$db->exec("CREATE TRIGGER deny_revocation BEFORE DELETE ON webcal_api_tokens BEGIN SELECT RAISE(ABORT, 'fixture revocation failure'); END");
    try {
      self::command([PHP_BINARY, '-r', $this->legacyScript('user_delete_user("alice");')], [], 255);
      self::assertSame(1, (int) self::$db->query("SELECT COUNT(*) FROM webcal_user WHERE cal_login = 'alice'")->fetchColumn());
      self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_tokens')->fetchColumn());
    } finally {
      self::$db->exec('DROP TRIGGER deny_revocation');
    }
  }

  public function testIncompleteApiSchemaCannotSilentlySkipRevocation(): void
  {
    $database = self::$directory . '/incomplete-api.sqlite';
    copy(self::$environment['WEBCALENDAR_DB_DATABASE'], $database);
    $db = new PDO('sqlite:' . $database);
    $db->exec('DROP TABLE webcal_api_tokens');
    self::command([PHP_BINARY, '-r', $this->legacyScript('user_delete_user("alice");')], ['WEBCALENDAR_DB_DATABASE' => $database], 255);
    self::assertSame(1, (int) $db->query("SELECT COUNT(*) FROM webcal_user WHERE cal_login = 'alice'")->fetchColumn());
  }

  private function legacyScript(string $body): string
  {
    return 'define("WEBCALENDAR_API_BOOTSTRAP", true); require '
      . var_export(dirname(__DIR__) . '/includes/api-bootstrap.php', true) . '; ' . $body;
  }

  public function testEventViewPermissionsStillApplyWithAValidToken(): void
  {
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('UAC_ENABLED', 'Y')");
    self::sql('INSERT OR REPLACE INTO webcal_access_function (cal_login, cal_permissions) VALUES (?, ?)', ['alice', str_repeat('N', 28)]);
    [$status] = $this->request('GET', '/v1/events?start_date=2026-11-01&end_date=2026-11-03', [], $this->token());
    self::assertSame(403, $status);
  }

  public function testEventsAreScopedToTheAuthenticatedCalendar(): void
  {
    $this->event(1, 'Private appointment', 'alice', 'R');
    $this->event(2, 'Other user secret', 'admin', 'R');
    $this->event(3, 'Rejected', 'alice', 'P', 'R');
    $this->event(4, 'Deleted', 'alice', 'P', 'D');
    $this->event(5, 'Pending invitation', 'alice', 'P', 'W');
    $token = $this->token();
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-01&end_date=2026-11-03', [], $token);
    self::assertSame(200, $status, json_encode($body));
    self::assertSame([1, 5], array_column($body['data'], 'id'));
    self::assertSame('Private appointment', $body['data'][0]['title']);
    self::assertSame('2026-11-02T10:00:00+00:00', $body['data'][0]['starts_at']);
    [$status] = $this->request('GET', '/v1/events?start_date=2026-11-01&end_date=2026-11-03&user=admin', [], $token);
    self::assertSame(422, $status);
    self::$db->exec("UPDATE webcal_api_tokens SET abilities = '[]'");
    [$status] = $this->request('GET', '/v1/events?start_date=2026-11-01&end_date=2026-11-03', [], $token);
    self::assertSame(403, $status);
  }

  public function testRecurringEventsHonorExceptionsAndAllDayDates(): void
  {
    $this->event(1, 'Daily', 'alice');
    self::$db->exec("UPDATE webcal_entry SET cal_type = 'M' WHERE cal_id = 1");
    self::sql('INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_end, cal_endtime, cal_wkst) VALUES (1, ?, 1, 20261105, 235959, ?)', ['daily', 'MO']);
    self::sql('INSERT INTO webcal_entry_repeats_not (cal_id, cal_date, cal_exdate) VALUES (1, 20261103, 1)');
    $this->event(2, 'All day', 'alice');
    self::$db->exec('UPDATE webcal_entry SET cal_time = 0, cal_duration = 1440 WHERE cal_id = 2');
    $this->event(3, 'Untimed', 'alice');
    self::$db->exec('UPDATE webcal_entry SET cal_time = -1, cal_duration = 0 WHERE cal_id = 3');
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-02&end_date=2026-11-04', [], $this->token());
    self::assertSame(200, $status, json_encode($body));
    $daily = array_values(array_filter($body['data'], fn ($event) => $event['id'] === 1));
    self::assertSame(['2026-11-02', '2026-11-04'], array_column($daily, 'date'));
    $allDay = array_values(array_filter($body['data'], fn ($event) => $event['id'] === 2))[0];
    self::assertTrue($allDay['all_day']);
    self::assertNull($allDay['starts_at']);
    self::assertSame('2026-11-02', $allDay['date']);
    $untimed = array_values(array_filter($body['data'], fn ($event) => $event['id'] === 3))[0];
    self::assertTrue($untimed['untimed']);
    self::assertNull($untimed['starts_at']);
  }

  public function testRecurringTimesMatchCalendarUtcTimesAcrossDaylightSaving(): void
  {
    $this->event(1, 'Daily across DST', 'alice');
    self::$db->exec("UPDATE webcal_entry SET cal_type = 'M', cal_date = 20261031 WHERE cal_id = 1");
    self::sql('INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_end, cal_endtime, cal_wkst) VALUES (1, ?, 1, 20261105, 235959, ?)', ['daily', 'MO']);
    self::sql('INSERT INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES (?, ?, ?)', ['alice', 'TIMEZONE', 'America/Los_Angeles']);
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-01&end_date=2026-11-02', [], $this->token());
    self::assertSame(200, $status, json_encode($body));
    self::assertSame(['2026-11-01T02:00:00-08:00', '2026-11-02T02:00:00-08:00'], array_column($body['data'], 'starts_at'));
  }

  public function testWeeklyByDayOccurrencesHonorWeekStartAndExceptions(): void
  {
    $this->event(1, 'Weekly fixture', 'alice');
    self::$db->exec("UPDATE webcal_entry SET cal_type = 'M', cal_date = 20261026 WHERE cal_id = 1");
    self::sql('INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_end, cal_endtime, cal_byday, cal_wkst) VALUES (1, ?, 1, 20261130, 235959, ?, ?)', ['weekly', 'MO,WE,FR', 'MO']);
    self::sql('INSERT INTO webcal_entry_repeats_not (cal_id, cal_date, cal_exdate) VALUES (1, 20261104, 1)');
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-02&end_date=2026-11-08', [], $this->token());
    self::assertSame(200, $status, json_encode($body));
    self::assertSame(['2026-11-02', '2026-11-06'], array_column($body['data'], 'date'));
    self::assertSame([1, 1], array_column($body['data'], 'id'));
  }

  public function testMonthlyOrdinalWeekdayOccurrences(): void
  {
    $this->event(1, 'Monthly fixture', 'alice');
    self::$db->exec("UPDATE webcal_entry SET cal_type = 'M', cal_date = 20261005 WHERE cal_id = 1");
    self::sql('INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_end, cal_endtime, cal_byday, cal_wkst) VALUES (1, ?, 1, 20261231, 235959, ?, ?)', ['monthlyByDay', '1MO', 'MO']);
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-01&end_date=2026-11-30', [], $this->token());
    self::assertSame(200, $status, json_encode($body));
    self::assertSame(['2026-11-02'], array_column($body['data'], 'date'));
  }

  public function testCountLimitedWeeklySeriesOutsideRangeDoesNotFail(): void
  {
    $this->event(1, 'Finished weekly fixture', 'alice');
    self::$db->exec("UPDATE webcal_entry SET cal_type = 'M', cal_date = 20261026 WHERE cal_id = 1");
    self::sql('INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_count, cal_byday, cal_wkst) VALUES (1, ?, 1, 3, ?, ?)', ['weekly', 'MO', 'MO']);
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-16&end_date=2026-11-22', [], $this->token());
    self::assertSame(200, $status, json_encode($body));
    self::assertSame([], $body['data']);
  }

  public function testWeeklyByDayTimesAcrossDaylightSaving(): void
  {
    $this->event(1, 'Weekly DST fixture', 'alice');
    self::$db->exec("UPDATE webcal_entry SET cal_type = 'M', cal_date = 20261026 WHERE cal_id = 1");
    self::sql('INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_end, cal_endtime, cal_byday, cal_wkst) VALUES (1, ?, 1, 20261130, 235959, ?, ?)', ['weekly', 'MO', 'MO']);
    self::sql('INSERT INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES (?, ?, ?)', ['alice', 'TIMEZONE', 'America/Los_Angeles']);
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-10-26&end_date=2026-11-02', [], $this->token());
    self::assertSame(200, $status, json_encode($body));
    self::assertSame(['2026-10-26', '2026-11-02'], array_column($body['data'], 'date'));
    self::assertSame(['2026-10-26T03:00:00-07:00', '2026-11-02T02:00:00-08:00'], array_column($body['data'], 'starts_at'));
  }

  public function testDateRangeUsesTheUsersTimezone(): void
  {
    $this->event(1, 'Evening', 'alice');
    self::$db->exec('UPDATE webcal_entry SET cal_time = 10000 WHERE cal_id = 1');
    self::sql('INSERT INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES (?, ?, ?)', ['alice', 'TIMEZONE', 'America/Los_Angeles']);
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-01&end_date=2026-11-01', [], $this->token());
    self::assertSame(200, $status, json_encode($body));
    self::assertCount(1, $body['data']);
    self::assertSame('2026-11-01', $body['data'][0]['date']);
    self::assertSame('2026-11-01T17:00:00-08:00', $body['data'][0]['starts_at']);
  }

  public function testInvalidReversedAndUnboundedDateRangesAreRejected(): void
  {
    $token = $this->token();
    foreach (['', '?start_date=2026-02-30&end_date=2026-03-01',
      '?start_date=2026-11-03&end_date=2026-11-01',
      '?start_date=2026-01-01&end_date=2026-12-31'] as $query) {
      [$status] = $this->request('GET', '/v1/events' . $query, [], $token);
      self::assertSame(422, $status, $query);
    }
  }

  public function testLoginIsRateLimited(): void
  {
    for ($attempt = 0; $attempt < 11; $attempt++) {
      [$status] = $this->request('POST', '/v1/tokens', ['username' => 'alice', 'password' => 'wrong', 'device_name' => 'iPhone']);
    }
    self::assertSame(429, $status);
  }

  public function testRuntimeStorageCannotBeInsideTheDocumentRoot(): void
  {
    $storage = __DIR__ . '/.api-storage-test-' . bin2hex(random_bytes(5));
    try {
      self::command([PHP_BINARY, 'bin/api.php', 'list'], [
        'WEBCALENDAR_API_STORAGE' => $storage,
      ], 255);
    } finally {
      (new Filesystem())->remove($storage);
    }
  }

  public static function httpAuthConfigurations(): array
  {
    return ['environment' => [true], 'settings file' => [false]];
  }

  /** @dataProvider httpAuthConfigurations */
  public function testHttpAuthInstallationsCannotIssueTokens(bool $useEnvironment): void
  {
    $fixture = self::$directory . '/http-auth-' . (int) $useEnvironment;
    $filesystem = new Filesystem();
    $filesystem->mkdir($fixture . '/includes');
    foreach (glob(dirname(__DIR__) . '/includes/*') as $path) {
      $name = basename($path);
      if ($name !== 'config.php' && $name !== 'api-bootstrap.php' && !str_starts_with($name, 'settings.php')) {
        $filesystem->symlink($path, $fixture . '/includes/' . $name);
      }
    }
    foreach (['api.php', 'includes/config.php', 'includes/api-bootstrap.php'] as $path) {
      $filesystem->copy(dirname(__DIR__) . '/' . $path, $fixture . '/' . $path);
    }
    foreach (['app', 'vendor'] as $path) {
      $filesystem->symlink(dirname(__DIR__) . '/' . $path, $fixture . '/' . $path);
    }
    file_put_contents($fixture . '/includes/settings.php', "<?php /*\n"
      . "db_type: sqlite3\ndb_database: " . self::$environment['WEBCALENDAR_DB_DATABASE']
      . "\ndb_debug: false\ndb_persistent: false\nuser_inc: user.php\nuse_http_auth: true\n*/");
    $script = <<<'PHP'
      $_SERVER = array_merge($_SERVER, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api.php/v1/tokens',
        'SCRIPT_NAME' => '/api.php', 'PHP_SELF' => '/api.php/v1/tokens',
        'SCRIPT_FILENAME' => $argv[1],
        'DOCUMENT_ROOT' => dirname($argv[1]),
        'HTTP_HOST' => 'localhost', 'SERVER_PORT' => '80', 'SERVER_PROTOCOL' => 'HTTP/1.1']);
      $_POST = ['username' => 'alice', 'password' => 'secret', 'device_name' => 'Forbidden device'];
      register_shutdown_function(static function () { fwrite(STDERR, 'HTTP_STATUS=' . http_response_code()); });
      require $argv[1];
      PHP;
    $output = self::command([PHP_BINARY, '-r', $script, $fixture . '/api.php'], [
      'WEBCALENDAR_USE_ENV' => $useEnvironment ? 'true' : 'false',
      'WEBCALENDAR_USE_HTTP_AUTH' => 'true',
    ]);
    self::assertStringContainsString('HTTP_STATUS=503', $output);
    self::assertStringNotContainsString('"token":', $output);
    self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_tokens')->fetchColumn());
  }

  private function token(): string
  {
    [$status, $body] = $this->request('POST', '/v1/tokens', ['username' => 'alice', 'password' => 'secret', 'device_name' => 'iPhone']);
    self::assertSame(201, $status, json_encode($body));
    return $body['data']['token'];
  }

  private function event(int $id, string $name, string $user, string $access = 'P', string $status = 'A'): void
  {
    self::sql('INSERT INTO webcal_entry (cal_id, cal_name, cal_description, cal_date, cal_time, cal_duration, cal_access, cal_type, cal_create_by, cal_priority, cal_mod_date, cal_mod_time) VALUES (?, ?, ?, 20261102, 100000, 60, ?, ?, ?, 5, 20261101, 120000)',
      [$id, $name, 'Description', $access, 'E', $user]);
    self::sql('INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status, cal_percent) VALUES (?, ?, ?, 0)', [$id, $user, $status]);
  }

  private function request(string $method, string $path, array $data = [], ?string $token = null): array
  {
    $context = stream_context_create(['http' => [
      'method' => $method, 'ignore_errors' => true, 'timeout' => 15,
      'header' => "Content-Type: application/json\r\n" . ($token ? "Authorization: Bearer $token\r\n" : ''),
      'content' => $data ? json_encode($data) : '',
    ]]);
    $stream = fopen('http://127.0.0.1:' . self::$port . '/api.php' . $path, 'rb', false, $context);
    self::assertIsResource($stream);
    $headers = stream_get_meta_data($stream)['wrapper_data'];
    $raw = stream_get_contents($stream);
    fclose($stream);
    preg_match('/\s(\d{3})\s/', $headers[0], $matches);
    $body = json_decode($raw, true);
    self::assertIsArray($body, $raw . "\n" . file_get_contents(self::$directory . '/server.log'));
    return [(int) $matches[1], $body, $headers];
  }

  private static function sql(string $sql, array $parameters = []): void
  {
    self::$db->prepare($sql)->execute($parameters);
  }

  private static function command(array $command, array $environment = [], int $expectedExit = 0): string
  {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), array_merge(self::$environment, $environment));
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    self::assertSame($expectedExit, proc_close($process), $output);
    return $output;
  }
}
