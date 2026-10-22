# v2.0.x Development Guide

## Release Philosophy

WebCalendar uses a development/stable release model:

| Series | Role | What goes in |
|--------|------|--------------|
| **1.9.x** | Development | New features, installer rewrite, MCP, env-var config, doc modernization |
| **2.0.x** | Stable/production | Bug fixes, security hardening, code quality. No new features. |
| **2.1.x** | Next development | New functional changes, architectural work |

The 1.9.x series was the development line that introduced the wizard
installer, MCP server support, environment variable configuration, and
modernized documentation. The 2.0.x series is the production release
of that work.

## Scope for v2.0.x

### In scope

**Security hardening:**

- Replace `dbi_escape_string()` / `addslashes()` with prepared statements
- Centralize input validation (replace scattered `clean_*()` functions)
- Replace `unserialize()` with `json_decode()` for cache files
- Replace `openssl_random_pseudo_bytes()` with `random_bytes()`
- Enable CSRF protection by default
- Add Content Security Policy headers

**Code quality:**

- Replace `strcmp()` comparisons with `===`
- Replace `die()` / `exit()` with proper error handling
- Fix PHP 8.1+ deprecation warnings at the source (not suppressed)
- Add `declare(strict_types=1)` to files as they are touched
- Add type declarations to functions as they are touched

**Testing:**

- Fix `phpunit.xml` coverage path (`src/` to `includes/classes/`)
- Add tests for security-critical paths: database layer, input
  sanitization, CSRF, authentication
- Coverage target: 20-30%

**Documentation:**

- Modernized Markdown documentation (done in 1.9.x, carried forward)
- Wizard installer documentation
- Migration guide from 1.x

**Infrastructure:**

- PHP 8.0+ required, 8.2+ recommended
- Formally deprecate untested database backends (Oracle, DB2, ODBC,
  Interbase) in documentation
- Fix cross-platform Makefile (currently Linux-only due to `sha384sum`)

### Out of scope (deferred to 2.1.x or later)

- Namespaces / PSR-4 autoloading
- Breaking up `functions.php` into separate classes
- New CSS framework (Pico CSS, etc.)
- New JavaScript calendar library
- REST API / CalDAV
- Plugin architecture
- Any new user-facing features

## Workflow

All v2.0.x work should follow this pattern:

1. **Identify** a specific bug, security issue, or code quality problem
2. **Write a test** that demonstrates the issue (when practical)
3. **Fix** with minimal, surgical changes
4. **Verify** the fix does not break existing behavior
5. **Document** if the change affects users or administrators

Avoid scope creep. If a fix reveals a deeper architectural problem,
file it for 2.1.x rather than expanding the change.
