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
      user reset-password --login=NAME [--stdin]
                          Set a new password. One is generated and printed
                          unless --stdin is given, in which case it is read
                          from standard input.
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

function wc_cmd_user(array $argv): int
{
  $action = $argv[0] ?? '';
  if ($action !== 'reset-password') {
    fwrite(STDERR, "Usage: php bin/webcal.php user reset-password --login=NAME [--stdin]\n");
    return 1;
  }

  $login = '';
  foreach ($argv as $arg) {
    if (str_starts_with($arg, '--login=')) {
      $login = substr($arg, 8);
    }
  }

  if ($login === '') {
    fwrite(STDERR, "Missing --login=NAME.\n");
    return 1;
  }

  if (!user_load_variables($login, 'wc_reset_')) {
    fwrite(STDERR, "No such user: $login\n");
    return 1;
  }

  $password = wc_read_or_generate_password($argv);

  if (!user_update_user_password($login, $password)) {
    fwrite(STDERR, "Could not update the password: "
      . ($GLOBALS['error'] ?? 'unknown error') . "\n");
    return 1;
  }

  // The activity log is how an administrator finds out this happened.
  //
  // 'u' is LOG_USER_UPDATE. The constant is defined in
  // WebCalendar::_initFunctions(), which this command does not run -- it
  // loads configuration and the database layer only -- so referring to the
  // constant here would silently skip the audit entry.
  activity_log(0, $login, $login, 'u', 'Password reset from the command line');

  echo "Password reset for $login.\n";
  if (!in_array('--stdin', $argv, true)) {
    echo "\n  $password\n\n";
    echo "Shown once. It is stored only as a hash.\n";
  }

  return 0;
}

/**
 * A generated password, or one read from standard input.
 *
 * Deliberately not accepted as a command-line option: arguments are visible
 * in ps output and land in shell history.
 */
function wc_read_or_generate_password(array $argv): string
{
  if (!in_array('--stdin', $argv, true)) {
    // Unambiguous alphabet: no O/0, l/1, I.
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $password = '';
    for ($i = 0; $i < 20; $i++) {
      $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $password;
  }

  $password = trim((string) fgets(STDIN));
  if ($password === '') {
    fwrite(STDERR, "No password on standard input.\n");
    exit(1);
  }

  return $password;
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
  case 'user':
    // Loaded here rather than inside a function on purpose.
    // includes/auth-settings.php assigns around twenty-five configuration
    // variables at file scope, and includes/user.php reads them, so requiring
    // either from inside a function would make them function-local and the
    // user layer would see nothing. Same reason config.php is loaded above.
    wc_bootstrap();

    // Changing webcal_user.cal_passwd only affects logins when WebCalendar is
    // the thing checking passwords. With LDAP, IMAP, NIS or Joomla the
    // password lives elsewhere, and writing that column would report success
    // while the user stayed locked out.
    $wcUserInc = basename((string) ($GLOBALS['user_inc'] ?? 'user.php'));
    if ($wcUserInc !== 'user.php') {
      fwrite(STDERR, "This installation authenticates through $wcUserInc, so "
        . "passwords are not stored in WebCalendar.\nReset it there instead;"
        . " changing the local column would not affect logins.\n");
      exit(1);
    }

    // The marker that these files were not reached directly over HTTP.
    // WebCalendar::_initFunctions() normally sets it; js_cacher.php and
    // css_cacher.php set it themselves for the same reason. The SAPI guard at
    // the top of this file already makes direct HTTP access impossible.
    if (!defined('_ISVALID')) {
      define('_ISVALID', true);
    }
    require_once WC_ROOT . '/includes/translate.php';
    require_once WC_ROOT . '/includes/functions.php';
    require_once WC_ROOT . '/includes/' . $wcUserInc;

    exit(wc_cmd_user($argv));
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
