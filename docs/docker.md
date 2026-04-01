# Docker Deployment

WebCalendar provides Docker Compose configurations for production,
development, and testing.

## Table of Contents

- [Quick Start](#quick-start)
- [Available Configurations](#available-configurations)
- [Production Deployment](#production-deployment)
- [Development Environment](#development-environment)
- [SQLite Development](#sqlite-development)
- [Testing](#testing)
- [Environment Variables](#environment-variables)
- [Volumes and Data Persistence](#volumes-and-data-persistence)

## Quick Start

```bash
git clone https://github.com/craigk5n/webcalendar.git
cd webcalendar
docker-compose -f docker/docker-compose-php8.yml up
# Open http://localhost:8080
```

## Available Configurations

| File | Purpose | Port(s) | Database |
|------|---------|---------|----------|
| `docker-compose-php8.yml` | Production | 8080 | MariaDB |
| `docker-compose-php8.1.yml` | Production (PHP 8.1) | 8080 | MariaDB |
| `docker-compose-php8-dev.yml` | Development | 8080, 8081 | MariaDB + PostgreSQL |
| `docker-compose-php8.1-dev.yml` | Development (PHP 8.1) | 8080 | MariaDB |
| `docker-compose-sqlite-dev.yml` | Development (SQLite) | 8081 | SQLite3 |
| `docker-compose-test-mysql.yml` | CI testing | internal | MySQL 8.0 |
| `docker-compose-test-postgresql.yml` | CI testing | internal | PostgreSQL |
| `docker-compose-test-sqlite.yml` | CI testing | internal | SQLite3 |

## Production Deployment

```bash
docker-compose -f docker/docker-compose-php8.yml up -d
```

This starts:

- **PHP 8 + Apache** container on port 8080
- **MariaDB** container with persistent volume

On first access, the installation wizard runs automatically.

### Customizing Production

Override settings with environment variables or a `.env` file:

```bash
# .env file in the docker/ directory
MYSQL_ROOT_PASSWORD=secure_root_password
MYSQL_PASSWORD=secure_app_password
```

## Development Environment

The dev configurations mount your local files into the container so
edits are reflected immediately.

### MariaDB + PostgreSQL (dual database)

```bash
docker-compose -f docker/docker-compose-php8-dev.yml up
```

- Port 8080: WebCalendar with MariaDB
- Port 8081: WebCalendar with PostgreSQL

### MariaDB only (PHP 8.1)

```bash
docker-compose -f docker/docker-compose-php8.1-dev.yml up
```

- Port 8080: WebCalendar with MariaDB
- Local files mounted for live editing

## SQLite Development

No external database server needed:

```bash
docker-compose -f docker/docker-compose-sqlite-dev.yml up
```

- Port 8081: WebCalendar with SQLite3
- Database stored as a file inside the container

## Testing

Docker Compose files for CI run automated tests against each database:

```bash
# MySQL tests
docker-compose -f docker/docker-compose-test-mysql.yml up --abort-on-container-exit

# PostgreSQL tests
docker-compose -f docker/docker-compose-test-postgresql.yml up --abort-on-container-exit

# SQLite tests
docker-compose -f docker/docker-compose-test-sqlite.yml up --abort-on-container-exit
```

These use Selenium for browser-based testing and pytest for integration
tests.

## Environment Variables

All WebCalendar containers accept these environment variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `WEBCALENDAR_USE_ENV` | `true` | Enable env-based configuration |
| `WEBCALENDAR_DB_TYPE` | `mysqli` | Database backend |
| `WEBCALENDAR_DB_HOST` | `db` | Database hostname |
| `WEBCALENDAR_DB_DATABASE` | `webcalendar` | Database name |
| `WEBCALENDAR_DB_LOGIN` | `webcalendar` | Database username |
| `WEBCALENDAR_DB_PASSWORD` | `webcalendar` | Database password |
| `WEBCALENDAR_MODE` | (none) | Set to `dev` for development mode |

See [Configuration Reference](configuration.md) for the full list.

## Volumes and Data Persistence

Production configurations use named Docker volumes for the database.
To back up:

```bash
# MySQL/MariaDB
docker exec <db_container> mysqldump -u root -p webcalendar > backup.sql

# PostgreSQL
docker exec <db_container> pg_dump -U webcalendar webcalendar > backup.sql
```

To restore:

```bash
# MySQL/MariaDB
docker exec -i <db_container> mysql -u root -p webcalendar < backup.sql

# PostgreSQL
docker exec -i <db_container> psql -U webcalendar webcalendar < backup.sql
```
