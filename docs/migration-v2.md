# Migration Guide: v1.x to v2.0.0

This guide covers breaking changes and migration steps when upgrading
from WebCalendar v1.x to v2.0.0.

For routine version upgrades within the 1.9.x line, see
[Upgrade Guide](upgrade-guide.md).

## Table of Contents

- [Before You Start](#before-you-start)
- [Breaking Changes](#breaking-changes)
- [Installation System](#installation-system)
- [Database Schema Changes](#database-schema-changes)
- [Configuration Changes](#configuration-changes)
- [PHP Version Requirements](#php-version-requirements)
- [Removed Features](#removed-features)
- [Authentication Bridges](#authentication-bridges)
- [Step-by-Step Migration](#step-by-step-migration)
- [Rollback](#rollback)
- [Known Issues](#known-issues)

## Before You Start

1. **Back up your database** — this is not optional:
   ```bash
   # MySQL / MariaDB
   mysqldump -u USERNAME -p DATABASE > backup-$(date +%F).sql

   # PostgreSQL
   pg_dump -U USERNAME DATABASE > backup-$(date +%F).sql

   # SQLite3
   cp /path/to/webcalendar.db backup-$(date +%F).db
   ```

2. **Back up your configuration:**
   ```bash
   cp includes/settings.php includes/settings.php.backup
   ```

3. **Back up custom files** (icons, themes).

4. **Note your current version.** Check in the admin panel or:
   ```sql
   SELECT cal_value FROM webcal_config
   WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION';
   ```

## Breaking Changes

### Installation System Replaced

| Before (v1.2 and earlier) | After (v1.9.13+) |
|----------------------------|-------------------|
| `install/index.php` | `wizard/index.php` |
| `install/sql/` | `wizard/shared/` |
| `install/default_config.php` | `wizard/shared/default_config.php` |
| `install/settings.php` (wizard password) | `includes/settings.php` (all config) |

The old `install/` directory no longer exists. If your deployment scripts
or cron jobs reference `install/`, they must be updated.

### Documentation Moved

| Before | After |
|--------|-------|
| `docs/WebCalendar-SysAdmin.html` | `docs/admin-guide.md` |
| `docs/WebCalendar-UserManual.html` | `docs/user-guide.md` |
| `docs/WebCalendar-DeveloperGuide.html` | `docs/developer-guide.md` |
| `docs/WebCalendar-Functions.html` | Removed (use IDE/PHPDoc) |
| `UPGRADING.html` | `docs/upgrade-guide.md` |

Legacy HTML docs are preserved in `docs/archive/legacy-html/` for
historical reference.

## Database Schema Changes

The upgrade wizard handles all schema changes automatically. The
following is for reference only — you do not need to run these manually.

### Changes from v1.9.0 to v1.9.16

**v1.9.6** — Entry categories primary key constraint:
```sql
-- webcal_entry_categories: cat_owner set to '' where NULL,
-- primary key constraint added
```

**v1.9.11** — Category icons:
```sql
ALTER TABLE webcal_categories ADD cat_status CHAR DEFAULT 'A';
ALTER TABLE webcal_categories ADD cat_icon_mime VARCHAR(32) DEFAULT NULL;
ALTER TABLE webcal_categories ADD cat_icon_blob LONGBLOB DEFAULT NULL;
```

**v1.9.12** — URL field widened:
```sql
ALTER TABLE webcal_nonuser_cals MODIFY cal_url VARCHAR(255);
ALTER TABLE webcal_entry MODIFY cal_url VARCHAR(255);
```

**v1.9.13** — MCP API token:
```sql
ALTER TABLE webcal_user ADD cal_api_token VARCHAR(255) DEFAULT NULL;
```

**v1.9.16** — Blob table ensured:
```sql
CREATE TABLE IF NOT EXISTS webcal_blob ( ... );
```

### Upgrading from Very Old Versions (pre-1.9.0)

If upgrading from v1.3.x or earlier, the wizard applies all
intermediate schema changes automatically. Major changes include:

- Timezone tables and GMT conversion (v1.1 → v1.3)
- Import tracking tables (v1.3 → v1.9)
- Reminder system overhaul (v1.1)

The wizard detects your current version by probing the database schema
and applies only the changes needed.

## Configuration Changes

### settings.php Location

The configuration file has always been `includes/settings.php`. If your
old installation had a separate `install/settings.php` (wizard
password), that file is no longer used.

### New Environment Variables

These environment variables were added in the v1.9.x series:

| Variable | Version | Purpose |
|----------|---------|---------|
| `WEBCALENDAR_USE_ENV` | v1.9.12 | Enable env-based config |
| `WEBCALENDAR_DB_TYPE` | v1.9.12 | Database backend |
| `WEBCALENDAR_DB_HOST` | v1.9.12 | Database hostname |
| `WEBCALENDAR_DB_DATABASE` | v1.9.12 | Database name |
| `WEBCALENDAR_DB_LOGIN` | v1.9.12 | Database username |
| `WEBCALENDAR_DB_PASSWORD` | v1.9.12 | Database password |
| `WEBCALENDAR_MODE` | v1.9.12 | `dev` or `prod` |
| `MCP_TOKEN` | v1.9.13 | MCP server API token |

### New Admin Settings

| Setting | Version | Purpose |
|---------|---------|---------|
| `MCP_SERVER_ENABLED` | v1.9.13 | Enable/disable MCP server |
| `MCP_RATE_LIMIT` | v1.9.13 | MCP requests per minute |

## PHP Version Requirements

| WebCalendar Version | PHP Minimum | Recommended |
|---------------------|-------------|-------------|
| v1.9.0 - v1.9.10 | 7.4 | 8.0 |
| v1.9.11 - v1.9.16 | 8.0 | 8.2+ |
| v2.0.x | 8.0 | 8.2+ |

If upgrading from PHP 7.x, update PHP first and verify your application
works before upgrading WebCalendar.

## Removed Features

### Old Installer (`install/` directory)

The `install/` directory and its contents were removed in v1.9.13,
replaced by the `wizard/` system. The new wizard provides:

- Single-page Bootstrap 5 interface
- Headless CLI mode (`wizard/headless.php`)
- Automatic version detection and schema upgrades
- Environment variable support for containers

### Legacy Database Backends

The following database backends have code in `includes/dbi4php.php` and
the wizard UI, but are **not tested on PHP 8** and may not work:

| Backend | Issue |
|---------|-------|
| Oracle | Uses `OCIParse`/`OCIExecute` aliases removed in PHP 8.0 |
| IBM DB2 | Requires PECL `ibm_db2` extension; untested |
| ODBC | Untested; no dedicated table creation SQL |
| Interbase/Firebird | `ibase_*` extension removed from PHP 8 core |
| SQLite (v2) | Extension removed from PHP 8.0; use `sqlite3` |

If you are using one of these backends, you should migrate to MySQL,
PostgreSQL, or SQLite3 before upgrading.

### Legacy Documentation Toolchain

The Perl-based documentation generators (`sql2html.pl`, `php2html.pl`,
`extractfaqs.pl`) are archived. Documentation is now maintained as
Markdown files in `docs/`.

### SQLite (Legacy)

The original SQLite extension (`sqlite`) is deprecated in favor of
`sqlite3`. If your `$db_type` is `sqlite`, change it to `sqlite3` and
verify your database file is compatible.

## Authentication Bridges

All authentication bridges remain compatible:

| Bridge | File | Status |
|--------|------|--------|
| Database (native) | `user.php` | Supported |
| LDAP | `user-ldap.php` | Supported |
| IMAP | `user-imap.php` | Supported |
| NIS | `user-nis.php` | Supported |
| Joomla | `user-app-joomla.php` | Supported |

No changes to the authentication bridge API have been made. Custom
bridges (`user-app-*.php`) should continue to work without modification.

## Step-by-Step Migration

### From v1.9.x (any)

1. Back up database and `includes/settings.php`.
2. Download and extract the new release to a new directory.
3. Copy `includes/settings.php` from your old installation.
4. Copy custom icons from `icons/`.
5. Access WebCalendar — the wizard detects the version mismatch and
   prompts for schema updates.
6. Review the SQL preview and apply.
7. Verify the application works (see
   [post-upgrade checklist](upgrade-guide.md#post-upgrade-verification)).

### From v1.3.x or Earlier

Same steps as above. The wizard handles all intermediate schema
upgrades. Allow extra time — there are many schema changes between
v1.3 and the current version.

### From Docker

Update the image tag in your `docker-compose` file and recreate:

```bash
docker-compose down
docker-compose pull
docker-compose up -d
```

The wizard runs automatically on first access.

## Rollback

If migration fails, restore your backup:

```bash
# Restore database
mysql -u USERNAME -p DATABASE < backup-YYYY-MM-DD.sql
# or
psql -U USERNAME DATABASE < backup-YYYY-MM-DD.sql
# or
cp backup-YYYY-MM-DD.db /path/to/webcalendar.db

# Restore settings
cp includes/settings.php.backup includes/settings.php

# Point web server back to old installation directory
```

## Known Issues

- **PHP 8.1+ deprecation warnings:** Some older code paths may emit
  deprecation notices on PHP 8.1+. These are cosmetic and do not affect
  functionality. Set `error_reporting = E_ALL & ~E_DEPRECATED` in
  `php.ini` if they are disruptive.

- **SQLite legacy driver:** If using `$db_type = 'sqlite'` (not
  `sqlite3`), you must migrate to `sqlite3` before upgrading. The
  legacy SQLite extension was removed from PHP 8.0.

- **v2.0.x is a stability release.** The 1.9.x series was the
  development line; 2.0.x is the corresponding production release.
  No new features are introduced — only bug fixes, security
  hardening, and code quality improvements.
