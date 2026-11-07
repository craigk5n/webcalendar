# Mobile API

WebCalendar's opt-in JSON API uses Laravel and Sanctum device tokens. It supports
signing in, signing out, reading the current user's profile and events, and
creating/editing supported non-recurring events on personal and assistant calendars.

## Selecting a calendar

`GET /api.php/v1/calendars` returns the authenticated user's calendar followed by
calendars for which that user is explicitly listed as an assistant. Nonuser
calendars are supported without a corresponding API identity. Administrator or
nonuser-administrator status alone does not add calendars to this list; assign
the account as an assistant in the browser's Assistants screen.

Each item in `data` contains `id` (the calendar login/primary key), `name`,
`timezone`, integer `week_start`, and Boolean `can_write`. Calendar preferences
override installation defaults for timezone and week start, including for nonuser
calendars. The signed-in account's function permissions still apply; choosing an
administrator's calendar does not confer administrator privileges.
If TIMEZONE is absent or empty, selection preserves the established installation
fallback (SERVER_TIMEZONE, then PHP's initial default timezone) before applying
the selected calendar's preference. The assistant's personal timezone is not an
installation default.

Use the selected ID as a URL-encoded path segment:

| Method | Path relative to `/api.php/v1` | Behavior |
| --- | --- | --- |
| GET | `/calendars/{calendar}/events` | Read occurrences with `start_date` and `end_date` |
| POST | `/calendars/{calendar}/events` | Create on the selected calendar |
| PATCH | `/calendars/{calendar}/events/{event}` | Edit a supported selected-calendar event |

These endpoints use the existing event payloads, token abilities, and date-window
limits. Bounds, floating dates, and timed-write date validation use the selected
calendar's timezone. Event responses include `calendar_id` and `calendar_name`.
`GET /users/current` continues to describe the authenticated person.

Assistant access and calendar existence/enabled status are rechecked on each
request and inside each write attempt. Unauthorized selections return 403.
When UAC is enabled, delegated reads require calendar-level view authorization
and filter occurrences by the event/visibility permission mask. Delegated writes
also require calendar-level edit authorization; editing checks both the selected
calendar and the event creator, including the existing and requested visibility.
The same edit policy controls can_edit, and calendar discovery reflects calendar
edit denial in can_write. Permission snapshots are refreshed on every activation,
including retry attempts. Private and confidential events are available only
within the applicable permission masks. Assistants may
edit supported events created by other people on that calendar; accepted status,
single-calendar participation, and the existing non-recurring/unlinked restrictions
still apply. New entries belong to the selected calendar; activity logs identify
the signed-in assistant separately from the target calendar.

The independent UAC **see time only** restriction is also enforced. This API
returns detail-bearing events rather than a busy-only representation, so delegated
event reads return 403 when effective time-only access applies, for both ordinary
and recurring events. Calendar discovery reports `can_write: false`; POST/PATCH
are denied too, since their responses contain event details. Internal conflict
checks still include hidden busy intervals. Personal-calendar access and the
configured UAC administrator override retain their established behavior.

Creation requires both view and edit permission for the effective visibility,
including DEFAULT_VISIBILITY when `access` is omitted. A public-only view mask
therefore cannot create a private event even with a full edit mask. Rejection
occurs before entry/participation/activity inserts. Successful create and update
responses calculate `can_edit` using the shared mutation policy.

The iOS app remembers one active calendar per server/account on the device. All
event browsing and saving uses that calendar and its timezone. Switching invalidates
cached views and open drafts. If access is removed, the app asks for a new selection
instead of silently switching writes to the personal calendar. Older servers that
return 404 for calendar discovery retain personal-calendar access.

No new migration is required. Deploy the updated API before the updated client;
clear/rebuild compiled route caches when deploying the new routes.

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

### Dependency security updates

The November 2026 security update locks `symfony/http-foundation`,
`symfony/http-kernel`, `symfony/routing` and the development-only
`symfony/filesystem` to 7.4.23. It resolves seven upstream advisories, including
CVE-2026-118514, CVE-2026-118516 and the joined-script-path parsing issue
GHSA-3h27-hjv7-fqjx. Install from the updated lock file on each API host:

```sh
composer install --no-dev --optimize-autoloader
composer audit --locked --no-dev
php bin/api.php route:clear
```

Run the API command as the PHP web-server user with the same private environment
configuration. If the deployment uses a compiled route cache, rebuild it after
clearing it: the routing fix changes generated matchers. Follow the host's normal
PHP worker/OPcache reload procedure when deploying dependency updates. CI also
audits development dependencies; do not suppress the advisory gate.

Regression tests cover HEAD authentication/read abilities, malformed script-name
prefixes (literal and encoded), valid subdirectory installations, and untrusted
forwarded headers. Apache smoke tests check HEAD authorization and rejection of
joined script paths through the actual server. The canonical API URL remains
`/api.php/v1`; `/api.phpv1` is not an alternative endpoint.

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
| POST | `/events` | Create a personal, non-recurring event |
| PATCH | `/events/{event}` | Update a supported personal event by its integer ID |

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

New tokens receive `events:read` and `events:write`; clients cannot request
additional abilities. Existing read-only tokens are not upgraded: sign out and
sign in again to obtain a write-capable token. A token ability never overrides
WebCalendar's current permissions or read-only installation setting, which are
checked on each write. Profile reads and current-token revocation require a valid token.
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

### Read the current user

`GET /users/current` returns the authenticated profile and its effective
first-weekday preference:

```json
{
  "data": {
    "username": "alice",
    "name": "Alice Example",
    "email": "",
    "week_start": 0,
    "timezone": "Europe/London"
  }
}
```

`week_start` is an integer from 0 (Sunday) through 6 (Saturday), matching
WebCalendar's **Preferences → Week starts on** setting. It uses the user's
override when present, otherwise the installation default (built-in: Sunday).
The effective value is resolved on every profile request, so clients can pick up
web preference changes when revalidating a session. This is a read-only field;
change the preference in the web application. It does not specify the minimum
days in the first week or a week-numbering convention.

Older API deployments omit `week_start`; clients should retain a local fallback
for those installations. This addition requires no database migration.

`timezone` is the account's effective timezone, resolved using the installation
default and user preferences. Use it to initialize an event editor and determine
the calendar date corresponding to a timed event. Older read-only API deployments
omit this field. This is the account timezone, not a separately stored timezone
per event.

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
      "timezone": "UTC",
      "can_edit": true
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
overlay and other users' calendars are not included by this personal endpoint.
Use the calendar-scoped routes above for assistant delegation.
WebCalendar's event-view permission still applies. Calendar selector
parameters (`user`, `username`, `calendar`) are rejected. The bounded collection
is returned without pagination; clients can request smaller date windows.

`can_edit` is an effective capability for the current token/account. It is false
for read-only tokens/installations, insufficient view/edit permissions, other
creators' events, invitations, shared events, and recurring or detached records.
The write endpoint rechecks eligibility; clients must handle a rejection even
when a previously fetched occurrence had `can_edit: true`.

For a non-recurring event spanning midnight, its starting-day occurrence retains
the complete stored start time and duration. Later days contain read-only display
continuations, including when the requested range starts after the event began.
Their time/duration describe the portion on that day; edit the starting-day event,
not a continuation fragment.

### Create an event

`POST /events` requires an `events:write` token. It creates a one-off event with
the authenticated account as its creator and sole accepted participant.

```http
POST /api.php/v1/events
Authorization: Bearer <token>
Content-Type: application/json

{
  "title": "Appointment",
  "description": "Bring notes",
  "location": "Office",
  "date": "2026-11-04",
  "all_day": false,
  "untimed": false,
  "starts_at": "2026-11-04T09:00:00+00:00",
  "duration_minutes": 37
}
```

A successful response has status **201** and contains the canonical saved event
inside `data`, with the same fields as a `GET /events` occurrence. The server
chooses the ID. The event can immediately be read through the agenda endpoint.

| Field | Contract |
| --- | --- |
| `title` | Required nonblank text, at most 80 characters; HTML tags are not accepted |
| `date` | Required `YYYY-MM-DD` account calendar date |
| `all_day`, `untimed` | Required booleans; both cannot be true |
| `starts_at` | Required for timed events; null or omitted for all-day/untimed events |
| `duration_minutes` | Timed: positive integer, at most 133,920 minutes (93 days by default); all-day: 1440; untimed: 0. Can be omitted for non-timed events |
| `description` | Optional stored description; null clears it to an empty string. Safe HTML is retained through the existing sanitizer when rich descriptions are enabled; dangerous markup is removed. The stored UTF-8 value must fit 65,535 bytes |
| `location` | Optional text, at most 100 characters, or null; HTML tags are not accepted |
| `access` | Optional `P` (public), `R` (private), or `C` (confidential). Defaults to the account's effective `DEFAULT_VISIBILITY` |

With both timing flags false, the event is timed. The timestamp must include
seconds equal to `00` and an explicit offset (`Z` or `+/-HH:MM`), without
fractional seconds. Offsets from `-14:00` through `+14:00` are accepted; the
timestamp identifies the exact instant, and its calendar date in the
**account timezone** must equal `date`.
The server stores timed values in UTC and returns them in the account timezone.
Both the account calendar date and the stored UTC date must remain within years
0001 through 9999.
Clients resolve daylight-saving wall times before submitting the explicit
instant, including the intended offset during a repeated autumn hour. Naive
timestamps are rejected rather than guessed. Minute values need not be multiples
of five.

All-day and untimed events use floating calendar dates, without UTC conversion:

```json
{"title":"Day off","date":"2026-11-04","all_day":true,"untimed":false}
```

```json
{"title":"Read proposal","date":"2026-11-04","all_day":false,"untimed":true}
```

WebCalendar represents all-day events as time `000000` and duration 1440. A timed
event starting at UTC midnight with exactly that duration cannot be distinguished
from all-day storage, so that timed combination is rejected with 422.

Unsupported/unknown fields are rejected, including calendar selectors,
participants, recurrence rules, creator, IDs and status. Text fields reject NUL
bytes for compatibility across supported databases. Each successful POST
creates a new event; do not automatically retry after an uncertain network result.

### Edit a personal event

`PATCH /events/{event}` requires `events:write`, current view/edit permission,
and a writable installation. The record must be an accepted, non-recurring
event created by the authenticated user, with that user as its only participant
and no external guests. Recurring series, recurrence exceptions/detached records,
tasks, journals, shared events and invitations are not editable through this API.
Legacy cross-day extension records and their parents are also excluded, since
editing those safely requires maintaining the linked records together.

```http
PATCH /api.php/v1/events/42
Authorization: Bearer <token>
Content-Type: application/json

{"title":"Updated appointment","location":null}
```

A successful response has status **200** and returns the complete saved event in
`data`. Supply at least one supported field. Omitted fields remain unchanged;
only `description` and `location` can be cleared with null. Privacy, priority,
URL, participants, categories, reminders, attachments and site extras are retained
unless an explicitly supported field is changed. The API updates the original
record instead of deleting/recreating its satellite rows.

Reminder configuration is preserved. Rescheduling resets `cal_times_sent` and
`cal_last_sent` for a relative reminder only when its effective trigger instant
changes: start-relative alarms follow the start, while end-relative alarms also
follow duration changes. Metadata-only edits, equivalent timestamps, and edits
that preserve the relevant anchor instant retain delivery state. Absolute alarms
retain both their timestamp and delivery state, avoiding a duplicate delivery of
an already-sent alarm. Reminder re-arming commits or rolls back with the event.
Anchor comparisons follow the existing reminder sender: timed starts use UTC,
all-day/untimed starts use midnight in the installation's reminder-runtime
timezone, and event ends use the sender's separate UTC calculation plus duration.
The API captures that installation timezone before loading account preferences;
an account timezone override does not redefine floating reminder triggers.

Timing edits are merged with the current stored schedule and validated as a
whole. Moving a timed event to a different `date` requires an explicit
`starts_at`; changing the date alone does not guess a new wall time or DST offset.
To convert a timed event to all-day, send `all_day: true`, `untimed: false`,
`starts_at: null`, and `duration_minutes: 1440`. To make it untimed, send
`all_day: false`, `untimed: true`, `starts_at: null`, and `duration_minutes: 0`.

Unknown/unavailable event IDs return 404. A visible event created by somebody
else returns 403. Unsupported owned events return 422 with
`code: "unsupported_event"`. Rejected writes do not change the event.

### Write policies and storage

When scheduling conflicts are disabled, writes enforce overlaps and the
configured daily appointment limit, including existing recurring events,
overnight spans and account-local all-day dates. Conflict responses use 409 with
`code: "schedule_conflict"`; they contain no HTML conflict list. There is no
conflict-override parameter in this version. `DISABLE_CROSSDAY_EVENTS` is also
respected. Title/description/location-only patches do not reschedule the event
or fail because of a pre-existing scheduling conflict.

The duration limit is configured in `app/Api/config/webcalendar.php`. Conflict
checking expands existing recurrence rules in bounded windows. If an existing
recurring event exceeds the supported duration limit, scheduling writes return
409 with `code: "unsupported_schedule"` instead of omitting possible overlaps.

Event insertion/update, owner participation and activity logging use one database
transaction. API writes first lock the installation's `webcal_config` WEBCAL_PROGRAM_VERSION
row, then the authenticated account's `webcal_api_users` row, before reading
schedules, and hold protection through commit. Edits acquire these locks before
the event lock. This deliberately serializes all API event writes, including
different assistants using a nonuser calendar, without a new mutex table or
requiring a target-calendar API identity. API bootstrap already requires the
version row for every supported installation; a physical TIMEZONE row is optional.
Browser and MCP writers
do not participate in this calendar-lock protocol. SQLite obtains database-wide
write intent before snapshot reads using an early managed update, including on
PHP 8.2/8.3, rather than relying on a version-dependent `IMMEDIATE` setting.

Recognized SQLite busy/locked errors, MySQL lock timeouts/deadlocks, PostgreSQL
serialization/deadlock/lock-unavailable errors, and event/activity ID collisions
retry the complete transaction only after successful rollback. Every attempt
reacquires protection, reloads the edited record, and reruns schedule checks.
There are at most five attempts with 20/40/80/160 ms backoff; SQLite's per-operation
busy timeout is 250 ms. Exhaustion returns 503 with `code: "calendar_busy"`.
Explicit PostgreSQL serialization/deadlock aborts (`40001`/`40P01`) at COMMIT
remain retryable after transaction-state cleanup even if PDO already reports no
active transaction: these are confirmed server aborts, not uncertain commits.
Unrelated SQL errors, failed rollbacks, and indeterminate connection/commit
failures are not retried. A lost response can still follow a committed write;
clients must check the calendar rather than blindly repeat a creation request.

MySQL/MariaDB installations must use **InnoDB** for `webcal_config`, `webcal_api_users`,
`webcal_entry`, `webcal_entry_user`, and `webcal_entry_log`. Re-arming a reminder
also requires InnoDB for `webcal_reminders`. Non-transactional
legacy tables return 503 with `code: "transactional_storage_required"`; convert
those tables through the installation's normal database-maintenance process.
These endpoints require no new API migration beyond the existing API tables.

## Errors and limits

| Status | Meaning |
| --- | --- |
| 401 | Missing/invalid/expired token, invalid credentials, or unavailable user |
| 403 | Missing token ability or WebCalendar event-view permission |
| 404 | Unknown endpoint or API disabled |
| 409 | Scheduling conflict or unsupported conflict-checking range; inspect `code` |
| 422 | Invalid input (`errors`) or unsupported event editing (`code`) |
| 429 | Rate limit exceeded; honor `Retry-After` |
| 503 | API dependencies/configuration/storage are unavailable, or write contention exhausted retries; inspect `code` |

Sign-in is limited to 10 requests per minute per client IP. The API as a whole
is limited to 120 requests per minute per client IP. Limits, token lifetime,
and maximum date range are defined in `app/Api/config/webcalendar.php`.
The API does not trust forwarded client-IP headers by default; configure trusted
proxies deliberately before using client-IP rate limits behind a reverse proxy.

This version uses bearer tokens only. Existing browser/MCP credentials do not
authenticate API requests. Recurrence writes, participant actions, delegated
calendars, browser session/CSRF integration and OAuth flows are future additions.

## Tests

Run the API integration tests with the locked Composer development dependencies:

```sh
php vendor/bin/phpunit tests/ApiIntegrationTest.php
```

The suite covers personal-event create/edit/read round-trips, read-only tokens
and installations, account permissions and ownership, unsupported event types,
metadata preservation, exact-minute durations, DST, floating dates in positive
and negative offset timezones, full overnight schedules/read-only continuations,
scheduling limits, out-of-range IDs/UTC years, transaction rollback, and both
event/activity ID collision retries. Independent-process/connection tests use
explicit pipe barriers for competing creates, edits to different events, daily
limits, SQLite contention/retry exhaustion, and fresh-state revalidation. Reminder
tests cover relative/absolute delivery state, no-op schedules and rollback;
failure-injection tests cover commit uncertainty and failed rollbacks. Fixtures
use disposable databases and synthetic accounts.

`tests/fixtures/api-postgres-commit-abort.php` separately exercises actual
PostgreSQL SERIALIZABLE commit failures through two synchronized connections,
including successful retry, a newly committed scheduling conflict, exhaustion,
managed/PDO cleanup and atomic entry/log persistence. Run it with
`php tests/fixtures/api-postgres-commit-abort.php` inside a disposable environment
configured through `WEBCALENDAR_*`: driver `postgresql`, database `api_smoke_db`,
API enabled/migrated, and an `api-smoke` account with an issued API token (the
HTTP database smoke setup supplies this). It inserts synthetic appointments and
updates fixture scheduling settings; it is not part of the SQLite PHPUnit run.

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
