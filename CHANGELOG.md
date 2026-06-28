# Changelog

All notable changes to WebCalendar are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

### Changed

### Fixed

### Removed

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
