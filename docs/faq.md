# Frequently Asked Questions

## General

### What is WebCalendar?

WebCalendar is a free, open-source, multi-user calendar application
written in PHP. It has been in active development since 1999 and supports
multiple database backends and authentication systems.

### What license is WebCalendar under?

GNU General Public License v2 (GPLv2). You can use, modify, and
distribute it freely under the terms of this license.

### Where can I get help?

- [GitHub Issues](https://github.com/craigk5n/webcalendar/issues) for
  bug reports
- [GitHub Discussions](https://github.com/craigk5n/webcalendar/discussions)
  for questions

## Installation

### What PHP version do I need?

PHP 8.0 or later. PHP 8.2+ is recommended. WebCalendar is tested against
PHP 8.2, 8.3, and 8.4 in CI.

### Which database should I use?

**MySQL/MariaDB** is the most widely tested and recommended for most
deployments. **PostgreSQL** is a strong alternative for environments that
prefer it. **SQLite3** works well for small, single-user installations
or development.

### Do I need Composer?

Not for a standard installation. Release packages include all
dependencies in the `pub/` directory. Composer is only needed if you are
developing or updating dependencies.

### The installer doesn't appear when I access WebCalendar.

The installation wizard (`wizard/index.php`) runs automatically when no
`includes/settings.php` file exists. If you already have a settings file
from a previous installation, the wizard won't auto-redirect. Access it
directly at `wizard/index.php`.

## Configuration

### How do I switch from database auth to LDAP?

Edit `includes/settings.php` and change:

```php
$user_inc = 'user-ldap.php';
```

Then configure the LDAP settings in the same file. See
[Admin Guide](admin-guide.md) for details.

### Can I use environment variables instead of settings.php?

Yes. Set `WEBCALENDAR_USE_ENV=true` and provide database connection
details as environment variables. See
[Configuration Reference](configuration.md).

### How do I enable the public calendar?

In the admin panel (`admin.php`), set **Public Access** to Yes. This
allows anonymous users to view the public calendar without logging in.

## Usage

### How do I import events from Google Calendar?

1. Export from Google Calendar as an `.ics` file.
2. In WebCalendar, go to **Import** (`import.php`).
3. Upload the `.ics` file.

### How do I subscribe to a WebCalendar feed in another app?

Use the published iCalendar URL:

```
https://yourserver.com/webcalendar/publish.php?user=USERNAME
```

Add this URL in Google Calendar, Apple Calendar, or Outlook as a
calendar subscription.

### Can multiple users share a calendar?

Yes. WebCalendar supports:

- **Groups** — organize users into teams
- **Layers** — overlay other users' calendars on your own view
- **Views** — create combined views of multiple calendars
- **Assistant/Boss** — delegate calendar management

### How do I set up email reminders?

An admin must configure email settings and set up a cron job:

```bash
*/15 * * * * /usr/bin/php /path/to/webcalendar/tools/send_reminders.php
```

Users can then add reminders to individual events.

## Troubleshooting

### I get a blank page / 500 error.

Check your PHP error log. Common causes:

- Missing PHP extensions (database driver, mbstring)
- Incorrect file permissions on `includes/settings.php`
- PHP version too old (requires 8.0+)

### Events show at the wrong time.

Check timezone settings:

1. Your user timezone in **Preferences**
2. PHP timezone (`date.timezone` in `php.ini`)
3. Database server timezone

### I forgot the admin password.

Connect to your database and update the password directly:

```sql
UPDATE webcal_user
SET cal_passwd = MD5('newpassword')
WHERE cal_login = 'admin';
```

Note: WebCalendar uses bcrypt (`password_hash()`) for new passwords, but
the login system still recognizes legacy MD5 hashes and auto-upgrades
them on the next successful login. The MD5 approach above works as a
quick reset.

### Email reminders aren't being sent.

1. Verify the cron job is running: `crontab -l`
2. Test email configuration: `php tools/send_test_email.php`
3. Check that `SEND_EMAIL` is enabled in admin settings.
4. Check your PHP mail configuration or SMTP settings.
