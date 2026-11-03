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
- Apache must forward bearer authorization to PHP. Both Dockerfiles install
  `docker/apache-api.conf`, which scopes `CGIPassAuth On` to `api.php`. A missing
  directive reproduced token issuance (201) followed by profile rejection (401).
  `sh tests/run-api-apache-tests.sh` covers this using a unique Compose project,
  a read-only checkout, and disposable tmpfs SQLite storage; no development DB
  or credentials are used. Rebuild the web image when changing Apache config.
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
  names and DNs are not immutable. Apply the subject-binding migration to existing
  API installations; unbound tokens require fresh sign-in.
- Composer archive timestamps can be older than bundled assets. After a TinyMCE
  update, force `make -B pub/tinymce/CHANGELOG.md` as well as `make`, and run
  `TinyMceBundleTest` to check that shipped scripts match the audited dependency.
- The browser and API both call `initialize_recurrence_globals()` from
  `includes/recurrence-globals.php`. Weekday-based weekly/monthly recurrence
  requires both BYDAY lookup arrays; the API previously omitted them and returned
  500 on real calendars. Keep recurrence initialization free of browser session
  setup. Tests cover weekday rules, exceptions, COUNT limits, and DST; the Apache
  smoke fixture seeds weekly and monthly events and asserts occurrence dates.
