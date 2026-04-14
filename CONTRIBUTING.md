# Contributing to WebCalendar

Thank you for your interest in contributing to WebCalendar! This
document explains how to get started.

## Code of Conduct

All contributors are expected to follow our
[Code of Conduct](CODE_OF_CONDUCT.md).

## Ways to Contribute

- **Bug fixes** — check [open issues](https://github.com/craigk5n/webcalendar/issues)
- **Features** — propose via an issue first
- **Documentation** — improve guides in `docs/`
- **Translations** — add or update language files in `translations/`
- **Testing** — expand test coverage or report bugs

## Development Setup

### Prerequisites

- PHP 8.0+ (8.2+ recommended)
- Composer
- MySQL, PostgreSQL, or SQLite3
- Git

### Quick Start

```bash
git clone https://github.com/craigk5n/webcalendar.git
cd webcalendar
composer install
make  # copies vendor assets to pub/
```

### Using Docker

```bash
docker-compose -f docker/docker-compose-php8-dev.yml up
# http://localhost:8080 (MariaDB) / http://localhost:8081 (PostgreSQL)
```

See [docs/developer-guide.md](docs/developer-guide.md) for full details.

## Running Tests

```bash
# Unit tests
vendor/bin/phpunit -c tests/phpunit.xml

# PHP syntax check
tests/compile_test.sh
```

## Coding Standards

- 2-space indentation
- UTF-8 encoding, LF line endings
- 80-character max line length
- Functions: `lowerCamelCase`
- Classes: `UpperCamelCase` in `includes/classes/ClassName.php`
- Constants: `UPPER_SNAKE_CASE`
- Database tables: `webcal_` prefix, `lower_snake_case`

See `.editorconfig` for editor settings.

## Submitting Changes

1. Fork the repository.
2. Create a feature branch from `master`:
   ```bash
   git checkout -b fix/brief-description
   ```
3. Make your changes. Keep commits focused and well-described.
4. Run tests and verify they pass.
5. Push and open a Pull Request against `master`.

### PR Guidelines

- Keep PRs focused — one bug fix or feature per PR.
- Include a clear description of what changed and why.
- Reference any related issues (e.g., "Fixes #123").
- Ensure all CI checks pass before requesting review.

## Reporting Bugs

Open a [GitHub issue](https://github.com/craigk5n/webcalendar/issues/new)
with:

- WebCalendar version
- PHP version
- Database type and version
- Steps to reproduce
- Expected vs. actual behavior

## Security Issues

Do **not** file security vulnerabilities as public issues. See
[SECURITY.md](SECURITY.md) for reporting instructions.
