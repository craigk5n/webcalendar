<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/McpServerFixture.php';

final class ApiEnvironmentTest extends TestCase
{
  private string $home;
  private string $installation;
  private string $private;
  private string $database;
  private array $environment;
  private $server;
  private int $port;
  private string $urlPrefix = '';
  private array $extraStorage = [];

  protected function setUp(): void
  {
    $this->home = McpServerFixture::tempPath('api-private-env', '');
    $this->installation = $this->home . '/webcal.example.test';
    $this->private = $this->home . '/private/webcal.example.test';
    $this->database = $this->home . '/calendar.sqlite';
    $fs = new Filesystem();
    $fs->mkdir([$this->installation . '/includes', $this->installation . '/bin', $this->private], 0700);
    $root = dirname(__DIR__);
    foreach (glob($root . '/includes/*') as $path) {
      $name = basename($path);
      if (!in_array($name, ['config.php', 'api-bootstrap.php'], true) && !str_starts_with($name, 'settings.php')) {
        $fs->symlink($path, $this->installation . '/includes/' . $name);
      }
    }
    foreach (['api.php', 'bin/api.php', 'includes/config.php', 'includes/api-bootstrap.php'] as $path) {
      $fs->copy($root . '/' . $path, $this->installation . '/' . $path);
    }
    foreach (['app', 'vendor'] as $name) {
      $fs->symlink($root . '/' . $name, $this->installation . '/' . $name);
    }
    $this->environment = getenv();
    foreach (array_keys($this->environment) as $name) {
      if (str_starts_with($name, 'WEBCALENDAR_') || str_starts_with($name, 'APP_')) {
        unset($this->environment[$name]);
      }
    }
    [$status, $output] = $this->command([PHP_BINARY, $root . '/wizard/headless.php', '--use-env',
      '--db-type=sqlite3', '--db-database=' . $this->database, '--admin-login=env-user',
      '--admin-password=fixture-password', '--install-password=fixture', '--force']);
    self::assertSame(0, $status, $output);
    file_put_contents($this->installation . '/includes/settings.php', "<?php /*\n"
      . "db_type: sqlite3\ndb_database: " . $this->database
      . "\ndb_debug: false\ndb_persistent: false\nuser_inc: user.php\nuse_http_auth: false\n*/");
  }

  protected function tearDown(): void
  {
    if (is_resource($this->server)) {
      proc_terminate($this->server);
      proc_close($this->server);
    }
    (new Filesystem())->remove([$this->home, ...$this->extraStorage]);
  }

  public function testPrivateFileEnablesHttpAndCliWithTheSameStorageAndLegacyDatabase(): void
  {
    file_put_contents($this->private . '/.env', "# Shared hosting configuration\nWEBCALENDAR_API_ENABLED=\"true\"\n");
    $this->startServer();
    [$status, $body] = $this->request('GET', '/v1/users/current');
    self::assertSame(401, $status, json_encode($body));
    [$status, $output] = $this->command([PHP_BINARY, $this->installation . '/bin/api.php', 'migrate', '--force']);
    self::assertSame(0, $status, $output);
    self::assertDirectoryExists($this->private . '/storage/bootstrap/cache');
    $db = new PDO('sqlite:' . $this->database);
    self::assertSame(1, (int) $db->query("SELECT COUNT(*) FROM sqlite_master WHERE name = 'webcal_api_tokens'")->fetchColumn());
    [$status, $body] = $this->request('POST', '/v1/tokens', [
      'username' => 'env-user', 'password' => 'fixture-password', 'device_name' => 'Private configuration',
    ]);
    self::assertSame(201, $status, json_encode($body));
    [$status, $body] = $this->request('GET', '/v1/users/current', [], $body['data']['token']);
    self::assertSame(200, $status, json_encode($body));
    self::assertSame('env-user', $body['data']['username']);
    self::assertNotEmpty(glob($this->private . '/storage/framework/cache/data/*/*/*'));
    [$status] = $this->request('GET', '/v1/users/current', [], null, 'untrusted.example.test');
    self::assertSame(401, $status);
  }

