# WebCalendar agent notes (v1.9.25)

`AGENTS.md` is the canonical project guidance. A root-level `CLAUDE.md` is
optional, local, and ignored by Git. Keep shared instructions here; a local
`CLAUDE.md` may import them with `@AGENTS.md`.

## Execution model
- This is a procedural PHP app: root PHP files are HTTP endpoints, and the
  repository root is the web document root (`pub/` contains browser assets).
- Most endpoints bootstrap through `includes/init.php`: optional Composer autoload,
  `WebCalendar` first-phase initialization, the configured `includes/$user_inc`
  authentication backend, then second-phase initialization. Inspect that flow
  before changing authentication or request setup.
- `WebCalendar\` autoloads from `app/`, but most shared logic remains in
  `includes/functions.php` and `includes/classes/`.
  Runtime helpers are also required directly by `includes/functions.php` so
  release archives work without `vendor/` or Composer.
- Database access uses the multi-backend `dbi_*` API in `includes/dbi4php.php`.
  Schema upgrades live in `wizard/shared/upgrade-sql.php` (default SQL plus
  backend-specific overrides), replacing the older `upgrade-*.sql` files;
  fresh-install schemas are the `wizard/shared/tables-*` files.

## Modernization direction
- The long-term goal is an object-oriented codebase, ideally using Laravel.
  Extract behavior incrementally into application classes with direct behavioral
  tests, preserving working release archives throughout the transition.
- Project-owned classes use `WebCalendar\` namespaces, such as
  `WebCalendar\Models\` and `WebCalendar\Services\`. Reserve `WebCalendar\Core\`
  for the upstream core library. The namespace expresses ownership; classes
  using Laravel components still depend on those components.
- Time formatting uses `WebCalendar\Helpers\DateDisplay::getShortTime()` and database
  configuration through `ConfigService`. Test the helper directly rather than
  restoring the removed procedural `getShortTime()` for imported upstream tests.

## Dependencies and generated assets
- GitHub Actions runners are pinned to `ubuntu-24.04`. Docker Hub publishing
  is opt-in: set repository Actions variable `PUBLISH_DOCKER_IMAGES` to `true`
  and configure secrets `DOCKER_HUB_USERNAME` and `DOCKER_HUB_ACCESS_TOKEN`.
  Both development and release publishing jobs skip when the variable is unset.
- Run `composer install` from the root using `composer.lock` (as CI does);
  the test README's `composer update` would change dependency resolution.
- After dependency/asset changes, run `make` on Linux. It copies selected
  Composer packages into `pub/`, `images/bootstrap-icons/`, and
  `includes/classes/phpmailer/`, and regenerates integrity hashes and
  `includes/load_assets.php`. Do not hand-edit that generated PHP file.
- The Makefile requires `/usr/bin/sha384sum` and `xxd`; it does not work
  unchanged on macOS. As of upstream v1.9.25, TinyMCE replaces CKEditor and
  `make` also rebuilds the bundled MCP SDK.
- Run asset builds in the development container:
  `docker compose -f docker/docker-compose-dev.yml exec -T webcalendar make`.
  Generated timestamps use `%a %d %b %Y %I:%M:%S %p %Z` and inherit the build
  environment's timezone (`TZ` when set, otherwise the system timezone);
  do not hardcode a timezone in the Makefile.
- Symfony November 2026 advisories are patched by locking filesystem,
  http-foundation, http-kernel and routing to 7.4.23. This targeted update changes
  only those four packages; PHP's 8.2 platform floor and composer.json stay intact.
  Both installed and locked Composer audits pass, including dev dependencies.
  Filesystem is dev-only; none of the four packages is in the MCP bundle closure.
  `make` completed with no generated bundle/assets/manifest diff. Clear/rebuild
  deployed compiled route caches after installing the new lock file: the routing
  fix changes the generated matcher. See docs/api.md for deployment commands.
  Six integration cases first reproduced `/api.phpv1/...` and encoded aliases
  reaching an authenticated handler; the patched dependency rejects them with
  404 while preserving valid subdirectory URLs. HEAD auth/abilities and forwarded
  IP/host/scheme/port/prefix checks also pass. Apache smoke verifies real HEAD
  authorization and malformed path rejection. Combined full suites pass on
  PHP 8.2–8.5: 1,208 passed, one optional external-DB skip, 5,484 assertions each;
  full lint, bundle/release checks, Apache and disposable database checks pass.

## Verification
Run these from the repository root unless specified otherwise:
- CI dependency check: `composer validate`.
- PHPUnit 9.6 suite: `vendor/bin/phpunit -c tests/phpunit.xml`.
- Single file: `vendor/bin/phpunit tests/functionsTest.php`; single method:
  `vendor/bin/phpunit tests/functionsTest.php --filter test_add_duration`.
- Focused PHP syntax check: `php -l path/to/changed.php`.
- Comment-style CI checks the complete PR diff. Run
  `php tools/check-ai-signals.php --since=origin/master --strict` in the
  development container, using the checkout owner's UID/GID as for PHPUnit.
  Use the PR base commit when it differs from `origin/master`; `--since=HEAD`
  compares HEAD with itself and checks no added lines. Before committing comment
  edits, also run the check in whole-tree advisory mode to verify those edits;
  `--since` inspects committed changes only. Preserve quoted diagnostics and
  operator instructions with a justified `ai-signals:allow` in that comment.
- After changing `includes/functions.php`, regenerate the committed function
  index with `php tools/build-codemap.php`.
- Run PHPUnit as a non-root user: the unreadable-file test fails under root.
  Use the host checkout owner's UID/GID so Git can inspect the bind-mounted
  checkout without unsafe-ownership errors. The dev image includes Git;
  release-classification tests must not silently skip in a Git checkout.
  The LDAP lifecycle TLS test requires the LDAP extension for the
  `LDAP_OPT_PROTOCOL_VERSION` constant. The development Dockerfile installs
  `libldap2-dev` and builds `ldap`; rebuild older images before running the suite.
- PHP is not installed on the host in this environment. Run the suite with
  `docker compose -f docker/docker-compose-dev.yml exec -T -u "$(id -u):$(id -g)" webcalendar php vendor/bin/phpunit -c tests/phpunit.xml`.
  Other PHP commands can use the same container via `exec -T webcalendar php`.
- Use `--init` for standalone Docker test containers so PHP is not PID 1 and
  spawned fixture processes are reaped when the test run exits.
- Syntax checks use `php tools/lint-php.php` in CI and through
  `tests/compile_test.sh`. The exact bundled UUID `bootstrap85.php` path is
  skipped only below PHP 8.5 and is linted on 8.5; do not exclude the whole SDK.
  Run the complete PHPUnit and lint matrix on PHP 8.2–8.5 for API changes.
  `tests/run_unit_tests.sh` is not a reliable test-exit-status wrapper; invoke
  PHPUnit directly.
- HTTP tests require an installed app at `http://localhost:8080`, `admin/admin`,
  and Python packages `pytest`, `httpx`, `lxml`. Invoke explicitly with
  `python -m pytest tests/ui-tests.py` (its filename is not default pytest
  discovery); these tests create calendar events.

