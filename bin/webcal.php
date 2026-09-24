<?php

declare(strict_types=1);

// Command-line only. Served over HTTP this hands execution to anyone who can
// reach the URL, and .htaccess cannot be relied on: the Debian and Ubuntu
// default of AllowOverride None makes it a no-op.
if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit(basename(__FILE__) . " must be run from the command line.\n");
}

/**
 * WebCalendar command line.
 *
 * Deliberately a dispatcher rather than a framework. Commands that need the
 * application bootstrap say so; `diagnose` is built to run when that
 * bootstrap fails, because an administrator whose calendar will not load is
 * exactly the person who needs to produce a report.
 */

const WC_ROOT = __DIR__ . '/..';

// Included at global scope on purpose. config.php assigns
// $config_possible_settings at file scope and do_config() reads it through
// `global`; requiring the file from inside a function would make that array
// function-local and do_config() would iterate null. It also require_once's
// load_assets.php by a relative path, hence the chdir around it.
// dbi4php.php comes first because do_config() calls dbi_connect() itself to
// compare the stored schema version.
require_once WC_ROOT . '/includes/dbi4php.php';
$wcCwd = getcwd();
chdir(WC_ROOT . '/includes');
require_once WC_ROOT . '/includes/config.php';
if (is_string($wcCwd)) {
  chdir($wcCwd);
}

function wc_usage(int $exitCode): never
{
  $out = $exitCode === 0 ? STDOUT : STDERR;
  fwrite($out, <<<TXT
    Usage: php bin/webcal.php <command> [options]

    Commands:
      diagnose [--json]   Print an environment report for a bug report.
                          Secrets are never included; see docs/troubleshooting.md.
      help                Show this message.

    TXT);
  exit($exitCode);
}

/**
 * Loads enough of WebCalendar to describe it.
 *
 * `$callingFromInstall` stops includes/config.php redirecting or calling
 * die_miserable_death() when settings.php is missing, which is the case this
 * command most needs to survive.
 *
 * @return array<string, mixed> environment facts for the collector
 */
function wc_bootstrap(): array
{
  $env = [];

  // true = callingFromInstall, which stops do_config() redirecting to the
  // wizard or calling die_miserable_death() when settings.php is absent.
  do_config(true);

  $type = $GLOBALS['db_type'] ?? '';
  if ($type === '') {
    $env['db_server_version'] = '(no database configured)';
    return $env;
  }

  $conn = @dbi_connect(
    (string) ($GLOBALS['db_host'] ?? ''),
    (string) ($GLOBALS['db_login'] ?? ''),
    (string) ($GLOBALS['db_password'] ?? ''),
    (string) ($GLOBALS['db_database'] ?? ''),
    false
  );

  if (!$conn) {
    $env['db_server_version'] = '(could not connect)';
    return $env;
  }

  $env['db_server_version'] = wc_db_server_version((string) $type);
  $env['settings'] = wc_db_settings();
  $env['db_schema_version'] = $env['settings']['WEBCAL_PROGRAM_VERSION']
    ?? '(unknown)';

  return $env;
}

function wc_db_server_version(string $type): string
{
  $sql = match (true) {
    str_contains($type, 'sqlite') => 'SELECT sqlite_version()',
    str_contains($type, 'postgres') => 'SELECT version()',
    default => 'SELECT VERSION()',
  };

  $res = @dbi_execute($sql);
  if (!$res) {
    return '(unavailable)';
  }
  $row = dbi_fetch_row($res);
  dbi_free_result($res);

  return isset($row[0]) ? substr((string) $row[0], 0, 80) : '(unavailable)';
}

/**
 * @return array<string, string>
 */
function wc_db_settings(): array
{
  $settings = [];
  $res = @dbi_execute('SELECT cal_setting, cal_value FROM webcal_config');
  if (!$res) {
    return $settings;
  }
  while ($row = dbi_fetch_row($res)) {
    $settings[(string) $row[0]] = (string) $row[1];
  }
  dbi_free_result($res);

  return $settings;
}

function wc_cmd_diagnose(array $argv): int
{
  require_once WC_ROOT . '/includes/classes/Diagnostics/ConfigPolicy.php';
  require_once WC_ROOT . '/includes/classes/Diagnostics/Report.php';
  require_once WC_ROOT . '/includes/classes/Diagnostics/Collector.php';

  $env = wc_bootstrap();
  $settings = $env['settings'] ?? [];
  unset($env['settings']);
  $env['install_root'] = realpath(WC_ROOT) ?: WC_ROOT;

  $report = \WebCalendar\Diagnostics\Collector::collect($settings, $env);

  echo in_array('--json', $argv, true)
    ? $report->toJson() . "\n"
    : $report->toText();

  return 0;
}

$argv = $_SERVER['argv'] ?? [];
array_shift($argv);
$command = array_shift($argv) ?? '';

switch ($command) {
  case 'diagnose':
    exit(wc_cmd_diagnose($argv));
  case 'help':
  case '--help':
  case '-h':
    wc_usage(0);
  case '':
    wc_usage(1);
  default:
    fwrite(STDERR, "Unknown command: $command\n\n");
    wc_usage(1);
}
