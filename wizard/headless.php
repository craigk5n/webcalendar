<?php
/**
 * Headless WebCalendar Install Wizard - CLI Automation
 *
 * This script allows automated installation/upgrade of WebCalendar without
 * requiring a web browser. Useful for Docker deployments and CI/CD pipelines.
 *
 * Usage:
 *   php headless.php [options]
 *
 * Options:
 *   --db-type=TYPE         Database type (mysqli, postgresql, sqlite3, oracle, etc.)
 *   --db-host=HOST         Database server hostname
 *   --db-login=USER        Database username
 *   --db-password=PASS     Database password
 *   --db-database=NAME     Database name (or SQLite3 file path)
 *   --db-cachedir=PATH     Cache directory (optional)
 *   --user-auth=METHOD     Authentication method (web, http, none)
 *   --user-db=BACKEND      User database backend (user.php, user-ldap.php, etc.)
 *   --single-user=LOGIN    Single user login (if auth=none)
 *   --admin-login=LOGIN    Admin username
 *   --admin-password=PASS  Admin password
 *   --admin-email=EMAIL    Admin email (optional)
 *   --install-password=PASS  Install/wizard password for settings.php
 *   --readonly             Enable read-only mode
 *   --dev-mode             Enable development mode
 *   --use-env              Use WEBCALENDAR_* environment variables for DB settings
 *   --from-settings        Use existing includes/settings.php for DB settings
 *   --force                Force overwrite existing settings
 *   --help                 Show this help message
 *
 * Example:
 *   php headless.php --db-type=mysqli --db-host=localhost --db-login=root
 *     --db-password=secret --db-database=webcalendar --admin-login=admin
 *     --admin-password=admin123
 *
 * Exit codes:
 *   0 - Success
 *   1 - Missing required parameters
 *   2 - Database connection failed
 *   3 - Database creation failed
 *   4 - Table creation/upgrade failed
 *   5 - Admin user creation failed
 *   6 - Settings file write failed
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
  die("This script can only be run from the command line.\n");
}

// Load required classes
require_once __DIR__ . '/WizardState.php';
require_once __DIR__ . '/WizardValidator.php';
require_once __DIR__ . '/WizardDatabase.php';

<<<<<<< HEAD
const PROGRAM_VERSION = 'v1.9.13';
=======
const PROGRAM_VERSION = 'v1.9.14';
>>>>>>> dev

// Parse command line arguments
$options = getopt('', [
  'db-type:',
  'db-host:',
  'db-login:',
  'db-password:',
  'db-database:',
  'db-cachedir:',
  'user-auth:',
  'user-db:',
  'single-user:',
  'admin-login:',
  'admin-password:',
  'admin-email:',
  'install-password:',
  'readonly',
  'dev-mode',
  'use-env',
  'from-settings',
  'force',
  'help',
]);

// Show help
if (isset($options['help']) || empty($options)) {
  showHelp();
  exit(0);
}

$useEnv = isset($options['use-env']);
$fromSettings = isset($options['from-settings']);
$skipSettingsFile = $useEnv || $fromSettings;

// Initialize state
$state = new WizardState();
$state->isInitialized = true;
$state->isValidUser = true;
$state->phpSettingsAcked = true;

// Load settings from environment variables or existing settings.php
if ($useEnv) {
  // Map WEBCALENDAR_* env vars to state
  $envMap = [
    'WEBCALENDAR_DB_TYPE' => 'dbType',
    'WEBCALENDAR_DB_HOST' => 'dbHost',
    'WEBCALENDAR_DB_LOGIN' => 'dbLogin',
    'WEBCALENDAR_DB_PASSWORD' => 'dbPassword',
    'WEBCALENDAR_DB_DATABASE' => 'dbDatabase',
    'WEBCALENDAR_DB_CACHEDIR' => 'dbCacheDir',
  ];
  foreach ($envMap as $envKey => $prop) {
    $val = getenv($envKey);
    if ($val !== false && $val !== '') {
      $state->$prop = $val;
    }
  }
  $state->usingEnv = true;
} elseif ($fromSettings) {
  $settingsPath = __DIR__ . '/../includes/settings.php';
  if (!file_exists($settingsPath)) {
    echo "ERROR: includes/settings.php not found.\n";
    exit(1);
  }
  $state->loadFromSettingsFile($settingsPath);
}

// CLI args override env/settings values when provided
if (isset($options['db-type'])) $state->dbType = $options['db-type'];
if (isset($options['db-host'])) $state->dbHost = $options['db-host'];
if (isset($options['db-login'])) $state->dbLogin = $options['db-login'];
if (isset($options['db-password'])) $state->dbPassword = $options['db-password'];
if (isset($options['db-database'])) $state->dbDatabase = $options['db-database'];
if (isset($options['db-cachedir'])) $state->dbCacheDir = $options['db-cachedir'];

// Set application settings from CLI args (override env/settings)
$state->userAuth = $options['user-auth'] ?? $state->userAuth ?: 'web';
$state->userDb = $options['user-db'] ?? $state->userDb ?: 'user.php';
if (isset($options['single-user'])) $state->singleUserLogin = $options['single-user'];
if (isset($options['readonly'])) $state->readonly = true;
if (isset($options['dev-mode'])) $state->runMode = 'dev';

// Set install password if provided
if (isset($options['install-password'])) {
  $state->setInstallPassword($options['install-password']);
}

// Validate required parameters based on mode
if (!$skipSettingsFile) {
  // Full CLI mode: require DB settings
  $required = ['db-type', 'db-database'];
  $missing = [];
  foreach ($required as $param) {
    $prop = str_replace('-', '', lcfirst(implode('', array_map('ucfirst', explode('-', $param)))));
    // Map param names to state properties
    $propMap = [
      'dbtype' => 'dbType',
      'dbdatabase' => 'dbDatabase',
    ];
    $stateProp = $propMap[$prop] ?? $prop;
    if (empty($state->$stateProp)) {
      $missing[] = $param;
    }
  }
  // Non-sqlite3 DBs need host and login
  if ($state->dbType !== 'sqlite3') {
    if (empty($state->dbHost)) $missing[] = 'db-host';
    if (empty($state->dbLogin)) $missing[] = 'db-login';
  }
  if (!empty($missing)) {
    echo "ERROR: Missing required parameters: " . implode(', ', $missing) . "\n";
    echo "Use --help for usage information.\n";
    exit(1);
  }
} else {
  // --use-env or --from-settings: validate that DB settings are populated
  if (empty($state->dbType) || empty($state->dbDatabase)) {
    echo "ERROR: Database type and database name must be available from "
      . ($useEnv ? "environment variables" : "settings.php") . ".\n";
    exit(1);
  }
}

// Check if settings already exist (only when writing settings.php)
$settingsPath = __DIR__ . '/../includes/settings.php';
if (!$skipSettingsFile && file_exists($settingsPath) && !isset($options['force'])) {
  echo "WARNING: Settings file already exists at $settingsPath\n";
  echo "Use --force to overwrite.\n";
  exit(1);
}

echo "WebCalendar Headless Installer\n";
echo "==============================\n\n";

if ($useEnv) {
  echo "Mode: Using environment variables\n";
} elseif ($fromSettings) {
  echo "Mode: Using existing settings.php\n";
} else {
  echo "Mode: CLI arguments\n";
}
echo "\n";

// Test database connection
echo "Step 1: Testing database connection...\n";
$db = new WizardDatabase($state);

if (!$db->testConnection()) {
  echo "ERROR: Database connection failed: " . $db->getError() . "\n";
  exit(2);
}

echo "  Database connection successful\n";

// Check database status
$db->checkDatabase();

// Create database if needed
if (!$state->databaseExists) {
  echo "\nStep 2: Creating database...\n";
  if (!$db->createDatabase()) {
    echo "ERROR: Failed to create database: " . $db->getError() . "\n";
    $db->closeConnection();
    exit(3);
  }
  echo "  Database created\n";
} else {
  echo "\nStep 2: Database already exists\n";

  // Check for upgrade
  if ($state->isUpgrade) {
    echo "  Detected version: {$state->detectedDbVersion}\n";
    echo "  Target version: " . PROGRAM_VERSION . "\n";
  }
}

// Create/upgrade tables
echo "\nStep 3: Creating/upgrading database tables...\n";
if (!$db->executeUpgrade()) {
  echo "ERROR: Failed to create/upgrade tables: " . $db->getError() . "\n";
  $db->closeConnection();
  exit(4);
}

echo "  Tables created/upgraded successfully\n";

// Reconnect to check admin users
$db->closeConnection();
if (!$db->testConnection()) {
  echo "ERROR: Failed to reconnect to database\n";
  exit(2);
}

// Check admin users
$db->checkDatabase();

// Only require admin credentials when no admin users exist
if ($state->adminUserCount == 0) {
  $adminLogin = $options['admin-login'] ?? '';
  $adminPassword = $options['admin-password'] ?? '';

  if (empty($adminLogin) || empty($adminPassword)) {
    echo "ERROR: No admin users exist. --admin-login and --admin-password are required.\n";
    $db->closeConnection();
    exit(1);
  }

  echo "\nStep 4: Creating admin user...\n";
  $adminEmail = $options['admin-email'] ?? '';

  if (!$db->createAdminUser($adminLogin, $adminPassword, $adminEmail)) {
    echo "ERROR: Failed to create admin user: " . $db->getError() . "\n";
    $db->closeConnection();
    exit(5);
  }

  echo "  Admin user '{$adminLogin}' created\n";
} else {
  echo "\nStep 4: Admin users already exist ({$state->adminUserCount})\n";
}

$db->closeConnection();

// Generate and save settings file (skip for --use-env and --from-settings)
if ($skipSettingsFile) {
  echo "\nStep 5: Skipping settings.php (using "
    . ($useEnv ? "environment variables" : "existing settings.php") . ")\n";
} else {
  echo "\nStep 5: Saving settings...\n";
  $settingsContent = generateSettingsContent($state);

  // Ensure includes directory exists
  $includesDir = dirname($settingsPath);
  if (!is_dir($includesDir)) {
    mkdir($includesDir, 0755, true);
  }

  if (file_put_contents($settingsPath, $settingsContent) === false) {
    echo "ERROR: Failed to write settings file to $settingsPath\n";
    echo "Make sure the directory is writable by the web server.\n";
    exit(6);
  }

  echo "  Settings saved to $settingsPath\n";
}

// Summary
echo "\n==============================\n";
echo "Installation Complete!\n";
echo "==============================\n\n";
echo "Database: {$state->dbType}://{$state->dbHost}/{$state->dbDatabase}\n";
echo "Version: " . PROGRAM_VERSION . "\n";
echo "Admin Users: " . ($state->adminUserCount > 0 ? $state->adminUserCount : 1) . "\n";
if (!$skipSettingsFile) {
  echo "Settings File: $settingsPath\n";
}
echo "\nYou can now access WebCalendar at:\n";
echo "  " . getBaseUrl() . "\n\n";

exit(0);

/**
 * Generate settings.php content using colon-separated format
 * matching the format expected by includes/config.php.
 */
