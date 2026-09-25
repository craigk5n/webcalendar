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
      seed --scenario=N   Load a named development scenario. SQLite only, and
           [--force]      needs --force or WEBCAL_ALLOW_SEED=1.
      seed --list         Show the available scenarios.
      reset [--force]     Remove every calendar entry. Same requirements.
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

/**
 * Loads the development classes and refuses unless this calendar is
 * demonstrably disposable. Returns the connected database file on success.
 */
function wc_require_disposable_calendar(array $argv): string
{
  wc_require_dev_classes();

  wc_bootstrap();

  $dbFile = (string) ($GLOBALS['db_database'] ?? '');
  $reason = \WebCalendar\Dev\DevGuard::refuseReason(
    (string) ($GLOBALS['db_type'] ?? ''),
    $dbFile,
    in_array('--force', $argv, true),
    (string) getenv(\WebCalendar\Dev\DevGuard::ENV_OPT_IN)
  );

  if ($reason !== null) {
    fwrite(STDERR, "REFUSING: $reason\n");
    exit(1);
  }

  return $dbFile;
}

/**
 * @param list<array{0: string, 1: list<mixed>}> $statements
 */
function wc_run_statements(array $statements): int
{
  $count = 0;
  foreach ($statements as [$sql, $params]) {
    if (!dbi_execute($sql, $params)) {
      fwrite(STDERR, "FAILED: $sql\n  " . dbi_error() . "\n");
      exit(1);
    }
    $count++;
  }

  return $count;
}

/**
 * includes/classes/Dev/ is excluded from release-files, so these commands do
 * not exist on a production install at all. The guard in DevGuard is the
 * second line of defence, not the first.
 */
function wc_require_dev_classes(): void
{
  $guard = WC_ROOT . '/includes/classes/Dev/DevGuard.php';
  if (!is_file($guard)) {
    fwrite(STDERR, "seed and reset are development commands and are not "
      . "included in releases.\nRun them from a git checkout.\n");
    exit(1);
  }

  require_once $guard;
  require_once WC_ROOT . '/includes/classes/Dev/Seeder.php';
}

function wc_cmd_seed(array $argv): int
{
  wc_require_dev_classes();

  if (in_array('--list', $argv, true)) {
    echo "Scenarios:\n";
    foreach (\WebCalendar\Dev\Seeder::scenarios() as $name => $description) {
      printf("  %-10s %s\n", $name, $description);
    }
    return 0;
  }

  $scenario = '';
  foreach ($argv as $arg) {
    if (str_starts_with($arg, '--scenario=')) {
      $scenario = substr($arg, 11);
    }
  }

  if ($scenario === '') {
    fwrite(STDERR, "Missing --scenario=NAME. Use --list to see the options.\n");
    return 1;
  }
  if (!\WebCalendar\Dev\Seeder::exists($scenario)) {
    fwrite(STDERR, "Unknown scenario: $scenario. Use --list to see the options.\n");
    return 1;
  }

  $dbFile = wc_require_disposable_calendar($argv);
  $baseDate = (int) gmdate('Ymd');

  $count = wc_run_statements(
    \WebCalendar\Dev\Seeder::statements($scenario, $baseDate)
  );

  echo "Seeded '$scenario' into $dbFile ($count statements, anchored on $baseDate).\n";

  return 0;
}

function wc_cmd_reset(array $argv): int
{
  $dbFile = wc_require_disposable_calendar($argv);
  $count = wc_run_statements(\WebCalendar\Dev\Seeder::resetStatements());

  echo "Removed every calendar entry from $dbFile ($count statements).\n";

  return 0;
}

$argv = $_SERVER['argv'] ?? [];
array_shift($argv);
$command = array_shift($argv) ?? '';

switch ($command) {
  case 'diagnose':
    exit(wc_cmd_diagnose($argv));
  case 'seed':
    exit(wc_cmd_seed($argv));
  case 'reset':
    exit(wc_cmd_reset($argv));
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
