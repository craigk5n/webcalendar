# Troubleshooting

Common issues and their solutions.

## Table of Contents

- [Installation Issues](#installation-issues)
- [Database Issues](#database-issues)
- [Authentication Issues](#authentication-issues)
- [Display Issues](#display-issues)
- [Email Issues](#email-issues)
- [Performance Issues](#performance-issues)
- [Docker Issues](#docker-issues)
- [Diagnostic Steps](#diagnostic-steps)

## Installation Issues

### Wizard does not load

**Symptom:** Accessing WebCalendar shows a blank page or error instead
of the installation wizard.

**Solutions:**

1. Verify PHP is working: create a `phpinfo.php` with `<?php phpinfo();`
   and load it in your browser.
2. Check that the `includes/` directory is writable by the web server
   (the wizard needs to create `settings.php`).
3. Check PHP error logs for specific errors.

### "Permission denied" writing settings.php

The web server user needs write access to the `includes/` directory
during installation:

```bash
chmod 775 /path/to/webcalendar/includes
chown www-data:www-data /path/to/webcalendar/includes
```

After installation, tighten permissions:

```bash
chmod 755 /path/to/webcalendar/includes
chmod 600 /path/to/webcalendar/includes/settings.php
```

### Database connection fails in wizard

- Verify the database server is running.
- Verify the hostname, username, password, and database name.
- Check that the PHP database extension is loaded (`mysqli`, `pgsql`,
  etc.): `php -m | grep -i mysql`
- For MySQL: verify the user has CREATE TABLE permissions.

## Database Issues

### "Table doesn't exist" errors

If tables are missing after an upgrade, re-run the wizard:

```
https://yourserver.com/webcalendar/wizard/index.php
```

The wizard detects the current schema version and applies missing
updates.

### Database connection drops

- Check `$db_persistent` in `includes/settings.php` — try toggling it.
- For MySQL: increase `wait_timeout` and `max_connections`.
- For PostgreSQL: check `max_connections` in `postgresql.conf`.

### Slow queries

- Add database indexes if missing (the wizard creates standard indexes).
- For MySQL: enable the slow query log to identify bottlenecks.
- Consider enabling `$db_cachedir` in settings for query caching.

## Authentication Issues

### Cannot log in after installation

- Verify the admin user was created during the wizard setup.
- Check that `$user_inc` in `includes/settings.php` matches your auth
  method (default: `user.php` for database auth).
- Try resetting the password directly in the database:

```sql
UPDATE webcal_user
SET cal_passwd = MD5('newpassword')
WHERE cal_login = 'admin';
```

### LDAP login fails

- Verify LDAP server is reachable from the web server.
- Check LDAP connection settings in `includes/settings.php`.
- Test with `ldapsearch` from the command line.
- Ensure PHP `ldap` extension is loaded: `php -m | grep ldap`

### HTTP authentication not working

- PHP must be running as an Apache module (not CGI/FPM) for
  `$_SERVER['PHP_AUTH_USER']` to be available.
- Set `$use_http_auth = true` in `includes/settings.php`.
- Configure Apache with `AuthType Basic` or your preferred method.

## Display Issues

### Events show at wrong times

1. Check your timezone in **Preferences**.
2. Check `date.timezone` in `php.ini`:
   ```bash
   php -r "echo ini_get('date.timezone');"
   ```
3. Verify the database stores times in GMT (standard since v1.1).

### Calendar layout is broken

- Clear your browser cache.
- Verify CSS files are accessible: check the browser console for 404
  errors on files in `includes/css/` or `pub/`.
- If using a reverse proxy, ensure it passes static assets correctly.

### Characters display incorrectly (mojibake)

- Verify your database uses UTF-8 encoding.
- Check that `php.ini` has `default_charset = "UTF-8"`.
- For MySQL: verify `character_set_server = utf8mb4`.

## Email Issues

### Reminders not being sent

1. Verify the cron job exists and runs:
   ```bash
   crontab -l | grep send_reminders
   ```
2. Test email manually:
   ```bash
   php tools/send_test_email.php
   ```
3. Check that `SEND_EMAIL` is `Y` in admin settings.
4. Check PHP error log for mail delivery errors.

### Email shows as spam

- Configure SPF, DKIM, and DMARC records for your sending domain.
- Use authenticated SMTP rather than PHP's `mail()` function.
- Set a valid `From:` address in admin email settings.

## Performance Issues

### Pages load slowly

- Enable PHP opcode caching (OPcache).
- Enable database query caching (`$db_cachedir` in settings).
- Check database performance (add indexes, optimize tables).
- For MySQL: tune `innodb_buffer_pool_size`.

### High memory usage

- Reduce `memory_limit` if set too high.
- If displaying many events, add date range limits to views.
- Consider PostgreSQL or MySQL instead of SQLite for large datasets.

## Docker Issues

### Container exits immediately

Check container logs:

```bash
docker-compose -f docker/docker-compose-php8.yml logs
```

Common causes:

- Database container not ready — the app may start before the DB.
  Restart the app container if this happens.
- Port conflict — another service is using port 8080.

### Changes not reflected in dev mode

Verify the volume mount is correct in your docker-compose file. Dev
configurations should mount the local directory into the container.

### Cannot connect to database from app container

- Verify the database service name matches `WEBCALENDAR_DB_HOST`.
- Wait for the database container to be fully started.
- Check that the database credentials match between services.

## Diagnostic Steps

### Check PHP configuration

```bash
php -v              # PHP version
php -m              # Loaded extensions
php -i | grep -i timezone  # Timezone setting
```

### Check file permissions

```bash
ls -la includes/settings.php
ls -la includes/
```

### Check database connectivity

```bash
# MySQL
mysql -u webcalendar -p -h localhost webcalendar -e "SELECT 1"

# PostgreSQL
psql -U webcalendar -h localhost webcalendar -c "SELECT 1"
```

### Check error logs

```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx + PHP-FPM
tail -f /var/log/nginx/error.log
tail -f /var/log/php-fpm/error.log

# Docker
docker-compose logs -f
```

### Run PHP syntax check

```bash
cd /path/to/webcalendar
tests/compile_test.sh
```