function generateSettingsContent(WizardState $state): string
{
  $settings = $state->getSettingsArray();
  $date = date('D, d M Y H:i:s T');

  $content = "<?php\n";
  $content .= "/* updated using WebCalendar " . PROGRAM_VERSION
    . " via wizard/headless.php on " . $date . "\n";

  foreach ($settings as $key => $value) {
    if ($value === null) {
      continue;
    }
    if (is_bool($value)) {
      $content .= $key . ": " . ($value ? 'true' : 'false') . "\n";
    } else {
      $content .= $key . ": " . $value . "\n";
    }
  }

  $content .= "# end settings.php */\n?>\n";
  return $content;
}

/**
 * Show help message
 */
function showHelp(): void
{
  echo <<<HELP
WebCalendar Headless Installer

Usage: php headless.php [options]

Required Options (when not using --use-env or --from-settings):
  --db-type=TYPE         Database type (mysqli, postgresql, sqlite3, oracle, ibm_db2, odbc, ibase)
  --db-host=HOST         Database server hostname (not needed for sqlite3)
  --db-login=USER        Database username (not needed for sqlite3)
  --db-database=NAME     Database name (or SQLite3 file path)
  --admin-login=LOGIN    Admin username (required only if no admin users exist)
  --admin-password=PASS  Admin password (required only if no admin users exist)

Optional Options:
  --db-password=PASS     Database password
  --db-cachedir=PATH     Cache directory
  --user-auth=METHOD     Authentication: web (default), http, or none
  --user-db=BACKEND      User DB: user.php (default), user-ldap.php, user-nis.php, user-imap.php, user-joomla.php
  --single-user=LOGIN    Single user login (when user-auth=none)
  --admin-email=EMAIL    Admin email address
  --install-password=PASS  Install/wizard password (included in settings.php)
  --readonly             Enable read-only mode
  --dev-mode             Enable development mode (verbose errors)
  --use-env              Read DB settings from WEBCALENDAR_* environment variables (skip settings.php write)
  --from-settings        Read DB settings from existing includes/settings.php (skip settings.php write)
  --force                Overwrite existing settings file
  --help                 Show this help message

Examples:

1. MySQL Installation:
   php headless.php --db-type=mysqli --db-host=localhost \\
     --db-login=root --db-password=secret --db-database=webcalendar \\
     --admin-login=admin --admin-password=admin123

2. SQLite3 Installation:
   php headless.php --db-type=sqlite3 \\
     --db-database=/var/www/webcalendar/data/webcalendar.sqlite3 \\
     --admin-login=admin --admin-password=admin123

3. Using environment variables (Docker):
   export WEBCALENDAR_DB_TYPE=mysqli
   export WEBCALENDAR_DB_HOST=db
   export WEBCALENDAR_DB_LOGIN=root
   export WEBCALENDAR_DB_PASSWORD=secret
   export WEBCALENDAR_DB_DATABASE=webcalendar
   php headless.php --use-env --admin-login=admin --admin-password=admin123

4. Using existing settings.php:
   php headless.php --from-settings

Exit Codes:
  0 - Success
  1 - Missing required parameters
  2 - Database connection failed
  3 - Database creation failed
  4 - Table creation/upgrade failed
  5 - Admin user creation failed
  6 - Settings file write failed

HELP;
}

/**
 * Get base URL
 */
function getBaseUrl(): string
{
  $scheme = 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = $_SERVER['SCRIPT_NAME'] ?? '/wizard/headless.php';
  $path = dirname(dirname($script));
  return $scheme . '://' . $host . $path . '/';
}
