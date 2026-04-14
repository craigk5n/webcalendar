# WebCalendar Developer Guide

Version: v1.9.16

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Directory Structure](#directory-structure)
- [Core Components](#core-components)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Dependencies](#dependencies)
- [Testing](#testing)
- [Database Abstraction](#database-abstraction)
- [Extension Points](#extension-points)
- [Translations](#translations)

## Architecture Overview

WebCalendar is a traditional PHP web application with server-rendered HTML. There is no MVC framework. Each root PHP file corresponds to a page (e.g., `month.php`, `week.php`, `day.php`, `edit_entry.php`).

Request lifecycle:

1. Browser requests a root PHP file.
2. The file includes `includes/init.php`, which bootstraps the application: starts the session, authenticates the user, opens a database connection, and loads required includes.
3. The page logic runs, queries the database through the abstraction layer, and renders HTML output directly.

## Directory Structure

```
/
├── *.php                  Root page files (month.php, week.php, admin.php, ...)
├── includes/
│   ├── init.php           Application bootstrap
│   ├── functions.php      ~6600 lines, 160+ utility functions (core logic)
│   ├── dbi4php.php        Database abstraction layer (8 backends)
│   ├── config.php         Loads settings.php or environment variables
│   ├── translate.php      Internationalization system
│   ├── user.php           Default (DB) authentication
│   ├── user-*.php         Pluggable auth bridges (LDAP, IMAP, NIS, Joomla)
│   ├── css/               Stylesheets and themes
│   └── classes/
│       ├── WebCalendar.php   Main application class
│       ├── Event.php         Event data object
│       ├── RptEvent.php      Repeating event data object
│       └── phpmailer/        PHPMailer library (3rd party)
├── wizard/                Installation and upgrade wizard
├── pub/                   Frontend vendor assets (populated by make)
├── tests/                 PHPUnit tests and test utilities
├── docker/                Docker configurations and Selenium tests
├── docs/                  Documentation
├── mcp.php                MCP server for AI assistant integration
└── composer.json          PHP dependency definitions
```

## Core Components

### includes/init.php

Bootstraps the application. Handles session initialization, authentication, database connection setup, and loading of required include files. Every page includes this file.

### includes/functions.php

Contains ~6600 lines and 160+ utility functions that implement the bulk of the application logic: event retrieval, date calculations, access control checks, HTML generation helpers, and more.

### includes/dbi4php.php

Database abstraction layer. Supported backends: MySQL/MariaDB, PostgreSQL, SQLite3. Legacy code exists for Oracle, DB2, ODBC, and Interbase but is untested on PHP 8. Provides a uniform interface so the rest of the codebase does not reference backend-specific APIs directly. See [Database Abstraction](#database-abstraction) for the API.

### includes/config.php

Loads application configuration from `includes/settings.php` (created by the installer) or from environment variables when `WEBCALENDAR_USE_ENV=true` is set.

### includes/translate.php

Internationalization system for multi-language support.

### includes/user.php and user-*.php

Pluggable authentication. The default `user.php` authenticates against the database. Alternative bridges (`user-ldap.php`, `user-imap.php`, `user-nis.php`, `user-app-joomla.php`) implement the same interface for external auth sources.

### includes/classes/

Object-oriented components:

- **WebCalendar.php** -- main application class
- **Event.php** -- event data object
- **RptEvent.php** -- repeating event data object
- **phpmailer/** -- PHPMailer email library (3rd party)

## Development Setup

### Prerequisites

- PHP 8.x
- Composer
- A supported database (MySQL, PostgreSQL, or SQLite3)
- GNU Make (for copying vendor assets on Linux)

### Clone and install

```bash
git clone https://github.com/craigk5n/webcalendar.git
cd webcalendar
composer install
make    # Copies vendor assets from vendor/ to pub/ (Linux only, requires sha384sum)
```

### Docker development

```bash
docker-compose -f docker/docker-compose-php8.1-dev.yml build
docker-compose -f docker/docker-compose-php8.1-dev.yml up
# Access at http://localhost:8080/
```

### Dev mode

Set the environment variable `WEBCALENDAR_MODE=dev` to enable development mode.

## Coding Standards

The project uses 2-space indentation, UTF-8 encoding, LF line endings, and an 80-character maximum line length. See `.editorconfig` in the repository root for editor configuration.

### Naming conventions

| Element         | Convention          | Example                          |
|-----------------|---------------------|----------------------------------|
| Classes         | UpperCamelCase      | `WebCalendar`, `RptEvent`        |
| Class files     | ClassName.php       | `includes/classes/Event.php`     |
| Functions       | lowerCamelCase      | `getPostValue()`, `isAllDay()`   |
| Variables       | lowerCamelCase      | `$eventDate`, `$loginName`       |
| Constants       | UPPER_SNAKE_CASE    | `define('MAX_EVENTS', 100)`      |
| DB tables       | webcal_ prefix      | `webcal_entry`, `webcal_user`    |
| Config values   | UPPER_SNAKE_CASE    | `DISPLAY_WEEKENDS`, `WORK_DAY_START_HOUR` |

Functions should use verb phrases (`getPostValue`, `isAllDay`). Variables should be descriptive nouns.

Config values are stored in the `webcal_config` (system-wide) and `webcal_user_pref` (per-user) tables.

## Dependencies

### Runtime (composer.json require)

| Package                   | Version  | Purpose              |
|---------------------------|----------|----------------------|
| components/jquery         | 3.5.*    | DOM manipulation     |
| twbs/bootstrap            | 4.6.*    | CSS framework        |
| twbs/bootstrap-icons      | 1.10.*   | Icon set             |
| tinymce/tinymce           | 7.7.*    | Rich text editor     |
| phpmailer/phpmailer       | 7.0.*    | Email sending        |
| mcp/sdk                   | ^0.3.0   | MCP server support   |

### Development (composer.json require-dev)

| Package                       | Version  | Purpose             |
|-------------------------------|----------|---------------------|
| phpunit/phpunit               | 9.6.*    | Unit testing        |
| phpstan/phpstan               | ^2.0     | Static analysis     |
| friendsofphp/php-cs-fixer    | ^3.86    | Code formatting     |
| squizlabs/php_codesniffer    | ^4.0     | Coding standards    |

Vendor assets are not served directly from `vendor/`. The `Makefile` copies the necessary frontend assets from `vendor/` to `pub/`, which is the web-accessible directory.

## Testing

### Unit tests

```bash
vendor/bin/phpunit -c tests/phpunit.xml            # Run all tests
vendor/bin/phpunit tests/functionsTest.php          # Run a specific test file
```

Test files include `functionsTest.php`, `RepeatingEventsTest.php`, `EventTest.php`, and others under `tests/`.

### PHP syntax check

```bash
./tests/compile_test.sh
```

Verifies that all PHP files in the project compile without syntax errors.

### Selenium UI tests

End-to-end browser tests are located in `docker/tests/` and use Python with pytest. These run against the Docker development environment.

## Database Abstraction

All database access goes through `includes/dbi4php.php`. The key functions are:

| Function         | Purpose                                |
|------------------|----------------------------------------|
| `dbi_connect()`  | Open a database connection             |
| `dbi_query()`    | Execute a SQL query string             |
| `dbi_execute()`  | Execute a prepared/parameterized query |
| `dbi_fetch_row()`| Fetch the next result row as an array  |
| `dbi_error()`    | Return the last database error message |
| `dbi_close()`    | Close the database connection          |

Supported backends: MySQL/MariaDB, PostgreSQL, SQLite3. Legacy code paths exist for Oracle, DB2, ODBC, and Interbase but are untested on PHP 8.

Database tables use the `webcal_` prefix with `lower_snake_case` names. See `docs/WebCalendar-Database.md` for the full schema.

## Extension Points

### Authentication bridges

Create `includes/user-app-yourapp.php` implementing the required auth interface functions (`user_valid_login()`, `user_get_users()`, etc.) to integrate with an external authentication system.

### Configuration overrides

Create `includes/config-app-yourapp.php` to provide application-specific configuration overrides.

### Themes

CSS theme files are located in `includes/css/`. Add or modify stylesheets there to change the application appearance.

### Custom event fields (site extras)

Additional event fields can be configured through the admin UI without code changes.

### MCP server

`mcp.php` in the project root exposes calendar operations as MCP tools for AI assistant integration. It uses STDIO transport and requires an API token set via the `MCP_TOKEN` environment variable. Available tools: `list_events`, `get_user_info`, `search_events`, `add_event`.

## Translations

WebCalendar includes translation files for 100+ languages in the
`translations/` directory. Approximately 35 are human-contributed with
substantial coverage; the remainder were generated via AI-assisted bulk
translation (see [AI-Assisted Translation](#ai-assisted-translation))
and should be reviewed by native speakers before being considered
production-quality.

### File Format

Translation files are plain text with the format:

```
English phrase: translated phrase
```

Lines starting with `#` are comments. A blank line ends the header
comment block. If the translation is identical to English, use `=`:

```
January: =
```

The reference file is `translations/English-US.txt`.

### Adding a New Language

1. Copy `English-US.txt` to your new language file:
   ```bash
   cp translations/English-US.txt translations/YourLanguage.txt
   ```
2. Translate all text to the **right** of the `: `. Do not modify text
   to the left.
3. For the month "May", note the special entry `May_` which should be
   the full month name, while `May` is the abbreviation.
4. Run the check tool to find missing translations:
   ```bash
   perl tools/check_translation.pl YourLanguage
   ```
5. Register the new language in `includes/translate.php`:
   - Add an entry to the `$languages` array
   - Add an entry to the `$browser_languages` array
6. Test by selecting the language in user Preferences.
7. Submit a pull request.

### Updating an Existing Translation

1. Run the update tool to find missing entries:
   ```bash
   perl tools/update_translation.pl YourLanguage
   ```
   This scans all PHP files for `translate()` calls and marks missing
   entries with `<< MISSING >>` in the translation file.
2. Open the translation file and search for `MISSING`. Translate each
   marked entry.
3. Run the check tool to verify completeness:
   ```bash
   perl tools/check_translation.pl YourLanguage
   ```

### AI-Assisted Translation

For bulk translation of missing entries, a Python script using OpenAI
is available:

```bash
cd tools
OPENAI_API_KEY=your-key python3 complete-translation.py YourLanguage > ../translations/YourLanguage.txt
perl update_translation.pl YourLanguage
```

Review the output — AI translations may have minor issues like extra
quotes. This tool works best with UTF-8 translation files.

### Translation Tools Summary

| Tool | Purpose |
|------|---------|
| `tools/check_translation.pl` | Report missing translations |
| `tools/update_translation.pl` | Scan code and update translation file with missing entries |
| `tools/complete-translation.py` | Use OpenAI to auto-translate missing entries |
