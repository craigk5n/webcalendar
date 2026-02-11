# WebCalendar

[![Version](https://img.shields.io/badge/version-v1.9.13-blue.svg)](https://github.com/craigk5n/webcalendar/releases)
[![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-8.0%2B-8892BF.svg)](https://php.net)
[![CI](https://github.com/craigk5n/webcalendar/workflows/CI/badge.svg)](https://github.com/craigk5n/webcalendar/actions)
[![GitHub stars](https://img.shields.io/github/stars/craigk5n/webcalendar.svg?style=social&label=Star)](https://github.com/craigk5n/webcalendar)
[![Downloads](https://img.shields.io/github/downloads/craigk5n/webcalendar/total.svg)](https://github.com/craigk5n/webcalendar/releases)

WebCalendar is a **multi-user, web-based calendar application** built with PHP. It supports multiple database backends, features event management, user groups, access controls, and integrates with external applications. Designed for both personal and enterprise use, WebCalendar can be deployed on any web server with PHP support.

![WebCalendar Screenshot](https://www.k5n.us/wp-content/gallery/webcalendar/wcss-month.png)

## ✨ Features

- **📅 Multiple Calendar Views** - Month, week, day, year, and agenda views
- **👥 Multi-User Support** - User management with groups and permissions
- **🔄 Recurring Events** - Support for complex event repetition patterns
- **🔐 Access Control** - Granular permissions for viewing and editing events
- **📧 Email Notifications** - Event reminders and updates via email
- **📱 Responsive Design** - Works on desktop and mobile devices
- **🌐 Multi-Language** - Available in 30+ languages
- **📤 iCal Import/Export** - Import and export calendar data in iCalendar format
- **🔗 External Integration** - LDAP, IMAP, and custom authentication bridges
- **🐳 Docker Ready** - Pre-built Docker images for easy deployment
- **🤖 MCP Server** - Model Context Protocol support for AI assistant integration

## 🚀 Quick Start

### Using Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/craigk5n/webcalendar.git
cd webcalendar

# Start with Docker Compose
docker-compose -f docker/docker-compose-php8.yml up

# Access at http://localhost:8080
```

### Manual Installation

1. **Download** the latest release or clone the repository:
   ```bash
   git clone https://github.com/craigk5n/webcalendar.git
   ```

2. **Point your web server** to the WebCalendar directory

3. **Run the web-based installer** by visiting your WebCalendar URL:
   ```
   https://yourserver.com/webcalendar/
   ```
   The installer will automatically redirect to the setup wizard.

4. **Follow the guided setup** to configure your database and admin user

### Headless Installation

For automated deployments, use the headless installer:

```bash
php wizard/headless.php
```

See the [Installation Guide](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-SysAdmin.html) for detailed instructions.

## 🐳 Docker Development

Build and run a development environment with live file mounting:

```bash
# Build the development container
docker-compose -f docker/docker-compose-php8-dev.yml build

# Start the containers
docker-compose -f docker/docker-compose-php8-dev.yml up

# Access at http://localhost:8080
```

Changes to your local files are immediately reflected in the container.

## ⚙️ Configuration

### Environment Variables

WebCalendar supports containerized deployments via environment variables:

```apache
SetEnv WEBCALENDAR_USE_ENV true
SetEnv WEBCALENDAR_DB_TYPE mysqli
SetEnv WEBCALENDAR_DB_HOST localhost
SetEnv WEBCALENDAR_DB_DATABASE webcalendar
SetEnv WEBCALENDAR_DB_LOGIN webcalendar
SetEnv WEBCALENDAR_DB_PASSWORD "your_secure_password"
SetEnv WEBCALENDAR_MODE prod
```

Add these to your `.htaccess` file or web server configuration.

### Database Support

- ✅ MySQL / MariaDB (recommended)
- ✅ PostgreSQL
- ✅ SQLite3
- ✅ Oracle
- ✅ IBM DB2
- ✅ ODBC

## 🧪 Testing

Run the test suite with PHPUnit:

```bash
# Install dependencies
composer install

# Run PHPUnit tests
cd tests; ./run_unit_tests.sh; cd ..

# Syntax check all PHP files
cd tests; ./compile_test.sh; cd ..
```

## 🏗️ Building from Source

WebCalendar includes all required dependencies in the release (primarily in the `pub/` directory). You **do not need to run Composer** unless you are adding or updating dependencies.

If you need to modify dependencies:

```bash
# Install PHP dependencies (only needed for adding/updating dependencies)
composer install

# Copy vendor assets to project directories
make
```

Note: The Makefile requires Linux (uses `sha384sum`).

## 🔌 External Application Integration

WebCalendar can integrate with external systems for user authentication and configuration:

### User Integration

Create a bridge script in `includes/user-app-yourapp.php`:

```php
// Implement required functions for authentication
function user_valid_login($login, $password) { ... }
function user_get_users() { ... }
```

See [user-ldap.php](includes/user-ldap.php) and [user-app-joomla.php](includes/user-app-joomla.php) for examples.

### Configuration Integration

Create `includes/config-app-yourapp.php` to override settings dynamically.

## 🗺️ Roadmap

### v1.9.X (Current)
- Bug fixes and PHP 8.x compatibility
- Improved Docker support
- Translation improvements
- New web-based installer

### v2.0 (In Progress)
- Modernized codebase with PHP 8+ features
- Namespace implementation
- Enhanced security

## 📚 Documentation

- [📖 System Administrator's Guide](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-SysAdmin.html) - Installation, configuration, and FAQ
- [⬆️ Upgrading Instructions](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/UPGRADING.html)
- [🗄️ Database Schema](docs/WebCalendar-Database.md)
- [💻 Developer Guide](http://htmlpreview.github.io/?https://github.com/craigk5n/webcalendar/blob/master/docs/WebCalendar-DeveloperGuide.html)

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

WebCalendar is licensed under the [GNU General Public License v2.0](LICENSE).

## 🔗 Links

- 🌐 **Website**: https://k5n.us/webcalendar/
- 🐛 **Issues**: https://github.com/craigk5n/webcalendar/issues
- 💾 **Releases**: https://github.com/craigk5n/webcalendar/releases

## 👨‍💻 Maintainer

**Craig Knudsen** - [craig@k5n.us](mailto:craig@k5n.us) - [https://k5n.us](https://k5n.us)

See [AUTHORS](AUTHORS) for a complete list of contributors.
