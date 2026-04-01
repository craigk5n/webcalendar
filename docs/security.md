# Security Best Practices

This guide covers secure deployment and maintenance of WebCalendar.

## Table of Contents

- [Built-in Security Features](#built-in-security-features)
- [File Permissions](#file-permissions)
- [Web Server Hardening](#web-server-hardening)
- [Database Security](#database-security)
- [Authentication Security](#authentication-security)
- [Session Security](#session-security)
- [Email Security](#email-security)
- [MCP Server Security](#mcp-server-security)

## Built-in Security Features

WebCalendar includes:

- **CSRF protection** on form submissions
- **Password hashing** for stored credentials
- **Input sanitization** on user-submitted data
- **Parameterized queries** via the database abstraction layer
- **XSS filtering** on output
- **Session-based authentication** with configurable timeouts

## File Permissions

After installation, restrict file permissions:

```bash
# Set ownership to web server user
chown -R www-data:www-data /path/to/webcalendar

# Directories: readable and traversable
find /path/to/webcalendar -type d -exec chmod 755 {} \;

# Files: readable only
find /path/to/webcalendar -type f -exec chmod 644 {} \;

# Protect settings file (contains database credentials)
chmod 600 /path/to/webcalendar/includes/settings.php
```

The `includes/settings.php` file contains database credentials and must
not be world-readable.

## Web Server Hardening

### Apache

Add to your Apache configuration or `.htaccess`:

```apache
# Block direct access to includes/ and wizard/shared/
<DirectoryMatch "(includes|wizard/shared)">
    Require all denied
</DirectoryMatch>

# Block access to hidden files
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

# Block access to SQL files
<FilesMatch "\.(sql|sql\.gz)$">
    Require all denied
</FilesMatch>

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

### Nginx

```nginx
# Block includes/ and wizard/shared/
location ~ /(includes|wizard/shared)/ {
    deny all;
    return 404;
}

# Block hidden files
location ~ /\. {
    deny all;
}

# Security headers
add_header X-Content-Type-Options "nosniff";
add_header X-Frame-Options "SAMEORIGIN";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

### HTTPS

Always use HTTPS in production. WebCalendar transmits passwords and
calendar data that should be encrypted in transit.

## Database Security

- Use a **dedicated database user** with only the permissions WebCalendar
  needs (SELECT, INSERT, UPDATE, DELETE on webcal_* tables).
- Do not use the database root account.
- Use a strong, unique password for the database user.
- Restrict database network access to only the web server host.
- For MySQL/MariaDB, disable `LOAD DATA LOCAL INFILE` unless needed.

## Authentication Security

### Database Authentication (default)

Passwords are hashed before storage. The hashing algorithm depends on
your PHP version (bcrypt on PHP 8+).

### LDAP Authentication

- Use LDAPS (LDAP over TLS) in production.
- Configure `$ldap_server` in settings with `ldaps://` protocol.
- Bind with a dedicated service account, not a user account.

### HTTP Authentication

When `$use_http_auth` is enabled, WebCalendar delegates authentication
to the web server (Apache/Nginx). Ensure the web server is configured
with a secure auth backend.

## Session Security

Recommended `php.ini` settings for production:

```ini
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.use_strict_mode = 1
session.gc_maxlifetime = 3600
```

The installation wizard uses a session timeout of 3600 seconds (1 hour).

## Email Security

If using email notifications (`tools/send_reminders.php`):

- Configure SMTP with authentication (not `mail()` function).
- Use TLS/STARTTLS for SMTP connections.
- Test with `tools/send_test_email.php` before deploying.

## MCP Server Security

The MCP server (`mcp.php`) provides AI assistant access to calendar data.

- **Disable by default** — set `MCP_SERVER_ENABLED=N` unless needed.
- **API tokens** — each user generates their own token in preferences.
  Tokens are passed via `MCP_TOKEN` environment variable (STDIO) or
  `X-MCP-Token` / `Authorization: Bearer` headers (HTTP).
- **Rate limiting** — configure `MCP_RATE_LIMIT` to prevent abuse.
- **Audit logging** — MCP requests are logged for review.
- Restrict network access to the MCP HTTP endpoint if exposed.