## Local runtime
- From the root: `docker compose -f docker/docker-compose-dev.yml up --build`.
  This bind-mounts the checkout, so install Composer dependencies on the host
  first; the dev Dockerfile does not install them. The development stack uses
  `docker/Dockerfile-dev` with `php:8.5.12-apache-trixie` and requires PHP 8.2+.
- The local upstream-based dev stack serves the app at `http://127.0.0.1:8090`
  using services `webcalendar` and `db`. It reuses the `docker_mysql-data`
  volume and `webcalendar_php8` database from the legacy stack. MariaDB is
  pinned to 13.0.2, the version already used by that volume; do not switch it
  to upstream's older 11.4 image against the existing data directory.
  Both services mount the host's `/etc/localtime` so build timestamps use
  the local timezone. The legacy PHP 8.3 Docker files and containers were
  removed after migration to the upstream filenames.
  Complete the web wizard after startup. With `WEBCALENDAR_USE_ENV=true`,
  `includes/config.php` reads `WEBCALENDAR_*` settings instead of the ignored
  local `includes/settings.php` file.
- For a fresh environment-backed database, open `/wizard/` directly: the root
  page reports a missing `webcal_config` table until installation is completed.

## PHP 8.5 compatibility
- Development and production Dockerfiles pin PHP 8.5.12 on Debian trixie.
  Keep Composer's platform at PHP 8.2.0 while PHP 8.2 remains supported;
  validate the actual image with `composer check-platform-reqs`.
