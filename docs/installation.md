# WebCalendar v1.9.16 Installation Guide

## Table of Contents

- [Requirements](#requirements)
- [Installation Methods](#installation-methods)
  - [Web Wizard](#web-wizard)
  - [Headless CLI](#headless-cli)
  - [Docker](#docker)
  - [Environment Variables](#environment-variables)
- [Configuration](#configuration)
- [Authentication Backends](#authentication-backends)
- [Composer and Assets](#composer-and-assets)
- [Upgrading](#upgrading)

## Requirements

**PHP**: 8.0 minimum, 8.2+ recommended (CI tests against 8.2, 8.3, 8.4)

**PHP Extensions** (required):
- A database driver: `mysqli`, `pgsql`, `sqlite3`, `oci8`, `ibm_db2`,
  `odbc`, or `interbase`
- `mbstring`
- `openssl`
- `session`
- `json`

**PHP Extensions** (optional):
- `gd` -- gradient backgrounds in calendar views

**Supported Databases** (via `includes/dbi4php.php`):
- MySQL / MariaDB (`mysqli`) — recommended, most widely tested
- PostgreSQL (`postgresql`) — fully supported
- SQLite3 (`sqlite3`) — good for small installations, no server needed

Legacy database backends (code present but **not tested on PHP 8**):
- Oracle (`oracle`) — uses deprecated OCI API removed in PHP 8
- IBM DB2 (`ibm_db2`) — requires PECL extension, untested
- ODBC (`odbc`) — untested, no dedicated table creation SQL
- Interbase/Firebird (`ibase`) — extension removed from PHP 8 core

## Installation Methods

### Web Wizard

The web-based installer lives at `wizard/index.php`. When no
`includes/settings.php` file exists, WebCalendar automatically
redirects to the wizard.

The wizard is a Bootstrap 5 single-page application with 10 steps:

1. Welcome
2. PHP Settings check
3. Database Settings
4. Create Database
5. Create/Upgrade Tables
6. Authentication
7. Admin User
8. Application Settings
9. Summary
10. Finish

On completion the wizard writes `includes/settings.php`.

### Headless CLI

For automated or Docker deployments, use the CLI installer:

```bash
php wizard/headless.php \
  --db-type=mysqli \
  --db-host=localhost \
  --db-login=root \
  --db-password=secret \
  --db-database=webcalendar \
  --admin-login=admin \
  --admin-password=admin123
```

Additional options:

| Flag | Description |
|------|-------------|
| `--db-cachedir=PATH` | Cache directory (optional) |
| `--user-auth=METHOD` | Auth method: `web`, `http`, `none` |
| `--user-db=BACKEND` | Backend file, e.g. `user-ldap.php` |
| `--single-user=LOGIN` | Single-user login (if auth=none) |
| `--admin-email=EMAIL` | Admin email (optional) |
| `--install-password=PASS` | Wizard/install password |
| `--readonly` | Enable read-only mode |
| `--dev-mode` | Enable development mode |
| `--use-env` | Read DB settings from env vars |
| `--from-settings` | Use existing `settings.php` |
| `--force` | Overwrite existing settings |

Exit codes: 0=success, 1=missing params, 2=DB connect failed,
3=DB create failed, 4=table create/upgrade failed,
5=admin user create failed, 6=settings write failed.

### Docker

Run all `docker compose` commands from the top-level WebCalendar
directory, not from the `docker/` subdirectory.

**Production** (MariaDB, port 8080):

```bash
docker compose -f docker/docker-compose-php8.yml up
```

**Development** (MariaDB on 8080, PostgreSQL on 8081):

```bash
docker compose -f docker/docker-compose-php8-dev.yml up
```

**SQLite Development** (port 8081):

```bash
docker compose -f docker/docker-compose-sqlite-dev.yml up
```

Shell access into a running container:

```bash
docker compose -f docker/docker-compose-php8.yml \
  exec webcalendar-php8 /bin/sh
```

### Environment Variables

For containerized or automated deployments you can skip
`includes/settings.php` entirely. Set `WEBCALENDAR_USE_ENV=true`
and provide the following variables:

| Variable | Example |
|----------|---------|
| `WEBCALENDAR_USE_ENV` | `true` |
| `WEBCALENDAR_DB_TYPE` | `mysqli` |
| `WEBCALENDAR_DB_HOST` | `db-mariadb` |
| `WEBCALENDAR_DB_DATABASE` | `webcalendar` |
| `WEBCALENDAR_DB_LOGIN` | `webcalendar` |
| `WEBCALENDAR_DB_PASSWORD` | `secret` |
| `WEBCALENDAR_DB_PERSISTENT` | `true` |
| `WEBCALENDAR_INSTALL_PASSWORD` | MD5 hash of password |
| `WEBCALENDAR_USER_INC` | `user.php` |
| `WEBCALENDAR_MODE` | `dev` or `prod` |

Generate the install password hash:

```bash
php -r "echo md5('YourPassword');"
```

## Configuration

The wizard writes `includes/settings.php`. This file contains
database connection details, authentication method, and install
password. Key settings:

| Setting | Description |
|---------|-------------|
| `db_type` | Database driver name |
| `db_host` | Database hostname |
| `db_database` | Database name or SQLite file path |
| `db_login` | Database username |
| `db_password` | Database password |
| `db_persistent` | Use persistent connections |
| `db_cachedir` | Cache directory path |
| `user_inc` | Auth backend file |
| `single_user` | Single-user mode (`Y`/`N`) |
| `readonly` | Read-only mode (`Y`/`N`) |
| `use_http_auth` | HTTP authentication |
| `mode` | `dev` or `prod` |

## Authentication Backends

Configured during installation via the wizard's Authentication
step or the `--user-db` flag in headless mode.

| Backend | File | Notes |
|---------|------|-------|
| Database | `user.php` | Default; users stored in `webcal_user` |
| LDAP | `user-ldap.php` | LDAP/Active Directory |
| IMAP | `user-imap.php` | IMAP server authentication |
| NIS | `user-nis.php` | Network Information Service |
| Joomla | `user-app-joomla.php` | Joomla CMS integration |

## Composer and Assets

Releases ship with front-end dependencies pre-built in the `pub/`
directory. Composer is only needed for development.

```bash
composer install
make              # copies vendor assets to pub/
```

The `make` target requires `sha384sum` (Linux). It copies
Bootstrap, jQuery, and other front-end assets from `vendor/` into
`pub/` where the application loads them.

## Upgrading

The web wizard and headless installer both detect the current
database version and apply any needed upgrade SQL automatically.
Run the wizard (web or CLI) against your existing database and
it will upgrade the schema in place.
