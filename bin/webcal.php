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
      db dump [--output=FILE]
                          Dump the webcal_* tables as SQL, to standard output
                          or to FILE, which is created readable only by you.
      db check            Report whether an upgrade is pending, and apply
                          nothing. Exit 0 up to date, 1 pending, 2 unknown.
      user reset-password --login=NAME [--stdin]
                          Set a new password. One is generated and printed
                          unless --stdin is given, in which case it is read
                          from standard input.
      reminders send      Send the email reminders that are due. The same work
                          as the documented cron entry, which keeps working.
      remotes refresh     Reload every calendar subscribed to a remote URL.
      email test --to=ADDRESS
                          Send one message with the configured mail settings
                          and say why it failed if it did.
      export --login=NAME Write a calendar as iCalendar, to standard output or
             [--output=FILE]   to FILE, which is created readable only by you.
             [--from=YYYYMMDD --to=YYYYMMDD]
                          Every date unless a range is given.
             [--include-layers] [--include-deleted] [--category=ID]
      config list         Show every setting, with secret values hidden.
      config get NAME     Print one value in full, secret or not.
      config set NAME VALUE
                          Store a value the way Admin > Settings does.
                          An unrecognised name needs --force.
      import --login=NAME --file=FILE [--overwrite] [--category=ID]
                          Import an .ics or .vcs file into a calendar.
                          Importing the same file twice updates the events it
                          created rather than duplicating them; --overwrite
                          also marks what an earlier import left behind as
                          deleted.
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

  wc_init_query_cache();

  $env['db_server_version'] = wc_db_server_version((string) $type);
  $env['settings'] = wc_db_settings();
  $env['db_schema_version'] = $env['settings']['WEBCAL_PROGRAM_VERSION']
    ?? '(unknown)';

  return $env;
}

/**
 * Tells this process where the query cache lives.
 *
 * do_config() only calls dbi_init_cache() when it is not being called from an
 * installer, and every command here passes $callingFromInstall = true so that
 * a missing settings.php produces a report rather than a redirect. The cost is
 * that dbi_execute()'s automatic invalidation -- it clears the cache on
 * anything that is not a SELECT -- had nothing to clear, because this process
 * did not know the directory existed.
 *
 * That silently mattered for every command that writes. An installation with
 * db_cachedir set serves webcal_config and the user rows out of
 * {db_cachedir}/*.dat, so a setting changed or a password reset from here
 * would not be seen by the web server until something else happened to clear
 * the cache. Issue #639 is the same failure with the schema version.
 */
