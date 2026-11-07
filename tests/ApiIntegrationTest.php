<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

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
    self::$db->exec('DELETE FROM webcal_access_user');
    self::$db->exec("DELETE FROM webcal_config WHERE cal_setting = 'SERVER_TIMEZONE'");
    self::$db->exec('DELETE FROM webcal_asst');
    self::$db->exec('DELETE FROM webcal_nonuser_cals');
    self::$db->exec('DELETE FROM webcal_api_tokens');
    self::$db->exec('DELETE FROM webcal_api_users');
    self::$db->exec('DELETE FROM webcal_entry_repeats_not');
    self::$db->exec('DELETE FROM webcal_entry_repeats');
    self::$db->exec('DELETE FROM webcal_entry_user');
    foreach (['webcal_entry_ext_user', 'webcal_entry_categories', 'webcal_reminders', 'webcal_site_extras', 'webcal_entry_log'] as $table) {
      self::$db->exec('DELETE FROM ' . $table);
    }
    self::$db->exec('DELETE FROM webcal_entry');
    self::$db->exec('DELETE FROM webcal_user_pref');
    self::$db->exec("UPDATE webcal_user SET cal_enabled = 'Y'");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('TIMEZONE', 'UTC')");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('UAC_ENABLED', 'N')");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('ALLOW_CONFLICTS', 'Y')");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('DEFAULT_VISIBILITY', 'P')");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('DISABLE_CROSSDAY_EVENTS', 'N')");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('LIMIT_APPTS', 'N')");
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
    self::assertSame(['events:read', 'events:write'], json_decode($row['abilities'], true));
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

  public function testProfileUsesEffectiveWeekStartAndRefreshesPreferences(): void
  {
    $token = $this->token();
    $original = self::$db->query("SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEEK_START'")->fetchColumn();
    try {
      self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('WEEK_START', '1')");
      [$status, $body] = $this->request('GET', '/v1/users/current', [], $token);
      self::assertSame(200, $status, json_encode($body));
      self::assertSame(1, $body['data']['week_start'] ?? null);
      foreach (range(0, 6) as $weekday) {
        self::sql('INSERT OR REPLACE INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES (?, ?, ?)',
          ['alice', 'WEEK_START', (string) $weekday]);
        [$status, $body] = $this->request('GET', '/v1/users/current', [], $token);
        self::assertSame(200, $status, json_encode($body));
        self::assertSame($weekday, $body['data']['week_start']);
      }
      self::$db->exec('DELETE FROM webcal_user_pref');
      self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('WEEK_START', '0')");
      [$status, $body] = $this->request('GET', '/v1/users/current', [], $token);
      self::assertSame(200, $status, json_encode($body));
      self::assertSame(0, $body['data']['week_start']);
      self::$db->exec("DELETE FROM webcal_config WHERE cal_setting = 'WEEK_START'");
      [$status, $body] = $this->request('GET', '/v1/users/current', [], $token);
      self::assertSame(200, $status, json_encode($body));
      self::assertSame(0, $body['data']['week_start']);
    } finally {
      self::$db->exec("DELETE FROM webcal_config WHERE cal_setting = 'WEEK_START'");
      if ($original !== false) {
        self::sql('INSERT INTO webcal_config (cal_setting, cal_value) VALUES (?, ?)', ['WEEK_START', $original]);
      }
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

  public function testHeadRequestsRetainAuthenticationAndReadAbilities(): void
  {
    foreach (['/api.php/v1/users/current', '/api.php/v1/events'] as $uri) {
      foreach ([null, 'synthetic-invalid-token'] as $token) {
        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->dispatchRequest('HEAD', $uri, $token)[0]);
      }
    }
    $token = $this->token();
    self::assertSame(Response::HTTP_OK, $this->dispatchRequest('HEAD', '/api.php/v1/users/current', $token)[0]);
    self::$db->exec("UPDATE webcal_api_tokens SET abilities = '[]'");
    self::assertSame(Response::HTTP_FORBIDDEN, $this->dispatchRequest('HEAD', '/api.php/v1/events', $token)[0]);
  }

  /** @dataProvider joinedScriptPaths */
  public function testScriptNamePrefixesCannotAliasProtectedApiRoutes(string $script, string $uri): void
  {
    $result = $this->dispatchRequest('GET', $uri, $this->token(), ['SCRIPT_NAME' => $script, 'PHP_SELF' => $script]);
    self::assertSame(Response::HTTP_NOT_FOUND, $result[0], json_encode($result));
    self::assertStringNotContainsString('"username":"alice"', $result[1]);
  }

  public static function joinedScriptPaths(): array
  {
    return [
      ['/api.php', '/api.phpv1/users/current'],
      ['/api.php', '/api%2Ephpv1/users/current'],
      ['/api.php', '/%61pi.phpv1/users/current'],
      ['/calendar/api.php', '/calendar/api.phpv1/users/current'],
      ['/calendar/api.php', '/calendar/api%2Ephpv1/users/current'],
      ['/calendar/api.php', '/calendar/%61pi.phpv1/users/current'],
    ];
  }

  public function testValidSubdirectoryScriptPathStillReachesTheApi(): void
  {
    $result = $this->dispatchRequest('GET', '/calendar/api.php/v1/users/current', $this->token(),
      ['SCRIPT_NAME' => '/calendar/api.php', 'PHP_SELF' => '/calendar/api.php']);
    self::assertSame(Response::HTTP_OK, $result[0]);
    self::assertSame('alice', json_decode($result[1], true)['data']['username']);
  }

  public function testUntrustedForwardedHeadersCannotChangeApiOriginOrRateLimitIdentity(): void
  {
    $token = $this->token();
    foreach (['::ffff:203.0.113.7', '2002:cb00:7107::1', '64:ff9b::cb00:7107'] as $address) {
      $result = $this->dispatchRequest('GET', '/api.php/v1/users/current', $token, [
        'REMOTE_ADDR' => $address, 'HTTP_X_FORWARDED_FOR' => '10.0.0.9', 'HTTP_X_FORWARDED_HOST' => 'attacker.example',
        'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '8443', 'HTTP_X_FORWARDED_PREFIX' => '/spoofed',
        'HTTP_FORWARDED' => 'for=10.0.0.9;host=attacker.example;proto=https',
      ]);
      self::assertSame(Response::HTTP_OK, $result[0]);
      self::assertSame($address, $result[2]);
      self::assertSame('http://calendar.example.test', $result[3]);
      self::assertSame('/api.php', $result[4]);
    }
  }

  private function dispatchRequest(string $method, string $uri, ?string $token = null, array $server = []): array
  {
    $body = <<<'PHP'
      $app = require 'app/Api/bootstrap.php';
      [$method, $uri, $token, $server] = json_decode($argv[1], true);
      $server = array_replace(['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri,
        'SCRIPT_NAME' => '/api.php', 'PHP_SELF' => '/api.php', 'SCRIPT_FILENAME' => getcwd() . '/api.php',
        'HTTP_HOST' => 'calendar.example.test', 'SERVER_NAME' => 'calendar.example.test',
        'SERVER_PORT' => '80', 'REMOTE_ADDR' => '203.0.113.7', 'HTTP_ACCEPT' => 'application/json'], $server);
      if ($token !== null) { $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token; }
      $request = new Illuminate\Http\Request([], [], [], [], [], $server);
      $response = $app->handle($request);
      echo json_encode([$response->getStatusCode(), $response->getContent(), $request->ip(),
        $request->getSchemeAndHttpHost(), $request->getBaseUrl()]);
      PHP;
    return json_decode(self::command([PHP_BINARY, '-r', "require 'vendor/autoload.php'; " . $this->legacyScript($body),
      json_encode([$method, $uri, $token, $server])]), true);
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

  public function testPersonalEventsCanBeCreatedAndReadBack(): void
  {
    $token = $this->token();
    foreach ([
      ['date' => '2026-11-10'],
      ['date' => '2026-11-11', 'all_day' => true, 'untimed' => false, 'duration_minutes' => 1440],
      ['date' => '2026-11-12', 'untimed' => false, 'starts_at' => '2026-11-12T09:00:00+00:00', 'duration_minutes' => 37],
    ] as $changes) {
      [$status, $body] = $this->request('POST', '/v1/events', $this->draft($changes), $token);
      self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
      $saved = $body['data'];
      self::assertSame($changes['date'], $saved['date']);
      self::assertSame(true, $saved['can_edit']);
      self::assertSame('A', $saved['status']);
      self::assertSame('Bring notes', $saved['description']);
      [$status, $read] = $this->request('GET', '/v1/events?start_date=' . $saved['date'] . '&end_date=' . $saved['date'], [], $token);
      self::assertSame(Response::HTTP_OK, $status);
      self::assertSame([$saved], $read['data']);
    }
    self::assertSame(3, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry_user')->fetchColumn());
    self::assertSame(3, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry_log')->fetchColumn());
    $rows = self::$db->query('SELECT cal_time, cal_duration FROM webcal_entry ORDER BY cal_id')->fetchAll(PDO::FETCH_NUM);
    self::assertEquals([[-1, 0], [0, 1440], [90000, 37]], $rows);
  }

  public function testPartialUpdatesPreserveMetadataAndClearOptionalFields(): void
  {
    $this->event(1, 'Private appointment', 'alice', 'R');
    self::sql("UPDATE webcal_entry SET cal_priority = 2, cal_url = 'https://example.com' WHERE cal_id = 1");
    self::sql("INSERT INTO webcal_entry_categories (cal_id, cat_id, cat_owner) VALUES (1, 42, 'alice')");
    self::sql("INSERT INTO webcal_reminders (cal_id, cal_date) VALUES (1, 20261101)");
    self::sql("INSERT INTO webcal_site_extras (cal_id, cal_name, cal_type, cal_data) VALUES (1, 'note', 1, 'preserve')");
    $token = $this->token();
    [$status, $body] = $this->request('PATCH', '/v1/events/1', ['title' => "Alice's appointment", 'description' => null, 'location' => null], $token);
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    self::assertSame("Alice's appointment", $body['data']['title']);
    self::assertSame('R', $body['data']['access']);
    self::assertSame('', $body['data']['description']);
    self::assertNull($body['data']['location']);
    self::assertSame('2026-11-02T10:00:00+00:00', $body['data']['starts_at']);
    $row = self::$db->query('SELECT * FROM webcal_entry WHERE cal_id = 1')->fetch(PDO::FETCH_ASSOC);
    self::assertSame('alice', $row['cal_create_by']);
    self::assertSame(2, (int) $row['cal_priority']);
    self::assertSame('https://example.com', $row['cal_url']);
    foreach (['webcal_entry_categories', 'webcal_reminders', 'webcal_site_extras'] as $table) {
      self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM ' . $table . ' WHERE cal_id = 1')->fetchColumn());
    }
    [$status] = $this->request('PATCH', '/v1/events/1', ['title' => ''], $token);
    self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $status);
  }

  /** @dataProvider reminderScheduleChanges */
  public function testReminderDeliveryStateTracksTheEffectiveTrigger(string $related, int $absolute, array $patch, bool $reset): void
  {
    $this->event(1, 'Reminder appointment', 'alice');
    self::sql('INSERT INTO webcal_reminders (cal_id, cal_date, cal_offset, cal_related, cal_before, cal_repeats, cal_duration, cal_action, cal_last_sent, cal_times_sent) VALUES (1, ?, 15, ?, ?, 2, 10, ?, 1793610000, 3)',
      [$absolute, $related, 'Y', 'EMAIL']);
    $before = self::$db->query('SELECT * FROM webcal_reminders WHERE cal_id = 1')->fetch(PDO::FETCH_ASSOC);
    [$status, $body] = $this->request('PATCH', '/v1/events/1', $patch, $this->token());
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    $after = self::$db->query('SELECT * FROM webcal_reminders WHERE cal_id = 1')->fetch(PDO::FETCH_ASSOC);
    if ($reset) { $before['cal_last_sent'] = 0; $before['cal_times_sent'] = 0; }
    self::assertEquals($before, $after);
  }

  public static function reminderScheduleChanges(): array
  {
    return [
      'start moved' => ['S', 0, ['starts_at' => '2026-11-02T11:00:00Z'], true],
      'end moved' => ['E', 0, ['duration_minutes' => 90], true],
      'start ignores duration' => ['S', 0, ['duration_minutes' => 90], false],
      'same end instant' => ['E', 0, ['starts_at' => '2026-11-02T10:30:00Z', 'duration_minutes' => 30], false],
      'absolute alarm' => ['S', 1793610000, ['starts_at' => '2026-11-02T11:00:00Z'], false],
      'metadata' => ['S', 0, ['title' => 'Renamed'], false],
      'same schedule' => ['E', 0, ['duration_minutes' => 60], false],
      'equivalent offset' => ['S', 0, ['starts_at' => '2026-11-02T11:00:00+01:00'], false],
      'timed to untimed' => ['S', 0, ['untimed' => true, 'starts_at' => null, 'duration_minutes' => 0], true],
      'timed to all day' => ['E', 0, ['all_day' => true, 'starts_at' => null, 'duration_minutes' => 1440], true],
    ];
  }

  public function testReminderRearmingRollsBackWithAFailedActivityLog(): void
  {
    $this->event(1, 'Original', 'alice');
    self::sql("INSERT INTO webcal_reminders (cal_id, cal_date, cal_related, cal_times_sent, cal_last_sent) VALUES (1, 0, 'S', 1, 1793610000)");
    $token = $this->token();
    self::$db->exec("CREATE TRIGGER fail_reminder_log BEFORE INSERT ON webcal_entry_log BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
    try {
      [$status] = $this->request('PATCH', '/v1/events/1', ['starts_at' => '2026-11-02T11:00:00Z'], $token);
      self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $status);
      self::assertSame(100000, (int) self::$db->query('SELECT cal_time FROM webcal_entry WHERE cal_id = 1')->fetchColumn());
      self::assertSame(1, (int) self::$db->query('SELECT cal_times_sent FROM webcal_reminders WHERE cal_id = 1')->fetchColumn());
      self::assertSame(1793610000, (int) self::$db->query('SELECT cal_last_sent FROM webcal_reminders WHERE cal_id = 1')->fetchColumn());
    } finally { self::$db->exec('DROP TRIGGER fail_reminder_log'); }
  }

  /** @dataProvider floatingReminderConversions */
  public function testFloatingReminderConversionsFollowTheSender(string $runtimeZone, string $accountZone, array $before, array $patch, string $related, bool $reset): void
  {
    self::sql("UPDATE webcal_config SET cal_value = ? WHERE cal_setting = 'TIMEZONE'", [$runtimeZone]);
    self::sql("INSERT INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES ('alice', 'TIMEZONE', ?)", [$accountZone]);
    $this->event(1, 'Floating reminder', 'alice');
    self::sql('UPDATE webcal_entry SET cal_date = ?, cal_time = ?, cal_duration = ? WHERE cal_id = 1', $before);
    self::sql('INSERT INTO webcal_reminders (cal_id, cal_date, cal_related, cal_times_sent, cal_last_sent) VALUES (1, 0, ?, 1, 1793610000)', [$related]);
    $oldRow = self::$db->query('SELECT * FROM webcal_entry WHERE cal_id = 1')->fetch(PDO::FETCH_ASSOC);
    [$status, $body] = $this->request('PATCH', '/v1/events/1', $patch, $this->token());
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    $newRow = self::$db->query('SELECT * FROM webcal_entry WHERE cal_id = 1')->fetch(PDO::FETCH_ASSOC);
    self::assertSame($reset, $this->senderAnchor($oldRow, $runtimeZone, $related) !== $this->senderAnchor($newRow, $runtimeZone, $related));
    $reminder = self::$db->query('SELECT * FROM webcal_reminders WHERE cal_id = 1')->fetch(PDO::FETCH_ASSOC);
    self::assertSame($reset ? 0 : 1, (int) $reminder['cal_times_sent']);
    self::assertSame($reset ? 0 : 1793610000, (int) $reminder['cal_last_sent']);
  }

  public static function floatingReminderConversions(): array
  {
    $cases = [];
    foreach (['all-day' => [0, 1440], 'untimed' => [-1, 0]] as $name => [$time, $duration]) {
      $floating = ['date' => '2026-11-02', 'starts_at' => null, 'all_day' => $time === 0, 'untimed' => $time < 0, 'duration_minutes' => $duration];
      foreach ([50000 => false, 0 => true] as $timedTime => $reset) {
        $start = $timedTime === 0 ? '2026-11-02T00:00:00Z' : '2026-11-02T00:00:00-05:00';
        $date = $timedTime === 0 ? '2026-11-01' : '2026-11-02';
        $timed = ['date' => $date, 'starts_at' => $start, 'all_day' => false, 'untimed' => false, 'duration_minutes' => 60];
        $cases[$name . ' to timed ' . $timedTime] = ['America/New_York', 'America/New_York', [20261102, $time, $duration], $timed, 'S', $reset];
        $cases['timed ' . $timedTime . ' to ' . $name] = ['America/New_York', 'America/New_York', [20261102, $timedTime, 60], $floating, 'S', $reset];
      }
      $endStart = $time === 0 ? '2026-11-02T23:00:00Z' : '2026-11-01T23:00:00Z';
      $endDate = $time === 0 ? '2026-11-02' : '2026-11-01';
      $cases[$name . ' unchanged UTC end'] = ['America/New_York', 'America/New_York', [20261102, $time, $duration],
        ['date' => $endDate, 'starts_at' => $endStart, 'all_day' => false, 'untimed' => false, 'duration_minutes' => 60], 'E', false];
      $cases[$name . ' DST midnight'] = ['America/New_York', 'America/New_York', [20261101, $time, $duration],
        ['date' => '2026-11-01', 'starts_at' => '2026-11-01T00:00:00-04:00', 'all_day' => false, 'untimed' => false, 'duration_minutes' => 60], 'S', false];
    }
    $cases['runtime differs from account'] = ['America/New_York', 'Europe/Paris', [20261102, 0, 1440],
      ['date' => '2026-11-02', 'starts_at' => '2026-11-02T06:00:00+01:00', 'all_day' => false, 'untimed' => false, 'duration_minutes' => 60], 'S', false];
    $cases['positive-offset runtime'] = ['Europe/Paris', 'America/New_York', [20261102, -1, 0],
      ['date' => '2026-11-01', 'starts_at' => '2026-11-01T18:00:00-05:00', 'all_day' => false, 'untimed' => false, 'duration_minutes' => 60], 'S', false];
    return $cases;
  }

  private function senderAnchor(array $row, string $timezone, string $related): int
  {
    $script = <<<'PHP'
      require 'includes/classes/Event.php';
      $GLOBALS['login'] = 'alice';
      date_default_timezone_set($argv[1]);
      $row = json_decode($argv[2], true);
      $event = new Event('Fixture', '', (string) $row['cal_date'], (string) $row['cal_time'], 1, null, 5, 'P',
        (int) $row['cal_duration'], 'A', 'alice', '', 'alice', 'E', null, '', '', 0, 0, '', '');
      echo $argv[3] === 'S' ? $event->getDateTimeTS() : $event->getEndDateTimeTS();
      PHP;
    return (int) self::command([PHP_BINARY, '-r', $script, $timezone, json_encode($row), $related]);
  }

  public function testWritesRequireAbilityPermissionAndPersonalOwnership(): void
  {
    $this->event(1, 'Personal', 'alice');
    $this->event(2, 'Other calendar', 'admin');
    [$status] = $this->request('POST', '/v1/events', $this->draft());
    self::assertSame(Response::HTTP_UNAUTHORIZED, $status);
    $token = $this->token();
    foreach (['0', '2147483648', '99999999999999999999999'] as $id) {
      [$status] = $this->request('PATCH', '/v1/events/' . $id, ['title' => 'Missing'], $token);
      self::assertSame(Response::HTTP_NOT_FOUND, $status);
    }
    self::$db->exec("UPDATE webcal_api_tokens SET abilities = '[\"events:read\"]'");
    foreach ([['POST', '/v1/events', $this->draft()], ['PATCH', '/v1/events/1', ['title' => 'Changed']]] as [$method, $path, $data]) {
      [$status] = $this->request($method, $path, $data, $token);
      self::assertSame(Response::HTTP_FORBIDDEN, $status);
    }
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-02&end_date=2026-11-02', [], $token);
    self::assertFalse($body['data'][0]['can_edit']);
    self::$db->exec("UPDATE webcal_api_tokens SET abilities = '[\"events:read\",\"events:write\"]'");
    [$status] = $this->request('PATCH', '/v1/events/2', ['title' => 'Forbidden'], $token);
    self::assertSame(Response::HTTP_NOT_FOUND, $status);
    self::sql("INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status) VALUES (2, 'alice', 'A')");
    [$status] = $this->request('PATCH', '/v1/events/2', ['title' => 'Forbidden'], $token);
    self::assertSame(Response::HTTP_FORBIDDEN, $status);
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('UAC_ENABLED', 'Y')");
    self::sql('INSERT OR REPLACE INTO webcal_access_function (cal_login, cal_permissions) VALUES (?, ?)', ['alice', str_repeat('N', 28)]);
    [$status] = $this->request('POST', '/v1/events', $this->draft(), $token);
    self::assertSame(Response::HTTP_FORBIDDEN, $status);
    [$status] = $this->request('PATCH', '/v1/events/1', ['title' => 'Forbidden'], $token);
    self::assertSame(Response::HTTP_FORBIDDEN, $status);
    self::assertSame('Personal', self::$db->query('SELECT cal_name FROM webcal_entry WHERE cal_id = 1')->fetchColumn());
  }

  public function testSharedRecurringAndDetachedEventsCannotBeEdited(): void
  {
    $token = $this->token();
    foreach ([1, 2, 3, 4, 5, 6, 7] as $id) { $this->event($id, 'Unsupported', 'alice'); }
    self::sql("UPDATE webcal_entry SET cal_type = 'M' WHERE cal_id = 1");
    self::sql("INSERT INTO webcal_entry_repeats (cal_id, cal_type) VALUES (1, 'daily')");
    self::sql("INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status) VALUES (2, 'admin', 'A')");
    self::sql("INSERT INTO webcal_entry_ext_user (cal_id, cal_fullname, cal_email) VALUES (3, 'Guest', 'guest@example.com')");
    self::sql('UPDATE webcal_entry SET cal_group_id = 1 WHERE cal_id = 4');
    self::sql("UPDATE webcal_entry_user SET cal_status = 'W' WHERE cal_id = 5");
    self::sql('UPDATE webcal_entry SET cal_ext_for_id = 6 WHERE cal_id = 7');
    foreach ([1, 2, 3, 4, 5, 6, 7] as $id) {
      [$status, $body] = $this->request('PATCH', '/v1/events/' . $id, ['title' => 'Not saved'], $token);
      self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $status, json_encode($body));
      self::assertSame('unsupported_event', $body['code']);
    }
    self::assertSame(0, (int) self::$db->query("SELECT COUNT(*) FROM webcal_entry WHERE cal_name = 'Not saved'")->fetchColumn());
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-02&end_date=2026-11-02', [], $token);
    self::assertSame(Response::HTTP_OK, $status);
    self::assertNotEmpty($body['data']);
    self::assertNotContains(true, array_column($body['data'], 'can_edit'));
  }

  public function testInvalidWritePayloadsDoNotCreateEvents(): void
  {
    $token = $this->token();
    foreach ([
      ['title' => ''], ['title' => str_repeat('x', 81)], ['location' => str_repeat('x', 101)],
      ['date' => '2026-02-30'], ['all_day' => true, 'untimed' => true],
      ['username' => 'admin'], ['participants' => ['admin']], ['recurrence' => ['frequency' => 'daily']],
      ['starts_at' => '2026-11-10T09:00:00+00:00'],
      ['untimed' => false, 'starts_at' => '2026-11-10T09:00:00', 'duration_minutes' => 60],
      ['untimed' => false, 'starts_at' => '2026-11-10T09:00:01Z', 'duration_minutes' => 60],
      ['untimed' => false, 'starts_at' => '2026-11-10T09:00:00Z', 'duration_minutes' => 0],
      ['untimed' => false, 'starts_at' => '2026-11-11T09:00:00Z', 'duration_minutes' => 60],
      ['untimed' => false, 'starts_at' => '2026-11-10T00:00:00Z', 'duration_minutes' => 1440],
      ['title' => '<img src=x onerror=alert(1)>'],
      ['title' => '<img src=x onerror=alert(1)'], ['location' => '<svg/onload=alert(1)'],
      ['title' => "Bad\0title"], ['location' => "Bad\0location"], ['description' => "Bad\0description"],
    ] as $changes) {
      [$status, $body] = $this->request('POST', '/v1/events', $this->draft($changes), $token);
      self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $status, json_encode([$changes, $body]));
      self::assertArrayHasKey('errors', $body);
    }
    self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry')->fetchColumn());
  }

  public function testTimedWritesUseAccountDatesAndExplicitDstInstants(): void
  {
    self::sql("INSERT INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES ('alice', 'TIMEZONE', 'America/New_York')");
    $token = $this->token();
    [$status, $profile] = $this->request('GET', '/v1/users/current', [], $token);
    self::assertSame('America/New_York', $profile['data']['timezone']);
    foreach (['2026-11-01T01:30:00-04:00' => 53000, '2026-11-01T01:30:00-05:00' => 63000] as $start => $utcTime) {
      [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
        'date' => '2026-11-01', 'untimed' => false, 'starts_at' => $start, 'duration_minutes' => 37,
      ]), $token);
      self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
      self::assertSame($start, $body['data']['starts_at']);
      self::assertSame($utcTime, (int) self::$db->query('SELECT cal_time FROM webcal_entry WHERE cal_id = ' . $body['data']['id'])->fetchColumn());
    }
    self::sql("UPDATE webcal_user_pref SET cal_value = 'Pacific/Kiritimati' WHERE cal_login = 'alice' AND cal_setting = 'TIMEZONE'");
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2026-11-02', 'untimed' => false, 'starts_at' => '2026-11-02T09:00:00+14:00', 'duration_minutes' => 60,
    ]), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
    $row = self::$db->query('SELECT cal_date, cal_time FROM webcal_entry WHERE cal_id = ' . $body['data']['id'])->fetch(PDO::FETCH_NUM);
    self::assertEquals([20261101, 190000], $row);
    [$status, $body] = $this->request('PATCH', '/v1/events/' . $body['data']['id'], ['title' => 'Moved title only'], $token);
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    self::assertSame('2026-11-02T09:00:00+14:00', $body['data']['starts_at']);
  }

  public function testFloatingDatesRoundTripInPositiveAndNegativeOffsetZones(): void
  {
    $token = $this->token();
    $day = 10;
    foreach (['America/New_York', 'Pacific/Kiritimati'] as $timezone) {
      self::sql("INSERT OR REPLACE INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES ('alice', 'TIMEZONE', ?)", [$timezone]);
      foreach ([false, true] as $allDay) {
        $date = sprintf('2026-11-%02d', $day++);
        [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
          'date' => $date, 'all_day' => $allDay, 'untimed' => !$allDay, 'duration_minutes' => $allDay ? 1440 : 0,
        ]), $token);
        self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
        [$status, $read] = $this->request('GET', '/v1/events?start_date=' . $date . '&end_date=' . $date, [], $token);
        self::assertSame(Response::HTTP_OK, $status);
        self::assertSame([$body['data']], $read['data'], $timezone);
      }
    }
  }

  public function testOvernightWritesRetainTheFullScheduleAndReadOnlyContinuations(): void
  {
    $token = $this->token();
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'untimed' => false, 'starts_at' => '2026-11-10T23:30:00Z', 'duration_minutes' => 120,
    ]), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
    $saved = $body['data'];
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-10&end_date=2026-11-11', [], $token);
    self::assertSame([$saved], array_values(array_filter($body['data'], fn ($event) => $event['date'] === '2026-11-10')));
    $continuation = array_values(array_filter($body['data'], fn ($event) => $event['date'] === '2026-11-11'));
    self::assertCount(1, $continuation);
    self::assertSame(90, $continuation[0]['duration_minutes']);
    self::assertSame('2026-11-11T00:00:00+00:00', $continuation[0]['starts_at']);
    self::assertFalse($continuation[0]['can_edit']);
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-11&end_date=2026-11-11', [], $token);
    self::assertSame($continuation, $body['data']);
    [$status, $body] = $this->request('PATCH', '/v1/events/' . $saved['id'], ['title' => 'Updated overnight'], $token);
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    self::assertSame(120, $body['data']['duration_minutes']);
    self::assertSame('2026-11-10T23:30:00+00:00', $body['data']['starts_at']);
    [$status, $body] = $this->request('PATCH', '/v1/events/' . $saved['id'], [
      'date' => '2026-11-08', 'starts_at' => '2026-11-08T22:00:00Z', 'duration_minutes' => 4000,
    ], $token);
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-09&end_date=2026-11-09', [], $token);
    self::assertSame(1440, $body['data'][0]['duration_minutes']);
    self::assertFalse($body['data'][0]['all_day']);
    self::assertFalse($body['data'][0]['can_edit']);
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2026-11-11&end_date=2026-11-11', [], $token);
    self::assertSame(1000, $body['data'][0]['duration_minutes']);
    self::assertFalse($body['data'][0]['can_edit']);
  }

  public function testOvernightContinuationsUseElapsedMinutesAcrossDst(): void
  {
    self::sql("INSERT INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES ('alice', 'TIMEZONE', 'America/New_York')");
    $token = $this->token();
    foreach ([
      ['2026-03-07', '2026-03-07T23:30:00-05:00', '2026-03-08', '2026-03-08T00:00:00-05:00'],
      ['2026-10-31', '2026-10-31T23:30:00-04:00', '2026-11-01', '2026-11-01T00:00:00-04:00'],
    ] as [$date, $start, $nextDate, $midnight]) {
      [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
        'date' => $date, 'untimed' => false, 'starts_at' => $start, 'duration_minutes' => 180,
      ]), $token);
      self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
      [$status, $body] = $this->request('GET', '/v1/events?start_date=' . $nextDate . '&end_date=' . $nextDate, [], $token);
      self::assertSame(Response::HTTP_OK, $status);
      self::assertCount(1, $body['data']);
      self::assertSame($midnight, $body['data'][0]['starts_at']);
      self::assertSame(150, $body['data'][0]['duration_minutes']);
      self::assertFalse($body['data'][0]['can_edit']);
    }
    self::sql("UPDATE webcal_user_pref SET cal_value = 'America/Sao_Paulo' WHERE cal_login = 'alice' AND cal_setting = 'TIMEZONE'");
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2018-11-03', 'untimed' => false, 'starts_at' => '2018-11-03T23:30:00-03:00', 'duration_minutes' => 2880,
    ]), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
    [$status, $body] = $this->request('GET', '/v1/events?start_date=2018-11-04&end_date=2018-11-05', [], $token);
    self::assertSame([1380, 1440], array_column($body['data'], 'duration_minutes'));
    self::assertSame(['2018-11-04T01:00:00-02:00', '2018-11-05T00:00:00-02:00'], array_column($body['data'], 'starts_at'));
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2017-10-15', 'all_day' => true, 'untimed' => false, 'duration_minutes' => 1440,
    ]), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('ALLOW_CONFLICTS', 'N')");
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2017-10-16', 'untimed' => false, 'starts_at' => '2017-10-16T00:30:00-02:00', 'duration_minutes' => 15,
    ]), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
  }

  public function testTimedWritesRejectUtcYearOverflowBeforeMutation(): void
  {
    $token = $this->token();
    foreach ([
      ['America/New_York', '9999-12-31', '9999-12-31T23:30:00-05:00'],
      ['Etc/GMT-14', '0001-01-01', '0001-01-01T00:30:00+14:00'],
    ] as [$timezone, $date, $start]) {
      self::sql("INSERT OR REPLACE INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES ('alice', 'TIMEZONE', ?)", [$timezone]);
      [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
        'date' => $date, 'untimed' => false, 'starts_at' => $start, 'duration_minutes' => 15,
      ]), $token);
      self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $status, json_encode($body));
      self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry')->fetchColumn());
    }
  }

  public function testFailedWritesRollBackEntryParticipationAndLog(): void
  {
    $token = $this->token();
    foreach (['webcal_entry_user', 'webcal_entry_log'] as $table) {
      self::$db->exec("CREATE TRIGGER fail_api_write BEFORE INSERT ON $table BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
      try {
        [$status] = $this->request('POST', '/v1/events', $this->draft(), $token);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $status);
        foreach (['webcal_entry', 'webcal_entry_user', 'webcal_entry_log'] as $check) {
          self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM ' . $check)->fetchColumn(), $check);
        }
      } finally { self::$db->exec('DROP TRIGGER fail_api_write'); }
    }
    $this->event(1, 'Original', 'alice');
    self::$db->exec("CREATE TRIGGER fail_api_log BEFORE INSERT ON webcal_entry_log BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
    try {
      [$status] = $this->request('PATCH', '/v1/events/1', ['title' => 'Not saved'], $token);
      self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $status);
      self::assertSame('Original', self::$db->query('SELECT cal_name FROM webcal_entry WHERE cal_id = 1')->fetchColumn());
    } finally { self::$db->exec('DROP TRIGGER fail_api_log'); }
  }

  public function testConfiguredConflictsRejectWritesIncludingRecurringAppointments(): void
  {
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('ALLOW_CONFLICTS', 'N')");
    $this->event(1, 'Busy', 'alice');
    $token = $this->token();
    $draft = $this->draft(['date' => '2026-11-02', 'untimed' => false, 'starts_at' => '2026-11-02T10:30:00Z', 'duration_minutes' => 37]);
    [$status, $body] = $this->request('POST', '/v1/events', $draft, $token);
    self::assertSame(Response::HTTP_CONFLICT, $status, json_encode($body));
    self::assertSame('schedule_conflict', $body['code']);
    self::sql("UPDATE webcal_entry SET cal_type = 'M' WHERE cal_id = 1");
    self::sql("INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency) VALUES (1, 'daily', 1)");
    $draft['date'] = '2026-11-03';
    $draft['starts_at'] = '2026-11-03T10:30:00Z';
    [$status] = $this->request('POST', '/v1/events', $draft, $token);
    self::assertSame(Response::HTTP_CONFLICT, $status);
    self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry')->fetchColumn());
  }

  public function testScheduleChangesAreValidatedAndConvertTimingModes(): void
  {
    $this->event(1, 'Appointment', 'alice');
    $token = $this->token();
    [$status] = $this->request('PATCH', '/v1/events/1', ['date' => '2026-11-03'], $token);
    self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $status);
    [$status, $body] = $this->request('PATCH', '/v1/events/1', [
      'date' => '2026-11-03', 'starts_at' => '2026-11-03T11:35:00Z', 'duration_minutes' => 17,
    ], $token);
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    self::assertSame(17, $body['data']['duration_minutes']);
    [$status, $body] = $this->request('PATCH', '/v1/events/1', [
      'all_day' => true, 'untimed' => false, 'starts_at' => null, 'duration_minutes' => 1440,
    ], $token);
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    self::assertTrue($body['data']['all_day']);
    self::assertNull($body['data']['starts_at']);
    [$status, $body] = $this->request('PATCH', '/v1/events/1', [
      'all_day' => false, 'untimed' => true, 'starts_at' => null, 'duration_minutes' => 0,
    ], $token);
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    self::assertTrue($body['data']['untimed']);
    self::assertSame('2026-11-03', $body['data']['date']);
    [$status] = $this->request('PATCH', '/v1/events/1', [], $token);
    self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $status);
  }

  public function testReadOnlyInstallationsDenyWritesAndAdvertiseReadOnlyEvents(): void
  {
    $this->event(1, 'Read only', 'alice');
    $token = $this->token();
    $body = <<<'PHP'
      $app = require 'app/Api/bootstrap.php';
      $request = Illuminate\Http\Request::create($argv[1], $argv[2], [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $argv[3]], $argv[4]);
      $response = $app->handle($request);
      echo json_encode([$response->getStatusCode(), json_decode($response->getContent(), true)]);
      PHP;
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript($body);
    foreach ([['POST', '/v1/events', $this->draft()], ['PATCH', '/v1/events/1', ['title' => 'Denied']]] as [$method, $path, $data]) {
      $result = json_decode(self::command([PHP_BINARY, '-r', $script, $path, $method, $token, json_encode($data)],
        ['WEBCALENDAR_READONLY' => 'true']), true);
      self::assertSame(Response::HTTP_FORBIDDEN, $result[0]);
    }
    $result = json_decode(self::command([PHP_BINARY, '-r', $script,
      '/v1/events?start_date=2026-11-02&end_date=2026-11-02', 'GET', $token, '{}'], ['WEBCALENDAR_READONLY' => 'true']), true);
    self::assertSame(Response::HTTP_OK, $result[0]);
    self::assertFalse($result[1]['data'][0]['can_edit']);
  }

  public function testConflictsIncludeOvernightEventsAndTouchingEventsAreAllowed(): void
  {
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('ALLOW_CONFLICTS', 'N')");
    $this->event(1, 'Overnight', 'alice');
    self::sql('UPDATE webcal_entry SET cal_time = 233000, cal_duration = 120 WHERE cal_id = 1');
    $token = $this->token();
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2026-11-03', 'untimed' => false, 'starts_at' => '2026-11-03T00:30:00Z', 'duration_minutes' => 15,
    ]), $token);
    self::assertSame(Response::HTTP_CONFLICT, $status, json_encode($body));
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2026-11-03', 'untimed' => false, 'starts_at' => '2026-11-03T01:30:00Z', 'duration_minutes' => 15,
    ]), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
    self::sql("UPDATE webcal_entry SET cal_type = 'M' WHERE cal_id = 1");
    self::sql("INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency) VALUES (1, 'daily', 1)");
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2026-11-04', 'untimed' => false, 'starts_at' => '2026-11-04T00:30:00Z', 'duration_minutes' => 15,
    ]), $token);
    self::assertSame(Response::HTTP_CONFLICT, $status, json_encode($body));
  }

  public function testConflictCheckingUsesFloatingAllDayDatesAndDailyLimits(): void
  {
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('ALLOW_CONFLICTS', 'N')");
    self::sql("INSERT INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES ('alice', 'TIMEZONE', 'America/New_York')");
    $this->event(1, 'All day', 'alice');
    self::sql('UPDATE webcal_entry SET cal_time = 0, cal_duration = 1440 WHERE cal_id = 1');
    $token = $this->token();
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2026-11-01', 'untimed' => false, 'starts_at' => '2026-11-01T23:00:00-05:00', 'duration_minutes' => 30,
    ]), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
    [$status] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2026-11-02', 'untimed' => false, 'starts_at' => '2026-11-02T00:30:00-05:00', 'duration_minutes' => 30,
    ]), $token);
    self::assertSame(Response::HTTP_CONFLICT, $status);
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('LIMIT_APPTS', 'Y')");
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('LIMIT_APPTS_NUMBER', '1')");
    [$status] = $this->request('POST', '/v1/events', $this->draft([
      'date' => '2026-11-01', 'untimed' => false, 'starts_at' => '2026-11-01T12:00:00-05:00', 'duration_minutes' => 30,
    ]), $token);
    self::assertSame(Response::HTTP_CONFLICT, $status);
  }

  public function testCrossDayPolicyAndSafeDescriptionHandling(): void
  {
    $token = $this->token();
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('DISABLE_CROSSDAY_EVENTS', 'Y')");
    [$status] = $this->request('POST', '/v1/events', $this->draft([
      'untimed' => false, 'starts_at' => '2026-11-10T23:30:00Z', 'duration_minutes' => 60,
    ]), $token);
    self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $status);
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('DEFAULT_VISIBILITY', 'R')");
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft([
      'description' => '<b>Keep notes</b><script>alert(1)</script><img src=x onerror=alert(1)>',
    ]), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
    self::assertSame('R', $body['data']['access']);
    self::assertStringContainsString('<b>Keep notes</b>', $body['data']['description']);
    self::assertStringNotContainsString('script', $body['data']['description']);
    self::assertStringNotContainsString('onerror', $body['data']['description']);
    [$status] = $this->request('POST', '/v1/events', $this->draft(['description' => str_repeat('🙂', 17000)]), $token);
    self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $status);
  }

  /** @dataProvider collidingTables */
  public function testIdCollisionRetriesTheWholeTransaction(string $table): void
  {
    $this->token();
    $body = <<<'PHP'
      Illuminate\Database\Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
        return new class($pdo, $database, $prefix, $config) extends Illuminate\Database\SQLiteConnection {
          public int $collisions = 0;
          public function insert($query, $bindings = []) {
            if (str_contains($query, 'insert into "TARGET_TABLE"') && $this->collisions++ === 0) {
              $cause = new PDOException('UNIQUE constraint failed: TARGET_TABLE');
              $cause->errorInfo = ['23000', 19, $cause->getMessage()];
              throw new Illuminate\Database\QueryException('webcalendar', $query, $bindings, $cause);
            }
            return parent::insert($query, $bindings);
          }
        };
      });
      $app = require 'app/Api/bootstrap.php';
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      $saved = $app->make(WebCalendar\Services\CalendarEventWriter::class)->create('alice', json_decode($argv[1], true));
      echo json_encode([$saved, Illuminate\Support\Facades\DB::connection()->collisions]);
      PHP;
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript(str_replace('TARGET_TABLE', $table, $body));
    $result = json_decode(self::command([PHP_BINARY, '-r', $script, json_encode($this->draft())]), true);
    self::assertSame('Appointment', $result[0]['title']);
    self::assertSame(2, $result[1]);
    foreach (['webcal_entry', 'webcal_entry_user', 'webcal_entry_log'] as $table) {
      self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn(), $table);
    }
  }

  public static function collidingTables(): array
  {
    return [['webcal_entry'], ['webcal_entry_log']];
  }

  /** @dataProvider concurrentCreatePolicies */
  public function testCreateRechecksTheScheduleAfterAnotherConnectionCommits(bool $dailyLimit, bool $delegated = false): void
  {
    $this->token();
    if ($delegated) {
      self::sql("DELETE FROM webcal_config WHERE cal_setting = 'TIMEZONE'");
      self::sql("INSERT INTO webcal_nonuser_cals (cal_login, cal_firstname, cal_lastname, cal_admin) VALUES ('__nu_team', 'Team', 'Calendar', 'admin')");
      self::sql("INSERT INTO webcal_asst VALUES ('__nu_team', 'alice')");
      self::sql("INSERT INTO webcal_asst VALUES ('__nu_team', 'admin')");
      self::assertSame(201, $this->request('POST', '/v1/tokens', ['username' => 'admin', 'password' => 'admin', 'device_name' => 'Second assistant'])[0]);
    }
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('ALLOW_CONFLICTS', 'N')");
    if ($dailyLimit) {
      self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('LIMIT_APPTS', 'Y')");
      self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('LIMIT_APPTS_NUMBER', '1')");
    }
    $body = <<<'PHP'
      Illuminate\Database\Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
        return new class($pdo, $database, $prefix, $config) extends Illuminate\Database\SQLiteConnection {
          public function beginTransaction() {
            if (getenv('PAUSE_BEFORE_TRANSACTION') === '1') {
              fwrite(STDOUT, "ready\n"); fflush(STDOUT);
              if (trim(fgets(STDIN)) !== 'continue') { throw new RuntimeException('Missing release barrier'); }
            }
            return parent::beginTransaction();
          }
        };
      });
      $app = require 'app/Api/bootstrap.php';
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      try {
        $saved = $app->make(WebCalendar\Services\CalendarEventWriter::class)->create($argv[2] ?? 'alice', json_decode($argv[1], true), $argv[3] ?? null);
        echo json_encode([201, $saved]);
      } catch (WebCalendar\Api\Exceptions\EventWriteException $e) {
        echo json_encode([$e->getStatusCode()]);
      }
      PHP;
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript($body);
    $draft = $this->draft(['untimed' => false, 'starts_at' => '2026-11-10T09:00:00Z', 'duration_minutes' => 60]);
    $process = proc_open([PHP_BINARY, '-r', $script, json_encode($draft), 'alice', $delegated ? '__nu_team' : 'alice'],
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__),
      array_merge(self::$environment, ['PAUSE_BEFORE_TRANSACTION' => '1']));
    try {
      stream_set_timeout($pipes[1], 10);
      self::assertSame("ready\n", fgets($pipes[1]));
      $secondDraft = $dailyLimit ? array_replace($draft, ['starts_at' => '2026-11-10T11:00:00Z']) : $draft;
      $second = json_decode(self::command([PHP_BINARY, '-r', $script, json_encode($secondDraft), $delegated ? 'admin' : 'alice', $delegated ? '__nu_team' : 'alice']), true);
      self::assertSame(Response::HTTP_CREATED, $second[0]);
      fwrite($pipes[0], "continue\n"); fclose($pipes[0]);
      $output = stream_get_contents($pipes[1]);
      $errors = stream_get_contents($pipes[2]);
      self::assertSame([Response::HTTP_CONFLICT], json_decode($output, true), $output . $errors);
    } finally {
      foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
      proc_terminate($process);
      proc_close($process);
    }
    self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry')->fetchColumn());
    self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry_log')->fetchColumn());
  }

  public static function concurrentCreatePolicies(): array
  {
    return ['overlap on empty calendar' => [false], 'last daily slot' => [true],
      'assistants overlap' => [false, true], 'assistants daily limit' => [true, true]];
  }

  /** @dataProvider contendedWrites */
  public function testSqliteContentionRetriesWithFreshState(string $operation, bool $exhaust): void
  {
    $this->token();
    if ($operation === 'update') { $this->event(1, 'Original', 'alice'); }
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('ALLOW_CONFLICTS', 'N')");
    $body = <<<'PHP'
      Illuminate\Database\Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
        return new class($pdo, $database, $prefix, $config) extends Illuminate\Database\SQLiteConnection {
          private bool $signaled = false;
          public function rollBack($toLevel = null) {
            parent::rollBack($toLevel);
            if (!$this->signaled) {
              $this->signaled = true;
              fwrite(STDOUT, "rolled back\n"); fflush(STDOUT);
              if (trim(fgets(STDIN)) !== 'continue') { throw new RuntimeException('Missing retry barrier'); }
            }
          }
        };
      });
      $app = require 'app/Api/bootstrap.php';
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 0');
      (new WebCalendar\Services\CalendarIdentity())->activate('alice');
      fwrite(STDOUT, "bootstrapped\n"); fflush(STDOUT);
      if (trim(fgets(STDIN)) !== 'start') { throw new RuntimeException('Missing start barrier'); }
      try {
        $writer = $app->make(WebCalendar\Services\CalendarEventWriter::class);
        $saved = $argv[1] === 'update' ? $writer->update('alice', 1, ['title' => 'Renamed'])
          : $writer->create('alice', json_decode($argv[2], true));
        echo json_encode([200, $saved]);
      } catch (WebCalendar\Api\Exceptions\EventWriteException $e) {
        echo json_encode([$e->getStatusCode()]);
      } catch (Illuminate\Database\QueryException $e) {
        echo json_encode([500]);
      }
      PHP;
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript($body);
    $draft = $this->draft(['date' => '2026-11-02', 'untimed' => false, 'starts_at' => '2026-11-02T10:00:00Z', 'duration_minutes' => 60]);
    $process = proc_open([PHP_BINARY, '-r', $script, $operation, json_encode($draft)],
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), self::$environment);
    try {
      stream_set_timeout($pipes[1], 10);
      self::assertSame("bootstrapped\n", fgets($pipes[1]));
      self::$db->beginTransaction();
      self::$db->exec("UPDATE webcal_api_users SET auth_backend = auth_backend WHERE username = 'alice'");
      fwrite($pipes[0], "start\n");
      $barrier = fgets($pipes[1]);
      self::assertSame("rolled back\n", $barrier, $barrier === false ? stream_get_contents($pipes[2]) : '');
      if (!$exhaust) {
        if ($operation === 'create') { $this->event(1, 'Concurrent appointment', 'alice'); }
        else { self::$db->exec("UPDATE webcal_entry SET cal_location = 'Concurrent location' WHERE cal_id = 1"); }
        self::$db->commit();
      }
      fwrite($pipes[0], "continue\n"); fclose($pipes[0]);
      $output = stream_get_contents($pipes[1]);
      $errors = stream_get_contents($pipes[2]);
      $result = json_decode($output, true);
      self::assertSame($exhaust ? Response::HTTP_SERVICE_UNAVAILABLE : ($operation === 'create' ? Response::HTTP_CONFLICT : Response::HTTP_OK), $result[0] ?? null, $output . $errors);
      if (!$exhaust && $operation === 'update') { self::assertSame('Concurrent location', $result[1]['location']); }
    } finally {
      if (self::$db->inTransaction()) { self::$db->rollBack(); }
      foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
      proc_terminate($process); proc_close($process);
    }
    self::assertSame($operation === 'update' && !$exhaust ? 1 : 0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry_log')->fetchColumn());
  }

  public static function contendedWrites(): array
  {
    return ['create revalidates conflicts' => ['create', false], 'patch reloads state' => ['update', false],
      'create retry exhaustion' => ['create', true], 'patch retry exhaustion' => ['update', true]];
  }

  public function testDifferentEventPatchesSerializeBeforeReadingTheCalendar(): void
  {
    $this->token();
    $this->event(1, 'First', 'alice');
    $this->event(2, 'Second', 'alice');
    self::sql('UPDATE webcal_entry SET cal_time = 120000 WHERE cal_id = 2');
    self::sql("INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('ALLOW_CONFLICTS', 'N')");
    $body = <<<'PHP'
      Illuminate\Database\Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
        return new class($pdo, $database, $prefix, $config) extends Illuminate\Database\SQLiteConnection {
          private bool $paused = false;
          public function update($query, $bindings = []) {
            $target = $GLOBALS['worker_id'] === 1 ? 'update "webcal_entry"' : 'update "webcal_';
            if (!$this->paused && str_starts_with($query, $target)) {
              $this->paused = true;
              fwrite(STDOUT, "writing\n"); fflush(STDOUT);
              if (trim(fgets(STDIN)) !== 'continue') { throw new RuntimeException('Missing write barrier'); }
            }
            return parent::update($query, $bindings);
          }
        };
      });
      $GLOBALS['worker_id'] = (int) $argv[1];
      $app = require 'app/Api/bootstrap.php';
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      (new WebCalendar\Services\CalendarIdentity())->activate('alice');
      Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 100');
      fwrite(STDOUT, "ready\n"); fflush(STDOUT);
      if (trim(fgets(STDIN)) !== 'start') { throw new RuntimeException('Missing start barrier'); }
      try {
        $saved = $app->make(WebCalendar\Services\CalendarEventWriter::class)->update('alice', $GLOBALS['worker_id'],
          ['date' => '2026-11-10', 'starts_at' => '2026-11-10T09:00:00Z']);
        echo json_encode([200, $saved]);
      } catch (WebCalendar\Api\Exceptions\EventWriteException $e) { echo json_encode([$e->getStatusCode()]); }
      catch (Illuminate\Database\QueryException $e) { echo json_encode([500]); }
      PHP;
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript($body);
    $workers = [];
    try {
      foreach ([1, 2] as $id) {
        $process = proc_open([PHP_BINARY, '-r', $script, (string) $id],
          [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), self::$environment);
        $workers[$id] = [$process, $pipes];
        stream_set_timeout($pipes[1], 10);
        self::assertSame("ready\n", fgets($pipes[1]));
      }
      foreach ([1, 2] as $id) {
        fwrite($workers[$id][1][0], "start\n");
        self::assertSame("writing\n", fgets($workers[$id][1][1]));
      }
      foreach ([1, 2] as $id) { fwrite($workers[$id][1][0], "continue\n"); }
      foreach ([1 => Response::HTTP_OK, 2 => Response::HTTP_CONFLICT] as $id => $expected) {
        $output = stream_get_contents($workers[$id][1][1]);
        $errors = stream_get_contents($workers[$id][1][2]);
        self::assertSame($expected, json_decode($output, true)[0] ?? null, $output . $errors);
      }
    } finally {
      foreach ($workers as [$process, $pipes]) {
        foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
        proc_terminate($process); proc_close($process);
      }
    }
    self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry WHERE cal_date = 20261110')->fetchColumn());
    self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry_log')->fetchColumn());
  }

  /** @dataProvider transactionFailureModes */
  public function testRetriesRequireAKnownFailureAndSuccessfulRollback(string $mode, int $attempts, bool $saved, int $committed, ?int $status = null): void
  {
    $this->token();
    $body = <<<'PHP'
      Illuminate\Database\Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
        return new class($pdo, $database, $prefix, $config) extends Illuminate\Database\SQLiteConnection {
          public int $attempts = 0;
          public function getDriverName() {
            return str_starts_with($GLOBALS['failure_mode'], 'postgres-') ? 'pgsql' : parent::getDriverName();
          }
          public function beginTransaction() { $this->attempts++; return parent::beginTransaction(); }
          private function failure(string $state, int $code): PDOException {
            $e = new PDOException('fixture transaction failure');
            $e->errorInfo = [$state, $code, $e->getMessage()];
            return $e;
          }
          public function insert($query, $bindings = []) {
            if (in_array($GLOBALS['failure_mode'], ['rollback-fails', 'nontransient'], true)) {
              throw new Illuminate\Database\QueryException('webcalendar', $query, $bindings,
                $this->failure('HY000', $GLOBALS['failure_mode'] === 'rollback-fails' ? 5 : 1));
            }
            return parent::insert($query, $bindings);
          }
          public function commit() {
            if ($GLOBALS['failure_mode'] === 'busy-commit' && $this->attempts === 1) { throw $this->failure('HY000', 5); }
            if (str_starts_with($GLOBALS['failure_mode'], 'postgres-') && ($this->attempts === 1 || $GLOBALS['failure_mode'] === 'postgres-exhaust')) {
              $this->getPdo()->rollBack();
              $state = match ($GLOBALS['failure_mode']) {
                'postgres-unknown' => 'XX000', 'postgres-lost' => '08006', 'postgres-deadlock' => '40P01', default => '40001',
              };
              throw $this->failure($state, 0);
            }
            parent::commit();
            if ($GLOBALS['failure_mode'] === 'lost-commit') { throw $this->failure('08006', 0); }
          }
          public function rollBack($toLevel = null) {
            if ($GLOBALS['failure_mode'] === 'rollback-fails') { throw new RuntimeException('fixture rollback failure'); }
            return parent::rollBack($toLevel);
          }
        };
      });
      $GLOBALS['failure_mode'] = $argv[1];
      $app = require 'app/Api/bootstrap.php';
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      $saved = false;
      $status = null;
      try { $app->make(WebCalendar\Services\CalendarEventWriter::class)->create('alice', json_decode($argv[2], true)); $saved = true; }
      catch (WebCalendar\Api\Exceptions\EventWriteException $e) { $status = $e->getStatusCode(); }
      catch (Throwable $e) {}
      echo json_encode([Illuminate\Support\Facades\DB::connection()->attempts, $saved, $status]);
      PHP;
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript($body);
    $result = json_decode(self::command([PHP_BINARY, '-r', $script, $mode, json_encode($this->draft())]), true);
    self::assertSame([$attempts, $saved, $status], $result);
    foreach (['webcal_entry', 'webcal_entry_user', 'webcal_entry_log'] as $table) {
      self::assertSame($committed, (int) self::$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn(), $table);
    }
  }

  public static function transactionFailureModes(): array
  {
    return [['busy-commit', 2, true, 1], ['lost-commit', 1, false, 1],
      ['rollback-fails', 1, false, 0], ['nontransient', 1, false, 0],
      ['postgres-abort', 2, true, 1], ['postgres-deadlock', 2, true, 1],
      ['postgres-exhaust', 5, false, 0, Response::HTTP_SERVICE_UNAVAILABLE],
      ['postgres-unknown', 1, false, 0], ['postgres-lost', 1, false, 0]];
  }

  private function draft(array $changes = []): array
  {
    return array_replace(['title' => 'Appointment', 'description' => 'Bring notes', 'location' => 'Office',
      'date' => '2026-11-10', 'all_day' => false, 'untimed' => true, 'starts_at' => null, 'duration_minutes' => 0], $changes);
  }

  public function testAssistantsChooseCalendarsAndUseTheirTimezone(): void
  {
    self::sql("INSERT INTO webcal_asst (cal_boss, cal_assistant) VALUES ('admin', 'alice')");
    self::sql("INSERT INTO webcal_user_pref VALUES ('admin', 'TIMEZONE', 'America/New_York')");
    $token = $this->token();
    [$status, $body] = $this->request('GET', '/v1/calendars', [], $token);
    self::assertSame(200, $status, json_encode($body));
    $calendars = array_column($body['data'], null, 'id');
    self::assertSame(['alice', 'admin'], array_keys($calendars));
    self::assertSame('America/New_York', $calendars['admin']['timezone']);
    self::assertTrue($calendars['admin']['can_write']);
    [$status, $body] = $this->request('POST', '/v1/calendars/admin/events', [
      'title' => 'Shared private event', 'date' => '2026-11-02', 'starts_at' => '2026-11-03T02:00:00Z',
      'all_day' => false, 'untimed' => false, 'duration_minutes' => 60, 'access' => 'R',
    ], $token);
    self::assertSame(201, $status, json_encode($body));
    $id = $body['data']['id'];
    self::assertSame('admin', $body['data']['calendar_id']);
    self::assertSame('2026-11-02T21:00:00-05:00', $body['data']['starts_at']);
    self::assertSame('admin', self::$db->query('SELECT cal_login FROM webcal_entry_user')->fetchColumn());
    $log = self::$db->query('SELECT cal_login, cal_user_cal FROM webcal_entry_log')->fetch(PDO::FETCH_ASSOC);
    self::assertSame(['cal_login' => 'alice', 'cal_user_cal' => 'admin'], $log);
    [$status, $body] = $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token);
    self::assertSame(200, $status, json_encode($body));
    self::assertSame([$id], array_column($body['data'], 'id'));
    self::assertTrue($body['data'][0]['can_edit']);
    self::sql("UPDATE webcal_entry SET cal_create_by = 'another-assistant' WHERE cal_id = ?", [$id]);
    [$status, $body] = $this->request('PATCH', '/v1/calendars/admin/events/' . $id, ['title' => 'Team edit'], $token);
    self::assertSame(200, $status, json_encode($body));
    self::$db->exec('DELETE FROM webcal_asst');
    self::assertSame(403, $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token)[0]);
    self::assertSame(403, $this->request('PATCH', '/v1/calendars/admin/events/' . $id, ['title' => 'Denied'], $token)[0]);
    self::assertSame('Team edit', self::$db->query('SELECT cal_name FROM webcal_entry')->fetchColumn());
  }

  public function testNonuserCalendarDoesNotNeedAnApiIdentityAndRejectsUnrelatedUsers(): void
  {
    self::sql("INSERT INTO webcal_nonuser_cals (cal_login, cal_firstname, cal_lastname, cal_admin, cal_is_public) VALUES ('__nu_team', 'Team', 'Calendar', 'admin', 'N')");
    self::sql("INSERT INTO webcal_asst (cal_boss, cal_assistant) VALUES ('__nu_team', 'alice')");
    self::sql("INSERT INTO webcal_user_pref VALUES ('alice', 'TIMEZONE', 'Asia/Tokyo')");
    $token = $this->token();
    [$status, $body] = $this->request('GET', '/v1/calendars', [], $token);
    self::assertSame(200, $status, json_encode($body));
    $calendars = array_column($body['data'], null, 'id');
    self::assertArrayNotHasKey('admin', $calendars);
    self::assertSame('UTC', $calendars['__nu_team']['timezone']);
    self::assertSame('Team Calendar', $calendars['__nu_team']['name']);
    [$status, $body] = $this->request('POST', '/v1/calendars/__nu_team/events', [
      'title' => 'Team booking', 'date' => '2026-11-02', 'all_day' => false, 'untimed' => true,
    ], $token);
    self::assertSame(201, $status, json_encode($body));
    self::assertSame('UTC', $body['data']['timezone']);
    self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_api_users')->fetchColumn());
    self::assertSame(403, $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token)[0]);
  }

  public function testDelegationDoesNotGrantTheCalendarOwnersFunctionPermissions(): void
  {
    self::sql("INSERT INTO webcal_asst VALUES ('admin', 'alice')");
    $token = $this->token();
    $this->event(1, 'Private delegated event', 'admin', 'R');
    self::$db->exec("UPDATE webcal_api_tokens SET abilities = '[\"events:read\"]'");
    [$status, $body] = $this->request('GET', '/v1/calendars', [], $token);
    self::assertSame(200, $status);
    self::assertFalse(array_column($body['data'], null, 'id')['admin']['can_write']);
    self::assertSame(403, $this->request('PATCH', '/v1/calendars/admin/events/1', ['title' => 'Forbidden'], $token)[0]);
    self::$db->exec("UPDATE webcal_api_tokens SET abilities = '[\"events:read\",\"events:write\"]'");
    self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('UAC_ENABLED', 'Y')");
    self::sql('INSERT OR REPLACE INTO webcal_access_function VALUES (?, ?)', ['alice', str_repeat('N', 28)]);
    self::assertSame(403, $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token)[0]);
    self::assertSame(403, $this->request('POST', '/v1/calendars/admin/events', $this->draft(), $token)[0]);
    self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('UAC_ENABLED', 'N')");
    self::sql("UPDATE webcal_user SET cal_enabled = 'N' WHERE cal_login = 'admin'");
    self::assertSame(403, $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token)[0]);
    self::assertSame('Private delegated event', self::$db->query('SELECT cal_name FROM webcal_entry')->fetchColumn());
  }

  public function testDelegatedRecurrenceUsesSelectedTimezoneAndRemainsReadOnly(): void
  {
    self::sql("INSERT INTO webcal_asst VALUES ('admin', 'alice')");
    self::sql("INSERT INTO webcal_user_pref VALUES ('admin', 'TIMEZONE', 'America/New_York')");
    self::sql("INSERT INTO webcal_user_pref VALUES ('alice', 'TIMEZONE', 'Asia/Tokyo')");
    $this->event(1, 'Private repeating appointment', 'admin', 'R');
    self::sql("UPDATE webcal_entry SET cal_type = 'M', cal_time = 140000 WHERE cal_id = 1");
    self::sql("INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_end) VALUES (1, 'daily', 1, 20261110)");
    $token = $this->token();
    [$status, $body] = $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-03&end_date=2026-11-03', [], $token);
    self::assertSame(200, $status, json_encode($body));
    self::assertCount(1, $body['data']);
    self::assertSame('Private repeating appointment', $body['data'][0]['title']);
    self::assertSame('2026-11-03T09:00:00-05:00', $body['data'][0]['starts_at']);
    self::assertFalse($body['data'][0]['can_edit']);
    self::assertSame(422, $this->request('PATCH', '/v1/calendars/admin/events/1', ['title' => 'Denied'], $token)[0]);
    self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('ALLOW_CONFLICTS', 'N')");
    self::assertSame(409, $this->request('POST', '/v1/calendars/admin/events', $this->draft([
      'date' => '2026-11-03', 'untimed' => false, 'starts_at' => '2026-11-03T09:00:00-05:00', 'duration_minutes' => 30,
    ]), $token)[0]);
  }

  public function testDelegatedCalendarUacDenialControlsCapabilitiesAndMutations(): void
  {
    self::sql("INSERT INTO webcal_asst VALUES ('admin', 'alice')");
    self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('UAC_ENABLED', 'Y')");
    self::sql('INSERT OR REPLACE INTO webcal_access_function VALUES (?, ?)', ['alice', str_repeat('Y', 28)]);
    self::sql("INSERT INTO webcal_access_user (cal_login, cal_other_user, cal_can_view, cal_can_edit) VALUES ('alice', 'admin', 511, 0)");
    $this->event(1, 'Protected event', 'admin', 'R');
    $token = $this->token();
    [$status, $body] = $this->request('GET', '/v1/calendars', [], $token);
    self::assertSame(200, $status);
    self::assertFalse(array_column($body['data'], null, 'id')['admin']['can_write']);
    [$status, $body] = $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token);
    self::assertSame(200, $status);
    self::assertFalse($body['data'][0]['can_edit']);
    self::assertSame(403, $this->request('PATCH', '/v1/calendars/admin/events/1', ['title' => 'Denied'], $token)[0]);
    self::assertSame(403, $this->request('POST', '/v1/calendars/admin/events', $this->draft(), $token)[0]);
    self::assertSame('Protected event', self::$db->query('SELECT cal_name FROM webcal_entry')->fetchColumn());
    self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry_log')->fetchColumn());
    self::sql("UPDATE webcal_access_user SET cal_can_edit = 1 WHERE cal_login = 'alice'");
    self::assertSame(403, $this->request('PATCH', '/v1/calendars/admin/events/1', ['title' => 'Private denied'], $token)[0]);
    self::assertSame(403, $this->request('POST', '/v1/calendars/admin/events', $this->draft(['access' => 'R']), $token)[0]);
    self::assertSame(201, $this->request('POST', '/v1/calendars/admin/events', $this->draft(['access' => 'P']), $token)[0]);
    self::sql("UPDATE webcal_access_user SET cal_can_edit = 511 WHERE cal_login = 'alice'");
    self::assertSame(200, $this->request('PATCH', '/v1/calendars/admin/events/1', ['title' => 'Allowed'], $token)[0]);
    self::sql("UPDATE webcal_access_user SET cal_can_view = 1 WHERE cal_login = 'alice'");
    [$status, $body] = $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token);
    self::assertSame(200, $status);
    self::assertSame([], $body['data']);
    self::sql("UPDATE webcal_access_user SET cal_can_view = 0 WHERE cal_login = 'alice'");
    self::assertSame(403, $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token)[0]);
  }

  public function testMissingInstallationTimezonePreservesFallbackAndAllowsWrites(): void
  {
    self::sql("DELETE FROM webcal_config WHERE cal_setting = 'TIMEZONE'");
    self::sql("INSERT INTO webcal_config VALUES ('SERVER_TIMEZONE', 'America/New_York')");
    self::sql("INSERT INTO webcal_asst VALUES ('admin', 'alice')");
    self::sql("INSERT INTO webcal_user_pref VALUES ('alice', 'TIMEZONE', 'Asia/Tokyo')");
    $token = $this->token();
    [$status, $body] = $this->request('GET', '/v1/calendars', [], $token);
    self::assertSame(200, $status);
    self::assertSame('America/New_York', array_column($body['data'], null, 'id')['admin']['timezone']);
    [$status, $body] = $this->request('POST', '/v1/calendars/admin/events', $this->draft([
      'date' => '2026-11-02', 'untimed' => false, 'starts_at' => '2026-11-03T02:00:00Z', 'duration_minutes' => 30,
    ]), $token);
    self::assertSame(201, $status, json_encode($body));
    $id = $body['data']['id'];
    self::assertSame('2026-11-02', $body['data']['date']);
    [$readStatus, $read] = $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token);
    self::assertSame(200, $readStatus);
    self::assertSame([$id], array_column($read['data'], 'id'));
    self::assertSame(200, $this->request('PATCH', '/v1/calendars/admin/events/' . $id, ['title' => 'Metadata edit'], $token)[0]);
    self::sql("DELETE FROM webcal_user_pref WHERE cal_login = 'alice' AND cal_setting = 'TIMEZONE'");
    [$status, $body] = $this->request('GET', '/v1/calendars', [], $token);
    self::assertSame('America/New_York', $body['data'][0]['timezone']);
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft(), $token);
    self::assertSame(201, $status, json_encode($body));
    self::assertSame(200, $this->request('PATCH', '/v1/events/' . $body['data']['id'], ['title' => 'Personal edit'], $token)[0]);
  }

  public function testCalendarSelectionUsesPhpTimezoneWithoutInstallationTimezoneSettings(): void
  {
    self::sql("DELETE FROM webcal_config WHERE cal_setting IN ('TIMEZONE', 'SERVER_TIMEZONE')");
    self::sql("INSERT INTO webcal_user_pref VALUES ('alice', 'TIMEZONE', 'Asia/Tokyo')");
    self::sql("INSERT INTO webcal_asst VALUES ('admin', 'alice')");
    $body = <<<'PHP'
      $app = require 'app/Api/bootstrap.php';
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      $selection = new WebCalendar\Services\CalendarSelection();
      $selection->activate('alice', 'alice');
      echo json_encode($selection->activate('alice', 'admin'));
      PHP;
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript($body);
    $result = json_decode(self::command([PHP_BINARY, '-d', 'date.timezone=America/New_York', '-r', $script]), true);
    self::assertSame('America/New_York', $result['timezone']);
  }

  public function testPersonalWritesDoNotRequireAPhysicalTimezoneSetting(): void
  {
    self::sql("DELETE FROM webcal_config WHERE cal_setting = 'TIMEZONE'");
    self::sql("INSERT INTO webcal_user_pref VALUES ('alice', 'TIMEZONE', 'America/New_York')");
    $token = $this->token();
    [$status, $body] = $this->request('POST', '/v1/events', $this->draft(), $token);
    self::assertSame(201, $status, json_encode($body));
    self::assertSame(200, $this->request('PATCH', '/v1/events/' . $body['data']['id'], ['title' => 'Metadata edit'], $token)[0]);
  }

  public function testDelegatedWriteRetryReloadsCalendarPermissions(): void
  {
    $this->token();
    self::sql("INSERT INTO webcal_asst VALUES ('admin', 'alice')");
    self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('UAC_ENABLED', 'Y')");
    self::sql('INSERT OR REPLACE INTO webcal_access_function VALUES (?, ?)', ['alice', str_repeat('Y', 28)]);
    self::sql("INSERT INTO webcal_access_user (cal_login, cal_other_user, cal_can_view, cal_can_edit) VALUES ('alice', 'admin', 511, 511)");
    $body = <<<'PHP'
      Illuminate\Database\Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
        return new class($pdo, $database, $prefix, $config) extends Illuminate\Database\SQLiteConnection {
          private bool $collided = false;
          public function insert($query, $bindings = []) {
            if (!$this->collided && str_contains($query, 'insert into "webcal_entry"')) {
              $this->collided = true;
              $cause = new PDOException('UNIQUE constraint failed: webcal_entry');
              $cause->errorInfo = ['23000', 19, $cause->getMessage()];
              throw new Illuminate\Database\QueryException('webcalendar', $query, $bindings, $cause);
            }
            return parent::insert($query, $bindings);
          }
          public function rollBack($toLevel = null) {
            parent::rollBack($toLevel);
            $other = new PDO('sqlite:' . $this->getDatabaseName());
            $other->exec("UPDATE webcal_access_user SET cal_can_edit = 0 WHERE cal_login = 'alice'");
          }
        };
      });
      $app = require 'app/Api/bootstrap.php';
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      try {
        $app->make(WebCalendar\Services\CalendarEventWriter::class)->create('alice', json_decode($argv[1], true), 'admin');
        echo 'saved';
      } catch (Illuminate\Auth\Access\AuthorizationException $e) { echo 'denied'; }
      PHP;
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript($body);
    self::assertSame('denied', self::command([PHP_BINARY, '-r', $script, json_encode($this->draft())]));
    self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry')->fetchColumn());
    self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry_log')->fetchColumn());
  }

  /** @dataProvider delegatedTimeOnlyEvents */
  public function testDelegatedTimeOnlyAccessRejectsDetailsAndMutations(bool $recurring, bool $defaultPolicy): void
  {
    self::sql("INSERT INTO webcal_asst VALUES ('admin', 'alice')");
    self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('UAC_ENABLED', 'Y')");
    self::sql('INSERT OR REPLACE INTO webcal_access_function VALUES (?, ?)', ['alice', str_repeat('Y', 28)]);
    self::sql('INSERT INTO webcal_access_user (cal_login, cal_other_user, cal_can_view, cal_can_edit, cal_see_time_only) VALUES (?, ?, 511, 511, ?)',
      ['alice', $defaultPolicy ? '__default__' : 'admin', 'Y']);
    $this->event(1, 'Secret title', 'admin', 'R');
    if ($recurring) {
      self::sql("UPDATE webcal_entry SET cal_type = 'M' WHERE cal_id = 1");
      self::sql("INSERT INTO webcal_entry_repeats (cal_id, cal_type, cal_frequency, cal_end) VALUES (1, 'daily', 1, 20261110)");
    }
    $token = $this->token();
    [$status, $body] = $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token);
    self::assertSame(Response::HTTP_FORBIDDEN, $status, json_encode($body));
    self::assertArrayNotHasKey('data', $body);
    self::assertStringNotContainsString('Secret title', json_encode($body));
    [$status, $body] = $this->request('GET', '/v1/calendars', [], $token);
    self::assertSame(Response::HTTP_OK, $status);
    self::assertFalse(array_column($body['data'], null, 'id')['admin']['can_write']);
    self::assertSame(Response::HTTP_FORBIDDEN, $this->request('POST', '/v1/calendars/admin/events', $this->draft(), $token)[0]);
    self::assertSame(Response::HTTP_FORBIDDEN, $this->request('PATCH', '/v1/calendars/admin/events/1', ['title' => 'Denied'], $token)[0]);
    self::assertSame(1, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry')->fetchColumn());
    self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM webcal_entry_log')->fetchColumn());
    self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('ALLOW_CONFLICTS', 'N')");
    $script = "require 'vendor/autoload.php'; " . $this->legacyScript(<<<'PHP'
      $app = require 'app/Api/bootstrap.php';
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      (new WebCalendar\Services\CalendarSelection())->activate('alice', 'admin');
      try {
        (new WebCalendar\Services\CalendarEventConflicts())->check('admin', json_decode($argv[1], true), new DateTimeZone('UTC'), 0, 'alice');
        echo 'missed';
      } catch (WebCalendar\Api\Exceptions\EventWriteException $e) { echo $e->getStatusCode(); }
      PHP);
    self::assertSame((string) Response::HTTP_CONFLICT, self::command([PHP_BINARY, '-r', $script, json_encode($this->draft([
      'date' => '2026-11-02', 'untimed' => false, 'starts_at' => '2026-11-02T10:00:00Z', 'duration_minutes' => 30,
    ]))]));
    self::sql("UPDATE webcal_access_user SET cal_see_time_only = 'N'");
    [$status, $body] = $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-02&end_date=2026-11-02', [], $token);
    self::assertSame(Response::HTTP_OK, $status, json_encode($body));
    self::assertSame('Secret title', $body['data'][0]['title']);
    self::assertSame('Description', $body['data'][0]['description']);
  }

  public static function delegatedTimeOnlyEvents(): array
  {
    return ['ordinary' => [false, false], 'recurring' => [true, false],
      'ordinary default policy' => [false, true], 'recurring default policy' => [true, true]];
  }

  public function testDelegatedCreationRequiresViewAndEditForEffectiveVisibility(): void
  {
    self::sql("INSERT INTO webcal_asst VALUES ('admin', 'alice')");
    self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('UAC_ENABLED', 'Y')");
    self::sql('INSERT OR REPLACE INTO webcal_access_function VALUES (?, ?)', ['alice', str_repeat('Y', 28)]);
    self::sql("INSERT INTO webcal_access_user (cal_login, cal_other_user, cal_can_view, cal_can_edit) VALUES ('alice', 'admin', 1, 511)");
    $token = $this->token();
    foreach ([['access' => 'R'], []] as $changes) {
      self::sql("INSERT OR REPLACE INTO webcal_config VALUES ('DEFAULT_VISIBILITY', 'R')");
      [$status, $body] = $this->request('POST', '/v1/calendars/admin/events', $this->draft($changes), $token);
      self::assertSame(Response::HTTP_FORBIDDEN, $status, json_encode($body));
      self::assertArrayNotHasKey('data', $body);
    }
    foreach (['webcal_entry', 'webcal_entry_user', 'webcal_entry_log'] as $table) {
      self::assertSame(0, (int) self::$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn());
    }
    [$status, $body] = $this->request('POST', '/v1/calendars/admin/events', $this->draft(['access' => 'P']), $token);
    self::assertSame(Response::HTTP_CREATED, $status, json_encode($body));
    self::assertTrue($body['data']['can_edit']);
    $id = $body['data']['id'];
    [$status, $body] = $this->request('GET', '/v1/calendars/admin/events?start_date=2026-11-10&end_date=2026-11-10', [], $token);
    self::assertSame(Response::HTTP_OK, $status);
    self::assertSame([$id], array_column($body['data'], 'id'));
    self::assertTrue($body['data'][0]['can_edit']);
    self::assertSame(Response::HTTP_OK, $this->request('PATCH', '/v1/calendars/admin/events/' . $id, ['title' => 'Editable'], $token)[0]);
    self::assertSame(Response::HTTP_FORBIDDEN, $this->request('PATCH', '/v1/calendars/admin/events/' . $id, ['access' => 'R'], $token)[0]);
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
