# WebCalendar Install Wizard

A modern Bootstrap 5-based installation wizard for WebCalendar v1.9.13+

## Features

- **Single Page Application (SPA)**: Smooth, AJAX-based navigation without page reloads
- **Bootstrap 5 Design**: Modern, responsive UI with local assets (no CDN)
- **Dark Mode Support**: Toggle between light and dark themes
- **Real-time Validation**: Instant field validation as you type
- **Progress Tracking**: Visual progress bar and step indicators
- **Summary Review**: Review all settings before final save
- **Database Upgrade Support**: Automatically detects and upgrades old database versions
- **CLI Automation**: Headless mode for Docker/CI-CD deployments
- **PHP 8.0+**: Uses modern PHP features including typed properties

## Installation Steps

1. **Welcome** - Overview and system check
2. **Authentication** - Set install password or log in
3. **PHP Settings** - Verify PHP requirements
4. **Application Settings** - Configure authentication and mode
5. **Database** - Set up database connection
6. **Create DB** - Create database if needed
7. **Tables** - Create/upgrade database tables
8. **Admin User** - Create default admin account
9. **Summary** - Review all settings
10. **Finish** - Complete installation

## Usage

### Web Installation

1. Upload WebCalendar files to your web server
2. Access the wizard at: `http://yourserver/wizard/`
3. Follow the step-by-step instructions
4. Remove or rename the wizard directory when complete

### CLI Installation (Headless)

```bash
# MySQL/MariaDB Installation
php wizard/headless.php --db-type=mysqli \
  --db-host=localhost \
  --db-login=root \
  --db-password=secret \
  --db-database=webcalendar \
  --admin-login=admin \
  --admin-password=admin123

# SQLite3 Installation
php wizard/headless.php --db-type=sqlite3 \
  --db-database=/path/to/webcalendar.sqlite3 \
  --admin-login=admin \
  --admin-password=admin123

# PostgreSQL with custom options
php wizard/headless.php --db-type=postgresql \
  --db-host=localhost \
  --db-login=webuser \
  --db-password=secret \
  --db-database=webcalendar \
  --user-auth=web \
  --user-db=user-ldap.php \
  --admin-login=admin \
  --admin-password=admin123 \
  --admin-email=admin@example.com \
  --force
```

See `php wizard/headless.php --help` for all options.

## Database Support

- **MySQL/MariaDB** (mysqli)
- **PostgreSQL** (postgresql)
- **SQLite3** (sqlite3)
- **Oracle** (oracle)
- **IBM DB2** (ibm_db2)
- **ODBC** (odbc)
- **InterBase/Firebird** (ibase)

## Directory Structure

```
wizard/
├── index.php              # Main SPA entry point
├── headless.php           # CLI automation script
├── WizardState.php        # State management (PHP8 typed properties)
├── WizardValidator.php    # Validation logic
├── WizardDatabase.php     # Database operations
├── wizard.js              # Client-side SPA logic
├── wizard.css             # Custom styles with dark mode
├── wizard_assets/         # Local Bootstrap 5 assets
│   ├── css/bootstrap.min.css
│   └── js/bootstrap.bundle.min.js
├── steps/                 # Step templates
│   ├── welcome.php
│   ├── auth.php
│   ├── phpsettings.php
│   ├── appsettings.php
│   ├── dbsettings.php
│   ├── createdb.php
│   ├── dbtables.php
│   ├── adminuser.php
│   ├── summary.php
│   └── finish.php
└── shared/                # SQL schemas, config defaults, and upgrade logic
    ├── default_config.php
    ├── tables-mysql.sql
    ├── tables-postgres.sql
    ├── tables-sqlite3.php
    ├── tables-oracle.sql
    ├── tables-db2.sql
    ├── tables-ibase.sql
    ├── upgrade_matrix.php
    └── upgrade-sql.php
```

## Database Upgrade Process

The wizard automatically detects existing WebCalendar installations and handles upgrades:

1. **Version Detection**: Checks `webcal_config.WEBCAL_PROGRAM_VERSION` for modern versions
2. **Schema Testing**: For older databases, tests table structures to determine version
3. **SQL Generation**: Retrieves appropriate upgrade commands from upgrade matrix
4. **Safe Execution**: Applies SQL and PHP upgrade functions in order
5. **Version Update**: Updates database version after successful upgrade

## Configuration Options

### User Authentication

- **WebCalendar** (default): Built-in web-based login
- **HTTP Auth**: Server-level authentication (.htaccess)
- **Single User**: No login required

### User Database Backends

- **WebCalendar** (default): Internal database
- **LDAP**: Connect to LDAP directory
- **NIS**: Network Information Service
- **IMAP**: IMAP/POP3 server authentication
- **Joomla**: Joomla! CMS integration

### Run Modes

- **Production**: Standard operation with minimal error display
- **Development**: Verbose error messages for debugging

## Security Notes

- Always remove the wizard directory after installation: `rm -rf wizard/`
- Set proper permissions on `includes/settings.php`: `chmod 640`
- Use strong passwords for both install wizard and admin accounts
- Consider renaming the wizard directory during development

## Compatibility

- **PHP**: 8.0 or higher
- **Web Browsers**: All modern browsers (Chrome, Firefox, Safari, Edge)
- **WebCalendar**: v1.9.13+

## Troubleshooting

### Database Connection Failed

- Verify database server is running
- Check credentials and host address
- Ensure database user has CREATE DATABASE privileges
- For SQLite3, ensure the directory is writable

### Permission Errors

- Ensure web server can write to `includes/` directory
- Check file ownership: `chown -R www-data:www-data wizard/`
- Verify `includes/` directory is writable

### PHP Requirements Not Met

- Upgrade to PHP 8.0 or higher
- Enable required extensions (mysqli, pgsql, etc.)
- Check `file_uploads` and `allow_url_fopen` settings

## Credits

Based on the original WebCalendar installer by Craig Knudsen and contributors.
Modernized with Bootstrap 5, PHP 8 features, and SPA architecture.
