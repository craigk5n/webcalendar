<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * do_config() in includes/config.php normalizes the single_user and readonly
 * settings to 'Y'/'N' with a regex. The old pattern /(1|true|yes|enable|on)/i
 * had two defects:
 *
 *   1. It did not match a bare 'Y' — even though WebCalendar's own convention
 *      is 'Y'/'N' and config.php itself OUTPUTS 'Y'. So `single_user: Y` in
 *      settings.php silently disabled single-user mode.
 *   2. It was unanchored, so it matched substrings — e.g. `single_user: none`
 *      contains "on" and wrongly enabled single-user mode.
 *
 * The fix anchors the pattern and adds 'y': /^(1|y|yes|true|enable|on)$/i.
 *
 * This test exercises the real do_config() through the WEBCALENDAR_USE_ENV
 * path (so it needs no settings.php and no database schema). It detects
 * whether single-user mode activated by a side effect: when single_user is
 * 'Y' and no login is given, config.php dies with "You must define
 * single_user_login"; when it is 'N', config.php skips that block and instead
 * fails later at the (empty) database. Presence of "single_user_login" in the
 * output therefore means the value parsed as truthy.
 */
final class ConfigTruthyValueTest extends TestCase
{
  private const REPO_ROOT = __DIR__ . '/..';

  /** @return array<string, array{0:string,1:bool}> */
  public static function singleUserValues(): array
  {
    return [
      // value => expected to enable single-user mode
      "'Y' (WebCalendar convention)" => ['Y', true],
      "'y' lowercase"                => ['y', true],
      "'yes'"                        => ['yes', true],
      "'true' (what the wizard writes)" => ['true', true],
      "'1'"                          => ['1', true],
      "'on'"                         => ['on', true],
      "'enable'"                     => ['enable', true],
      "'N'"                          => ['N', false],
      "'no'"                         => ['no', false],
      "'false'"                      => ['false', false],
      "'0'"                          => ['0', false],
      "'off'"                        => ['off', false],
      // Regression for the unanchored-substring bug: "none" contains "on".
      "'none' (must NOT match 'on')" => ['none', false],
    ];
  }

  /**
   * @dataProvider singleUserValues
   */
  public function testSingleUserValueParsing(string $value, bool $shouldEnable): void
  {
    $output = $this->runDoConfigSingleUser($value);
    $enabled = stripos($output, 'single_user_login') !== false;

    self::assertSame(
      $shouldEnable,
      $enabled,
      "single_user: '$value' should " . ($shouldEnable ? 'ENABLE' : 'NOT enable')
      . " single-user mode.\n--- do_config output ---\n" . $output
    );
  }

  /**
   * Run do_config() with WEBCALENDAR_SINGLE_USER=$value and no login, in an
   * isolated subprocess (config.php defines functions; keep the PHPUnit
   * process clean). CWD is the includes dir for config.php's relative include.
   */
  private function runDoConfigSingleUser(string $value): string
  {
    $db = sys_get_temp_dir() . '/wc-truthy-' . getmypid() . '-' . uniqid() . '.db';
    $code = 'chdir(' . var_export(self::REPO_ROOT . '/includes', true) . ');'
      . 'require "translate.php"; require "config.php"; require "dbi4php.php";'
      . 'do_config();';

    $env = [
      'WEBCALENDAR_USE_ENV'    => 'true',
      'WEBCALENDAR_DB_TYPE'    => 'sqlite3',
      'WEBCALENDAR_DB_DATABASE' => $db,
      'WEBCALENDAR_SINGLE_USER' => $value,
      'PATH'                   => getenv('PATH') ?: '/usr/bin:/bin',
    ];

    $cmd = escapeshellarg(PHP_BINARY)
      . ' -d error_reporting=E_ALL -d display_errors=stderr'
      . ' -r ' . escapeshellarg($code);

    $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $desc, $pipes, self::REPO_ROOT, $env);
    self::assertIsResource($proc, 'Could not launch PHP for do_config()');
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    if (is_file($db)) {
      @unlink($db);
    }
    return strip_tags((string) $out . (string) $err);
  }
}