- PHP 8.5 deprecates `curl_close()`, `imagedestroy()`, reflection
  `setAccessible()`, and semicolon-terminated switch cases. Handles are objects
  on all supported PHP versions; release them with `unset()` when needed.
  Reflection can access non-public members directly on PHP 8.1+.
- The optional MySQL/PostgreSQL PHPUnit fixture in
  `tests/CrossDatabaseTestHelper.php` still feeds SQLite-specific SQL to other
  backends. Its MySQL `PRAGMA` failure reproduces on PHP 8.4; use disposable
  databases for backend installation checks rather than the live development DB.

## UI and packaging conventions
- Route displayed text through `translate()` / `etranslate()` and update
  `translations/English-US.txt` when adding translation keys; see
  `docs/developer-guide.md#translations` for translation conventions.
- Release archives copy only paths listed in `release-files`; update that
  manifest when adding files needed in a distributed installation.
  Classify development-only files in `release-files-excluded`.

## Authentication boundaries
- Wizard API actions are default-deny; only explicit public setup/auth actions
  may run anonymously. Initial password creation requires an unconfigured
  installation, checked against settings/environment before session overrides.
  Existing installations must authenticate with their configured password;
  wizard administrator creation requires a successfully verified zero
  administrator count. Query failures must never be treated as zero.
- MCP authentication compares only the SHA-256 hash of the presented token.
  Do not restore a plaintext fallback: stored hashes must never authenticate.
  Legacy plaintext tokens require regeneration in Preferences.

## Mobile API
- Assistant calendar selection uses `GET /calendars` and
  `/calendars/{calendar}/events` GET/POST/PATCH routes (PATCH adds `{event}`).
  Calendar IDs are existing login primary keys. CalendarSelection keeps the actor's
  permissions while resolving target timezone/week-start from preferences and
  installation defaults. Explicit webcal_asst membership is required for other
  calendars, including nonusers; admin status alone does not grant API delegation.
  Recheck before reads and within each write attempt. Entry ownership/participation
  belongs to the target, while logs retain the actor. Calendar-scoped reads/writes
  return calendar_id/name. Existing personal routes remain supported.
  Writes now lock the required WEBCAL_PROGRAM_VERSION config row before account/event locks
  (SQLite still obtains write intent first). This coarse mutex serializes different
  assistants without target API identities or a migration; webcal_config must also
  be InnoDB. No coordination with browser/MCP writes is implied.
  Regression coverage includes revocation, disabled targets, actor UAC/token
  permissions, private recurrence/timezones, nonuser defaults, and independent
  assistants competing for overlapping events/daily limits. PHP 8.2–8.5 verification
  covers 1,225 tests, one optional external-DB skip, 5,812 assertions; release
  classification requires new runtime files present in Git's index. Full lint and
  existing Apache smoke checks pass.
  CalendarEventAccess applies calendar-level UAC and event/visibility masks to
  delegated capabilities and writes (selected calendar plus event creator).
  Selection refreshes the legacy permission cache per activation/write attempt;
  retry regressions revoke edit access after rollback on an independent connection.
  Delegated reads filter by calendar view masks; internal conflict checks retain
  hidden busy intervals. API bootstrap captures api_installation_timezone before
  account preferences, preserving SERVER_TIMEZONE/PHP fallback without leaking an
  assistant timezone. Missing TIMEZONE regressions cover reads, create, metadata
  PATCH, and competing assistants. No mutex initialization/migration is needed.
  CalendarEventAccess.canViewDetails checks the effective time permission as the
  string 'N' (casting 'Y'/'N' to bool is incorrect). Time-only delegated detail
  reads and POST/PATCH return 403, and discovery reports can_write false. Internal
  schedule checks still see hidden ordinary/recurring busy intervals. Creation
  now uses canEditEvent, requiring view and edit masks for explicit/default
  visibility before inserts; response can_edit is computed from the same policy.
  Regression coverage includes ordinary/recurring time-only access, inherited
  default policies, restored detail access, internal conflicts, rejected private
  creation and allowed public create/read/PATCH under public-only view masks.
