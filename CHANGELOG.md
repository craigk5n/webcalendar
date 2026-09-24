# Changelog

All notable changes to WebCalendar are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- **Diagnostic report for bug reports.** `php bin/webcal.php diagnose` prints the WebCalendar and PHP versions, loaded extensions, database type and server version, directory writability and the relevant configuration, so a report carries its environment instead of the reporter hand-typing it. Admins who can still log in get the same report with a copy button under **Admin > Security Audit**. It runs on a broken install: a missing `settings.php` produces a report rather than a redirect to the wizard
- **`bin/webcal.php`**, a command-line entry point. Refuses to run under any SAPI but CLI

- The PHP 8.1 and SQLite Docker dev environments are now tracked: `docker/Dockerfile-dev`, `docker/docker-compose-dev.yml`, `docker/docker-compose-prod.yml`, `docker/docker-compose-sqlite-dev.yml`, `docker/mysql-init.sql` and the selenium login tests under `docker/tests/`. `CLAUDE.md` and the developer docs referenced these but they had never been committed
- `tests/DestructiveTestGuardTest.php` keeps that refusal in place: the guard must exist in each runner, must come before any docker command, and the workflow must keep supplying the override
- `tests/PhpFloorConsistencyTest.php` locks the supported PHP version together across `composer.json`, `WizardValidator`, the CI matrices and the docs. They had drifted into four different answers
- `tests/DockerReferencesTest.php` fails the build when a compose file, workflow or shell script names a Dockerfile or compose file that does not exist. The three Selenium wizard jobs reach `docker/Dockerfile-php8-dev` three hops down — workflow, to `tests/run-*-install-tests.sh`, to a `docker-compose-test-*.yml` `dockerfile:` key — so renaming it broke CI with nothing failing locally
- `tests/CliOnlyGuardTest.php` fails the build when a script under `tools/` or `bin/` is added without a command-line guard, or when an existing guard is moved below code that would already have run
- **Reminder web trigger.** Admin > Settings > Email can generate a token that lets `tools/send_reminders.php` be run by fetching its URL, for hosts that offer no way to run a command. Off until a token is generated. The token is generated server-side, shown once, and stored only as a SHA-256 hash, so a database read or backup does not yield a working credential. It may be passed as `?token=` or as an `X-Reminder-Token` header, and every run triggered this way is written to the activity log with the requesting IP

### Changed

- `.github/ISSUE_TEMPLATE/bug_report.md` asks for the output of `php bin/webcal.php diagnose` instead of six hand-typed environment fields, with the admin page and the manual fields as fallbacks

- **PHP 8.2 is now the minimum, and PHP 8.1 is no longer supported.** 8.1 reached end of life on 31 December 2025. The repository had stated four different floors: `composer.json` required `^8.2`, `README.md` said 8.2+, `CONTRIBUTING.md` said 8.0+, `docs/installation.md` said "8.0 minimum", and the signed-manifest decisions log recorded 8.1 with 8.2+ language features deliberately avoided — while `php-syntax-check.yml` and `test-install.yml` still tested 8.1 and the installation wizard admitted anything from 8.0 up. The wizard now reports PHP below 8.2 as an error, both workflows drop 8.1, and the docs agree. New code may use `readonly class`, typed class constants and `#[\Override]`. PHP 8.2 itself loses security support on 31 December 2026

- **One development image for every backend.** `Dockerfile-dev` now builds `mysqli`, `pgsql`, `pdo_pgsql`, `sqlite3`, `pdo_sqlite` and `gd`, so the MariaDB, PostgreSQL and SQLite environments and the wizard CI that exercises all three share a single image instead of two that drifted apart
- **Docker dev and production files renamed to drop the PHP version.** `Dockerfile-php8.1-dev` is now `Dockerfile-dev`, `docker-compose-php8.1-dev.yml` is `docker-compose-dev.yml`, `docker-compose-php8.1.yml` is `docker-compose-prod.yml` and `Dockerfile-php8` is `Dockerfile-prod` (`docker/build_and_push.sh` and the `docker.yml` and `docker-dev.yml` workflows, which build and publish the `k5nus/webcalendar` images, were updated to match). Encoding the runtime in the filename meant every version bump either renamed the files or left the name lying; the names now describe the role instead. Documentation referencing the old `docker-compose-php8*.yml` files was repointed at the working equivalents
- **Docker images bumped to PHP 8.4 and all floating tags pinned.** The dev containers ran PHP 8.1, which reached end of life on 31 December 2025 and is below the `^8.2` floor `composer.json` declares, so the documented dev environment did not match the product's own minimum. `Dockerfile-php8` and `Dockerfile-php8-dev` used the floating `php:8-apache` tag, giving two developers on the same commit different runtimes. Now `php:8.4-apache` throughout, with `mariadb` pinned to `11.4` (LTS), `postgres` to `16` and `alpine` to `3.20`. The `docker-compose-test-*.yml` files used by the wizard CI are unchanged
- **Shebang lines removed from `tools/*.php`.** PHP strips `#!` only under the CLI SAPI; served by Apache the line was emitted as page output, which sent the response headers before the new guard could set a status. The scripts answered `200` with the shebang text in the body instead of `403`. Every documented and CI invocation already uses `php tools/<script>.php`, and most of the removed lines pointed at `/usr/local/bin/php`, which does not exist on Debian, Ubuntu or RHEL. A crontab invoking a script directly rather than through `php` needs updating
- **Scripts under `tools/` now refuse to run over HTTP.** They live inside the web root and three of them ship in releases: `send_reminders.php`, `reload_remotes.php` and `convert_passwords.php`. The last converts stored password hashes and was reachable by URL. `.htaccess` was never protection here — Debian and Ubuntu ship `AllowOverride None` for `/var/www`, which makes the whole file a no-op, so each script now checks `PHP_SAPI` itself and answers 403
- **Breaking, for sites triggering reminders by URL:** fetching `tools/send_reminders.php` with no token now returns 403. `docs/admin-guide.md` previously offered it as an alternative for hosts without PHP CLI. Affected sites should move to a cron entry running the PHP CLI binary — cPanel, Plesk and DirectAdmin all provide one — or generate a token and add it to the URL
- Config defaults and `db_load_config()` moved from `wizard/shared/default_config.php` to `includes/default_config.php`. The wizard still reads it as the single source of truth, but it is no longer inside a directory administrators are told to delete (#707)

### Fixed

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
