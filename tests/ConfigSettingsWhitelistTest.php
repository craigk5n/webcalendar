<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression guard for issue #666 (bug #2).
 *
 * includes/config.php parses settings.php by looping over the
 * $config_possible_settings whitelist (do_config(), the `foreach
 * ($possible_settings ...)` loop). A key that isn't in that whitelist is
 * never read from settings.php, even when the wizard wrote it there.
 *
 * `single_user_login` was missing from the whitelist while `single_user`
 * was present. So enabling Single User Mode wrote `single_user_login:` to
 * settings.php, but do_config() never loaded it — config.php then read the
 * unset key ("Undefined array key") and died with "You must define
 * single_user_login", even though the value was on disk.
 *
 * The two must always travel together: config.php reads
 * $settings['single_user_login'] whenever single_user is 'Y'.
 */
final class ConfigSettingsWhitelistTest extends TestCase
{
  private const REPO_ROOT = __DIR__ . '/..';

  /**
   * Read $config_possible_settings exactly as config.php defines it, in an
   * isolated subprocess (config.php also defines functions, so we avoid
   * redeclare clashes and global pollution in the PHPUnit process). CWD is
   * the includes dir because config.php does `require_once 'load_assets.php'`.
   *
   * @return array<string,string>
   */
  private function configWhitelist(): array
  {
    $code = 'chdir(' . var_export(self::REPO_ROOT . '/includes', true) . ');'
      . 'require "config.php";'
      . 'echo json_encode($config_possible_settings);';
    $cmd = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code);
    $out = shell_exec($cmd . ' 2>/dev/null');
    self::assertIsString($out, 'Could not execute config.php to read whitelist');
    $decoded = json_decode(trim((string) $out), true);
    self::assertIsArray(
      $decoded,
      "config.php did not yield a \$config_possible_settings array. Output:\n" . $out
    );
    return $decoded;
  }

  public function testSingleUserLoginIsARecognizedSetting(): void
  {
    $whitelist = $this->configWhitelist();
    self::assertArrayHasKey(
      'single_user_login',
      $whitelist,
      "single_user_login must be in \$config_possible_settings, or do_config() "
      . "will never read it from settings.php (issue #666 bug #2)."
    );
  }

  public function testSingleUserAndItsLoginTravelTogether(): void
  {
    $whitelist = $this->configWhitelist();
    // config.php reads $settings['single_user_login'] whenever single_user is
    // enabled, so if one is a recognized setting the other must be too.
    if (array_key_exists('single_user', $whitelist)) {
      self::assertArrayHasKey(
        'single_user_login',
        $whitelist,
        'single_user is a recognized setting but single_user_login is not — '
        . 'Single User Mode will fail at runtime.'
      );
    }
  }
}
