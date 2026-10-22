<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WizardAuthorizationTest extends TestCase
{
  private string $root;
  private string $sessionId;
  private SQLite3 $db;

  protected function setUp(): void
  {
    $this->root = sys_get_temp_dir() . '/wc-wizard-auth-' . bin2hex(random_bytes(8));
    mkdir($this->root, 0700);
    mkdir($this->root . '/includes');
    mkdir($this->root . '/sessions');
    $this->copyTree(__DIR__ . '/../wizard', $this->root . '/wizard');
    $this->sessionId = bin2hex(random_bytes(16));
    $this->db = new SQLite3($this->root . '/calendar.sqlite');
    foreach (require __DIR__ . '/../wizard/shared/tables-sqlite3.php' as $sql) {
      $this->db->exec($sql);
    }
    $this->db->exec("INSERT OR REPLACE INTO webcal_config VALUES ('WEBCAL_PROGRAM_VERSION', 'v1.9.25')");
    $this->db->exec("INSERT INTO webcal_user (cal_login, cal_is_admin) VALUES ('existing', 'Y')");

    file_put_contents($this->root . '/request.php', <<<'PHP'
<?php
$request = json_decode($argv[1], true);
foreach (array_keys(getenv()) as $key) {
  if (strpos($key, 'WEBCALENDAR_') === 0) {
    putenv($key);
  }
}
foreach ($request['env'] as $key => $value) {
  putenv("$key=$value");
}
session_id($request['session']);
if ($request['seed'] !== null) {
  session_name('WebCalendar-Wizard-' . md5(__DIR__ . '/wizard'));
  session_start();
  $_SESSION['wizard_state'] = $request['seed'];
  session_write_close();
}
$_SERVER['REQUEST_METHOD'] = $request['post'] ? 'POST' : 'GET';
$_POST = $request['post'];
$_GET = $request['get'];
register_shutdown_function(static function () {
  fwrite(STDERR, 'STATUS:' . (http_response_code() ?: 200));
});
require __DIR__ . '/wizard/index.php';
PHP
    );
  }

  protected function tearDown(): void
  {
    $this->db->close();
    $this->removeTree($this->root);
  }

  public static function configurations(): array
  {
    return [['settings'], ['environment']];
  }

  public static function privilegedActions(): array
  {
    $cases = [];
    foreach (['settings', 'environment'] as $mode) {
      foreach ([
        'save-php-settings-ack', 'save-app-settings', 'test-db-connection',
        'save-db-settings', 'continue-db-readonly', 'create-database',
        'execute-upgrade', 'get-upgrade-sql', 'save-settings-file', 'unknown-action',
      ] as $action) {
        $cases["$mode: $action"] = [$mode, $action];
      }
    }
    return $cases;
  }

  /** @dataProvider privilegedActions */
  public function testAnonymousPrivilegedActionsAreDenied(string $mode, string $action): void
  {
    $response = $this->request($mode, ['action' => $action]);
    self::assertSame(403, $response['status']);
    self::assertFalse($response['json']['success']);
    self::assertFalse($this->request($mode, [], ['ajax' => 'state'])['json']['state']['isValidUser']);
  }

  /** @dataProvider configurations */
  public function testStaleSessionCannotOverrideConfiguredAuthentication(string $mode): void
  {
    $response = $this->request($mode, [], ['ajax' => 'state'], true, [
      'isInitialized' => false, 'isValidUser' => true,
      'installPassword' => md5('Untrusted-password'),
    ]);
    self::assertFalse($response['json']['state']['isValidUser']);
    self::assertFalse($this->request($mode, [
      'action' => 'login', 'password' => 'Untrusted-password',
    ])['json']['success']);
    self::assertSame(403, $this->request($mode, [
      'action' => 'save-install-password',
      'password' => 'Replacement-password', 'password2' => 'Replacement-password',
    ])['status']);
    self::assertTrue($this->request($mode, [
      'action' => 'login', 'password' => 'Original-password',
    ])['json']['success']);
  }

  /** @dataProvider configurations */
  public function testAnonymousAdminCreationDoesNotChangeDatabase(string $mode): void
  {
    $before = $this->users();
    $response = $this->request($mode, $this->adminRequest());
    self::assertSame($before, $this->users());
    self::assertSame(403, $response['status']);
    self::assertFalse($response['json']['success']);
  }

  /** @dataProvider configurations */
  public function testPasswordSetupCannotAuthenticateAnInitializedInstallation(string $mode): void
  {
    $before = $this->users();
    $response = $this->request($mode, [
      'action' => 'save-install-password',
      'password' => 'Untrusted-new-password',
      'password2' => 'Untrusted-new-password',
    ]);
    $state = $this->request($mode, [], ['ajax' => 'state']);
    self::assertFalse($state['json']['state']['isValidUser']);
    self::assertFalse($response['json']['success']);
    self::assertSame(403, $response['status']);
    self::assertSame($before, $this->users());
    self::assertSame(403, $this->request($mode, [], ['action' => 'phpinfo'])['status']);
    self::assertTrue($this->request($mode, [
      'action' => 'login', 'password' => 'Original-password',
    ])['json']['success'], 'The configured password must remain usable.');
  }

  /** @dataProvider configurations */
  public function testConfiguredInstallationWithoutPasswordCannotBeClaimed(string $mode): void
  {
    $response = $this->request($mode, [
      'action' => 'save-install-password',
      'password' => 'Untrusted-new-password',
      'password2' => 'Untrusted-new-password',
    ], [], false);
    self::assertFalse($response['json']['success']);
    self::assertFalse($this->request($mode, [], ['ajax' => 'state'], false)['json']['state']['isValidUser']);
  }

  /** @dataProvider configurations */
  public function testAuthenticatedSetupCanCreateOnlyTheInitialAdministrator(string $mode): void
  {
    self::assertTrue($this->request($mode, [
      'action' => 'login', 'password' => 'Original-password',
    ])['json']['success']);
    $before = $this->users();
    self::assertFalse($this->request($mode, $this->adminRequest())['json']['success']);
    self::assertSame($before, $this->users());

    $this->db->exec('DELETE FROM webcal_user');
    self::assertTrue($this->request($mode, $this->adminRequest())['json']['success']);
    $user = $this->users()[0];
    self::assertSame('newadmin', $user['cal_login']);
    self::assertSame('Y', $user['cal_is_admin']);
    self::assertTrue(password_verify('New-admin-password', $user['cal_passwd']));
  }

  public function testFreshSetupAndPublicValidationStillWork(): void
  {
    self::assertTrue($this->request('fresh', [
      'action' => 'validate-field', 'field' => 'password', 'value' => 'First-password',
    ])['json']['success']);
    self::assertTrue($this->request('fresh', [
      'action' => 'save-install-password',
      'password' => 'First-password', 'password2' => 'First-password',
    ])['json']['success']);
    self::assertTrue($this->request('fresh', [], ['ajax' => 'state'])['json']['state']['isValidUser']);
  }

  public function testEmptySettingsFileDoesNotEnableAnonymousPasswordSetup(): void
  {
    file_put_contents($this->root . '/includes/settings.php', '');
    $response = $this->request('settings', [
      'action' => 'save-install-password',
      'password' => 'Replacement-password', 'password2' => 'Replacement-password',
    ]);
    self::assertSame(403, $response['status']);
    self::assertFalse($response['json']['success']);
    self::assertFalse($this->request('settings', [], ['ajax' => 'state'])['json']['state']['isValidUser']);
  }

  public function testAnonymousSettingsSaveIsForbidden(): void
  {
    $this->request('settings', [], ['ajax' => 'state']);
    $settings = file_get_contents($this->root . '/includes/settings.php');
    $response = $this->request('settings', ['action' => 'save-settings-file']);
    self::assertSame(403, $response['status']);
    self::assertSame($settings, file_get_contents($this->root . '/includes/settings.php'));
    self::assertSame([], glob($this->root . '/includes/settings.php.backup.*'));
  }

  private function adminRequest(): array
  {
    return [
      'action' => 'create-admin-user', 'admin_login' => 'newadmin',
      'admin_password' => 'New-admin-password', 'admin_password2' => 'New-admin-password',
    ];
  }

  private function users(): array
  {
    $rows = [];
    $result = $this->db->query('SELECT * FROM webcal_user ORDER BY cal_login');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $rows[] = $row;
    }
    return $rows;
  }

  private function request(string $mode, array $post = [], array $get = [],
    bool $password = true, ?array $seed = null): array
  {
    $settings = [
      'db_type' => 'sqlite3', 'db_database' => $this->root . '/calendar.sqlite',
    ];
    if ($password) {
      $settings['install_password'] = md5('Original-password');
    }
    $env = [];
    if ($mode === 'settings') {
      $path = $this->root . '/includes/settings.php';
      if (!file_exists($path)) {
        $content = "<?php /*\n";
        foreach ($settings as $key => $value) {
          $content .= "$key: $value\n";
        }
        file_put_contents($path, $content . "*/\n");
      }
    } elseif ($mode === 'environment') {
      $env['WEBCALENDAR_USE_ENV'] = 'true';
      foreach ($settings as $key => $value) {
        $env['WEBCALENDAR_' . strtoupper($key)] = $value;
      }
    }
    $process = proc_open([
      PHP_BINARY, '-d', 'session.save_path=' . $this->root . '/sessions',
      $this->root . '/request.php', json_encode([
        'env' => $env, 'session' => $this->sessionId, 'post' => $post, 'get' => $get,
        'seed' => $seed,
      ]),
    ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    self::assertSame(0, proc_close($process), $error . $output);
    self::assertSame(1, preg_match('/STATUS:(\d+)/', $error, $matches), $error);
    return ['status' => (int) $matches[1], 'json' => json_decode($output, true)];
  }

  private function copyTree(string $source, string $target): void
  {
    mkdir($target);
    foreach (new DirectoryIterator($source) as $file) {
      if ($file->isDot()) continue;
      $destination = $target . '/' . $file->getFilename();
      if ($file->isDir()) {
        $this->copyTree($file->getPathname(), $destination);
      } else {
        copy($file->getPathname(), $destination);
      }
    }
  }

  private function removeTree(string $path): void
  {
    foreach (new DirectoryIterator($path) as $file) {
      if ($file->isDot()) continue;
      if ($file->isDir()) {
        $this->removeTree($file->getPathname());
      } else {
        unlink($file->getPathname());
      }
    }
    rmdir($path);
  }
}