function wc_init_query_cache(): void
{
  $settings = $GLOBALS['settings'] ?? [];
  if (!is_array($settings)) {
    return;
  }

  // The order do_config() uses: db_cachedir first, then cachedir.
  $dir = (string) ($settings['db_cachedir'] ?? '');
  if ($dir === '') {
    $dir = (string) ($settings['cachedir'] ?? '');
  }
  if ($dir !== '') {
    dbi_init_cache($dir);
  }
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

/**
 * Dumps the webcal_* tables by handing the work to the database's own tool.
 *
 * Reimplementing this in PHP would mean getting quoting, ordering and
 * constraints right on three backends, and a backup that does not restore is
 * worse than no backup. mysqldump, pg_dump and sqlite3 already do it.
 *
 * The value here is that an administrator does not have to dig credentials
 * out of settings.php, which is the same reason `diagnose` exists.
 *
 * Only the webcal_* tables are included. The configured database may hold
 * other applications' tables, and they are not WebCalendar's to copy.
 */
function wc_cmd_db(array $argv): int
{
  if (($argv[0] ?? '') === 'check') {
    return wc_db_check();
  }

  if (($argv[0] ?? '') !== 'dump') {
    fwrite(STDERR, "Usage: php bin/webcal.php db dump [--output=FILE]\n"
      . "       php bin/webcal.php db check\n");
    return 1;
  }

  $output = '';
  foreach ($argv as $arg) {
    if (str_starts_with($arg, '--output=')) {
      $output = substr($arg, 9);
    }
  }

  $type = (string) ($GLOBALS['db_type'] ?? '');
  $tables = wc_webcal_tables($type);
  if ($tables === []) {
    fwrite(STDERR, "No webcal_ tables found. Is this the right database?\n");
    return 1;
  }

  [$command, $env, $cleanup] = wc_dump_command($type, $tables);
  if ($command === []) {
    return 1;
  }

  $binary = $command[0];
  if (wc_which($binary) === null) {
    fwrite(STDERR, "$binary is not installed or not on PATH.\n"
      . "It ships with the database client package for " . $type . ".\n");
    $cleanup();
    return 1;
  }

  // 0600: a dump is every event, every user and every hashed password.
  $target = $output === '' ? 'php://stdout' : $output;
  if ($output !== '') {
    touch($output);
    chmod($output, 0600);
  }

  $descriptors = [1 => ['file', $target, 'w'], 2 => ['file', 'php://stderr', 'w']];
  $process = proc_open($command, $descriptors, $pipes, null, $env);
  $status = is_resource($process) ? proc_close($process) : 1;

  $cleanup();

  if ($status !== 0) {
    fwrite(STDERR, "$binary exited with status $status.\n");
    return 1;
  }

  if ($output !== '') {
    fwrite(STDERR, 'Wrote ' . count($tables) . " tables to $output\n");
  }

  return 0;
}

/**
 * @return list<string>
 */
/**
 * Reports whether the schema matches the program, and applies nothing.
 *
 * The same comparison do_config() makes when it decides whether to send a
 * browser to the wizard, with an exit status instead of a redirect, so a
 * deployment can ask the question without a browser: 0 up to date, 1 an
 * upgrade is pending, 2 the answer could not be established.
 */
function wc_db_check(): int
{
  $program = (string) ($GLOBALS['PROGRAM_VERSION'] ?? '');
  $type = (string) ($GLOBALS['db_type'] ?? '');

  // Read directly rather than through dbi_get_cached_rows(). A stale cache
  // file pinned the old version and looped the administrator back to the
  // wizard on every request (#639); includes/config.php reads it the same way
  // and says so for the same reason.
  $stored = null;
  $res = dbi_execute("SELECT cal_value FROM webcal_config
    WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'", [], false, false);
  if ($res) {
    $row = dbi_fetch_row($res);
    if ($row && isset($row[0])) {
      $stored = (string) $row[0];
    }
    dbi_free_result($res);
  }

  $tables = wc_webcal_tables($type);

  printf("%-18s %s\n", 'Program version:',
    $program === '' ? '(unknown)' : $program);
  printf("%-18s %s\n", 'Database version:',
    ($stored === null || $stored === '') ? '(no row)' : $stored);
  printf("%-18s %d\n", 'webcal_ tables:', count($tables));
  echo "\n";

  if ($stored === null || $stored === '') {
    fwrite(STDERR, "webcal_config holds no WEBCAL_PROGRAM_VERSION row, so "
      . "there is nothing to compare against.\nA complete installation always "
      . "has one; run the wizard.\n");
    return 2;
  }

  if ($stored === $program) {
    echo "Up to date. Nothing to apply.\n";
    return 0;
  }

  $normalise = static fn (string $v): string
    => str_replace('v', '', strtolower($v));

  if (version_compare($normalise($stored), $normalise($program), '>')) {
    fwrite(STDERR, "The database is ahead of the code: $stored against "
      . "$program.\nThat is an older WebCalendar deployed over a database "
      . "another copy has already upgraded. Deploy the matching version "
      . "rather than moving the schema back.\n");
    return 2;
  }

  // Soft dependency, and deliberately so: administrators are told they may
  // remove wizard/ once installed, and upgrade_requires_db_changes() answers
  // conservatively when it is gone. Worth saying which of the two happened.
  $upgradeSql = WC_ROOT . '/wizard/shared/upgrade-sql.php';

  if (!upgrade_requires_db_changes($type, $stored, $program)) {
    // config.php's own handling of this case: no schema delta, so it calls
    // update_webcalendar_version_in_db() and carries on. No wizard needed.
    echo "No upgrade steps are recorded between $stored and $program, so the "
      . "schema itself matches.\nThe stored version is behind and is brought "
      . "forward automatically on the next page load.\n";
    return 0;
  }

  echo "An upgrade is pending: $stored to $program.\n\n";
  if (!is_file($upgradeSql)) {
    echo "wizard/ is not present, so which steps apply cannot be read from "
      . "here and this answer is the conservative one. Restore the directory "
      . "from the release to upgrade.\n";
  } else {
    echo "Run wizard/index.php in a browser, or wizard/headless.php from a "
      . "shell. Nothing was changed by this command.\n";
  }

  return 1;
}

function wc_webcal_tables(string $type): array
{
  // Asked of the live database rather than taken from a list in this file,
  // which would drift from the schema.
  //
  // The pattern is deliberately loose. LIKE treats _ as a single-character
  // wildcard and the three backends disagree about escaping it -- MySQL's
  // SHOW TABLES and SQLite's .dump do not accept ESCAPE at all -- so the
  // prefix is matched exactly in PHP below instead of three ways in SQL.
  $sql = match (true) {
    str_contains($type, 'sqlite') =>
      "SELECT name FROM sqlite_master WHERE type = 'table' AND name LIKE 'webcal%' ORDER BY name",
    str_contains($type, 'postgres') =>
      "SELECT tablename FROM pg_tables WHERE tablename LIKE 'webcal%' ORDER BY tablename",
    default =>
      "SHOW TABLES LIKE 'webcal%'",
  };

  $res = @dbi_execute($sql);
  if (!$res) {
    return [];
  }

  $tables = [];
  while ($row = dbi_fetch_row($res)) {
    $name = (string) ($row[0] ?? '');
    if (str_starts_with($name, 'webcal_')) {
      $tables[] = $name;
    }
  }
  dbi_free_result($res);

  return $tables;
}

/**
 * The command, its environment, and a cleanup callback.
 *
 * Passwords never reach the argument list: those are visible in ps output to
 * every user on the machine. MySQL gets a 0600 defaults file, PostgreSQL gets
 * PGPASSWORD in the child's environment, and SQLite has no credentials.
 *
 * @param list<string> $tables
 * @return array{0: list<string>, 1: array<string, string>|null, 2: callable}
 */
function wc_dump_command(string $type, array $tables): array
{
  $nothing = static function (): void {};
  $host = (string) ($GLOBALS['db_host'] ?? '');
  $database = (string) ($GLOBALS['db_database'] ?? '');
  $login = (string) ($GLOBALS['db_login'] ?? '');
  $password = (string) ($GLOBALS['db_password'] ?? '');

  if (str_contains($type, 'sqlite')) {
    // .dump takes a LIKE pattern and does not accept ESCAPE, so the
    // underscore here is a single-character wildcard rather than a literal.
    // Every table in the schema begins with the literal "webcal_", and
    // nothing else begins with "webcal", so the match is the same set.
    return [['sqlite3', $database, ".dump 'webcal_%'"], null, $nothing];
  }

  if (str_contains($type, 'postgres')) {
    $command = ['pg_dump', '--no-owner', '--no-privileges'];
    if ($host !== '') {
      $command[] = '--host=' . $host;
    }
    if ($login !== '') {
      $command[] = '--username=' . $login;
    }
    foreach ($tables as $table) {
      $command[] = '--table=' . $table;
    }
    $command[] = $database;

    return [$command, ['PGPASSWORD' => $password] + getenv(), $nothing];
  }

  // MySQL and MariaDB. --defaults-extra-file has to be the first argument.
  $file = tempnam(sys_get_temp_dir(), 'wcdump');
  if ($file === false) {
    fwrite(STDERR, "Could not create a temporary credentials file.\n");
    return [[], null, $nothing];
  }
  chmod($file, 0600);
  file_put_contents($file, "[client]\nuser=" . $login . "\npassword=\""
    . str_replace('"', '\\"', $password) . "\"\n"
    . ($host === '' ? '' : 'host=' . $host . "\n"));

  $command = ['mysqldump', '--defaults-extra-file=' . $file,
    '--single-transaction', '--no-tablespaces', $database];
  foreach ($tables as $table) {
    $command[] = $table;
  }

  return [$command, null, static function () use ($file): void {
    @unlink($file);
  }];
}

function wc_which(string $binary): ?string
{
  $path = trim((string) shell_exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null'));

  return $path === '' ? null : $path;
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
  activity_log(0, $login, $login, LOG_USER_UPDATE,
    'Password reset from the command line');

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

/**
 * Sends one message through the configured mailer.
 *
 * docs/admin-guide.md, faq.md, security.md and troubleshooting.md told
 * administrators to run tools/send_test_email.php for this. That script was
 * never committed, so no release has ever contained it.
 */
function wc_cmd_email(array $argv): int
{
  if (($argv[0] ?? '') !== 'test') {
    fwrite(STDERR, "Usage: php bin/webcal.php email test --to=ADDRESS\n");
    return 1;
  }

  $to = '';
  foreach ($argv as $arg) {
    if (str_starts_with($arg, '--to=')) {
      $to = substr($arg, 5);
    }
  }
  if ($to === '') {
    fwrite(STDERR, "Missing --to=ADDRESS. Send the test somewhere you can "
      . "read it.\n");
    return 1;
  }
  if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Not an email address: $to\n");
    return 1;
  }

  $mailer = (string) ($GLOBALS['EMAIL_MAILER'] ?? 'mail');
  $from = (string) ($GLOBALS['EMAIL_FALLBACK_FROM'] ?? '');

  echo "Mailer:  $mailer\n";
  if ($mailer === 'smtp') {
    echo 'Server:  ' . ($GLOBALS['SMTP_HOST'] ?? '(none configured)') . ':'
      . ($GLOBALS['SMTP_PORT'] ?? '(no port)') . "\n";
    echo 'Auth:    ' . ((($GLOBALS['SMTP_AUTH'] ?? '') === 'Y')
      ? 'yes, as ' . ($GLOBALS['SMTP_USERNAME'] ?? '(no username)')
      : 'no') . "\n";
  }
  echo "From:    " . ($from === '' ? '(not set)' : $from) . "\n";
  echo "To:      $to\n\n";

  if ($from === '' || $from === 'youremailhere') {
    fwrite(STDERR, "Admin > Settings > Email has no sender address, so the "
      . "message would be sent with an empty From header and most servers "
      . "will reject it.\n");
    return 1;
  }

  // WebCalMailer reports failures by appending to this global rather than
  // returning a reason; approve_entry.php and edit_entry_handler.php read it
  // the same way. Cleared first so an earlier message cannot be reported.
  $GLOBALS['mailerError'] = '';

  $mail = new WebCalMailer();
  $sent = $mail->WC_Send(
    'WebCalendar',
    $to,
    $to,
    'Test message',
    "This is a test message from WebCalendar.\n\nIf you are reading it, "
      . "outgoing mail works.\n"
  );

  if ($sent) {
    echo "Sent. If it does not arrive, the message left WebCalendar and the "
      . "problem is beyond it:\ncheck the mail server log, the spam folder "
      . "and SPF or DKIM for the sender domain.\n";
    return 0;
  }

  // LastError() is PHPMailer's own account of the failure. $mailerError is
  // where WebCalMailer collects its own, which is what the web pages read.
  $reason = trim((string) $mail->LastError());
  if ($reason === '') {
    $reason = trim(strip_tags(str_replace('<br>', "\n",
      (string) ($GLOBALS['mailerError'] ?? ''))));
  }
  fwrite(STDERR, "Not sent.\n"
    . ($reason === '' ? "The mailer gave no reason.\n" : "$reason\n"));

  return 1;
}

/**
 * The value given to --name=..., or '' when the option is absent.
 */
function wc_opt(array $argv, string $name): string
{
  $prefix = '--' . $name . '=';
  foreach ($argv as $arg) {
    if (str_starts_with($arg, $prefix)) {
      return substr($arg, strlen($prefix));
    }
  }

  return '';
}

/**
 * Chooses a language and makes translate() work.
 *
 * load_translation_text() opens translations/<language>.txt by a relative
 * path, so the install directory has to be the working directory, and
 * translate() hands back its own argument while $LANGUAGE is empty.
 */
function wc_init_language(): void
{
  chdir(WC_ROOT);

  $language = (string) ($GLOBALS['LANGUAGE'] ?? '');
  if ($language === '' || $language === 'none'
    || $language === 'Browser-defined') {
    // Nothing to ask: there is no browser on this side.
    $language = 'English-US';
  }
  $GLOBALS['LANGUAGE'] = $language;
  reset_language($language);
}

/**
 * Confirms a calendar exists before reading from or writing to it.
 */
function wc_require_login(array $argv, string $prefix): ?string
{
  $login = wc_opt($argv, 'login');
  if ($login === '') {
    fwrite(STDERR, "Missing --login=NAME.\n");
    return null;
  }
  if (!user_load_variables($login, $prefix)) {
    fwrite(STDERR, "No such user: $login\n");
    return null;
  }

  return $login;
}

/**
 * Writes a calendar as iCalendar.
 *
 * Deliberately the same code path as export_handler.php, so the file this
 * produces is the file the Export page produces. That includes exporting
 * through export_ical()'s echo rather than asking it to return the document:
 * the returning branch exists for email attachments and skips
 * save_uid_for_event(), so the UIDs in the file would not be recorded and a
 * later --overwrite import would duplicate every event instead of replacing
 * it.
 */
function wc_cmd_export(array $argv): int
{
  $login = wc_require_login($argv, 'wc_export_');
  if ($login === null) {
    return 1;
  }

  $from = wc_opt($argv, 'from');
  $to = wc_opt($argv, 'to');
  foreach (['from' => $from, 'to' => $to] as $option => $value) {
    if ($value !== '' && preg_match('/^\d{8}$/', $value) !== 1) {
      fwrite(STDERR, "--$option takes a date as YYYYMMDD, not \"$value\".\n");
      return 1;
    }
  }
  if ($from !== '' && $to !== '' && $from > $to) {
    fwrite(STDERR, "--from is after --to.\n");
    return 1;
  }

  // export_get_event_entry() reads every one of these through `global`.
  $GLOBALS['login'] = $login;
  $GLOBALS['user'] = '';
  // Empty on purpose: this is not publish.php, so the public-events-only
  // restriction must not apply. It used to apply to any non-empty value,
  // because the condition was an assignment rather than a comparison.
  $GLOBALS['type'] = '';
  $GLOBALS['cat_filter'] = wc_opt($argv, 'category');
  $GLOBALS['include_layers']
    = in_array('--include-layers', $argv, true) ? 'y' : '';
  // The Export page's checkbox, and off by default here for the same reason:
  // a deleted event is one somebody removed, and resurrecting them on the way
  // back in would be a surprise.
  $GLOBALS['include_deleted']
    = in_array('--include-deleted', $argv, true) ? 'y' : '';
  // The Export page prefills a date window. A calendar exported from a shell
  // is wanted whole, so the default here is every date.
  $GLOBALS['use_all_dates'] = ($from === '' && $to === '') ? 'y' : '';
  $GLOBALS['startdate'] = $from === '' ? '00000000' : $from;
  $GLOBALS['enddate'] = $to === '' ? '99991231' : $to;
  $GLOBALS['moddate'] = '00000000';
  // A backup that leaves out events awaiting approval is not a backup. The
  // page uses whatever the exporting user happens to prefer.
  $GLOBALS['DISPLAY_UNAPPROVED'] = 'Y';

  if ($GLOBALS['include_layers'] !== '') {
    load_user_layers();
  }

  ob_start();
  export_ical('all');
  $ics = (string) ob_get_clean();

  // Two ways to come back with nothing, and they used to answer differently:
  // export_ical() returns before writing a byte when the query matches no
  // rows, but a category filter that excludes every event leaves a valid
  // VCALENDAR with nothing in it. Counting components treats both the same,
  // so a backup can never quietly be an empty file.
  $components = preg_match_all('/^BEGIN:(VEVENT|VTODO|VJOURNAL)\r?$/m', $ics);
  if ($components === 0 || $components === false) {
    fwrite(STDERR, "No events matched, so nothing was written.\n");
    return 1;
  }

  // The buffer catches whatever was printed, which on an installation with
  // display_errors pointed at standard output would include any PHP notice
  // raised while building the document. Refusing beats writing a file that
  // says it is a calendar and is not.
  if (!str_starts_with($ics, 'BEGIN:VCALENDAR')) {
    fwrite(STDERR, "The export did not begin with BEGIN:VCALENDAR, so "
      . "something was printed into it. Nothing was written. First line:\n  "
      . strtok($ics, "\n") . "\n");
    return 1;
  }

  $output = wc_opt($argv, 'output');
  if ($output === '') {
    echo $ics;
    return 0;
  }

  // 0600 before the first byte: a calendar is personal data, and the same
  // reasoning as `db dump`.
  touch($output);
  chmod($output, 0600);
  if (file_put_contents($output, $ics) === false) {
    fwrite(STDERR, "Could not write $output\n");
    return 1;
  }

  fwrite(STDERR, 'Wrote ' . $components . ' events, ' . strlen($ics)
    . " bytes, to $output\n");

  return 0;
}

/**
 * Reads an iCalendar or vCalendar file into a calendar.
 *
 * import.php also offers Palm, Outlook CSV and git log, each through its own
 * parser and its own page-level setup. The two calendar formats are the ones
 * worth driving from a shell.
 */
function wc_cmd_import(array $argv): int
{
  $login = wc_require_login($argv, 'wc_import_');
  if ($login === null) {
    return 1;
  }

  $file = wc_opt($argv, 'file');
  if ($file === '') {
    fwrite(STDERR, "Missing --file=FILE.\n");
    return 1;
  }
  if (!is_file($file) || !is_readable($file)) {
    fwrite(STDERR, "Cannot read $file\n");
    return 1;
  }

  $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  $GLOBALS['errormsg'] = '';
  $GLOBALS['tz'] = $GLOBALS['tz'] ?? 0;

  if ($extension === 'ics' || $extension === 'ical') {
    $GLOBALS['ImportType'] = 'ICAL';
    $type = 'ical';
    $data = parse_ical($file);
  } elseif ($extension === 'vcs' || $extension === 'vcal') {
    $GLOBALS['ImportType'] = 'VCAL';
    $type = 'vcal';
    $data = parse_vcal($file);
  } else {
    fwrite(STDERR, "Cannot tell the format of $file from its name. Expected "
      . ".ics or .vcs; import.php handles the other formats.\n");
    return 1;
  }

  if ((string) ($GLOBALS['errormsg'] ?? '') !== '') {
    fwrite(STDERR, 'Could not parse the file: '
      . strip_tags((string) $GLOBALS['errormsg']) . "\n");
    return 1;
  }
  if (empty($data)) {
    fwrite(STDERR, "No events found in $file\n");
    return 1;
  }

  $GLOBALS['calUser'] = $login;
  // The actor recorded in the activity log. There is no session here, so the
  // owner of the calendar is the closest true answer.
  $GLOBALS['login'] = $login;
  $GLOBALS['importcat'] = wc_opt($argv, 'category');
  $GLOBALS['count_suc'] = 0;
  $GLOBALS['count_con'] = 0;
  $GLOBALS['error_num'] = 0;
  $GLOBALS['numDeleted'] = 0;

  $overwrite = in_array('--overwrite', $argv, true);

  // true = silent. Otherwise import_data() writes an HTML conflict report,
  // headings and all, which is what import_handler.php wants and this does
  // not. load_remote_calendar() passes it for the same reason.
  import_data($data, $overwrite, $type, true);

  echo "Imported " . basename($file) . " into $login\n";
  printf("  events imported:    %d\n", (int) $GLOBALS['count_suc']);
  printf("  marked deleted:     %d\n", (int) $GLOBALS['numDeleted']);
  printf("  conflicts skipped:  %d\n", (int) $GLOBALS['count_con']);
  printf("  errors:             %d\n", (int) $GLOBALS['error_num']);

  if ((string) ($GLOBALS['errormsg'] ?? '') !== '') {
    fwrite(STDERR, strip_tags((string) $GLOBALS['errormsg']) . "\n");
  }

  return ((int) $GLOBALS['error_num']) > 0 ? 1 : 0;
}

/**
 * Reads and writes webcal_config, the table behind Admin > Settings.
 *
 * The reason to have this is the case Admin > Settings cannot reach: a
 * setting that stops the administrator logging in or renders the admin page
 * unusable. It deliberately does not touch includes/settings.php, which holds
 * the database credentials and is the installer's business.
 */
function wc_cmd_config(array $argv): int
{
  $action = $argv[0] ?? '';

  return match ($action) {
    'list' => wc_config_list(),
    'get' => wc_config_get($argv[1] ?? ''),
    'set' => wc_config_set($argv[1] ?? '', $argv[2] ?? null, $argv),
    default => wc_config_usage(),
  };
}

function wc_config_usage(): int
{
  fwrite(STDERR, <<<TXT
    Usage: php bin/webcal.php config list
           php bin/webcal.php config get NAME
           php bin/webcal.php config set NAME VALUE [--force]

    TXT);

  return 1;
}

/**
 * @return array<string, string> setting name => stored value
 */
function wc_config_rows(): array
{
  $rows = [];
  $res = dbi_execute('SELECT cal_setting, cal_value FROM webcal_config');
  if (!$res) {
    return $rows;
  }
  while ($row = dbi_fetch_row($res)) {
    $rows[(string) $row[0]] = (string) ($row[1] ?? '');
  }
  dbi_free_result($res);
  ksort($rows);

  return $rows;
}

function wc_config_list(): int
{
  $rows = wc_config_rows();
  if ($rows === []) {
    fwrite(STDERR, "webcal_config is empty. Is this installation set up?\n");
    return 1;
  }

  $hidden = 0;
  $width = max(array_map('strlen', array_keys($rows)));

  foreach ($rows as $name => $value) {
    if (\WebCalendar\Diagnostics\ConfigPolicy::isSecret($name)) {
      $hidden++;
      // Presence still matters: whether a token exists is half of most
      // support questions about one.
      $shown = $value === '' ? '(empty)' : '(set, hidden)';
    } else {
      $shown = $value === '' ? '(empty)' : $value;
    }
    printf("%-{$width}s  %s\n", $name, $shown);
  }

  fwrite(STDERR, "\n" . count($rows) . ' settings, ' . $hidden
    . " hidden as secrets.\nconfig get NAME prints one value in full.\n");

  return 0;
}

function wc_config_get(string $name): int
{
  if ($name === '') {
    fwrite(STDERR, "Usage: php bin/webcal.php config get NAME\n");
    return 1;
  }

  $name = strtoupper($name);
  $rows = wc_config_rows();

  if (array_key_exists($name, $rows)) {
    // The value alone on standard output, so it can be read by a script.
    echo $rows[$name] . "\n";
    return 0;
  }

  // A row holding '' and no row at all are different states, and treating
  // them alike is what issue #734 was about: call sites disagreed on what an
  // absent setting meant.
  require_once WC_ROOT . '/includes/default_config.php';
  $defaults = webcal_config_defaults();

  fwrite(STDERR, "$name has no row in webcal_config.\n");
  if (array_key_exists($name, $defaults)) {
    fwrite(STDERR, 'The built-in default is "' . $defaults[$name]
      . "\".\n");
  } else {
    fwrite(STDERR, "It is not a setting this version knows about either.\n");
  }

  return 1;
}

function wc_config_set(string $name, ?string $value, array $argv): int
{
  if ($name === '' || $value === null) {
    fwrite(STDERR, "Usage: php bin/webcal.php config set NAME VALUE\n"
      . "Clear a setting with an empty value: config set NAME ''\n");
    return 1;
  }

  $name = strtoupper($name);
  // The character set Admin > Settings accepts, which is what the column has
  // held for twenty years.
  if (preg_match('/^[A-Z0-9_]+$/', $name) !== 1) {
    fwrite(STDERR, "A setting name is letters, digits and underscores: "
      . "\"$name\" is not.\n");
    return 1;
  }

  $forced = in_array('--force', $argv, true);

  // Not a preference. wizard/ reads it to decide which upgrade steps an
  // installation still needs, so a hand-edited value makes the wizard skip
  // migrations or run them twice.
  if ($name === 'WEBCAL_PROGRAM_VERSION' && !$forced) {
    fwrite(STDERR, "WEBCAL_PROGRAM_VERSION records the schema the database is "
      . "at, not a preference.\nThe installation wizard reads it to decide "
      . "which upgrades to apply. --force if you are sure.\n");
    return 1;
  }

  $rows = wc_config_rows();
  require_once WC_ROOT . '/includes/default_config.php';
  $known = array_keys(webcal_config_defaults());

  if (!array_key_exists($name, $rows) && !in_array($name, $known, true)
    && !$forced) {
    fwrite(STDERR, "$name is not a setting this version knows about, and has "
      . "no row already.\n");
    $near = wc_config_near_matches($name, array_unique(
      array_merge($known, array_keys($rows))));
    if ($near !== []) {
      fwrite(STDERR, 'Did you mean: ' . implode(', ', $near) . "?\n");
    }
    fwrite(STDERR, "--force to store it anyway.\n");
    return 1;
  }

  $before = $rows[$name] ?? null;

  // DELETE then INSERT, the way admin.php and load_global_settings() do it:
  // cal_setting is the primary key, so a bare INSERT fails when the row is
  // already there. The row is always written back rather than left absent,
  // because '' is a recorded choice and a missing row is not (#734).
  //
  // No cache to invalidate by hand: dbi_execute() clears the query cache
  // itself on anything that is not a SELECT.
  if (!dbi_execute('DELETE FROM webcal_config WHERE cal_setting = ?',
    [$name], false, false)) {
    fwrite(STDERR, 'Could not remove the old row: ' . dbi_error() . "\n");
    return 1;
  }
  if (!dbi_execute('INSERT INTO webcal_config ( cal_setting, cal_value ) '
    . 'VALUES ( ?, ? )', [$name, $value], false, false)) {
    fwrite(STDERR, 'Could not write the new row: ' . dbi_error() . "\n");
    return 1;
  }

  $secret = \WebCalendar\Diagnostics\ConfigPolicy::isSecret($name);
  $render = static function (?string $v) use ($secret): string {
    if ($v === null) {
      return '(no row)';
    }
    if ($v === '') {
      return '(empty)';
    }

    return $secret ? '(hidden)' : '"' . $v . '"';
  };

  echo "$name\n";
  echo '  was: ' . $render($before) . "\n";
  echo '  now: ' . $render($value) . "\n";

  return 0;
}

/**
 * Setting names close enough to $name to be worth suggesting.
 *
 * A typed SEND_EMAILS for SEND_EMAIL would otherwise store a row nothing
 * reads, and the administrator would be left wondering why the change had no
 * effect.
 *
 * @param list<string> $candidates
 * @return list<string>
 */
function wc_config_near_matches(string $name, array $candidates): array
{
  $near = [];
  foreach ($candidates as $candidate) {
    if (levenshtein($name, $candidate) <= 3) {
      $near[] = $candidate;
    }
  }
  sort($near);

  return array_slice($near, 0, 5);
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
  case 'db':
    $wcEnv = wc_bootstrap();

    if (($argv[0] ?? '') === 'check') {
      // upgrade_requires_db_changes() is in functions.php, which assigns at
      // file scope like the rest -- hence the require here rather than inside
      // a function. Only `check` needs it; `dump` shells out.
      if (!defined('_ISVALID')) {
        define('_ISVALID', true);
      }
      require_once WC_ROOT . '/includes/translate.php';
      require_once WC_ROOT . '/includes/functions.php';
    }
    // Listing the tables needs a working connection. Without one the query
    // below would reach into an extension that is not loaded and raise a
    // fatal, which is what dbi_connect() was just taught not to do.
    if (($wcEnv['db_server_version'] ?? '') !== ''
      && !str_starts_with((string) $wcEnv['db_server_version'], '(')) {
      exit(wc_cmd_db($argv));
    }
    fwrite(STDERR, 'Cannot reach the database: '
      . ($wcEnv['db_server_version'] ?? '(unknown)') . "\n"
      . "Run `php bin/webcal.php diagnose` to see the configuration.\n");
    exit(1);
  case 'config':
    $wcEnv = wc_bootstrap();
    if (($wcEnv['db_server_version'] ?? '') === ''
      || str_starts_with((string) $wcEnv['db_server_version'], '(')) {
      fwrite(STDERR, 'Cannot reach the database: '
        . ($wcEnv['db_server_version'] ?? '(unknown)') . "\n"
        . "Run `php bin/webcal.php diagnose` to see the configuration.\n");
      exit(1);
    }
    require_once WC_ROOT . '/includes/classes/Diagnostics/ConfigPolicy.php';
    exit(wc_cmd_config($argv));
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
    require_once WC_ROOT . '/includes/activity-log-constants.php';
    require_once WC_ROOT . '/includes/translate.php';
    require_once WC_ROOT . '/includes/functions.php';
    require_once WC_ROOT . '/includes/' . $wcUserInc;

    exit(wc_cmd_user($argv));
  case 'reminders':
  case 'remotes':
    [$wcVerb, $wcScript] = $command === 'reminders'
      ? ['send', 'send_reminders.php']
      : ['refresh', 'reload_remotes.php'];

    if (($argv[0] ?? '') !== $wcVerb) {
      fwrite(STDERR, "Usage: php bin/webcal.php $command $wcVerb\n");
      exit(1);
    }

    $wcScript = WC_ROOT . '/tools/' . $wcScript;
    if (!is_file($wcScript)) {
      fwrite(STDERR, "Missing " . $wcScript . ".\n");
      exit(1);
    }

    // Run rather than reimplemented, and required here rather than from a
    // function. These are top-level scripts: they resolve their includes
    // against '../includes/' and assign configuration at file scope, so
    // pulled in from inside a function those assignments would be
    // function-local and the script would see nothing. Hence the chdir too.
    //
    // The documented cron entries keep working unchanged, which is the point
    // -- send_reminders.php emails users and nothing tests what it selects.
    chdir(dirname($wcScript));
    require $wcScript;
    exit(0);
  case 'email':
  case 'export':
  case 'import':
    wc_bootstrap();

    // Same reason as the user command below: these files assign at file
    // scope and are read through `global`, so they cannot be required from
    // inside a function. xcal.php holds the export and import functions, and
    // WebCalMailer pulls it in anyway for ICS attachments.
    if (!defined('_ISVALID')) {
      define('_ISVALID', true);
    }
    require_once WC_ROOT . '/includes/activity-log-constants.php';
    require_once WC_ROOT . '/includes/translate.php';
    require_once WC_ROOT . '/includes/functions.php';
    require_once WC_ROOT . '/includes/'
      . basename((string) ($GLOBALS['user_inc'] ?? 'user.php'));
    require_once WC_ROOT . '/includes/xcal.php';
    load_global_settings();

    // WebCalMailer's constructor asks translate() for 'charset' and would
    // otherwise be handed the literal string 'charset' as its encoding;
    // export and import translate category and status names.
    wc_init_language();

    if ($command === 'email') {
      require_once WC_ROOT . '/includes/classes/WebCalMailer.php';
      exit(wc_cmd_email($argv));
    }

    exit($command === 'export'
      ? wc_cmd_export($argv)
      : wc_cmd_import($argv));
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