- The opt-in Laravel/Sanctum API is served by `api.php/v1/*`; enable it with
  `WEBCALENDAR_API_ENABLED=true`. `app/Api/` contains its bootstrap, config,
  routes, controllers, requests, and token models. `app/Services/CalendarIdentity.php`
  and `CalendarEvents.php` adapt the procedural authentication/event functions.
- API dependencies require Composer; the legacy UI still supports archives
  without `vendor/`. Run `php bin/api.php migrate --force` as the web-server
  user to install optional API tables. API migrations are separate from the
  wizard's versioned schema upgrades. PDO drivers are required alongside DBI
  extensions; the Docker images include `pdo_mysql` for this reason.
- API identities contain no passwords and support database/LDAP authentication.
  Recheck the configured backend and current user on every token-authenticated
  request. Disable DBI's persistent query cache in the API so account and
  permission changes are visible immediately. Keep Sanctum and MCP tokens separate.
- Initial event reads are own-calendar only: no layers/public overlay or
  delegation. Recurrence dates are local but stored event times are UTC;
  preserve that distinction across DST. Date bounds are inclusive and limited
  to 93 days. `tests/ApiIntegrationTest.php` installs a disposable SQLite calendar
  and tests real HTTP requests without modifying the development database.
- `POST /events` and `PATCH /events/{event}` support personal, accepted,
  non-recurring events created by the requesting account. New tokens include
  `events:write`; old tokens stay read-only until a fresh sign-in. Recheck view/
  edit permissions and the installation's readonly flag on every write. Return
  effective `can_edit` on occurrences and effective `timezone` on the profile.
  Shared/external-guest records, recurring/detached records, legacy cross-day
  extensions and their parents are not editable. Membership is not ownership.
- `EventWriteRequest` and its store/update subclasses own structural and merged
  schedule validation; controllers return EventResource responses. Timed writes
  require explicit-offset, minute-precision timestamps and an account-local date;
  untimed/all-day dates remain floating. WebCalendar cannot distinguish a timed
  UTC-midnight/1440-minute event from all-day storage, so reject that combination.
  See docs/api.md for patch/null semantics, limits and error codes.
- CalendarEventWriter uses one Laravel/PDO transaction for the entry,
  participation, reminder re-arming and activity log. Do not wrap DBI writes in a Laravel transaction:
  they use a different connection. Retry whole transactions for manual-ID key
  collisions, including activity-log IDs. MySQL write tables must be InnoDB;
  non-transactional tables return 503 before mutation. The API bootstrap explicitly
  loads activity-log-constants.php for LOG_CREATE/LOG_UPDATE.
- Event writes lock the authenticated account's existing `webcal_api_users` row
  before schedule reads and hold it through commit, before any event-row lock.
  SQLite first performs a no-op managed update to acquire write intent; installed
  Laravel ignores SQLite `transaction_mode` on PHP 8.2/8.3. API-to-API protection
  does not coordinate browser/MCP writers. MySQL requires InnoDB for the mutex too.
  Known contention errors and manual-ID collisions get at most five whole-write
  attempts with exponential backoff, fresh state and conflict checks after each
  confirmed rollback. Uncertain commits, failed rollbacks and other SQL errors
  must not retry. Exhaustion is 503 `calendar_busy`; SQLite busy_timeout is 250 ms.
  Legacy identity activation can insert a first-use LANGUAGE preference through
  DBI: initialize it before taking the PDO write lock, then recheck activation
  under protection. Independent workers must also complete this before barriers.