  public function testServerEnvironmentOverridesPrivateEnableFlagAndStorage(): void
  {
    $fileStorage = $this->home . '/file-storage';
    $serverStorage = $this->home . '/server-storage';
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\nWEBCALENDAR_API_STORAGE=\"$fileStorage\"\n");
    $overrides = ['WEBCALENDAR_API_ENABLED' => 'false', 'WEBCALENDAR_API_STORAGE' => $serverStorage];
    [$status, $output] = $this->command([PHP_BINARY, $this->installation . '/bin/api.php', 'migrate', '--force'], $overrides);
    self::assertSame(0, $status, $output);
    self::assertDirectoryExists($serverStorage . '/bootstrap/cache');
    self::assertDirectoryDoesNotExist($fileStorage);
    $this->startServer($overrides);
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(404, $status);
  }

  public function testFileStorageSettingIsSharedWithoutServerOverrides(): void
  {
    $storage = $this->home . '/custom-storage';
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\nWEBCALENDAR_API_STORAGE=\"$storage\"\n");
    [$status, $output] = $this->command([PHP_BINARY, $this->installation . '/bin/api.php', 'migrate', '--force']);
    self::assertSame(0, $status, $output);
    self::assertDirectoryExists($storage . '/bootstrap/cache');
    $this->startServer();
    [$status, $body] = $this->request('POST', '/v1/tokens', [
      'username' => 'env-user', 'password' => 'fixture-password', 'device_name' => 'Custom storage',
    ]);
    self::assertSame(201, $status, json_encode($body));
    self::assertNotEmpty(glob($storage . '/framework/cache/data/*/*/*'));
    self::assertDirectoryDoesNotExist($this->private . '/storage');
  }

  public function testServerOnlyConfigurationStillWorksWithoutPrivateFile(): void
  {
    $overrides = ['WEBCALENDAR_API_ENABLED' => 'true', 'WEBCALENDAR_API_STORAGE' => $this->home . '/server-only-storage'];
    [$status, $output] = $this->command([PHP_BINARY, $this->installation . '/bin/api.php', 'migrate', '--force'], $overrides);
    self::assertSame(0, $status, $output);
    $this->startServer($overrides);
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(401, $status);
    self::assertDirectoryDoesNotExist($this->private . '/storage');
  }

  public function testServerOnlyConfigurationKeepsTheExistingTemporaryStorageDefault(): void
  {
    $storage = sys_get_temp_dir() . '/webcalendar-api-' . substr(hash('sha256', $this->installation), 0, 16);
    $this->extraStorage[] = $storage;
    $overrides = ['WEBCALENDAR_API_ENABLED' => 'true'];
    [$status, $output] = $this->command([PHP_BINARY, $this->installation . '/bin/api.php', 'migrate', '--force'], $overrides);
    self::assertSame(0, $status, $output);
    self::assertDirectoryExists($storage . '/bootstrap/cache');
    $this->startServer($overrides);
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(401, $status);
    self::assertDirectoryDoesNotExist($this->private . '/storage');
  }

  public function testPublicRootEnvIsIgnoredAndPrivateValuesDoNotReplaceLegacySettings(): void
  {
    file_put_contents($this->installation . '/.env', "APP_DEBUG=true\nWEBCALENDAR_API_ENABLED=false\nINVALID=\"unterminated\n");
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\nWEBCALENDAR_USE_ENV=true\nWEBCALENDAR_DB_DATABASE=/nonexistent/poison.sqlite\nAPP_DEBUG=true\n");
    [$status, $output] = $this->command([PHP_BINARY, $this->installation . '/bin/api.php', 'migrate', '--force']);
    self::assertSame(0, $status, $output);
    $this->startServer();
    [$status, $body] = $this->request('GET', '/v1/users/current');
    self::assertSame(401, $status, json_encode($body));
    [$status, $body] = $this->request('GET', '/v1/unknown-route');
    self::assertSame(404, $status, json_encode($body));
    self::assertArrayNotHasKey('trace', $body);
  }

  public function testFileValuesStayOutOfProcessAndGlobalEnvironmentRepositories(): void
  {
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\nWEBCALENDAR_USE_ENV=true\nAPP_DEBUG=true\n");
    $script = <<<'PHP'
      require $argv[1] . '/vendor/autoload.php';
      $env = $_ENV;
      $server = $_SERVER;
      $configuration = WebCalendar\Api\Environment::load($argv[1]);
      echo json_encode([
        'enabled' => $configuration->enabled(),
        'process_flag' => getenv('WEBCALENDAR_API_ENABLED'),
        'legacy_flag' => getenv('WEBCALENDAR_USE_ENV'),
        'env_changed' => $env !== $_ENV,
        'server_changed' => $server !== $_SERVER,
      ]);
      PHP;
    [$status, $output] = $this->command([PHP_BINARY, '-r', $script, $this->installation]);
    self::assertSame(0, $status, $output);
    self::assertSame(['enabled' => true, 'process_flag' => false, 'legacy_flag' => false,
      'env_changed' => false, 'server_changed' => false], json_decode($output, true));
  }

  public function testServerConfigurationDoesNotRequireAccessToAnOptionalPrivateDirectory(): void
  {
    $allowed = $this->installation . PATH_SEPARATOR . dirname(__DIR__);
    $script = <<<'PHP'
      require $argv[1] . '/vendor/autoload.php';
      echo json_encode(WebCalendar\Api\Environment::load($argv[1])->enabled());
      PHP;
    [$status, $output] = $this->command([PHP_BINARY, '-d', 'open_basedir=' . $allowed,
      '-d', 'display_errors=stderr', '-r', $script, $this->installation], ['WEBCALENDAR_API_ENABLED' => 'true']);
    self::assertSame(0, $status, $output);
    self::assertSame('true', $output);
  }

  public function testApiRemainsDisabledWhenNoConfigurationEnablesIt(): void
  {
    file_put_contents($this->installation . '/.env', "WEBCALENDAR_API_ENABLED=true\n");
    $this->startServer();
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(404, $status);
    self::assertDirectoryDoesNotExist($this->private . '/storage');
  }

  public function testMalformedPrivateFileFailsWithoutExposingItsContents(): void
  {
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\nWEBCALENDAR_API_KEY=\"synthetic-secret-unterminated\n");
    [$status, $output] = $this->command([PHP_BINARY, $this->installation . '/bin/api.php', 'migrate', '--force']);
    self::assertNotSame(0, $status);
    self::assertStringNotContainsString('synthetic-secret', $output);
    $this->startServer();
    [$status, $body] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertStringNotContainsString('synthetic-secret', json_encode($body));
    self::assertDirectoryDoesNotExist($this->private . '/storage');
  }

  public function testUnreadablePrivateFileFailsRatherThanBeingIgnored(): void
  {
    $path = $this->private . '/.env';
    file_put_contents($path, "WEBCALENDAR_API_ENABLED=true\n");
    chmod($path, 0000);
    try {
      self::assertFalse(is_readable($path), 'Run the suite as a non-root user.');
      $this->startServer();
      [$status] = $this->request('GET', '/v1/users/current');
      self::assertSame(503, $status);
    } finally {
      chmod($path, 0600);
    }
  }

  public function testAFileWithinTheActualDocumentRootIsNotLoaded(): void
  {
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\n");
    $this->startServer([], $this->home);
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertDirectoryDoesNotExist($this->private . '/storage');
  }

  public function testSuppliedButUnresolvableDocumentRootIsRejectedWithoutDiagnostics(): void
  {
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\n");
    $allowed = $this->installation . PATH_SEPARATOR . $this->private . PATH_SEPARATOR . dirname(__DIR__);
    $script = <<<'PHP'
      require $argv[1] . '/vendor/autoload.php';
      try {
        WebCalendar\Api\Environment::load($argv[1], $argv[2]);
        echo json_encode(['rejected' => false]);
      } catch (RuntimeException $exception) {
        echo json_encode(['rejected' => true]);
      }
      PHP;
    [$status, $output] = $this->command([PHP_BINARY, '-d', 'open_basedir=' . $allowed,
      '-d', 'display_errors=stderr', '-r', $script, $this->installation, $this->home]);
    self::assertSame(0, $status, $output);
    self::assertSame('{"rejected":true}', $output);
    self::assertDirectoryDoesNotExist($this->private . '/storage');
  }

  public function testPublicConfigurationAliasPointingOutsideIsRejected(): void
  {
    $secure = McpServerFixture::tempPath('api-secure-configuration', '');
    $this->extraStorage[] = $secure;
    $fs = new Filesystem();
    $fs->rename($this->private, $secure);
    $fs->symlink($secure, $this->private);
    file_put_contents($secure . '/.env', "WEBCALENDAR_API_ENABLED=true\n");
    $this->startServer([], $this->home);
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertDirectoryDoesNotExist($secure . '/storage');
  }

  public static function publicStorageSuffixes(): array
  {
    return ['existing directory' => [''], 'missing child directories' => ['/new/nested']];
  }

  /** @dataProvider publicStorageSuffixes */
  public function testPublicStorageAliasPointingOutsideIsRejectedBeforeCreatingFiles(string $suffix): void
  {
    $secure = $this->home . '/secure-storage';
    mkdir($secure);
    $alias = $this->installation . '/public-storage';
    (new Filesystem())->symlink($secure, $alias);
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\nWEBCALENDAR_API_STORAGE=\"$alias$suffix\"\n");
    $this->startServer();
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertSame(['.', '..'], scandir($secure));
  }

  public function testStorageAliasUnderBroaderDocumentRootIsRejected(): void
  {
    $secure = McpServerFixture::tempPath('api-secure-storage', '');
    $this->extraStorage[] = $secure;
    mkdir($secure);
    $alias = $this->home . '/public-storage';
    (new Filesystem())->symlink($secure, $alias);
    $this->startServer(['WEBCALENDAR_API_ENABLED' => 'true', 'WEBCALENDAR_API_STORAGE' => $alias], $this->home);
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertSame(['.', '..'], scandir($secure));
  }

  public function testPublicStorageDirectoriesAreRejectedBeforeMkdir(): void
  {
    $storage = $this->installation . '/new-public-storage/nested';
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\nWEBCALENDAR_API_STORAGE=\"$storage\"\n");
    $this->startServer();
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertDirectoryDoesNotExist($this->installation . '/new-public-storage');
  }

  public function testRuntimeCacheAliasesAreValidatedBeforeCreatingTheirChildren(): void
  {
    $public = $this->installation . '/public-cache';
    $storage = $this->private . '/storage';
    $fs = new Filesystem();
    $fs->mkdir([$public, $storage]);
    $fs->symlink($public, $storage . '/bootstrap');
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\n");
    $this->startServer();
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertSame(['.', '..'], scandir($public));
  }

  public static function documentRootAccessForms(): array
  {
    return ['public alias' => [true], 'canonical root' => [false]];
  }

  /** @dataProvider documentRootAccessForms */
  public function testSymlinkedDocumentRootsProtectBothAccessForms(bool $useAlias): void
  {
    $public = $this->home . '/public';
    $alias = $this->home . '/public-alias';
    $secure = $this->home . '/secure-storage';
    $fs = new Filesystem();
    $fs->mkdir([$public, $secure]);
    $fs->symlink($public, $alias);
    $fs->symlink($secure, $public . '/outward');
    $path = ($useAlias ? $alias : $public) . '/outward';
    $script = <<<'PHP'
      require $argv[1] . '/vendor/autoload.php';
      $environment = WebCalendar\Api\Environment::load($argv[1], $argv[2]);
      try {
        $environment->assertOutsideDocumentRoot($argv[3]);
        echo json_encode(['rejected' => false]);
      } catch (RuntimeException $exception) {
        echo json_encode(['rejected' => true]);
      }
      PHP;
    [$status, $output] = $this->command([PHP_BINARY, '-r', $script, $this->installation, $alias, $path]);
    self::assertSame(0, $status, $output);
    self::assertSame('{"rejected":true}', $output);
  }

  public function testEnvironmentSymlinksIntoThePublicDirectoryAreRejected(): void
  {
    file_put_contents($this->installation . '/.env', "WEBCALENDAR_API_ENABLED=true\n");
    (new Filesystem())->symlink($this->installation . '/.env', $this->private . '/.env');
    $this->startServer();
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
  }

  public function testPrivateEnvironmentSymlinkCanUseADifferentTargetFilename(): void
  {
    $target = $this->home . '/alternate-api-settings';
    file_put_contents($target, "WEBCALENDAR_API_ENABLED=true\n");
    (new Filesystem())->symlink($target, $this->private . '/.env');
    [$status, $output] = $this->command([PHP_BINARY, $this->installation . '/bin/api.php', 'migrate', '--force']);
    self::assertSame(0, $status, $output);
    self::assertDirectoryExists($this->private . '/storage/bootstrap/cache');
    $this->startServer();
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(401, $status);
  }

  public function testStorageSymlinksIntoThePublicDirectoryAreRejected(): void
  {
    $publicStorage = $this->installation . '/public-storage';
    mkdir($publicStorage);
    (new Filesystem())->symlink($publicStorage, $this->private . '/storage');
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\n");
    $this->startServer();
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertSame(['.', '..'], scandir($publicStorage));
  }

  public function testComposerFreeDisabledEndpointStillReturnsJson(): void
  {
    (new Filesystem())->remove($this->installation . '/vendor');
    $this->startServer();
    [$status] = $this->request('GET', '/v1/users/current');
    self::assertSame(404, $status);
  }

  public function testComposerFreeDisabledEndpointAlsoSupportsFilesystemRestrictions(): void
  {
    (new Filesystem())->remove($this->installation . '/vendor');
    $allowed = $this->installation . PATH_SEPARATOR . dirname(__DIR__);
    [$status, $output] = $this->command([PHP_BINARY, '-d', 'open_basedir=' . $allowed,
      '-d', 'display_errors=stderr', '-r', 'require $argv[1] . "/api.php";', $this->installation]);
    self::assertSame(0, $status, $output);
    self::assertSame(['message' => 'API is not enabled.'], json_decode($output, true), $output);
  }

  public function testPrivateConfigurationWithoutComposerReportsMissingDependencies(): void
  {
    file_put_contents($this->private . '/.env', "WEBCALENDAR_API_ENABLED=true\n");
    (new Filesystem())->remove($this->installation . '/vendor');
    $this->startServer();
    [$status, $body] = $this->request('GET', '/v1/users/current');
    self::assertSame(503, $status);
    self::assertSame('API dependencies are not installed.', $body['message']);
  }

  private function startServer(array $overrides = [], ?string $documentRoot = null): void
  {
    $this->port = McpServerFixture::freePort();
    $this->urlPrefix = $documentRoot === $this->home ? '/webcal.example.test' : '';
    $this->server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $this->port, '-t', $documentRoot ?? $this->installation],
      [0 => ['pipe', 'r'], 1 => ['file', $this->home . '/server.log', 'a'], 2 => ['file', $this->home . '/server.log', 'a']],
      $pipes, $this->home, array_merge($this->environment, $overrides));
    self::assertIsResource($this->server);
    fclose($pipes[0]);
    for ($attempt = 0; $attempt < 50; $attempt++) {
      $socket = @fsockopen('127.0.0.1', $this->port);
      if ($socket) {
        fclose($socket);
        return;
      }
      usleep(100000);
    }
    self::fail('The private-environment HTTP fixture did not start.');
  }

  private function request(string $method, string $path, array $body = [], ?string $token = null, ?string $host = null): array
  {
    $headers = "Accept: application/json\r\nContent-Type: application/json\r\n";
    $headers .= $token === null ? '' : "Authorization: Bearer $token\r\n";
    $headers .= $host === null ? '' : "Host: $host\r\n";
    $context = stream_context_create(['http' => ['method' => $method, 'header' => $headers,
      'content' => $body ? json_encode($body) : '', 'ignore_errors' => true, 'timeout' => 15]]);
    $stream = fopen('http://127.0.0.1:' . $this->port . $this->urlPrefix . '/api.php' . $path, 'rb', false, $context);
    self::assertIsResource($stream);
    $responseHeaders = stream_get_meta_data($stream)['wrapper_data'];
    $raw = stream_get_contents($stream);
    fclose($stream);
    preg_match('/\s(\d{3})\s/', $responseHeaders[0], $matches);
    $decoded = json_decode($raw, true);
    self::assertIsArray($decoded, $raw . "\n" . file_get_contents($this->home . '/server.log'));
    return [(int) $matches[1], $decoded];
  }

  private function command(array $command, array $overrides = []): array
  {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes, $this->home, array_merge($this->environment, $overrides));
    self::assertIsResource($process);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $output];
  }
}
