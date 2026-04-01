# Configuration Reference

This document covers all configuration methods for WebCalendar.

## Table of Contents

- [Configuration Sources](#configuration-sources)
- [Settings File](#settings-file)
- [Environment Variables](#environment-variables)
- [Database Settings](#database-settings)
- [Application Settings](#application-settings)
- [User Preferences](#user-preferences)

## Configuration Sources

WebCalendar loads configuration in this order:

1. `includes/settings.php` — created by the installation wizard
2. Environment variables — override settings.php when `WEBCALENDAR_USE_ENV=true`

The wizard (`wizard/index.php`) creates `includes/settings.php` during
installation. For containerized deployments, environment variables are
preferred.

## Settings File

The file `includes/settings.php` contains database connection parameters
and core application settings. It is a PHP file that sets global variables.

Key variables:

| Variable | Description | Example |
|----------|-------------|---------|
| `$db_type` | Database backend | `mysqli`, `postgresql`, `sqlite3` |
| `$db_host` | Database hostname | `localhost`, `db.example.com` |
| `$db_database` | Database name or SQLite file path | `webcalendar`, `/data/webcal.db` |
| `$db_login` | Database username | `webcalendar` |
| `$db_password` | Database password | |
| `$db_persistent` | Use persistent connections | `true`, `false` |
| `$db_cachedir` | Query cache directory | `/tmp/webcal_cache` |
| `$user_inc` | Authentication module | `user.php`, `user-ldap.php` |
| `$use_http_auth` | Enable HTTP authentication | `true`, `false` |
| `$single_user` | Single-user mode username | `admin` (or empty) |
| `$readonly` | Read-only mode | `Y`, `N` |

## Environment Variables

Set `WEBCALENDAR_USE_ENV=true` to use environment variables instead of
`includes/settings.php`. This is the recommended approach for Docker and
cloud deployments.

| Environment Variable | Maps To | Required |
|---------------------|---------|----------|
| `WEBCALENDAR_USE_ENV` | Enables env-based config | Yes (to use env vars) |
| `WEBCALENDAR_DB_TYPE` | `$db_type` | Yes |
| `WEBCALENDAR_DB_HOST` | `$db_host` | Yes (except SQLite) |
| `WEBCALENDAR_DB_DATABASE` | `$db_database` | Yes |
| `WEBCALENDAR_DB_LOGIN` | `$db_login` | Yes (except SQLite) |
| `WEBCALENDAR_DB_PASSWORD` | `$db_password` | Yes (except SQLite) |
| `WEBCALENDAR_DB_PERSISTENT` | `$db_persistent` | No |
| `WEBCALENDAR_MODE` | `$mode` | No (`dev` or `prod`) |

Apache `.htaccess` example:

```apache
SetEnv WEBCALENDAR_USE_ENV true
SetEnv WEBCALENDAR_DB_TYPE mysqli
SetEnv WEBCALENDAR_DB_HOST localhost
SetEnv WEBCALENDAR_DB_DATABASE webcalendar
SetEnv WEBCALENDAR_DB_LOGIN webcalendar
SetEnv WEBCALENDAR_DB_PASSWORD "your_password"
```

Docker Compose example:

```yaml
environment:
  WEBCALENDAR_USE_ENV: "true"
  WEBCALENDAR_DB_TYPE: mysqli
  WEBCALENDAR_DB_HOST: db
  WEBCALENDAR_DB_DATABASE: webcalendar
  WEBCALENDAR_DB_LOGIN: webcalendar
  WEBCALENDAR_DB_PASSWORD: webcalendar
```

## Database Settings

Database type values for `$db_type` / `WEBCALENDAR_DB_TYPE`:

| Value | Database | Status |
|-------|----------|--------|
| `mysqli` | MySQL / MariaDB | Supported |
| `postgresql` | PostgreSQL | Supported |
| `sqlite3` | SQLite 3 | Supported |
| `oracle` | Oracle 8+ | Legacy — broken on PHP 8 (deprecated OCI API) |
| `ibm_db2` | IBM DB2 | Legacy — untested on PHP 8 |
| `odbc` | ODBC | Legacy — untested on PHP 8 |
| `ibase` | Interbase | Legacy — extension removed from PHP 8 |
| `sqlite` | SQLite (v2) | Legacy — extension removed from PHP 8 |

## Application Settings

Application-wide settings are stored in the `webcal_config` table and
managed through the admin interface (`admin.php`). These control features,
display options, and behavior for all users.

Common settings (partial list):

| Setting | Values | Description |
|---------|--------|-------------|
| `WEBCAL_PROGRAM_VERSION` | version string | Current WebCalendar version |
| `ALLOW_HTML_DESCRIPTION` | `Y`/`N` | Allow HTML in event descriptions |
| `DISABLE_ACCESS_FIELD` | `Y`/`N` | Hide access level on events |
| `DISPLAY_WEEKENDS` | `Y`/`N` | Show weekends in views |
| `WORK_DAY_START_HOUR` | `0`-`23` | Start of work day |
| `WORK_DAY_END_HOUR` | `0`-`23` | End of work day |
| `REQUIRE_APPROVALS` | `Y`/`N` | Require event approval |
| `PUBLIC_ACCESS` | `Y`/`N` | Allow anonymous calendar access |
| `SEND_EMAIL` | `Y`/`N` | Enable email notifications |
| `MCP_SERVER_ENABLED` | `Y`/`N` | Enable MCP server for AI assistants |
| `MCP_RATE_LIMIT` | integer | MCP requests per minute limit |

Defaults for all settings are defined in
`wizard/shared/default_config.php`.

## User Preferences

Per-user settings are stored in the `webcal_user_pref` table and managed
through the preferences page (`pref.php`). Each user can override system
defaults for display and notification settings.

Common preferences:

| Preference | Description |
|------------|-------------|
| `LANGUAGE` | Display language |
| `TIMEZONE` | User timezone |
| `DATE_FORMAT` | Date display format |
| `TIME_FORMAT` | 12-hour or 24-hour |
| `DISPLAY_WEEKENDS` | Show/hide weekends |
| `WORK_DAY_START_HOUR` | Work day start |
| `WORK_DAY_END_HOUR` | Work day end |
| `WEEK_START` | First day of week (0=Sun, 1=Mon) |
| `FONTS` | Font preferences |
