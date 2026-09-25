# WebCalendar Web-Based Installer Test

This directory contains automated tests for the WebCalendar web-based installer wizard.

## Files

- `web-install-test.sh` - Automated bash script that tests the wizard/ installer via HTTP requests

## Prerequisites

The following tools must be installed:
- PHP 8.0+ with sqlite3 and pdo_sqlite extensions
- sqlite3 CLI
- curl
- jq (for JSON parsing)
- netstat or ss (for port checking)

On Ubuntu/Debian:
```bash
sudo apt-get install php8.2-cli php8.2-sqlite3 sqlite3 curl jq net-tools
```

## Usage

### Run the test (keeps DB on success for debugging)
```bash
./tests/web-install-test.sh
```

### Run and clean up database after test
```bash
./tests/web-install-test.sh --cleanup
```

### Run in debug mode (keeps all files for inspection)
```bash
./tests/web-install-test.sh --debug
```

### Run inside Docker
```bash
# Using php:8.2-cli image (lightweight)
docker run --rm -v $(pwd):/var/www/html -w /var/www/html php:8.2-cli \
  bash -c "apt-get update && apt-get install -y jq sqlite3 curl net-tools && ./tests/web-install-test.sh --cleanup"

# Using docker-compose (if you have a compose file set up)
docker-compose -f docker/docker-compose-dev.yml up -d webcalendar
docker-compose -f docker/docker-compose-dev.yml exec webcalendar \
  bash -c "apt-get update && apt-get install -y jq sqlite3 curl net-tools && ./tests/web-install-test.sh --cleanup"
```

## What It Tests

The script performs a full installation via the web wizard:

1. **Welcome Page** - Loads the wizard welcome page
2. **Start Installation** - Clicks "Start Installation" button
3. **Set Password** - Sets the installation password
4. **PHP Settings** - Acknowledges PHP settings check
5. **App Settings** - Configures authentication and mode settings
6. **Test DB Connection** - Tests connection to SQLite database
7. **Save DB Settings** - Saves database configuration
8. **Create Database** - Creates the database (SQLite)
9. **Create Tables** - Executes SQL to create database tables
10. **Save Settings File** - Creates includes/settings.php
11. **Verification** - Verifies:
    - Database tables exist (webcal_config, webcal_user, etc.)
    - Admin user was created
    - Version is set in database
    - settings.php was created

## How It Works

The script:
1. Starts a PHP built-in web server on an available port
2. Uses curl to simulate user interactions with the wizard
3. Maintains session state using a cookie jar
4. Parses JSON responses from the wizard's AJAX endpoints
5. Verifies the installation by checking the database and settings file

## CI/CD Integration

See `.github/workflows/test-web-wizard.yml` for GitHub Actions integration.

The workflow:
- Runs on push to master/release branches
- Runs on pull requests
- Can be triggered manually via workflow_dispatch
- Has optional Docker-based testing

## Exit Codes

- `0` - All tests passed
- `1` - Test failed (see error output for details)

## Troubleshooting

### "Invalid JSON" error
This usually means PHP errors are being output along with the JSON response. The script sets `display_errors=Off` to prevent this, but if you're running the PHP server manually, ensure error display is disabled.

### Port already in use
The script automatically finds an available port starting from 8001. If ports 8001-8100 are all in use, it will fail with an error.

### Session/cookie issues
The script clears the cookie jar at the start. If you're debugging, check the `cookies.txt` file to see what cookies are being set.

## Notes

- The script uses SQLite3 for fast, isolated testing
- Database is created in `/tmp/` by default
- The wizard must return valid JSON for all AJAX endpoints
- Settings are configured for single-user mode with web authentication