- Relative reminder counters reset only when the sender's start/end anchor instant
  changes (`cal_related` selects the anchor). Start-relative duration-only edits,
  unchanged end instants, equivalent offsets and metadata preserve delivery state.
  Absolute `cal_date` alarms are preserved. Check InnoDB for `webcal_reminders`
  before a reset and keep it in the same transaction as event/log changes.
  Match Event::getDateTimeTS: timed starts are UTC but floating starts use local
  mktime in the reminder runtime's installation timezone. Event::getEndDateTimeTS
  separately uses UTC plus duration, even for floating events. API bootstrap
  captures `api_reminder_timezone` before account preferences; temporarily scoped
  floating-anchor calculations restore the prior PHP default timezone. Tests
  compare with real Event methods across non-UTC forward/reverse conversions,
  DST and differing account/runtime zones. Do not localize the end calculation.
  QA regressions add pipe-synchronized independent-process create/create,
  different-event PATCH/PATCH, SQLite busy/retry/exhaustion and state reload checks,
  plus reminder lifecycle and uncertain-commit/rollback-failure coverage.
  Full suites now pass PHP 8.2–8.5: 1,180 passed, one optional external-DB skip
  (1,181 total), 5,215 assertions each; full lint and Apache checks pass.
  Disposable MariaDB 13/PostgreSQL 17 checks also pass concurrent create/patch,
  reminder re-arming/preservation, rollback and MySQL mutex/reminder engine guards.
- PostgreSQL COMMIT SQLSTATE 40001/40P01 is a confirmed abort even when PDO is no
  longer in a transaction. Clean up Laravel transaction state and permit a fresh
  whole-write attempt on the same connection. Other no-active-transaction commit
  errors remain uncertain and must not replay. The checked-in disposable fixture
  `tests/fixtures/api-postgres-commit-abort.php` uses real SERIALIZABLE dependency
  cycles to verify commit-time 40001 with PDO inactive and Laravel level 1,
  recovery, schedule revalidation, five-attempt exhaustion and no partial/duplicate
  rows. It requires the installed/migrated api_smoke_db and token-initialized
  api-smoke account; see docs/api.md. It ran successfully on PostgreSQL 17.
  After these residual QA fixes, full PHP 8.2–8.5 suites each pass 1,199 tests,
  with one optional external-DB skip (1,200 total), 5,409 assertions. Full syntax,
  Apache smoke and disposable MariaDB 13/PostgreSQL 17 checks pass.
- CalendarEventConflicts compares absolute intervals and floating all-day dates,
  using the existing recurrence engine in bounded windows. It honors ALLOW_CONFLICTS,
  daily appointment limits and overnight overlaps. Keep conflict messages JSON,
  without the browser helper's HTML. Schedule checks are not run for metadata-only
  patches. Related-record eligibility is batched, without per-occurrence queries.
- CalendarEventData provides the canonical PDO-row representation for writes and
  ordinary reads. CalendarEvents keeps full start/duration on the starting day and
  builds bounded, read-only display continuations for later days, even when the
  event starts before the requested range. Do not use the browser's clipped
  getOverLap fragments to prefill an editable event: they shorten a two-hour
  overnight event to 29 minutes. Conflict checks exclude continuation fragments
  and use full recurring durations from the database. Recurrence expansion remains
  in the existing engine. Floating all-day/untimed dates use Gregorian UTC backing;
  account-local timestamps determine timed grouping. read_events' timestamp bounds
  alone omit all-day events in negative-offset zones; round-trip coverage includes
  both offset directions, later-day-only windows, multi-day and DST spans. Normalize
  each civil-day boundary after advancing: a midnight DST jump to 01:00 must not
  carry that clock time into following days. Tests cover Sao Paulo midnight jumps
  as well as New York's spring/fall transitions.
- Event-write verification includes 49 API integration tests on PHP 8.2–8.5,
  Apache server/private-file/.htaccess paths, and disposable MariaDB 13/PostgreSQL
  17 HTTP checks for create/edit/read, DST and rollback. Full suites pass on each
  PHP version: 1,158 passed, one optional external-DB test skipped, zero failures.
  The release manifest includes all new runtime files; its tracking check passes
  once those files are added to Git. Existing legacy deprecation output is
  unrelated to these API changes.
- `GET /users/current` includes integer `week_start` (0 = Sunday, 6 = Saturday).
  CalendarIdentity.weekStart() uses the existing activation/preference resolver
  so user overrides, including string '0', take precedence over installation
  defaults. Resolve it on every profile read; no migration is needed. Isolated
  API installation fixtures must include translations for preference loading.
  Clients may fall back to regional defaults when older servers omit this field;
  it does not define minimum-days-in-first-week or a week-numbering convention.
