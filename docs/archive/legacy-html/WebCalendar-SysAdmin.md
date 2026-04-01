# WebCalendar System Administrator's Guide

**WebCalendar Version: 1.9.13**

This guide provides comprehensive information for system administrators responsible for installing, configuring, and maintaining WebCalendar.

## Table of Contents

- [Introduction](#introduction)
- [System Requirements](#system-requirements)
- [Installation Methods](#installation-methods)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [User Management](#user-management)
- [System Settings](#system-settings)
- [Email Configuration](#email-configuration)
- [Security](#security)
- [MCP Server Integration](#mcp-server-integration)
- [Backup and Maintenance](#backup-and-maintenance)
- [Troubleshooting](#troubleshooting)
- [Performance Tuning](#performance-tuning)
- [Docker Deployment](#docker-deployment)

## Introduction

WebCalendar is a multi-user PHP web-based calendar application supporting multiple database backends. It provides calendar views, event management, user groups, access controls, and external application integration.

### Key Features

- **Multi-user support** with individual calendars and permissions
- **Group support** for team collaboration
- **Multiple calendar views**: day, week, month, year, and agenda
- **Event management**: repeating events, reminders, notifications
- **Access control**: public access, user permissions, group restrictions
- **Import/Export**: iCalendar, vCalendar, Palm Pilot formats
- **Authentication methods**: web-based, HTTP, LDAP, NIS, IMAP, Joomla
- **Email notifications and reminders**
- **Customizable appearance** with themes and color schemes
- **Multi-language support** (40+ languages)
- **RSS feeds and iCalendar publishing**
- **MCP Server integration** for AI assistant connectivity

## System Requirements

### Web Server

- **PHP**: 8.0 or later (PHP 8.x required)
- **Web Server**: Apache 2.4+, Nginx 1.18+, or equivalent
- **Memory**: Minimum 128MB PHP memory limit (256MB+ recommended)

### Database Support

WebCalendar supports these database backends:

- **MySQL/MariaDB** (recommended)
- **PostgreSQL** 
- **SQLite3** (file-based, suitable for small installations)
- **Oracle** (requires OCI extension)
- **IBM DB2** (requires ibm_db2 extension)
- **ODBC** (generic connectivity)
- **Interbase/Firebird** (requires ibase extension)

### PHP Extensions

Required extensions:
- `pdo` (for database connectivity)
- `json` (for configuration and API)
- `mbstring` (for multi-language support)
- `session` (for user authentication)

Optional extensions:
- `gd` (for gradient backgrounds and image processing)
- `curl` (for remote calendar subscriptions)
- `imap` (for IMAP authentication)
- `ldap` (for LDAP authentication)
- `openssl` (for SMTP STARTTLS and secure connections)

### Client Requirements

- Modern web browser with CSS3 and JavaScript support
- Cookies enabled (unless using HTTP authentication)
- 1024×768 screen resolution recommended

## Installation Methods

### Method 1: Web Installation Wizard (Recommended)

The web-based installation wizard provides a step-by-step setup process:

1. **Extract Files**: Unpack WebCalendar to your web server's document root
2. **Access Wizard**: Navigate to the WebCalendar URL in your browser
3. **Follow Steps**: The wizard will guide you through:
   - PHP requirements verification
   - Application settings configuration
   - Database connection setup
   - Database table creation
   - Admin user creation
4. **Complete Installation**: Launch WebCalendar and begin configuration

The wizard supports both new installations and upgrades from previous versions.

### Method 2: Docker Deployment

For containerized environments:

```bash
# Quick start with Docker Compose
docker-compose -f docker/docker-compose-php8.yml up -d

# Access at http://localhost:8080
```

See [Docker Deployment](#docker-deployment) for detailed container setup instructions.

### Method 3: Manual Installation

For advanced users or specific environments:

1. **Create Database**: Manually create database and user
2. **Load Schema**: Import SQL schema from `tables/*.sql`
3. **Configure Settings**: Create `includes/settings.php`
4. **Set Permissions**: Ensure web server can write to necessary directories
5. **Verify Installation**: Test database connectivity and basic functionality

## Configuration

### Configuration Methods

WebCalendar supports two configuration approaches:

#### 1. Settings File (Traditional)

Create `includes/settings.php` with configuration parameters:

```php
<?php
# Database Configuration
db_type: mysqli
db_host: localhost
db_database: webcalendar
db_login: webcalendar_user
db_password: your_password

# Application Settings
single_user: false
use_http_auth: false
user_inc: user.php

# Server Configuration
server_url: https://your-domain.com/webcalendar/
mode: prod
?>
```

#### 2. Environment Variables (Container/Cloud)

Set `WEBCALENDAR_USE_ENV=true` and provide configuration via environment variables:

```bash
export WEBCALENDAR_USE_ENV=true
export WEBCALENDAR_DB_TYPE=mysqli
export WEBCALENDAR_DB_HOST=localhost
export WEBCALENDAR_DB_DATABASE=webcalendar
export WEBCALENDAR_DB_LOGIN=webcalendar_user
export WEBCALENDAR_DB_PASSWORD=your_password
```

### Key Configuration Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `db_type` | Database driver | `mysqli` |
| `db_host` | Database hostname | `localhost` |
| `db_database` | Database name | `webcalendar` |
| `db_login` | Database username | (required) |
| `db_password` | Database password | (required) |
| `single_user` | Single-user mode | `false` |
| `use_http_auth` | HTTP authentication | `false` |
| `user_inc` | Authentication module | `user.php` |
| `mode` | Application mode (`prod`/`dev`) | `prod` |
| `server_url` | Base URL | Auto-detect |

## Database Setup

### MySQL/MariaDB Setup

```sql
-- Create database
CREATE DATABASE webcalendar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'webcalendar'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON webcalendar.* TO 'webcalendar'@'localhost';
FLUSH PRIVILEGES;
```

### PostgreSQL Setup

```sql
-- Create database
CREATE DATABASE webcalendar;

-- Create user
CREATE USER webcalendar WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE webcalendar TO webcalendar;
```

### SQLite Setup

For SQLite, ensure the web server can write to the database file directory:

```bash
mkdir -p /path/to/webcalendar/data
chmod 755 /path/to/webcalendar/data
touch /path/to/webcalendar/data/webcalendar.sqlite
chmod 666 /path/to/webcalendar/data/webcalendar.sqlite
```

### Database Table Creation

The installation wizard will automatically create and update database tables. For manual installation, import the appropriate SQL schema file:

- MySQL: `sql/tables-mysql.sql`
- PostgreSQL: `sql/tables-postgresql.sql`
- SQLite: `sql/tables-sqlite.sql`
- Oracle: `sql/tables-oracle.sql`

## User Management

### Authentication Methods

WebCalendar supports multiple authentication mechanisms:

#### 1. Web-based Authentication (Default)

Users authenticate via WebCalendar's built-in login system. Credentials are stored in the `webcal_user` table.

#### 2. HTTP Authentication

Configure your web server to handle authentication:

**Apache Example:**
```apache
<Directory "/var/www/html/webcalendar">
    AuthType Basic
    AuthName "WebCalendar"
    AuthUserFile /etc/webcalendar/.htpasswd
    Require valid-user
</Directory>
```

Set `use_http_auth = true` in configuration.

#### 3. LDAP Authentication

Configure LDAP settings in `includes/user-ldap.php`:

```php
$ldap_host = 'ldap://ldap.example.com';
$ldap_port = 389;
$ldap_base_dn = 'ou=users,dc=example,dc=com';
$ldap_filter = '(uid=%username%)';
```

Set `user_inc = user-ldap.php` in configuration.

#### 4. IMAP Authentication

Configure IMAP settings in `includes/user-imap.php`:

```php
$imap_host = 'imap.example.com';
$imap_port = 143;
$imap_auth_type = 'CRAM-MD5';
```

Set `user_inc = user-imap.php` in configuration.

### User Creation and Management

#### Creating Users via Web Interface

1. Login as administrator
2. Navigate to **Admin** → **Users**
3. Click **Add New User**
4. Fill in user details and save

#### Creating Users via Database

```sql
INSERT INTO webcal_user (cal_login, cal_passwd, cal_firstname, cal_lastname, cal_email)
VALUES ('jdoe', 'hashed_password_here', 'John', 'Doe', 'jdoe@example.com');
```

**Note:** Passwords should be created through the WebCalendar interface which uses secure password hashing (bcrypt/PHP password_hash). Direct database insertion is not recommended for production use.

#### User Groups

Create groups through the WebCalendar admin interface:
1. Login as administrator
2. Navigate to **Admin** → **Groups**
3. Click **Add New Group** and configure group members

## System Settings

System settings are managed through **Admin** → **System Settings**. Settings are organized into tabs:

### Settings Tab

**System Options:**
- **Application Name**: Displayed in browser title
- **Server URL**: Base URL for email links and API calls
- **Home URL**: Return link for exiting WebCalendar
- **Language**: Default interface language

**Site Security:**
- **Content Security Policy**: Control iframe embedding
- **CSRF Protection**: Enable cross-site request forgery protection

**Site Customization:**
- **Custom Script/Stylesheet**: Additional CSS/JavaScript
- **Custom Header/Trailer**: HTML inserted before/after content
- **External Files**: Allow loading header/trailer from files

**Date and Time:**
- **Server Timezone**: Server's timezone setting
- **Default Client Timezone**: Users' default timezone
- **Date Format**: Display format for dates
- **Time Format**: 12-hour or 24-hour format
- **Week Starts On**: Sunday (0) or Monday (1)

**Appearance:**
- **Preferred View**: Default calendar view (day/week/month/year)
- **Fonts**: Default font family
- **Display Weekends**: Show weekend days
- **Display Week Number**: Show ISO week numbers

**Restrictions:**
- **Allow Viewing Other Users**: Permission to view others' calendars
- **Require Event Approvals**: Events need approval before appearing
- **Conflict Checking**: Check for scheduling conflicts
- **Cross-Day Events**: Allow events spanning multiple days

### Public Access Tab

Configure anonymous access to WebCalendar:

- **Allow Public Access**: Enable anonymous calendar viewing
- **Public Access Can Add Events**: Allow anonymous event submission
- **Require Approval**: Admin approval needed for anonymous events
- **Override Event Details**: Mask event details for public viewers

### User Access Control Tab

Advanced permission management:

- **UAC Enabled**: Enable granular user permissions
- Configure which functions each user can access
- Set user-to-user calendar access permissions

### Groups Tab

Group management settings:

- **Groups Enabled**: Enable group functionality
- **User Sees Only His Groups**: Hide users outside same groups

### Resource Calendars Tab

Non-user calendar management:

- **Resource Calendars Enabled**: Enable resource booking (rooms, equipment)
- **Display Position**: Show resources in participant lists

### Other Tab

Additional features:

- **Reports Enabled**: Enable report generation
- **Remote Subscriptions**: Allow iCalendar subscriptions
- **Remote Calendars**: Enable external calendar consumption
- **RSS Feed**: Enable RSS syndication
- **Categories**: Enable event categorization
- **Tasks**: Enable task management
- **Attachments**: Allow file attachments to events
- **Comments**: Enable event comments
- **Self-Registration**: Allow public user registration

### MCP Server Tab

AI assistant integration:

- **MCP Server Enabled**: Enable Model Context Protocol server
- **Write Access**: Allow modifications via MCP
- **Rate Limit**: Requests per hour per client
- **CORS Origins**: Configure cross-origin access

### Email Tab

Email configuration:

- **Email Enabled**: Enable email functionality
- **Default Sender**: From address for system emails
- **Email Mailer**: PHP mail, SMTP, or sendmail
- **SMTP Settings**: Server, port, authentication, TLS
- **Event Notifications**: Configure email triggers

### Colors Tab

Visual customization:

- **Color Scheme**: Configure calendar colors
- **Gradient Images**: Enable gradient backgrounds (requires GD)
- **Background Image**: Custom background image
- **User Customization**: Allow users to set own colors

## Email Configuration

### PHP Mail (Default)

Uses the server's built-in mail function:

```php
$EMAIL_MAILER = 'mail';
$EMAIL_FALLBACK_FROM = 'webcalendar@yourdomain.com';
```

### SMTP Configuration

Recommended for reliable email delivery:

```php
$EMAIL_MAILER = 'smtp';
$SMTP_HOST = 'smtp.gmail.com';
$SMTP_PORT = 587;
$SMTP_AUTH = true;
$SMTP_STARTTLS = true;
$SMTP_USERNAME = 'your_email@gmail.com';
$SMTP_PASSWORD = 'your_app_password';
```

### Sendmail Configuration

Use local sendmail or compatible MTA:

```php
$EMAIL_MAILER = 'sendmail';
```

### Email Notification Types

Configure which events trigger email notifications:

- **Event Reminders**: Send reminder emails before events
- **Events Added**: Notify when events are added to user's calendar
- **Events Updated**: Notify when events are modified
- **Events Deleted**: Notify when events are removed
- **Event Rejected**: Notify when participants reject events

## Security

### File Permissions

Secure file permissions for production:

```bash
# Web server files (read-only)
find /var/www/html/webcalendar -type f -exec chmod 644 {} \;
find /var/www/html/webcalendar -type d -exec chmod 755 {} \;

# Settings file (protected)
chmod 600 /var/www/html/webcalendar/includes/settings.php

# Uploads directory (writable)
chmod 755 /var/www/html/webcalendar/uploads
chown www-data:www-data /var/www/html/webcalendar/uploads
```

### Security Settings

**Content Security Policy (CSP):**
- `none`: No site can frame the content (default)
- `same`: Only same-origin framing allowed
- `any`: Any site can frame the content (less secure)

**CSRF Protection:**
Enable to prevent cross-site request forgery attacks (recommended).

### Authentication Security

**Password Storage:**
Passwords are hashed using MD5 (legacy) or stronger algorithms when available.

**Session Security:**
- Sessions are server-side with secure random IDs
- Session timeout can be configured in `php.ini`
- Use HTTPS to protect session cookies

**Public Access Security:**
- Limit public access capabilities
- Require approval for anonymous event submissions
- Consider CAPTCHA for public event submissions

### Database Security

**Connection Security:**
- Use SSL/TLS for database connections when possible
- Limit database user permissions to required tables only
- Avoid using database root credentials for web application

**SQL Injection Protection:**
WebCalendar uses prepared statements to prevent SQL injection attacks.

## MCP Server Integration

WebCalendar includes a Model Context Protocol (MCP) server for AI assistant integration.

### MCP Server Features

- **API Token Authentication**: Secure access with user-specific tokens
- **Rate Limiting**: Configurable request limits per hour
- **Audit Logging**: Track all MCP requests and responses
- **CORS Support**: Cross-origin requests for web-based AI clients
- **Multiple Transport**: STDIO (local) and HTTP (remote) support

### Enabling MCP Server

1. **Enable in System Settings**: Set **MCP Server enabled** to "Yes"
2. **Configure Access**: Set write permissions and rate limits
3. **Generate API Tokens**: Users create tokens in their preferences
4. **Configure CORS**: Set allowed origins for web-based clients

### MCP Client Configuration

**STDIO (Claude Desktop):**
```json
{
  "mcpServers": {
    "webcalendar": {
      "command": "php",
      "args": ["/absolute/path/to/webcalendar/mcp.php"],
      "env": {
        "MCP_TOKEN": "user_api_token_here"
      }
    }
  }
}
```

**HTTP (Recommended):**
```json
{
  "mcpServers": {
    "webcalendar": {
      "url": "https://your-domain.com/webcalendar/mcp.php",
      "headers": {
        "X-MCP-Token": "user_api_token_here"
      }
    }
  }
}
```

### MCP Tools Available

- `list_events`: Query calendar events with filters
- `get_user_info`: Retrieve user information and preferences
- `search_events`: Search events by text content
- `add_event`: Create new calendar events (if write access enabled)

### MCP Security Considerations

- **API Token Management**: Tokens are stored hashed in database
- **Rate Limiting**: Default 100 requests per hour per user
- **Access Control**: MCP respects user permissions and access controls
- **Audit Trail**: All MCP actions are logged for security review

## Backup and Maintenance

### Database Backup

#### MySQL/MariaDB

```bash
# Full backup
mysqldump -u webcalendar -p webcalendar > webcalendar_backup_$(date +%Y%m%d).sql

# Compressed backup
mysqldump -u webcalendar -p webcalendar | gzip > webcalendar_backup_$(date +%Y%m%d).sql.gz
```

#### PostgreSQL

```bash
pg_dump -U webcalendar webcalendar > webcalendar_backup_$(date +%Y%m%d).sql
```

#### SQLite

```bash
cp /path/to/webcalendar/data/webcalendar.sqlite /backup/webcalendar_$(date +%Y%m%d).sqlite
```

### File System Backup

Backup important WebCalendar files:

```bash
# Configuration and uploads
tar -czf webcalendar_files_$(date +%Y%m%d).tar.gz \
    /var/www/html/webcalendar/includes/settings.php \
    /var/www/html/webcalendar/uploads/ \
    /var/www/html/webcalendar/themes/
```

### Automated Backup Script

Create `/usr/local/bin/backup-webcalendar.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/backup/webcalendar"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u webcalendar -p'password' webcalendar | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz \
    /var/www/html/webcalendar/includes/settings.php \
    /var/www/html/webcalendar/uploads/

# Cleanup old backups (keep 30 days)
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
```

Add to crontab:
```bash
0 2 * * * /usr/local/bin/backup-webcalendar.sh
```

### Regular Maintenance Tasks

#### Database Optimization

```sql
-- MySQL optimization
OPTIMIZE TABLE webcal_entry, webcal_user, webcal_config;

-- PostgreSQL optimization
VACUUM ANALYZE webcal_entry, webcal_user, webcal_config;
```





## Troubleshooting

### Common Issues

#### Installation Problems

**Wizard won't start:**
- Check PHP version (7.4+ required)
- Verify file permissions in `includes/` directory
- Ensure `settings.php` is writable by web server

**Database connection fails:**
- Verify database server is running
- Check database credentials in configuration
- Test database user permissions
- Ensure firewall allows database connections

#### Runtime Issues

**Blank pages/white screen:**
- Check PHP error logs: `/var/log/apache2/error.log` or `/var/log/php_errors`
- Enable PHP error reporting: `error_reporting(E_ALL); ini_set('display_errors', 1);`
- Verify all required PHP extensions are loaded

**Login problems:**
- Clear browser cookies and cache
- Check `sessions` directory permissions
- Verify user exists in `webcal_user` table
- Check authentication method configuration

**Email not sending:**
- Verify email configuration in System Settings
- Check SMTP server connectivity and credentials
- Review mail server logs for delivery issues
- Test with simple PHP mail script

#### Performance Issues

**Slow page loads:**
- Enable database query caching
- Check database server performance
- Optimize large calendar views
- Consider database indexing

**Memory errors:**
- Increase PHP memory limit: `memory_limit = 256M`
- Check for memory leaks in custom code
- Monitor database connection usage

### Debug Mode

Enable debug mode for troubleshooting:

```php
// In settings.php or environment variable
db_debug: true
mode: dev
```

Debug mode provides:
- Detailed SQL error messages
- Database query logging
- PHP error display
- Performance timing information

### Log Locations

- **Apache Error Log**: `/var/log/apache2/error.log`
- **PHP Error Log**: `/var/log/php_errors` (configured in `php.ini`)
- **Database Logs**: Varies by database system

### Getting Help

1. **Documentation**: [WebCalendar Wiki](https://github.com/craigk5n/webcalendar/wiki)
2. **Community**: [GitHub Discussions](https://github.com/craigk5n/webcalendar/discussions)
3. **Bug Reports**: [GitHub Issues](https://github.com/craigk5n/webcalendar/issues)
4. **Source Code**: [GitHub Repository](https://github.com/craigk5n/webcalendar)

## Performance Tuning

### Database Optimization

WebCalendar automatically creates necessary database indexes during installation. For optimal performance:

- Use database query caching (configure in System Settings)
- Use appropriate database engine (InnoDB recommended for MySQL/MariaDB)
- Consider database server configuration for production workloads

### PHP Optimization

#### Opcode Caching

Enable PHP opcode caching for better performance:

```ini
; OPcache settings
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

#### Session Optimization

Configure session settings for better performance:

```ini
; Session settings
session.save_handler=files
session.save_path=/var/lib/php/sessions
session.gc_probability=1
session.gc_divisor=100
session.gc_maxlifetime=1440
```

### Web Server Optimization

#### Apache Configuration

```apache
# Enable compression
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
</Location>

# Enable caching
LoadModule expires_module modules/mod_expires.so
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
</IfModule>
```

#### Nginx Configuration

```nginx
# Enable gzip compression
gzip on;
gzip_types text/css application/javascript image/svg+xml;

# Enable caching
location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Caching Strategies

#### Database Query Caching

Configure database cache directory:

```php
// In settings.php
db_cachedir: /var/www/html/webcalendar/cache/db_cache
```

Ensure the cache directory is writable by the web server:

```bash
mkdir -p /var/www/html/webcalendar/cache/db_cache
chown www-data:www-data /var/www/html/webcalendar/cache/db_cache
chmod 755 /var/www/html/webcalendar/cache/db_cache
```

#### Application Caching

- Enable PHP opcode caching
- Use HTTP caching headers for static assets
- Consider reverse proxy (Varnish, Nginx) for high-traffic sites

## Docker Deployment

WebCalendar provides comprehensive Docker support for both development and production deployments.

### Quick Start

```bash
# Clone repository
git clone https://github.com/craigk5n/webcalendar.git
cd webcalendar

# Start with Docker Compose
docker-compose -f docker/docker-compose-php8.yml up -d

# Access WebCalendar
open http://localhost:8080
```

### Docker Images

Official images are available on Docker Hub:

```bash
# Pull latest version
docker pull craigk5n/webcalendar:latest

# Specific version
docker pull craigk5n/webcalendar:v1.9.13
```

### Environment Variables

Configure WebCalendar via environment variables:

```bash
# Required variables
WEBCALENDAR_USE_ENV=true
WEBCALENDAR_DB_TYPE=mysqli
WEBCALENDAR_DB_HOST=mariadb
WEBCALENDAR_DB_DATABASE=webcalendar
WEBCALENDAR_DB_LOGIN=webcalendar
WEBCALENDAR_DB_PASSWORD=secure_password
WEBCALENDAR_INSTALL_PASSWORD=install_password

# Optional variables
WEBCALENDAR_SERVER_URL=https://calendar.example.com/
WEBCALENDAR_EMAIL_ENABLED=Y
WEBCALENDAR_SMTP_HOST=smtp.gmail.com
WEBCALENDAR_SMTP_PORT=587
```

### Production Docker Setup

```yaml
version: '3.8'

services:
  webcalendar:
    image: craigk5n/webcalendar:v1.9.13
    restart: unless-stopped
    environment:
      - WEBCALENDAR_USE_ENV=true
      - WEBCALENDAR_DB_TYPE=mysqli
      - WEBCALENDAR_DB_HOST=mariadb
      - WEBCALENDAR_DB_DATABASE=webcalendar
      - WEBCALENDAR_DB_LOGIN=webcalendar
      - WEBCALENDAR_DB_PASSWORD=secure_password
    volumes:
      - webcalendar_uploads:/var/www/html/webcalendar/uploads
    depends_on:
      - mariadb
    networks:
      - webcalendar_network

  mariadb:
    image: mariadb:10.6
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=webcalendar
      - MYSQL_USER=webcalendar
      - MYSQL_PASSWORD=secure_password
    volumes:
      - mariadb_data:/var/lib/mysql
    networks:
      - webcalendar_network

volumes:
  webcalendar_uploads:
  mariadb_data:

networks:
  webcalendar_network:
    driver: bridge
```

### Docker Development

For development with file mounting:

```bash
# Build development image
docker-compose -f docker/docker-compose-php8-dev.yml build

# Start development environment
docker-compose -f docker/docker-compose-php8-dev.yml up -d

# View logs
docker-compose -f docker/docker-compose-php8-dev.yml logs -f
```

### Docker Maintenance

#### Backup Docker Volumes

```bash
# Backup database volume
docker run --rm -v webcalendar_mariadb_data:/data -v $(pwd):/backup alpine tar czf /backup/mariadb_backup.tar.gz -C /data .

# Backup uploads volume
docker run --rm -v webcalendar_uploads:/data -v $(pwd):/backup alpine tar czf /backup/uploads_backup.tar.gz -C /data .
```

#### Update Containers

```bash
# Pull latest images
docker-compose pull

# Restart with new images
docker-compose up -d
```

#### Monitor Container Health

```bash
# Check container status
docker-compose ps

# View resource usage
docker stats

# Check logs
docker-compose logs -f webcalendar
```

---

## Additional Resources

- **Online Documentation**: [WebCalendar Documentation](https://github.com/craigk5n/webcalendar/wiki)
- **Community Forums**: [WebCalendar Support](https://sourceforge.net/p/webcalendar/discussion/)
- **Bug Reports**: [GitHub Issues](https://github.com/craigk5n/webcalendar/issues)
- **Security Issues**: Report to security@webcalendar.org

## Version History

- **v1.9.13**: Current release with MCP server support, Docker improvements, and security enhancements
- **v1.9.12**: Added PHP 8.x support and improved wizard installer
- **v1.9.11**: Enhanced mobile support and performance improvements
- **v1.9.10**: Security updates and database compatibility improvements

---

*This document covers WebCalendar v1.9.13. For older versions, please refer to the appropriate documentation in the repository.*