# WebCalendar Web-Based Upgrade Test

This directory contains automated tests for upgrading WebCalendar from older versions using the web-based installer wizard.

## Files

- `web-upgrade-test.sh` - Automated bash script that tests upgrading via the wizard
- `fixtures/v1.3.0-schema-sqlite3.sql` - SQL fixture for v1.3.0 database schema

## How It Works

The upgrade test:
1. Creates a SQLite database with an old version schema (e.g., v1.3.0)
2. Creates a settings.php pointing to this existing database
3. Starts the wizard and selects "Quick Upgrade"
4. Authenticates with the install password
5. Executes the database upgrade
6. Verifies:
   - Version number updated in database
   - Core tables still exist
   - Admin user preserved
   - New schema changes applied

## Prerequisites

Same as `web-install-test.sh`:
- PHP 8.0+ with sqlite3 extension
- sqlite3 CLI
- curl
- jq (for JSON parsing)
- netstat or ss (for port checking)

## Usage

### Test upgrade from v1.3.0 (default)
```bash
./tests/web-upgrade-test.sh
```

### Test upgrade from a different version
```bash
# You'll need to create the SQL fixture first
./tests/web-upgrade-test.sh --from-version=v1.9.0
```

### Run and clean up database after test
```bash
./tests/web-upgrade-test.sh --cleanup
```

### Run in debug mode (keeps all files for inspection)
```bash
./tests/web-upgrade-test.sh --debug
```

### Run inside Docker
```bash
docker run --rm -v $(pwd):/var/www/html -w /var/www/html php:8.2-cli \
  bash -c "apt-get update && apt-get install -y jq sqlite3 curl net-tools && ./tests/web-upgrade-test.sh --cleanup"
```

## Adding Support for New Old Versions

To test upgrading from a different version (e.g., v1.9.0):

1. Create a SQL fixture file: `tests/fixtures/v1.9.0-schema-sqlite3.sql`
2. Include all tables and schema as they existed in that version
3. Include the version marker: `INSERT INTO webcal_config VALUES ('WEBCAL_PROGRAM_VERSION', 'v1.9.0');`
4. Include at least one admin user for testing

Example SQL fixture structure:
```sql
-- Create all tables as they existed in the old version
CREATE TABLE webcal_config (...);
CREATE TABLE webcal_user (...);
-- ... other tables

-- Set the version marker
INSERT INTO webcal_config (cal_setting, cal_value) 
VALUES ('WEBCAL_PROGRAM_VERSION', 'v1.9.0');

-- Add test admin user
INSERT INTO webcal_user (cal_login, cal_passwd, cal_is_admin, ...) 
VALUES ('admin', 'md5_hash_here', 'Y', ...);
```

## SQL Fixtures

### v1.3.0 Schema
The v1.3.0 fixture includes:
- All core tables (webcal_config, webcal_user, webcal_entry, etc.)
- Standard v1.3.0 schema (before v1.9.0 changes)
- Version marker set to `v1.3.0`
- Test admin user (password: admin123)

## Upgrade Path Tested

The test verifies the upgrade path through the wizard:
1. **Welcome Page** - Detects existing installation with settings.php
2. **Quick Upgrade** - User selects "Quick Upgrade" option
3. **Authentication** - User enters install password
4. **Database Upgrade** - Wizard executes upgrade SQL
5. **Finish** - Upgrade complete

## Verification Steps

After upgrade, the test verifies:
- ✅ `WEBCAL_PROGRAM_VERSION` updated to current version
- ✅ Core tables still exist (webcal_config, webcal_user, webcal_entry, webcal_entry_user)
- ✅ Admin user preserved
- ✅ Critical schema changes applied (e.g., `cal_api_token` in `webcal_user`)
- ✅ Wizard reaches finish page

## Exit Codes

- `0` - Upgrade test passed
- `1` - Upgrade test failed (see error output)

## Troubleshooting

### "SQL fixture not found"
Create the SQL fixture file for the version you want to test:
```bash
touch tests/fixtures/v1.9.0-schema-sqlite3.sql
# Then populate it with the schema
```

### "Version not updated correctly"
The upgrade SQL may have failed. Check:
- `php_error.log` for SQL errors
- Database state with: `sqlite3 /tmp/webcalendar-upgrade-*.sqlite ".tables"`

### "Table X missing after upgrade"
The upgrade SQL might have dropped tables it shouldn't have. Check the upgrade SQL in `wizard/shared/upgrade-sql.php`.

## Integration with CI/CD

See `.github/workflows/test-web-wizard.yml` for GitHub Actions integration examples.

## Upgrade Logic Improvements

The installer wizard has been improved to handle SQLite upgrades more robustly:

1. **Global Scope for Updates**: The `$updates` array is now correctly loaded into the global scope so that `getSqlUpdates()` can find it during all steps of the wizard.
2. **Comment Stripping**: The upgrade executor now strips out SQL comments (lines starting with `--`) before execution, preventing errors in some database drivers like SQLite.
3. **SQLite Dialect Support**: Redundant MySQL-specific statements (like `ALTER TABLE ... MODIFY COLUMN`) are avoided or handled via `sqlite3-sql` overrides in `upgrade-sql.php`.

## Notes

- Uses SQLite3 for fast, isolated testing
- Each test creates a fresh database copy
- The wizard's "Quick Upgrade" flow is tested (not "Full Setup")
- Upgrade is non-destructive (admin user and data preserved)
- **Known limitation**: Some complex schema changes (like changing PRIMARY KEY on existing tables) are still not supported in SQLite upgrades and may require manual intervention or table recreation.