- API storage belongs outside the document root. CLI and HTTP must share its
  owner/path (`WEBCALENDAR_API_STORAGE`). `WebCalendar\Api\Environment` loads
  `../private/<physical-installation-directory>/.env` before either entry point
  initializes legacy code. With that file, storage defaults to its adjacent
  `storage/`; otherwise the installation-specific temporary default remains.
  Server environment values override file values. DotEnv writes only to an
  isolated API repository: do not inject file values into legacy `getenv()` or
  load the public repository `.env`. Configuration/storage symlinks into the
  installation or HTTP document root are rejected. Do not cache adapted legacy
  configuration. See `docs/api.md` and `docs/api.env.example` for deployment.
  Optional private-directory probes must not emit `open_basedir` warnings on
  server-only or Composer-free installations.
- `PrivatePathPolicy` validates normalized access paths, canonical targets, and
  intermediate aliases against both access/canonical public-root forms. A supplied
  document root must resolve; do not silently skip an inaccessible boundary.
  Validate storage before `mkdir()` or discarding its original alias, then
  revalidate created runtime directories. CLI lacks a broader HTTP document root.
  Apache path-policy fixtures use public canaries to prove outward aliases are
  reachable before checking that the API rejects them without writing files.
  Inspect each `readlink()` destination, including relative targets; a final
  `realpath()` result loses chained public hops. Process dot segments after
  preceding links, reject uninspectable required components, and bound traversal
  with cycle-state checks. Canonical boundary ancestors are the only metadata
  shortcut; missing children require an inspectable parent and existing link targets.
- Apache must forward bearer authorization to PHP. Both Dockerfiles install
  `docker/apache-api.conf`, which scopes `CGIPassAuth On` to `api.php`. A missing
  directive reproduced token issuance (201) followed by profile rejection (401).
  `sh tests/run-api-apache-tests.sh` covers this using a unique Compose project,
  a read-only checkout, and disposable tmpfs SQLite storage; no development DB
  or credentials are used. Rebuild the web image when changing Apache config.
  Shared Apache/Plesk hosts can merge `.htaccess.example` into `.htaccess` beside
  the root `api.php`, with `AuthConfig` overrides permitted. A file in `bin/`
  affects only the CLI directory. The Apache smoke suite tests the example with
  the Docker forwarding configuration disabled; active `.htaccess` stays local.
- Composer and the bundled MCP loader share eager dependencies. The bundle
  generator uses Composer's package/file identifiers and
  `__composer_autoload_files` registry to run each eager bootstrap once in either
  loader order. Keep that behavior in `tools/build-mcp-sdk.php`, regenerate with
  `make mcp-sdk`, and test both loader orders plus Composer-free releases.
  Requiring both physical UUID bootstrap copies caused duplicate-constant
  warnings on PHP 8.5 and broke browser headers/cookies.
- API startup must check parsed `$settings['use_http_auth']`, not the unassigned
  legacy `$use_http_auth` global. Tests cover environment and isolated file-backed
  configuration. HTTP test fixtures use stream metadata for response headers;
  indirect `get_defined_vars()` lookup fails on PHP 8.2/8.3.
- User-deletion backends call the Composer-independent `ApiCredentialLifecycle`
  helper before deleting account data. It checks optional tables without DBI's
  persistent cache and aborts on partial schemas or revocation errors. DBI strips
  one slash-escaping layer from parameters, including tokenable class names.
- API subject bindings invalidate old tokens on database credential changes or
  LDAP entry replacement. LDAP API lookups require `entryUUID` or `objectGUID`;
  names and DNs are not immutable. The single initial API migration includes
  subject bindings with the identity/token tables; unbound tokens require fresh
  sign-in. This schema was consolidated before first production deployment;
  subsequent deployed-schema changes require separate migration files.
- Composer archive timestamps can be older than bundled assets. After a TinyMCE
  update, force `make -B pub/tinymce/CHANGELOG.md` as well as `make`, and run
  `TinyMceBundleTest` to check that shipped scripts match the audited dependency.
- The browser and API both call `initialize_recurrence_globals()` from
  `includes/recurrence-globals.php`. Weekday-based weekly/monthly recurrence
  requires both BYDAY lookup arrays; the API previously omitted them and returned
  500 on real calendars. Keep recurrence initialization free of browser session
  setup. Tests cover weekday rules, exceptions, COUNT limits, and DST; the Apache
  smoke fixture seeds weekly and monthly events and asserts occurrence dates.
