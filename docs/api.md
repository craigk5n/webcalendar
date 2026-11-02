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

3. Run the API migrations **as the PHP web-server user**, with the same
   `WEBCALENDAR_*` environment/settings as the web application:

   ```sh
   php bin/api.php migrate --force
   ```

   This adds `webcal_api_users`, `webcal_api_tokens`, and `webcal_api_migrations`
   to the existing database. The identity table stores usernames and the
   authentication backend; it does not duplicate passwords. These optional
   tables are managed by API migrations rather than the web installer.

4. Set `WEBCALENDAR_API_ENABLED=true` in the PHP web-server environment.
   Restart the web service after changing its environment.

For the development Docker stack:

```sh
WEBCALENDAR_API_ENABLED=true docker compose -f docker/docker-compose-dev.yml up -d --build webcalendar
docker compose -f docker/docker-compose-dev.yml exec -T -u www-data webcalendar php bin/api.php migrate --force
```

The API reads the existing `includes/settings.php` or `WEBCALENDAR_USE_ENV`
configuration. Laravel does not load the repository's `.env` file; pass settings
through the server environment. The supplied Compose file reads
`WEBCALENDAR_API_ENABLED` from the host environment or Compose `.env` file.

Runtime storage defaults to an installation-specific directory under PHP's temp
directory. For persistent deployments, set `WEBCALENDAR_API_STORAGE` to an
absolute path outside the web document root, writable by the web-server user.
Use the same path and user for API CLI commands. With multiple web servers,
share this storage so rate limits are enforced across instances. Do not run
Laravel's configuration or route cache commands: the API adapts the current
WebCalendar configuration on startup.

Database-password and LDAP-password authentication are supported. HTTP-auth,
single-user, and other external authentication modes need a separate sign-in
adapter. LDAP requires the existing LDAP configuration and extension; password
verification and user lookup are delegated to that backend. The integration
suite exercises database-password authentication with a disposable SQLite
installation.

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

The Apache check creates a unique Compose project, mounts the checkout read-only,
and installs a synthetic account into a tmpfs SQLite database. It verifies token
issuance, profile/events reads, revocation, and missing/invalid/revoked token
rejection. The fixture container and data are removed on exit; it uses neither
the development database nor its accounts. CI runs this check alongside PHPUnit.
The events fixture contains weekly and monthly weekday rules and asserts the
returned occurrence dates, rather than checking only an empty collection. API
integration tests additionally cover weekly exceptions, COUNT-limited series,
and weekday-based recurrence across DST.
