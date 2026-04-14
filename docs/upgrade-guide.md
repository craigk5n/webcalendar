# Upgrade Guide

Instructions for upgrading WebCalendar from a previous version.

## Table of Contents

- [Before You Upgrade](#before-you-upgrade)
- [Upgrade Methods](#upgrade-methods)
- [Web Wizard Upgrade](#web-wizard-upgrade)
- [Headless Upgrade](#headless-upgrade)
- [Docker Upgrade](#docker-upgrade)
- [Manual File Upgrade](#manual-file-upgrade)
- [Post-Upgrade Verification](#post-upgrade-verification)
- [Rollback](#rollback)

## Before You Upgrade

### 1. Back Up Your Database

```bash
# MySQL / MariaDB
mysqldump -u USERNAME -p DATABASE > webcalendar-backup-$(date +%F).sql

# PostgreSQL
pg_dump -U USERNAME DATABASE > webcalendar-backup-$(date +%F).sql

# SQLite3
cp /path/to/webcalendar.db webcalendar-backup-$(date +%F).db
```

### 2. Back Up Your Configuration

```bash
cp includes/settings.php includes/settings.php.backup
```

### 3. Back Up Custom Files

If you have custom icons or themes, back up the `icons/` directory and
any custom CSS files.

### 4. Check PHP Version

Verify your PHP version meets the requirements for the target release:

```bash
php -v
```

WebCalendar v1.9.16 requires PHP 8.0 or later.

## Upgrade Methods

### Web Wizard Upgrade

The installation wizard detects your current version and applies
database schema changes automatically.

1. Download and extract the new release into a **new** directory.
2. Copy your `includes/settings.php` from the old installation.
3. Copy any custom icons from your old `icons/` directory.
4. Point your web server to the new directory (or rename directories).
5. Access WebCalendar in your browser — the wizard runs automatically
   when it detects a version mismatch.
6. Follow the wizard steps. The **Database Tables** step shows SQL
   changes that will be applied. Review them before proceeding.
7. After completion, verify the application works correctly.

The wizard can also be accessed manually at:
```
https://yourserver.com/webcalendar/wizard/index.php
```

### Headless Upgrade

For automated or scripted upgrades:

```bash
php wizard/headless.php
```

The headless installer reads configuration from `includes/settings.php`
or environment variables, detects the current version, and applies
schema updates non-interactively.

### Docker Upgrade

1. Update the image tag in your `docker-compose` file.
2. Recreate the container:

```bash
docker-compose -f docker/docker-compose-php8.yml down
docker-compose -f docker/docker-compose-php8.yml pull
docker-compose -f docker/docker-compose-php8.yml up -d
```

The wizard runs automatically on first access if a schema update is
needed. For fully automated upgrades with environment variable
configuration, the headless installer runs during container startup.

### Manual File Upgrade

If you prefer to update files in place (not recommended for major
version upgrades):

1. Back up your entire WebCalendar directory.
2. Download the new release.
3. Extract over your existing installation, preserving:
   - `includes/settings.php`
   - `icons/` (if you have custom icons)
4. Access the application — the wizard will prompt for schema updates.

## Post-Upgrade Verification

After upgrading:

- [ ] Application loads without errors
- [ ] Login works for admin and regular users
- [ ] Calendar views display correctly (month, week, day)
- [ ] Events display with correct dates and times
- [ ] Creating and editing events works
- [ ] Repeating events display correctly
- [ ] Email reminders are still configured (check cron job)
- [ ] If using LDAP/IMAP auth, verify login still works

## Rollback

If the upgrade fails:

1. Restore your backup database:

```bash
# MySQL / MariaDB
mysql -u USERNAME -p DATABASE < webcalendar-backup-YYYY-MM-DD.sql

# PostgreSQL
psql -U USERNAME DATABASE < webcalendar-backup-YYYY-MM-DD.sql

# SQLite3
cp webcalendar-backup-YYYY-MM-DD.db /path/to/webcalendar.db
```

2. Restore your old `includes/settings.php`.
3. Point your web server back to the old WebCalendar directory
   (or restore from your file backup).
