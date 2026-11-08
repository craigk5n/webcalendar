<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../vendor/autoload.php';

final class ApiCredentialConcurrencyTest extends TestCase
{
  private static PDO $db;
  private static array $environment;
  private static string $storage;

  public static function setUpBeforeClass(): void
  {
    $host = getenv('WEBCALENDAR_API_TEST_POSTGRES_HOST');
    if (!$host) { self::markTestSkipped('Requires a disposable PostgreSQL api_credential_test database.'); }
    self::$storage = sys_get_temp_dir() . '/credential-test-' . bin2hex(random_bytes(8));
    self::$environment = array_merge(getenv(), [
      'WEBCALENDAR_USE_ENV' => 'true', 'WEBCALENDAR_DB_TYPE' => 'postgresql',
      'WEBCALENDAR_DB_HOST' => $host, 'WEBCALENDAR_DB_DATABASE' => 'api_credential_test',
      'WEBCALENDAR_DB_LOGIN' => 'credential_test', 'WEBCALENDAR_DB_PASSWORD' => 'synthetic-test-password',
      'WEBCALENDAR_USER_INC' => 'user.php', 'WEBCALENDAR_API_ENABLED' => 'true',
      'WEBCALENDAR_API_STORAGE' => self::$storage,
    ]);
    self::$db = new PDO('pgsql:host=' . $host . ';dbname=api_credential_test', 'credential_test', 'synthetic-test-password',
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    self::$db->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public');
    self::command(['wizard/headless.php', '--use-env', '--db-type=postgresql', '--db-host=' . $host,
      '--db-database=api_credential_test', '--db-login=credential_test', '--db-password=synthetic-test-password',
      '--admin-login=admin', '--admin-password=synthetic-admin-password', '--install-password=synthetic-install', '--force']);
    self::command(['bin/api.php', 'migrate', '--force']);
  }

  public static function tearDownAfterClass(): void
  {
    if (isset(self::$storage)) { (new Filesystem())->remove(self::$storage); }
  }

  protected function setUp(): void
  {
    self::$db->exec("DELETE FROM webcal_api_tokens; DELETE FROM webcal_api_users; DELETE FROM webcal_user WHERE cal_login = 'race-user'");
    self::$db->prepare('INSERT INTO webcal_user (cal_login, cal_passwd, cal_firstname, cal_lastname, cal_is_admin, cal_enabled) VALUES (?, ?, ?, ?, ?, ?)')
      ->execute(['race-user', password_hash('original', PASSWORD_DEFAULT), 'Race', 'Fixture', 'N', 'Y']);
  }

  /** @dataProvider invalidationModes */
  public function testRenewalCannotEscapeAccountInvalidation(string $mode): void
  {
    $original = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'issue']), true);
    $orphans = self::$db->query('SELECT * FROM webcal_api_tokens')->fetchAll(PDO::FETCH_ASSOC);
    [$renewal, $renewalPipes] = self::worker('renew', $original['refresh_token']);
    [$invalidation, $invalidationPipes] = [null, []];
    try {
      self::assertNotNull(json_decode(fgets($renewalPipes[1]), true)['pid'] ?? null);
      if ($mode === 'sign-in') {
        self::$db->prepare('UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?')
          ->execute([password_hash('replacement', PASSWORD_DEFAULT), 'race-user']);
      }
      [$invalidation, $invalidationPipes] = self::worker($mode);
      $pid = json_decode(fgets($invalidationPipes[1]), true)['pid'] ?? null;
      self::assertNotNull($pid);
      self::waitForLock((int) $pid);
      fwrite($renewalPipes[0], "continue\n");
      $rotated = self::finish($renewal, $renewalPipes);
      $replacement = self::finish($invalidation, $invalidationPipes);
      foreach ([$original['token'], $rotated['token']] as $access) {
        $response = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'authenticate', $access]), true);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response['status'], 'Old-session access survived invalidation.');
      }
      foreach ([$original['refresh_token'], $rotated['refresh_token']] as $refresh) {
        $response = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'refresh', $refresh]), true);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response['status']);
      }
      $response = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'authenticate', $replacement['token']]), true);
      self::assertSame(Response::HTTP_OK, $response['status']);
      foreach ($orphans as $row) {
        $columns = implode(', ', array_keys($row));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));
        self::$db->prepare('INSERT INTO webcal_api_tokens (' . $columns . ') VALUES (' . $placeholders . ')')->execute(array_values($row));
      }
      foreach (['authenticate' => $original['token'], 'refresh' => $original['refresh_token']] as $action => $credential) {
        $response = json_decode(self::command(['tests/fixtures/api-credential-worker.php', $action, $credential]), true);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response['status'], 'A stale token row must not attach to the new generation.');
      }
      $response = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'authenticate', $replacement['token']]), true);
      self::assertSame(Response::HTTP_OK, $response['status']);
    } finally {
      foreach ([[$renewal, $renewalPipes], [$invalidation, $invalidationPipes]] as [$process, $pipes]) {
        foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
        if (is_resource($process)) { proc_terminate($process); proc_close($process); }
      }
    }
  }

  public function testInvalidationCommittingFirstRejectsWaitingRenewal(): void
  {
    $original = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'issue']), true);
    self::$db->prepare('UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?')
      ->execute([password_hash('replacement', PASSWORD_DEFAULT), 'race-user']);
    [$login, $loginPipes] = self::worker('sign-in-paused');
    [$renewal, $renewalPipes] = [null, []];
    try {
      self::assertNotNull(json_decode(fgets($loginPipes[1]), true)['pid'] ?? null);
      [$renewal, $renewalPipes] = self::worker('waiting-refresh', $original['refresh_token']);
      self::waitForLock((int) (json_decode(fgets($renewalPipes[1]), true)['pid'] ?? 0));
      fwrite($loginPipes[0], "continue\n");
      $replacement = self::finish($login, $loginPipes);
      self::assertSame(Response::HTTP_UNAUTHORIZED, self::finish($renewal, $renewalPipes)['status']);
      $response = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'authenticate', $replacement['token']]), true);
      self::assertSame(Response::HTTP_OK, $response['status']);
    } finally {
      self::stop($login, $loginPipes);
      self::stop($renewal, $renewalPipes);
    }
  }

  /** @dataProvider staleOperations */
  public function testStalePasswordProofAndAuthenticationCannotAlterNewGeneration(string $mode, bool $recreated): void
  {
    $original = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'issue']), true);
    [$worker, $pipes] = self::worker($mode, $original['token']);
    try {
      self::assertNotNull(json_decode(fgets($pipes[1]), true)['pid'] ?? null);
      if (!$recreated) {
        self::$db->prepare('UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?')
          ->execute([password_hash('replacement', PASSWORD_DEFAULT), 'race-user']);
      }
      [$login, $loginPipes] = self::worker($recreated ? 'delete-recreate' : 'sign-in');
      fgets($loginPipes[1]);
      try { $replacement = self::finish($login, $loginPipes); }
      finally { self::stop($login, $loginPipes); }
      fwrite($pipes[0], "continue\n");
      self::assertSame(Response::HTTP_UNAUTHORIZED, self::finish($worker, $pipes)['status']);
      $response = json_decode(self::command(['tests/fixtures/api-credential-worker.php', 'authenticate', $replacement['token']]), true);
      self::assertSame(Response::HTTP_OK, $response['status']);
    } finally { self::stop($worker, $pipes); }
  }

  public static function staleOperations(): array
  {
    return [
      'password proof' => ['stale-issue', false],
      'cached authentication' => ['stale-authenticate', false],
      'cached authentication after identical-hash recreation' => ['stale-authenticate', true],
    ];
  }

  private static function waitForLock(int $pid): void
  {
    $deadline = microtime(true) + 10;
    $query = self::$db->prepare('SELECT wait_event_type FROM pg_stat_activity WHERE pid = ?');
    do {
      $query->execute([$pid]);
      $waiting = $query->fetchColumn() === 'Lock';
      if (!$waiting) { usleep(1000); }
    } while (!$waiting && microtime(true) < $deadline);
    self::assertTrue($waiting, 'The competing operation must reach a real database lock wait.');
  }

  private static function stop($process, array $pipes): void
  {
    foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } }
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
  }

  public static function invalidationModes(): array
  {
    return ['password reauthentication' => ['sign-in'], 'deletion and identical-hash recreation' => ['delete-recreate']];
  }

  private static function worker(string ...$arguments): array
  {
    $process = proc_open(array_merge([PHP_BINARY, 'tests/fixtures/api-credential-worker.php'], $arguments),
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), self::$environment);
    stream_set_timeout($pipes[1], 20);
    return [$process, $pipes];
  }

  private static function finish($process, array $pipes): array
  {
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    self::assertNotFalse($result = json_decode($output, true), $output . $errors);
    self::assertIsArray($result, $output . $errors);
    return $result;
  }

  private static function command(array $arguments): string
  {
    $process = proc_open(array_merge([PHP_BINARY], $arguments),
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__), self::$environment);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    self::assertSame(0, proc_close($process), $output . $errors);
    return $output;
  }
}
