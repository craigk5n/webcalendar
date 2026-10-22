# WebCalendar agent notes

## Execution model
- This is a procedural PHP app: root PHP files are HTTP endpoints, and the
  repository root is the web document root (`pub/` contains browser assets).
- Most endpoints bootstrap through `includes/init.php`: optional Composer autoload,
  `WebCalendar` first-phase initialization, the configured `includes/$user_inc`
  authentication backend, then second-phase initialization. Inspect that flow
  before changing authentication or request setup.
- `App\` autoloads from `app/`, but most shared logic remains in
  `includes/functions.php` and `includes/classes/`.
  Runtime helpers are also required directly by `includes/functions.php` so
  release archives work without `vendor/` or Composer.
- Database access uses the multi-backend `dbi_*` API in `includes/dbi4php.php`.
  Schema upgrades live in `wizard/shared/upgrade-sql.php` (default SQL plus
  backend-specific overrides), replacing the older `upgrade-*.sql` files;
  fresh-install schemas are the `wizard/shared/tables-*` files.

## Dependencies and generated assets
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
- Full syntax helper: run `bash compile_test.sh` with `tests/` as the working
  directory. Both it and `tests/run_unit_tests.sh` always exit 0, even on
  failures; inspect the syntax summary and invoke PHPUnit directly for a
  reliable test exit status.
- HTTP tests require an installed app at `http://localhost:8080`, `admin/admin`,
  and Python packages `pytest`, `httpx`, `lxml`. Invoke explicitly with
  `python -m pytest tests/ui-tests.py` (its filename is not default pytest
  discovery); these tests create calendar events.

## Local runtime
- From the root: `docker compose -f docker/docker-compose-dev.yml up --build`.
  This bind-mounts the checkout, so install Composer dependencies on the host
  first; the dev Dockerfile does not install them. Upstream v1.9.25 uses
  `docker/Dockerfile-dev` with `php:8.4-apache` and requires PHP 8.2+.
- The local upstream-based dev stack serves the app at `http://127.0.0.1:8090`
  using services `webcalendar` and `db`. It reuses the `docker_mysql-data`
  volume and `webcalendar_php8` database from the legacy stack. MariaDB is
  pinned to 13.0.2, the version already used by that volume; do not switch it
  to upstream's older 11.4 image against the existing data directory.
  Both services mount the host's `/etc/localtime` so build timestamps use
  the local timezone. The legacy PHP 8.3 Docker files and containers were
  removed after migration to the upstream filenames and PHP 8.4 image.
  Complete the web wizard after startup. With `WEBCALENDAR_USE_ENV=true`,
  `includes/config.php` reads `WEBCALENDAR_*` settings instead of the ignored
  local `includes/settings.php` file.
- For a fresh environment-backed database, open `/wizard/` directly: the root
  page reports a missing `webcal_config` table until installation is completed.

## UI and packaging conventions
- Route displayed text through `translate()` / `etranslate()` and update
  `translations/English-US.txt` when adding translation keys; see
  `docs/WebCalendar-DeveloperGuide.html` for translation conventions.
- Release archives copy only paths listed in `release-files`; update that
  manifest when adding files needed in a distributed installation.
  Classify development-only files in `release-files-excluded`.

## Authentication boundaries
- Wizard API actions are default-deny; only explicit public setup/auth actions
  may run anonymously. Initial password creation requires an unconfigured
  installation, checked against settings/environment before session overrides.
  Existing installations must authenticate with their configured password;
  wizard administrator creation is limited to the first administrator.
- MCP authentication compares only the SHA-256 hash of the presented token.
  Do not restore a plaintext fallback: stored hashes must never authenticate.
  Legacy plaintext tokens require regeneration in Preferences.
