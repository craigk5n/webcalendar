# Mobile API

WebCalendar's opt-in JSON API uses Laravel and Sanctum device tokens. The initial
API supports signing in, signing out, reading the current user's profile, and
reading events on that user's calendar.

## Installation

1. Complete the normal WebCalendar installation or upgrade first.
2. Install the locked Composer dependencies in the WebCalendar root:

   ```sh
   composer install --no-dev --optimize-autoloader
   composer check-platform-reqs --no-dev
   ```

   The API requires PHP 8.2+, PDO, and the PDO driver for your database
   (`pdo_mysql`, `pdo_pgsql`, or `pdo_sqlite`), in addition to WebCalendar's
   existing database extension. Release archives include the API source, but
   Laravel and Sanctum must be installed with Composer to enable it.

3. Configure the API using the [private environment file](#private-environment-file-shared-hosting-and-plesk)
   or the PHP web-server environment. Set `WEBCALENDAR_API_ENABLED=true` to
   enable API requests. File changes are read on the next request; changes to
   server environment settings require reloading the corresponding service.

4. Run the API migrations **as the PHP web-server user**, with the same
   configuration as the web application:

   ```sh
   php bin/api.php migrate --force
   ```

   This adds `webcal_api_users`, `webcal_api_tokens`, and `webcal_api_migrations`
   to the existing database. The identity table stores usernames, the
   authentication backend, and a hashed subject binding; it does not duplicate
   passwords. These optional tables are managed by API migrations rather than
   the web installer. A single initial migration creates the identity table
   with subject bindings and the device-token table together.

For the development Docker stack, the existing server-environment configuration
continues to work:

```sh
WEBCALENDAR_API_ENABLED=true docker compose -f docker/docker-compose-dev.yml up -d --build webcalendar
docker compose -f docker/docker-compose-dev.yml exec -T -u www-data webcalendar php bin/api.php migrate --force
```

The supplied Compose file reads `WEBCALENDAR_API_ENABLED` from the host
environment or Compose `.env` file and forwards it into PHP. Explicit server
environment values take precedence over the private file, including an explicit
`false` value. Docker Compose interpolation and the API's private file loader
are independent mechanisms.

### Private environment file (shared hosting and Plesk)

Both `api.php` and `bin/api.php` automatically look for:

```text
../private/<installation-directory>/.env
```

The location is relative to the physical WebCalendar installation directory,
not the current shell directory or the incoming hostname. The file is parsed
using Laravel's DotEnv dependency before the API enable flag and storage path
are evaluated. No PHP-FPM environment override is required.

For a Plesk site whose document root is `calendar.example.com` under its home
directory, create this layout using File Manager:

```text
Home directory/
├── private/
│   └── calendar.example.com/
│       ├── .env
│       └── storage/                 (created automatically when writable)
└── calendar.example.com/             (public document root)
    ├── api.php
    ├── app/
    ├── bin/
    └── includes/
```

Copy `docs/api.env.example` to the private `.env`, or create it with:

```dotenv
WEBCALENDAR_API_ENABLED=true
```

Runtime storage then defaults to `storage/` beside that `.env`. PHP must be able
to read the private file and create/write the storage directory; use the
domain's system user for both deployment and migration commands. On Plesk with
PHP 8.3, run this from the WebCalendar installation directory:

```sh
/opt/plesk/php/8.3/bin/php bin/api.php migrate --force
```

The private directory must be outside **every public document root**. If the
application is installed in a subdirectory of a larger public directory, do not
assume its sibling `private/` directory is protected: adjust the deployment
layout so the convention resolves outside the public tree. HTTP startup rejects
configuration or storage that resolves inside the installation or the current
document root, including symlinks into those directories.

Path validation checks both the normalized access path and its canonical
target. Public-facing symlinks pointing outward to private targets are also
rejected, along with intermediate aliases that enter the public tree. Storage
and its runtime subdirectories are validated before creation and checked again
afterward. Storage overrides must be absolute filesystem paths.

Symlinks are expanded one hop at a time, including relative destinations based
on the link's containing directory. Each traversed destination is checked before
following another link, so a private link pointing to a public link that points
back outside is rejected. Dot segments are processed in filesystem traversal
order. Repeated resolution states and a bounded expansion count reject cycles;
broken links are not treated as missing directories to create.

Required components must be inspectable. Canonical boundary paths prove their
own non-symlink ancestors, allowing restricted parent metadata access where that
proof is available. Other inaccessible components fail closed. Missing storage
children are permitted only when absence can be established in an inspectable
private parent, and only after existing symlink destinations have been resolved.

A supplied HTTP document-root boundary must itself be a resolvable directory.
If `open_basedir` permits child directories but prevents resolving their public
parent, startup fails cleanly rather than skipping that boundary. CLI commands
have no authoritative broader HTTP document root; they always protect the
installation boundary, but the deployment must still place configuration and
storage outside every site's public tree. These checks do not discover unrelated
web-server aliases or block direct HTTP access through aliases an administrator
has configured elsewhere. Remove public aliases to private configuration/storage.

The public installation's `.env` is deliberately ignored. Private file values
are kept in an API-only configuration repository; they do not replace
WebCalendar's existing `includes/settings.php`, enable `WEBCALENDAR_USE_ENV`, or
change database credentials. Existing real environment-based database settings
continue to work. The supported API file settings are
`WEBCALENDAR_API_ENABLED`, `WEBCALENDAR_API_STORAGE`, and the optional
`WEBCALENDAR_API_KEY`; no encryption key is required for bearer-token sign-in.

If `WEBCALENDAR_API_STORAGE` is supplied in the private file or server
environment, it overrides the storage default. Use an absolute path. If there
is no private file and no explicit storage setting, the existing
installation-specific temporary storage default is retained. CLI and HTTP must
use the same owner and configuration. With multiple web servers, share the
storage so rate limits are enforced across instances.

Missing private files are optional. Files PHP can locate but cannot read, or
files with malformed syntax, fail startup without returning their contents.
PHP filesystem restrictions such as `open_basedir` must permit the private
directory for file-based configuration; server-only configuration does not
require access to that optional directory. A deployment with a private configuration
file still requires Composer dependencies. Do not run Laravel's configuration
or route cache commands: the API adapts the current WebCalendar configuration
on startup.

Database-password and LDAP-password authentication are supported. HTTP-auth,
single-user, and other external authentication modes need a separate sign-in
adapter. LDAP requires the existing LDAP configuration and extension; password
verification and user lookup are delegated to that backend. The integration
suite exercises database-password authentication with a disposable SQLite
installation.

LDAP API access additionally requires the directory reader to return the user's
immutable `entryUUID` (OpenLDAP) or `objectGUID` (Active Directory). These
attributes are requested explicitly. A username or DN alone is not accepted as
an account identity; an unreadable/missing immutable identifier returns 503.
The binding includes the configured directory server, port, and base DN.
Replacing a directory entry under the same username invalidates its old tokens.

### Apache authorization forwarding

The web server must pass the `Authorization` header to PHP. Otherwise sign-in
can return 201 and issue a valid token, while the next profile request returns
401 because PHP never receives the bearer token.

Both supplied Docker images enable `docker/apache-api.conf`. Rebuild the web
image after changing this configuration. For another Apache 2.4.13+ installation,
use the equivalent configuration for its installation directory:

```apache
<Directory /path/to/webcalendar>
    <Files "api.php">
        CGIPassAuth On
    </Files>
</Directory>
```

Validate Apache's configuration and reload the service after making this change.

For shared hosting, including Plesk with **FPM application served by Apache**,
the supplied `.htaccess.example` provides the same forwarding directive:

```apache
<Files "api.php">
    CGIPassAuth On
</Files>
```

Copy `.htaccess.example` to `.htaccess` in the installation directory beside
the root `api.php`. If `.htaccess` already exists, merge the block into it rather
than replacing existing settings. The `bin/api.php` file is a CLI tool; placing
`.htaccess` in `bin/` does not configure the HTTP endpoint. The active `.htaccess`
is installation-specific and ignored by Git; the example ships in release archives.

In Plesk, use File Manager to edit this file when the additional Apache directives
fields are unavailable. Apache normally reads `.htaccess` on each request, so
no reload is needed. The host must permit the `AuthConfig` override (or explicitly
allow `CGIPassAuth`). If adding the block causes HTTP 500, remove it and inspect
the domain's Apache error log. A `CGIPassAuth not allowed here` diagnostic requires
the hosting provider to permit the override or configure forwarding for `api.php`.
With a fresh token, `GET /api.php/v1/users/current` should return 200.

Reverse proxies and FastCGI configurations must also preserve `Authorization`;
forward the original header rather than placing tokens in URLs. No rewrite
rules are required for the canonical `api.php/v1` path.

## Endpoints

The base URL is `https://calendar.example.com/api.php/v1`. For an installation
in a subdirectory, include that prefix, such as
`https://example.com/webcalendar/api.php/v1`. The server must pass PHP path
information to `api.php`; Apache supports this without rewrite rules. A reverse
proxy can expose a cleaner `/api/v1` URL if desired.

All responses are JSON. Successful responses wrap their payload in `data`.
Send `Content-Type: application/json` for sign-in. Protected endpoints require
`Authorization: Bearer <token>`. Use HTTPS for deployed apps.

| Method | Path | Purpose |
| --- | --- | --- |
| POST | `/tokens` | Verify credentials and issue a device token |
| GET | `/users/current` | Read the signed-in user's profile |
| DELETE | `/tokens/current` | Revoke the token used for this request |
| GET | `/events?start_date=2026-11-01&end_date=2026-11-30` | Read event occurrences |

### Sign in

```http
POST /api.php/v1/tokens
Content-Type: application/json

{"username":"alice","password":"your-password","device_name":"Alice's iPhone"}
```

A successful response has HTTP status `201`:

```json
{
  "data": {
    "token": "1|example-plaintext-token",
    "token_type": "Bearer",
    "expires_at": "2026-12-02T12:00:00+00:00"
  }
}
```

Store the token in iOS Keychain. Each sign-in creates a separate token, even if
the device name matches a previous sign-in. Only the SHA-256 hash is stored in
the database. Tokens expire after 30 days; this version requires signing in
again after expiration and has no refresh-token endpoint.

Tokens receive only the `events:read` ability; clients cannot request additional
abilities. Profile reads and current-token revocation require a valid token.
Revoking one token leaves other devices signed in. On sign-out, call
`DELETE /tokens/current`, then remove the token from Keychain. Disabling or
deleting a database-backed user denies their next request and revokes their
tokens. Changing authentication backends also invalidates existing tokens.

Database-account deletion revokes tokens at the deletion boundary, including
when no API request occurs before the username is recreated. Tokens are also
bound to the current database password hash; password changes or password-hash
upgrades require fresh sign-in on other devices. LDAP tokens use the immutable
directory-entry binding instead. Tokens without a valid subject binding are
rejected and require fresh sign-in. Revocation runs without Composer and is
skipped only when neither optional API credential table exists. An incomplete
schema or a revocation failure prevents account deletion.

To remove expired token records periodically, run as the web-server user:

```sh
php bin/api.php sanctum:prune-expired --hours=24
```

### Read events

Both dates are required, inclusive, and interpreted in the user's configured
timezone. A request may cover at most 93 days. The API returns accepted events
and pending invitations, expands recurring events using WebCalendar's
recurrence engine, and honors recurrence exceptions. Rejected and deleted
entries are omitted. Task and journal records are not included.

```json
{
  "data": [
    {
      "id": 42,
      "date": "2026-11-02",
      "title": "Appointment",
      "description": "Bring notes",
      "location": "Office",
      "starts_at": "2026-11-02T10:00:00+00:00",
      "duration_minutes": 60,
      "all_day": false,
      "untimed": false,
      "status": "A",
      "access": "P",
      "timezone": "UTC"
    }
  ]
}
```

For all-day and untimed events, `starts_at` is `null`; use `date` and the
corresponding flag. Repeating occurrences share an `id`; identify an occurrence
by `(id, date)`. `description` contains the stored description and may contain
HTML when rich descriptions are enabled; render it appropriately for the client.

The events endpoint is restricted to the authenticated user's calendar,
including their private events. Calendar layers, the automatic public-calendar
overlay, other users' calendars, and administrator/assistant delegation are not
included. WebCalendar's event-view permission still applies. Calendar selector
parameters (`user`, `username`, `calendar`) are rejected. The bounded collection
is returned without pagination; clients can request smaller date windows.

## Errors and limits

| Status | Meaning |
| --- | --- |
| 401 | Missing/invalid/expired token, invalid credentials, or unavailable user |
| 403 | Missing token ability or WebCalendar event-view permission |
| 404 | Unknown endpoint or API disabled |
| 422 | Validation failed; field errors are in `errors` |
| 429 | Rate limit exceeded; honor `Retry-After` |
| 503 | API dependencies or WebCalendar startup configuration are unavailable |

Sign-in is limited to 10 requests per minute per client IP. The API as a whole
is limited to 120 requests per minute per client IP. Limits, token lifetime,
and maximum date range are defined in `app/Api/config/webcalendar.php`.
The API does not trust forwarded client-IP headers by default; configure trusted
proxies deliberately before using client-IP rate limits behind a reverse proxy.

This version uses bearer tokens only. Existing browser/MCP credentials do not
authenticate API requests. Browser session/CSRF integration, event writes, and
OAuth authorization flows are separate future additions.

## Tests

Run the API integration tests with the locked Composer development dependencies:

```sh
php vendor/bin/phpunit tests/ApiIntegrationTest.php
```

Those tests use PHP's built-in server. Run the separate Apache deployment check
from the repository root to cover bearer-header forwarding:

```sh
sh tests/run-api-apache-tests.sh
```

The Apache check creates a unique Compose project with separate server-environment
and private-`.env` fixtures, mounts the checkout read-only, and installs synthetic
accounts into isolated tmpfs SQLite databases. It verifies token issuance,
profile/events reads, revocation, missing/invalid/revoked token rejection, and
shared private storage for CLI and HTTP. The fixture containers and data are
removed on exit; it uses neither the development database nor its accounts.
CI runs this check alongside PHPUnit.
Two additional Apache fixtures deliberately enable symlink following and serve
harmless configuration/storage canaries through outward public aliases. They
verify that those aliases are actually web-reachable, then require API startup
to reject them with clean JSON and no newly created runtime files. The fixtures
contain no deployment secrets or real calendar data.
A separate fixture disables the Docker forwarding configuration and installs
`.htaccess.example` as the root `.htaccess` with `AllowOverride AuthConfig`, then
checks token issuance, authenticated reads, and revocation through Apache.
Three private-starting chain variants cover configuration, existing storage,
and missing storage children: the configured private link points through the
web-reachable public alias before reaching its private final target.
The events fixture contains weekly and monthly weekday rules and asserts the
returned occurrence dates, rather than checking only an empty collection. API
integration tests additionally cover weekly exceptions, COUNT-limited series,
and weekday-based recurrence across DST.
