# Changelog

All notable changes to WebCalendar are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- **`.gitignore` now covers the files that must never be committed**, and `tests/NoSensitiveFilesTrackedTest.php` enforces that against what git actually tracks — an ignore rule does nothing once a file has been added. Between them they cover live configuration copies (`includes/settings.php.*`, which hold database credentials, with the shipped `settings.php.orig` template deliberately exempt), calendar databases (`*.db`, `*.sqlite`, `*.sqlite3` — real events and password hashes), terraform state and its ~330MB of provider binaries, editor and tool caches, and backup files. The prompt was a near miss: `git add -A includes tools` picked up `includes/settings.php.craig` on 2026-09-25 and was caught by reading the staged list, not by any rule

- **`php bin/webcal.php db check`** reports whether the database schema is behind the program, and applies nothing. It makes the same comparison `do_config()` makes when it decides whether to send a browser to the wizard — the stored `WEBCAL_PROGRAM_VERSION` against the version in `includes/config.php`, then `upgrade_requires_db_changes()` — and answers with an exit status a deployment script can read: **0** up to date, **1** an upgrade is pending, **2** the question could not be settled, which covers both a missing version row and a database *ahead* of the code (an older WebCalendar deployed over a schema another copy has already upgraded). The stored version is read without the query cache, because a stale cache file used to pin the old value and send the administrator back to the wizard on every request (#639). `wizard/` does not need to be present: the answer is then the conservative one, and says so

- **`config list`, `config get NAME` and `config set NAME VALUE` on `bin/webcal.php`**, for `webcal_config` — the table behind **Admin > Settings**, and the recovery path for when a setting is itself what stops an administrator reaching that page. `list` shows every setting with secret values hidden by name, using the same `ConfigPolicy` rule the diagnostic report uses rather than a second copy of it; `get` prints one value in full, secret included, since it was asked for by name; `set` writes the way `admin.php` does, deleting the row and inserting it back so that clearing a setting stores `''` rather than leaving no row, which call sites disagreed about (#734). An unrecognised name is refused with near matches suggested — a typed `SEND_EMAILS` for `SEND_EMAIL` would otherwise store a row nothing reads — and `WEBCAL_PROGRAM_VERSION` is refused outright, because the installation wizard reads it to decide which upgrades an installation still needs. `--force` lifts both refusals. `includes/settings.php`, which holds the database credentials, is not touched

- **`export` and `import` on `bin/webcal.php`.** `export --login=NAME` writes a calendar as iCalendar to standard output, or to `--output=FILE` created readable only by its owner; `--from` and `--to` narrow the date range, which otherwise covers every date, and `--include-layers` and `--category` match the Export page. `import --login=NAME --file=FILE` reads an `.ics` or `.vcs` file and reports how many events it imported, marked deleted, skipped as conflicts and failed on. Both drive the same functions as the Export and Import pages, so the file the command produces is the file the page produces — including exporting through `export_ical()`'s echoing branch, since the branch that returns the document instead is for email attachments and skips recording the UIDs that a later import matches on. Verified as a round trip: 23 seeded events exported, imported into a second calendar, and exported again with identical `SUMMARY` and `RRULE` lines

- **`includes/activity-log-constants.php`** holds the activity log's `cal_type` letters. They were defined inside `WebCalendar::_initFunctions()`, which the web pages and the `tools/` scripts run and `bin/webcal.php` does not, so `import` died with `Undefined constant "LOG_CREATE"` after it had already parsed the file and inserted the import row — and `user reset-password` had worked around the same gap by passing the literal `'u'`, which now names the constant. `tests/ActivityLogConstantsTest.php` fails the build when the code uses a constant the file does not define, or when `WebCalendar.php` starts defining them again; the letters are the stored column values, so two copies is how they drift apart

- **Three operator commands on `bin/webcal.php`.** `reminders send` and `remotes refresh` run `tools/send_reminders.php` and `tools/reload_remotes.php` rather than reimplementing them: both are documented cron entries of twenty years' standing, and nothing tests what the reminder script selects, so the command line gives them one discoverable entry point and existing crontabs keep working unchanged. `email test --to=ADDRESS` is new — it prints the mailer, server and sender it is about to use, sends one message, and reports the mail server's own refusal when there is one

- **`php bin/webcal.php db dump [--output=FILE]`** writes the `webcal_*` tables as SQL, handing the work to `mysqldump`, `pg_dump` or `sqlite3` according to the configured backend. The value is that credentials come from the existing configuration rather than being looked up by hand; they never reach the argument list, where `ps` would show them, so MySQL gets a temporary `0600` defaults file and PostgreSQL gets `PGPASSWORD` in the child environment. Only WebCalendar's own tables are included, since the database may be shared, and `--output` creates the file readable only by its owner. `docker/Dockerfile-dev` now installs the `sqlite3` command-line tool alongside `mariadb-client`, so the dump works in the development containers without installing anything first

- **`php bin/webcal.php user reset-password --login=NAME`** sets a new password from the command line, for the case there was previously no answer to: an administrator locked out of their own calendar. Passwords are bcrypt, so no hand-written `UPDATE` recovers the account. A password is generated and shown once, or read from standard input with `--stdin`; it is never taken as an argument, where it would appear in `ps` output and shell history. Hashing is delegated to `user_update_user_password()` so it cannot drift from what login checks, the account is confirmed to exist first, and the reset is written to the activity log. Installations authenticating through LDAP, IMAP, NIS or Joomla are refused with an explanation rather than having a column changed that would not affect logins

- **`tools/check-ai-signals.php`**, which flags comments that read as machine-written using the mechanical subset of `webcalendar-core`'s `AI-SIGNALS.md`: second-person address, instructional phrasing, the buzzword list and bookend section markers. A pull request job checks only the lines it adds. Run over the whole tree it reports around sixty findings, and nearly all are legitimate — WebCalendar has twenty-five years of comments that speak to an administrator directly, which is good writing for its audience rather than a machine tell — so existing prose is left alone. A comment containing `ai-signals:allow` is skipped, for code that legitimately quotes the phrasing it detects

- **The test suite refuses to run concurrently with itself.** `McpIntegrationTest` installs into a fixed SQLite file in the temp directory and serves it on `localhost:8099`, and the other MCP tests use 8100-8104, so two runs on one machine delete each other's database and fight over the ports, producing a scatter of errors that point nowhere near the cause. `tests/bootstrap.php` now takes an exclusive lock for the run and fails fast with an explanation; `WEBCAL_TEST_ALLOW_PARALLEL=1` overrides it. The lock is global rather than per-checkout because the resources it protects are

- **`bin/webcal.php seed` and `reset`** load named development scenarios into a SQLite calendar: a populated month, overlapping events for conflict work, a repeating series with exception dates, and a group of users with layers. `reset` empties the calendar without uninstalling. Both refuse unless the database is SQLite **and** `--force` or `WEBCAL_ALLOW_SEED=1` is given; `mode: dev` is deliberately not accepted as consent, because live installations set it
- **`make check`** runs the compile check, PHPStan and PHPUnit in the order CI runs them, so a green result locally predicts a green pipeline
- **`docs/CODEMAPS/functions.md`**, an index of the 161 functions in `includes/functions.php` and 19 in `includes/dbi4php.php` with line numbers and one-line purposes. Generated by `tools/build-codemap.php`; `tests/CodemapTest.php` fails the build when it falls behind

- **Diagnostic report for bug reports.** `php bin/webcal.php diagnose` prints the WebCalendar and PHP versions, loaded extensions, database type and server version, directory writability and the relevant configuration, so a report carries its environment instead of the reporter hand-typing it. Admins who can still log in get the same report with a copy button under **Admin > Security Audit**. It runs on a broken install: a missing `settings.php` produces a report rather than a redirect to the wizard
- **`bin/webcal.php`**, a command-line entry point. Refuses to run under any SAPI but CLI

- The PHP 8.1 and SQLite Docker dev environments are now tracked: `docker/Dockerfile-dev`, `docker/docker-compose-dev.yml`, `docker/docker-compose-prod.yml`, `docker/docker-compose-sqlite-dev.yml`, `docker/mysql-init.sql` and the selenium login tests under `docker/tests/`. `CLAUDE.md` and the developer docs referenced these but they had never been committed
- `tests/TestFixtureRequiresTest.php` fails when a test uses a helper from `tests/` without requiring it. Not every invocation loads `tests/bootstrap.php`: `test-mcp.yml` runs phpunit with no `-c`, so relying on the bootstrap passes locally and fails in CI
- `tests/DestructiveTestGuardTest.php` keeps that refusal in place: the guard must exist in each runner, must come before any docker command, and the workflow must keep supplying the override
- `tests/PhpFloorConsistencyTest.php` locks the supported PHP version together across `composer.json`, `WizardValidator`, the CI matrices and the docs. They had drifted into four different answers
- `tests/DockerReferencesTest.php` fails the build when a compose file, workflow or shell script names a Dockerfile or compose file that does not exist. The three Selenium wizard jobs reach `docker/Dockerfile-php8-dev` three hops down — workflow, to `tests/run-*-install-tests.sh`, to a `docker-compose-test-*.yml` `dockerfile:` key — so renaming it broke CI with nothing failing locally
- `tests/CliOnlyGuardTest.php` fails the build when a script under `tools/` or `bin/` is added without a command-line guard, or when an existing guard is moved below code that would already have run
- **Reminder web trigger.** Admin > Settings > Email can generate a token that lets `tools/send_reminders.php` be run by fetching its URL, for hosts that offer no way to run a command. Off until a token is generated. The token is generated server-side, shown once, and stored only as a SHA-256 hash, so a database read or backup does not yield a working credential. It may be passed as `?token=` or as an `X-Reminder-Token` header, and every run triggered this way is written to the activity log with the requesting IP

### Changed

- **`test-mcp.yml` runs its unit job once instead of three times.** `tests/McpTest.php` is already executed on 8.2, 8.3 and 8.4 by `ci.yml`, which runs the whole suite — `tests/phpunit.xml` takes every `*Test.php` in `tests/`. The matrix re-ran one of those files on the same three versions. What the job does cover, and the suite cannot, is the *invocation*: phpunit is called with no `-c`, so `phpunit.xml` and its bootstrap are never read, and a test that leans on the bootstrap to load a helper passes locally and fails here — which is how 57 "Class not found" errors once arrived. That is about the absence of a bootstrap rather than the language version, so it needs one job, named for what it checks. `TestFixtureRequiresTest` now also fails if `-c` is ever added to that step, since it is the only place in CI that runs phpunit without a configuration file and the rule it enforces would otherwise have nothing behind it

- **CI installs dependencies from dist archives, which takes about five minutes off every pull request.** `composer install` without `--prefer-dist` installs from source, cloning a git repository for each package. Measured on the same commit and the same lock file: the PHPStan job, which already passed the flag, spent **6 seconds** installing; the CI job, which did not, spent **301** — and ran its actual tests in 33. Three of the pipeline's slowest jobs were spending nine tenths of their time fetching packages. `ci.yml` and `test-mcp.yml` now pass `--no-interaction --prefer-dist --no-progress`, and `tests/WorkflowComposerInstallTest.php` fails the build if the flag goes missing again. The Selenium wizard jobs, a single ~280-second docker step each, become the limit on how fast a pull request can go green

- The four workflows that run on every pull request declare a concurrency group, so pushing again cancels the superseded run instead of leaving it to occupy runners. Pushes to `master` are never cancelled

- Upgrading corrects events created through MCP before this release, which have `cal_type = 'E'` despite having a `webcal_entry_repeats` row. The statement is scoped by that join, so one-off events, already-correct rows and tasks are untouched, and re-running an upgrade is a no-op
- The event write path moved out of `mcp.php` into `includes/classes/Event/`. `add_event` and `add_recurring_event` each carried their own copy of the cal_id allocation loop, the `webcal_entry` insert and the participation insert, forked from `edit_entry_handler.php` and then forked again from each other; `delete_event` carried its own cascade. `EventService::createEvent()`, `storeRecurrence()` and `deleteEvent()` are now the single copy, named to match `webcalendar-core`'s `EventService` so a later port is a rename. Behaviour is unchanged, including that repeating events keep `webcal_entry.cal_type` at the column default

- `.github/PULL_REQUEST_TEMPLATE.md` asks for `make check` in place of the separate PHPUnit and compile boxes it subsumed, and adds a `CHANGELOG.md` item
- `docs/troubleshooting.md` leads its Diagnostic Steps section with `php bin/webcal.php diagnose`, which collects in one command what the individual checks below it gather by hand

- `.github/ISSUE_TEMPLATE/bug_report.md` asks for the output of `php bin/webcal.php diagnose` instead of six hand-typed environment fields, with the admin page and the manual fields as fallbacks

- **PHP 8.2 is now the minimum, and PHP 8.1 is no longer supported.** 8.1 reached end of life on 31 December 2025. The repository had stated four different floors: `composer.json` required `^8.2`, `README.md` said 8.2+, `CONTRIBUTING.md` said 8.0+, `docs/installation.md` said "8.0 minimum", and the signed-manifest decisions log recorded 8.1 with 8.2+ language features deliberately avoided — while `php-syntax-check.yml` and `test-install.yml` still tested 8.1 and the installation wizard admitted anything from 8.0 up. The wizard now reports PHP below 8.2 as an error, both workflows drop 8.1, and the docs agree. New code may use `readonly class`, typed class constants and `#[\Override]`. PHP 8.2 itself loses security support on 31 December 2026

- **One development image for every backend.** `Dockerfile-dev` now builds `mysqli`, `pgsql`, `pdo_pgsql`, `sqlite3`, `pdo_sqlite` and `gd`, so the MariaDB, PostgreSQL and SQLite environments and the wizard CI that exercises all three share a single image instead of two that drifted apart
- **Docker dev and production files renamed to drop the PHP version.** `Dockerfile-php8.1-dev` is now `Dockerfile-dev`, `docker-compose-php8.1-dev.yml` is `docker-compose-dev.yml`, `docker-compose-php8.1.yml` is `docker-compose-prod.yml` and `Dockerfile-php8` is `Dockerfile-prod` (`docker/build_and_push.sh` and the `docker.yml` and `docker-dev.yml` workflows, which build and publish the `k5nus/webcalendar` images, were updated to match). Encoding the runtime in the filename meant every version bump either renamed the files or left the name lying; the names now describe the role instead. Documentation referencing the old `docker-compose-php8*.yml` files was repointed at the working equivalents
- **Docker images bumped to PHP 8.4 and all floating tags pinned.** The dev containers ran PHP 8.1, which reached end of life on 31 December 2025 and is below the `^8.2` floor `composer.json` declares, so the documented dev environment did not match the product's own minimum. `Dockerfile-php8` and `Dockerfile-php8-dev` used the floating `php:8-apache` tag, giving two developers on the same commit different runtimes. Now `php:8.4-apache` throughout, with `mariadb` pinned to `11.4` (LTS), `postgres` to `16` and `alpine` to `3.20`. The `docker-compose-test-*.yml` files used by the wizard CI are unchanged
- **Shebang lines removed from `tools/*.php`.** PHP strips `#!` only under the CLI SAPI; served by Apache the line was emitted as page output, which sent the response headers before the new guard could set a status. The scripts answered `200` with the shebang text in the body instead of `403`. Every documented and CI invocation already uses `php tools/<script>.php`, and most of the removed lines pointed at `/usr/local/bin/php`, which does not exist on Debian, Ubuntu or RHEL. A crontab invoking a script directly rather than through `php` needs updating
- **Scripts under `tools/` now refuse to run over HTTP.** They live inside the web root and two of them ship in releases: `send_reminders.php` and `reload_remotes.php`. A third, `convert_passwords.php`, rewrote stored password hashes and was reachable by URL; it is deleted in this release rather than guarded (see Removed). `.htaccess` was never protection here — Debian and Ubuntu ship `AllowOverride None` for `/var/www`, which makes the whole file a no-op, so each script now checks `PHP_SAPI` itself and answers 403
- **Breaking, for sites triggering reminders by URL:** fetching `tools/send_reminders.php` with no token now returns 403. `docs/admin-guide.md` previously offered it as an alternative for hosts without PHP CLI. Affected sites should move to a cron entry running the PHP CLI binary — cPanel, Plesk and DirectAdmin all provide one — or generate a token and add it to the URL
- Config defaults and `db_load_config()` moved from `wizard/shared/default_config.php` to `includes/default_config.php`. The wizard still reads it as the single source of truth, but it is no longer inside a directory administrators are told to delete (#707)

### Fixed

- **The Export page's "Include deleted entries" checkbox does something.** `del_entry.php` deletes an event by setting its `cal_status` to `'D'` rather than removing the row, so the option is a question about which statuses the export query accepts — but `export_get_event_entry()` never looked at it. `export_handler.php` had collected the checkbox into `$include_deleted` and nothing read the variable, so ticking the box changed nothing at all. The statuses are now built as a list and bound as parameters rather than spliced into the string. Measured against a throwaway installation with 21 events, three of them deleted: 18 exported without the option and 21 with it, the difference being exactly those three. `php bin/webcal.php export --include-deleted` matches the page

- **An export could be restricted to public events by an unrelated request parameter.** `export_get_event_entry()` decided whether to apply the public-events-only restriction with `if ( ! empty ( $type ) && $type = 'publish' )`. The inner `=` is an assignment, not a comparison: it always evaluates true, so the condition reduced to "any non-empty `$type`", and it overwrote the caller's `$type` with `'publish'` on the way through. `publish.php`, the one page that serves a calendar to an unauthenticated visitor, sets `$type = 'publish'` and so got the restriction it wanted by luck. `approve_entry.php` takes `$type` straight from the request and attaches an ICS to the approval mail, so approving an event with any `type` parameter quietly filtered that attachment down to public events. Now a comparison. `tests/ExportAccessFilterTest.php` lifts the function out of `includes/xcal.php`, captures the statement it builds, and pins both halves: `publish` restricts and nothing else does, and the caller's `$type` survives the call

- **Seven guard tests were passing over the defects they exist to catch.** Each structural guard was given the defect it guards against and then run to see whether it noticed — sixty-eight mutations across twenty guards, of which seven were missed. Every miss was the same mistake: the assertion matched text that is not the mechanism.

  - `DestructiveTestGuardTest` missed four, which matters because it is the guard added after `tests/run-*-install-tests.sh` deleted `includes/settings.php` and took a live calendar down on 2026-09-24. Each runner explains its refusal in a comment block naming `includes/settings.php` and `WEBCAL_TEST_ALLOW_DESTRUCTIVE`, so replacing the condition with `if false` left all three assertions true. The ordering check compared against the first mention of the variable, which is in that comment, above everything — so it could never fail. `substr_count()` counted a longer name that merely begins with the variable's, so renaming it left the total at three while no job set it. And the `exit 1` check matched a second, unrelated exit further down the file. It now reads the runners with comment lines removed, anchors on the condition itself, requires the exit inside the refusal's own block, and counts assignments rather than occurrences
  - `UserResetPasswordTest` looked for the literal `'--password='`. An option read through the generic `wc_opt()` helper never spells that anywhere, so a password could be accepted as an argument — visible in `ps` and shell history — with the check still green. The rule is now the mechanism: the only thing the password function may read from the argument list is the `--stdin` flag
  - `DbDumpTest` checked the whole of `bin/webcal.php` for `chmod($output, 0600)`. `export` sets the same mode on its own output, so the dump's chmod could be deleted and the assertion stayed true. Both are now scoped to the function that owns them, and each requires the mode to be set before the file is written
  - `DbiConnectExtensionGuardTest` asserted the driver map and the error message, which are data. Replacing the condition that reads them with `if( false )` left every assertion true while the probe did nothing at all. It now requires the live `function_exists()` and `class_exists()` conditions, positioned before the driver branches, each returning

- **`sigstore/cosign-installer` stays pinned to a commit digest.** The release workflow pins it, but nothing kept it pinned, and the existing check covered only the cosign binary version. A tag or branch there would let the action's author change what runs with the release job's token after the fact

- **`tests/WizardRemovableTest.php` was not enforcing the rule it documents.** Nothing outside `wizard/` may require a file from it, because the security audit tells administrators they may delete the directory once installed (#707) — but the pattern wanted a quoted path immediately after `require`, so `require_once WC_ROOT . '/wizard/...'`, the form every file in this tree actually uses, matched nothing. `bin/` was also not among the directories it read at all, and neither were the class subdirectories added since it was written, so `bin/webcal.php` and its eleven commands were unchecked. Confirmed by adding such a require to `bin/webcal.php` and to `includes/classes/Diagnostics/Collector.php`: neither was caught before, both are now. Comments are stripped before matching, since `includes/default_config.php` explains in prose that `wizard/WizardDatabase.php` requires *it*, which the widened pattern first read as a dependency in the opposite direction

- **Everything written from the command line was invisible on an installation with `db_cachedir` set.** `dbi_execute()` clears the query cache on any statement that is not a `SELECT`, but `do_config()` calls `dbi_init_cache()` only when it is *not* being called from an installer — and every `bin/webcal.php` command passes that flag, so that a missing `settings.php` produces a diagnostic report instead of a redirect to the wizard. The process therefore never learned where the cache directory was, and the invalidation had nothing to clear. Measured against a throwaway installation: a value written with `config set` was in the database while a page still read the old one. It applied to `user reset-password`, whose whole purpose is recovering an account nobody can get into, and to `import`. The shared bootstrap now initialises the cache for every command; #639 is the same failure with the schema version

- **`determineServerUrl()` raised two PHP warnings on every call made from the command line.** It read `$_SERVER['HTTP_HOST']` and `$_SERVER['SERVER_PORT']` with no request present — once per event during an export, into output that has to stay a valid iCalendar document, and on every reminder run and headless installation. With no request it now returns `http://localhost/` as a placeholder, which **Admin > Settings > Server URL** overrides; `SERVER_PORT` was read and never used

- **`export` cannot write a file with no events in it.** Coming back with nothing had two different answers: no matching rows gave an empty document, while a category filter that excluded every event gave a valid `VCALENDAR` containing nothing and exit 0. Both now report that nothing matched and exit non-zero, and `--output` is written only once the document is known to hold components and to begin with `BEGIN:VCALENDAR` — so a notice printed by an installation with `display_errors` pointed at standard output cannot end up inside a backup

- A docblock in `includes/functions.php` listed the constant as `LOG_NEWUSEREMAIL`. It is `LOG_NEWUSER_EMAIL`

- **One unreachable remote calendar no longer stops the rest from refreshing.** `parse_ical()` reports failure by appending to the global `$errormsg`, and nothing cleared it between calendars, while `load_remote_calendar()` both gates its import on `empty($errormsg)` and derives its return value from the same global — so after the first failure every later subscription reported an error and imported nothing. `tools/reload_remotes.php` made that invisible: its loop also required `empty($errormsg)`, so the calendars after a failure were counted and then skipped without a word, and the per-calendar outcome it did compute was assigned to a variable it never printed. The function now clears the global as its first act, the tool reports every calendar and exits non-zero when any of them failed. Against three unreachable subscriptions it attempted one before and attempts three now

- **`get_remote_calendar_last_md5()` and `update_import_check_date()` returned an undefined variable** when a calendar had no earlier import, which PHP 8 reports as `Undefined variable $ret`. It printed into the middle of the reload output on the first refresh of every subscription

- **Four shipped documents told administrators to run a script that has never existed.** `docs/admin-guide.md`, `docs/faq.md`, `docs/security.md` and `docs/troubleshooting.md` all pointed at `php tools/send_test_email.php` for testing mail configuration. It was never committed — it lived on one developer's machine, gitignored, with personal addresses hardcoded in it — so the instruction failed with "No such file or directory" in every release ever made. All four now point at `php bin/webcal.php email test`, and `tests/DocsToolReferencesTest.php` fails the build when documentation names a script that is missing from the repository, or present locally but not in git, which is how this went unnoticed

- **A failed send now says why.** `WebCalMailer` composes PHPMailer rather than extending it, so PHPMailer's `ErrorInfo` was unreachable from outside and the class's own `SetError()` was never called by it: `WC_Send()` returned false and `$mailerError` — which `approve_entry.php`, `del_entry.php`, `edit_entry_handler.php` and `reject_entry.php` all read to decide what to report — stayed empty. `WebCalMailer::LastError()` exposes the reason, so a refused SMTP connection reports `Connection refused` instead of nothing at all

- **The two cron scripts report to a terminal properly.** `tools/reload_remotes.php` wrote `<br>` before each of its two normal-path messages and ended without a newline, left over from when the script was reachable in a browser. Both it and `tools/send_reminders.php` also exited 0 after failing to reach the database, so a cron entry could not distinguish a failed run from a quiet one; both now exit 1

- **A missing database extension no longer kills every command.** `dbi_connect()` called straight into the PHP extension for the configured backend, so a `db_type` whose extension was absent raised `Call to undefined function pg_pconnect()` rather than failing the connection. That took down `php bin/webcal.php diagnose`, whose whole purpose is to run when the installation is broken. All six backends are probed first, and the diagnostic report now prints with `(could not connect)` and the loaded-extension list showing why

- **The test suite can run concurrently with itself.** Six MCP test classes hard-coded a port (8099 to 8104), a SQLite file and a server log path, and `McpTestHelper`, `CrossDatabaseTestHelper` and two scratch scripts written into `tests/` used fixed paths too. All are machine-wide, so two runs deleted each other's databases and fought over the ports, surfacing as errors in unrelated tests. Ports now come from the operating system and every path is unique per run; `tests/McpServerFixture.php` holds both helpers. Two full suites in parallel now pass, where they previously produced around sixty errors each

- **MCP recurring events are stored as repeating.** `add_recurring_event` omitted `cal_type` from its insert and inherited the `webcal_entry` column default of `'E'`, so every recurring event the MCP server created was labelled a one-off. `edit_entry_handler.php` is the authority on that column and sets `'M'` when a recurrence rule is present. Nothing broke visibly — the view and export queries accept `'E'` and `'M'` alike, which is why it went unnoticed — but the column said the wrong thing, and anything reading it for its documented meaning, such as a migration to a newer WebCalendar, would have been misled. Existing rows are not rewritten; see below

- **`CrossDatabaseCompatibilityTest` no longer reports success without testing anything.** It attempted MySQL and PostgreSQL whenever the extension was loaded, against hardcoded `testuser`/`testpass` credentials, then swallowed the connection failure and carried on against SQLite alone — so on every machine without that exact account, including CI, the suite printed `Skipping mysql: Access denied` between the progress dots and still reported `OK`. Those backends were in practice never exercised by this file. They are now attempted only when `WEBCAL_TEST_MYSQL_HOST`/`_DATABASE` (or the `POSTGRESQL` equivalents) are set; a backend configured but unreachable fails the test instead of being ignored, and a run covering SQLite alone is reported as skipped rather than passed

- **`tests/run-{mysql,postgresql,sqlite}-install-tests.sh` no longer destroy a live installation.** Each drives the web installation wizard against a container that bind mounts the working copy, and `tests/web-install-*.py` deletes `includes/settings.php` as part of its setup. On a CI checkout that costs nothing; on a working copy that is also a live install it removes the configuration and takes the calendar down. All three now refuse to start when `includes/settings.php` is present, unless `WEBCAL_TEST_ALLOW_DESTRUCTIVE=1` is set, which the wizard CI jobs supply

- `docker/build_and_push.sh` pushes to `craigk5n/webcalendar`, the repository the release workflows actually publish to. It was hardcoded to `k5nus/webcalendar`, untouched since April 2022. The branch tag also lost its `-t`, so `$tagBranchParam` expanded to a second positional argument and the build died with `"docker buildx build" requires exactly 1 argument` before pushing anything
- `docker.yml` and `docker-dev.yml` set step outputs through `$GITHUB_OUTPUT` instead of the `::set-output` workflow command, which GitHub deprecated in October 2022 and has said it will disable

- `docker/docker-compose-sqlite-dev.yml` works. It had never parsed: `db_initializer` declared `depends_on: webcalendar` while the service was named `webcalendar-sqlite`, and the two depended on each other in a cycle. Beyond that the two services bind mounted different host directories to the same container path, the relative paths resolved against `docker/` rather than the working copy so the web root was the `docker/` directory itself, the initializer piped a nonexistent `init.sql` into sqlite3, and `image:` alongside `build:` meant a local build would be tagged `php:8.4-apache`, shadowing the official image. The initializer is gone — the installation wizard creates the schema, as it does for the MariaDB environment — and the remaining service mounts the working copy with the database in `sqlite-data/`

- Admin Settings no longer returns a 500 error after following the security audit's advice to remove or `chmod 000` the `wizard/` directory. `admin.php` hard-required a file from `wizard/`, so either action made the page fatal (#707)
- The security audit's "Wizard directory exists" check now passes when `wizard/` has been made unreadable. It only tested `is_dir()`, which still succeeds on a `chmod 000` directory, so the "restrict permissions" option the audit itself recommends could never clear the item (#707)
- Purge Events now works at all. Its Delete button carried no `value` attribute, so browsers submitted an empty `delete=`, the `! empty()` guard never fired, and clicking Delete silently redisplayed the form. Reported by Tom (shycat.net)
- Purge Events reads the date field the form actually renders. It looked for `end_year`/`end_month`/`end_day`, but `date_selection()` emits a single `end__YMD` input, so the cutoff was always `00000000` and a date-based purge matched nothing. Reported by Tom (shycat.net)
- **Purge Events no longer ignores "Purge deleted only" when All users is selected.** That branch overwrote the SQL tail instead of appending to it, discarding the status restriction — an admin asking to purge deleted events would have irreversibly purged every event before the cutoff. This was unreachable only because the Delete button was inert; fixing the button without this would have armed it
- Purge Events reports accurate row counts. Its date query joined `webcal_entry_user` with no join condition, producing a cartesian product that multiplied every count by the number of participant rows. Reported by Tom (shycat.net)
- Purge Events no longer errors when "Purge deleted only" is used with a named user. That query filtered on `weu.cal_status` without `webcal_entry_user` in its `FROM` clause. Reported by Tom (shycat.net)
- A completed purge is no longer labelled `[Preview]`. The results line hardcoded the prefix, so an irreversible delete reported itself as a dry run
- Login page layout is centered and no longer double-padded. `#login-container` was a Bootstrap `.container` nested inside another `.container`, and every wrapper in the form used `.row` with no column child, so the negative row margins pulled the form off-centre and toward the screen edge on narrow viewports. Reported by Tom (shycat.net)
- Login page labels are associated with their inputs. "Username:" pointed at `login`, which resolved to `<body id="login">` rather than the text field, and "Remember me" pointed at `exampleCheck1`, a leftover from the Bootstrap docs that does not exist on the page. Neither label did anything when clicked

### Removed

- **`tools/convert_passwords.php`.** It converted stored passwords to md5 as part of upgrading to version 0.9.43, and its own header said to run it once and then delete it — but it was listed in `release-files`, so it arrived again with every release since. WebCalendar has stored bcrypt hashes for years, and the script re-hashes whatever it finds rather than a password: run against a current installation it reads each bcrypt hash out of `webcal_user`, md5s that string and writes the 32-character result back. `user_valid_login()` reads a 32-character hex value as an old md5 password and compares `md5(entered password)` against it, so every account would be locked out with no way back, the original hashes having been overwritten. The accident check the script carries does not prevent this: it stops if the first row's password is longer than 30 characters, which a bcrypt hash satisfies, but the row is whichever one the database returns first, and a single short or empty `cal_passwd` — an account never migrated, or a placeholder — lets the run continue. Nothing referenced it: not the wizard, the upgrade SQL or the documentation. Installations still on 0.9.43 upgrade through the wizard, which does not use it

- `docker/Dockerfile-php8-dev`, `docker/docker-compose-php8.yml` and `docker/docker-compose-php8-dev.yml`. Both compose files bind mounted `../install/sql/tables-mysql.sql` and `../install/sql/permissions-mysql.sql`, and `install/` was replaced by `wizard/` in 1.9.13, so neither could start. They duplicated the roles of `docker-compose-prod.yml` and `docker-compose-dev.yml`. `Dockerfile-php8-dev` was the only image carrying the PostgreSQL and gd extensions, so those moved into `Dockerfile-dev` rather than being lost

## [v1.9.23] - 2026-08-12

### Added

- Admin "Export for WordPress" is now reachable from the Admin Settings menu. It was previously linked only from the trailer's Admin page, so sites that disable the trailer had no way to reach it (#681)

### Changed

- English-US translations are now 100% complete: 24 missing phrases filled in, and 59 stale entries removed that no longer correspond to any `translate()` call (leftovers from the removed `install/` directory). Three phrases containing a colon were reworded — the translation parser splits each line on the first colon, so `Options: restrict permissions`, `Manifest signature FAILED: XXX`, and `The MCP SDK PHP package must be installed. Run: composer install` could never be translated in any language (#701)
- Docker Hub images are now also published under a bare version tag (for example `webcalendar:1.9.23`) alongside the existing `-php8-apache` and `latest` tags
- Upgraded dev dependency `squizlabs/php_codesniffer` from 4.0.1 to 4.0.4, resolving CVE-2026-67434. Dev-only — `vendor/` is not shipped in releases, so this was never a runtime exposure (#697)
- CI: removed two unreliable assertions from the MCP test suite — a wall-clock standard-deviation bound that failed at random on shared runners, and a memory test whose assertions could never fail because every measurement was zero (#698)
- CI: PHPStan passes again; two baseline ignore patterns no longer matched any reported error, which PHPStan treats as a failure (#696)

### Fixed

- Month/Week/Year date selectors appear at the bottom of the page again when the top menu is disabled, and the Admin "Date Selectors position" setting works once more. Both broke in v1.9.0, when the Bootstrap navbar rewrite moved the selectors into the top menu and left `MENU_DATE_TOP` read by nothing — disabling the top menu removed the selectors entirely, with no way to get them back (#695)
- Admin page no longer loads the obsolete `install/default_config.php` (#693)

### Removed

## [v1.9.22] - 2026-07-29

### Added

- MCP: write tools `add_recurring_event`, `update_event`, and `delete_event`, gated behind the new `MCP_WRITE_ACCESS` setting
- MCP: read tools `get_availability` and `check_conflicts` for scheduling agents
- MCP: optional event time on `add_event` (#670)
- MCP: per-user connection help tab showing the endpoint URL and a sample agent configuration (#682)
- Admin "Export for WordPress" page (#680)

### Changed

- Upgraded `mcp/sdk` from 0.3.0 to 0.6.0 (#659)
- Updated Dutch translation (#674)
- CI: flaky MySQL/PostgreSQL Selenium wizard tests are now non-blocking (#688, tracked in #689)

### Fixed

- Release ZIP now includes 77 translation files that were dropped from the release manifest when translations were renamed (Hebrew, Chinese, Arabic, Hindi, and others offered in the UI but missing from releases), plus the `export_wordpress.php` admin page. Added an inverse drift check: every git-tracked file must be listed in `release-files` or `release-files-excluded`, so unclassified new files fail CI
- Widen `webcal_user.cal_passwd` to VARCHAR(255) during upgrade, fixing "Error executing query" on first login after upgrading a legacy database on strict-mode MySQL/MariaDB (#676)
- MCP: deleted and rejected events are no longer returned by read tools (#671)
- MCP: CLI/STDIO transport works again via the shared tool dispatcher (#669)
- Color picker preferences: live preview updates as colors change, including with gradients enabled (#684)
- Preferences: Colors tab content no longer renders outside its tab pane (#683)
- Page-specific styles apply again — the `<body>` id is lowercased to match stylesheet selectors (#685)
- Layers: Edit Layer dialog opens as a proper centered Bootstrap modal (#687)

### Removed

## [v1.9.21] - 2026-07-13

### Added

### Changed

### Fixed

- Release ZIP now includes the complete `wizard/` directory (installer and DB-upgrade path). It had been omitted from the release manifest since 1.9.13, so ZIP-based upgrades silently skipped required DB migrations (#667)
- CI: raise the wizard Selenium `wait_for_text` timeout from 15s to 45s for the MySQL and PostgreSQL install tests, fixing a flaky `TimeoutException` on the table-creating "Finish" step

### Removed

## [v1.9.20] - 2026-07-12

### Added

- Release-archive smoke test that catches runtime files missing from the release manifest (#666)

### Changed

- Publish the Docker Hub image as a multi-arch manifest (linux/amd64 + linux/arm64), with a manual workflow trigger

### Fixed

- Restore files missing from the release archive that broke installs from the release zip: the MCP subsystem (`includes/mcp-loader.php`, `mcp.php`, loaded on every request), the signed-manifest `Security\*` classes used by the security-audit page, and the TinyMCE rich-text editor assets (#666)
- Single User Mode now works: `single_user_login` is now read from `settings.php`/env instead of failing with an "Undefined array key" error and a false "You must define single_user_login" message (#666)
- Accept `Y`/`N` (and anchor the match) for the `single_user` and `readonly` settings, so a hand-edited `single_user: Y` correctly enables single-user mode

## [v1.9.19] - 2026-06-28

### Changed

- Bump twbs/bootstrap-icons from 1.10.5 to 1.13.1 (#554)
- Bump actions/checkout from 6 to 7 (#650)
- Bump actions/cache from 5 to 6 (#661)

### Fixed

- `includes/htmlsanitize.php` was missing from the release zip; added to `release-files` manifest (#662)
- MCP: `X-MCP-Token` header now takes priority over `MCP_TOKEN` env var for token extraction

## [v1.9.18] - 2026-06-22

### Security

This release remediates the critical and high-priority findings from a full
security assessment (OWASP Top 10 and beyond). Several issues were remotely
exploitable by any authenticated low-privilege user against a default
configuration, so upgrading is strongly recommended.

- Fix privilege escalation that let any authenticated user grant themselves
  administrator rights, and account takeover that let any user reset another
  user's password, via `users_ajax.php`; gate user and group create/delete on
  real authorization (#654).
- Enforce authorization on event approval/rejection and on attachment/comment
  downloads (blob IDOR), and sanitize the attachment `Content-Disposition` and
  MIME handling in `doc.php` (#654).
- Prevent SSRF / arbitrary local-file read via remote-calendar subscriptions by
  validating URL schemes and rejecting internal/loopback addresses (#654).
- Remove "pass-the-hash" remember-me cookie validation (token-only now),
  regenerate the session id on login to prevent session fixation, and set
  `SameSite`/`Secure`/`HttpOnly` and strict-mode session cookies (#654).
- Hash MCP API tokens at rest, generate them server-side, show them once, and
  stop logging token material (#654).
- Record failed logins and throttle repeated failures to blunt online brute
  force (#654).
- Sanitize rich-text event descriptions and comments server-side when HTML
  descriptions are enabled, replacing the previous trust-the-editor behavior
  that allowed stored XSS (#655).
- Escape user-controlled output across views — event, category, group, layer,
  participant and comment names, custom fields, search results and admin
  dropdowns — and stop linkifying dangerous URL schemes such as `javascript:`
  and `data:` (#654, #655).
- Require an authenticated wizard session for the installer's `phpinfo()`
  output (#654).
- Add a `composer audit` gate to CI and pin secret-handling GitHub Actions to
  commit SHAs (#654).

### Added

- Mobile camera capture for event attachments.

### Changed

- Remember-me cookies issued by earlier versions are invalidated by the
  authentication hardening; users will need to log in again once after
  upgrading.

### Removed

- The default `admin`/`admin` account is no longer created on new installs; the
  installer now requires creating an administrator with a real, hashed password.
- The MCP `?token=` query-string authentication method (tokens in URLs leak into
  logs and history). Use the `Authorization`/`X-MCP-Token` header or the
  `MCP_TOKEN` environment variable instead.

## [v1.9.17] - 2026-06-21

### Security

- Update TinyMCE to 7.9.3 to address CVE-2026-47759 (XSS).

### Fixed

- Prevent "Unexpected end of JSON input" during install/upgrade on PHP 8.1+;
  the wizard now returns proper JSON instead of an empty body on DB errors (#642)
- Restore missing wizard methods so the admin-user and upgrade-SQL steps work
  again (createAdminUser, getUpgradeSqlCommands) (#642)
- Ship the TinyMCE table plugin referenced by the editor configuration
- Guard count() against null in RSS feed generation on PHP 8
- Return MCP event times in the user's local timezone
- Repair MCP add_event and rate limiting, and isolate the MCP test suite
- Handle mixed latin1/UTF-8 data in the charset conversion tool

## [v1.9.16] - 2026-04-01

### Security

- Security audit now verifies a signed manifest of release files and reports
  extra, modified, and missing files — a defense against opportunistic
  webshell drops (#233). See `docs/release-signing.md` for the maintainer
  runbook and independent verification instructions.

### Fixed

- Restore CREATE TABLE IF NOT EXISTS for webcal_blob in upgrade SQL
- Remove spurious CREATE TABLE from upgrade SQL preview; fix copy button
- Add --skip-ssl fallback for MySQL CLI; create webcal_blob if missing
- Align day view time column when untimed events are present (#100)
- Nav links from view_entry.php now return to own calendar (#159)
- Preserve correct date for all-day events during CSV import (#193)
- Replace += 86400 day loop with mktime to prevent DST duplicate days (#167)
- Honor BYMONTH selection for monthly repeating events (#155)
- Repair Expert Mode ByDay/ByMonthDay/BySetPos button selection (#165)
- Skip BYMONTHDAY values exceeding month length per RFC 5545 (#149)
- Use exclusive DTEND for untimed events per RFC 5545 (#144)
- Widen webcal_blob.cal_name from VARCHAR(30) to VARCHAR(255) (#105)
- Correct ICS export timezone/DST handling (#74)
- Add maxlength to brief description input to prevent silent truncation (#60)
- Empty ICS email attachment when creator is not a participant (#236)
- Add missing management pages to access control page lookup (#368)
- Remove ldap_sort() call removed in PHP 8.0 (#373)
- Enable double-tap to add events on Android Chrome (#528)
- Preserve English fallback translations for non-English languages (#450)
- Prevent password lockout when cal_passwd column is too narrow (#567)
- Gracefully handle invalid or unwritable cache directory (#617)
- Time selection dropdowns wrapping in Repeat/Reminders tabs (#625)
- Add missing globals for name validation in save_user() (#498)
- End time wrapping on separate lines in event editor (#596)
- Handle missing wizard gracefully (#610)
- PHP 8.2+ float-to-int deprecation in time_selection() (#612)
- Typos in HTML attributes, CSS selectors, and class names (#574)
- Respect EMAIL_MAILER setting instead of hardcoding SMTP (#629)
- Replace broken DES-crypt session cookies with secure token-based remember-me
- Always use UTF-8 meta charset; add latin1-to-utf8 migration tool (#626)
- Set UTF-8 charset on database connections and HTTP headers (#626)
- Upgrade wizard skipping v1.9.11 SQL when upgrading from v1.9.10 (#624)
- Display events of all participants
- Wizard upgrade regressions blocking install and post-install runtime (#639)
- Restore upgrade helper functions dropped in wizard rewrite (#639)
- `cat_owner` NULL cleanup and version-stamp error reporting in installer (#639)
- Schema probe now authoritative over stale version stamp during upgrade (#639)
- Bypass and clear stale query cache for `WEBCAL_PROGRAM_VERSION` (#639)
- Custom template HTML save, Firefox select font, and mobile minical fixes (#639)
- Keep Month/Week/Year views horizontal on iPad portrait (#639)
- Narrow-viewport minical caption-side rendering (#640)
- Stop wiping non-array `site_extras` values on save (#641)
- Navbar collapse breakpoint and dual-collapse toggle on mobile (#639)
- Stray quote and PHP block in `styles.css` (#639)
- Correct ICS export timezone for Apple Calendar and CalDAV clients
- Support emoji and full Unicode in iCal imports and display

### Changed

- Upgrade PHPMailer from 6.8.1 to 7.0.2 (#602)
- Upgrade PHP_CodeSniffer from 3.x to 4.0.1
- Documentation modernized: legacy HTML docs archived, replaced with Markdown
- Database backend support clarified: MySQL, PostgreSQL, SQLite3 are supported and tested; Oracle, DB2, ODBC, Interbase are legacy/untested on PHP 8

### Added

- MCP server unit and integration tests with CI workflow
- Comprehensive Markdown documentation in docs/
- Manual ordering UI for event categories (#493)
- MkDocs Material documentation site with GitHub Pages deployment

## [v1.9.15] - 2026-02-27

### Fixed

- Initialize undefined vars in edit_entry_handler to fix redirect
- Populate default webcal_config on fresh wizard install
- Guard undefined globals on login page after fresh install
- Handle missing SERVER_TIMEZONE after fresh install
- Allow empty db_login and db_host for SQLite databases
- Session cookie invalid when random salt contains bad characters
- Handle PHP 8.1+ DB exceptions during wizard upgrade (#613)
- User cannot confirm conflicts (#618)
- determineServerUrl() when invoked from CLI (#620)
- French translations encoding and wording (#619)
- Map db driver names to SQL filenames in wizard installer (#616)
- Wizard new-install support and PHP 8.x compatibility

### Added

- PHPStan static analysis at level 0 with baseline
- Multi-PHP-version CI matrix (8.2, 8.3, 8.4)
- Comprehensive wizard installer test infrastructure
- Post-install smoke tests in Selenium test suite
- Screenshot capture on Selenium test failure
- v1.9.10 and v1.9.12 upgrade test fixtures
- Release workflow gated on all test suites passing

## [v1.9.14] - 2026-02-11

### Fixed

- Install error fixes
- Composer.lock sync

## [v1.9.13] - 2026-02-04

### Added

- New web-based installation wizard (wizard/) replacing old install/ directory (#608)
- Headless CLI installer (wizard/headless.php) for automated deployments
- Environment variable configuration support (WEBCALENDAR_USE_ENV)
- MCP server for AI assistant integration (mcp.php)
- API token field (cal_api_token) in webcal_user table
- GitHub workflow for automated installation testing

### Changed

- Replaced CKEditor v4 with TinyMCE 7.x (CKEditor v4 end-of-life) 
- Updated Bootstrap Icons
- Updated Composer dependencies
- Category icon storage moved from filesystem to database (webcal_blob)

### Removed

- Old install/ directory and installer
- MS SQL Server support (extension removed from PHP 8)
- PHP 7 Docker files

### Fixed

- Default Visibility setting not saved in admin.php (#592)
- JSON parsing for layers
- Password column length during v1.9.12 upgrade
- SQLite3 fixes for dbi4php.php and installation SQL (#587)

### Security

- CSRF fix in reject_entry.php
- XSS fix for report name

## [v1.9.10] - 2023-10-02

### Fixed

- PHP 8.2/8.3 deprecation warnings
- Category settings 500 error (#426)
- Global categories not loading after cat_owner NULL change
- "Remember me" on login page (#527)
- Week view display (#529)
- Various PHP 8 compatibility fixes
- DST correction for reminders on recurring events

### Changed

- Updated PHPUnit to 9.6.15
- Export "All" checkbox on export page
- Improvements to server base URL determination
- PHP session uses install-directory-specific name

## [v1.9.8] - 2023-09-11

### Fixed

- PHP 8 deprecation fixes
- Category creation errors (#496, #507)
- User management fixes
- Various HTML5 compliance updates
- Spelling and documentation fixes

### Added

- Dark/light theme user option
- PostgreSQL development Docker support (port 8081)
- Spanish UTF-8 translations
- AI-assisted translation updates (German, French, Polish)

## [v1.9.0] - 2022-03-04

### Added

- Initial PHP 8 support
- Docker-based development environment
- GitHub Actions CI

### Changed

- Modernized for PHP 8 compatibility
- Updated Composer dependencies

## Earlier Releases

For releases prior to v1.9.0, see the
[GitHub releases page](https://github.com/craigk5n/webcalendar/releases)
and the git log.

[Unreleased]: https://github.com/craigk5n/webcalendar/compare/v1.9.16...HEAD
[v1.9.16]: https://github.com/craigk5n/webcalendar/compare/v1.9.15...v1.9.16
[v1.9.15]: https://github.com/craigk5n/webcalendar/compare/v1.9.14...v1.9.15
[v1.9.14]: https://github.com/craigk5n/webcalendar/compare/v1.9.13...v1.9.14
[v1.9.13]: https://github.com/craigk5n/webcalendar/compare/v1.9.10...v1.9.13
[v1.9.10]: https://github.com/craigk5n/webcalendar/compare/v1.9.8...v1.9.10
[v1.9.8]: https://github.com/craigk5n/webcalendar/compare/v1.9.0...v1.9.8
[v1.9.0]: https://github.com/craigk5n/webcalendar/compare/v1.3.0...v1.9.0
