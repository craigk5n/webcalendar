<?php
/* This script can be used to update the database headlessly rather than using the
 * installation script.
 *
 * You must copy the settings.php file from your original installation, or create it
 * yourself in the case of a new install. This script will not prompt you for any of
 * your settings; and requires settings.php to be present and complete.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable debug output (set to true for verbose logging)
define('DEBUG', false);

function debug_echo($message) {
    if (DEBUG) {
        echo $message . PHP_EOL;
    }
}

if (php_sapi_name() !== 'cli') {
    echo 'This is a CLI script and should not be invoked via the web server' . PHP_EOL;
    exit(1);
}

// Start output buffering to prevent headers-sent warnings
ob_start();

$required_files = [
    __DIR__ . '/../includes/translate.php',
    __DIR__ . '/../includes/dbi4php.php',
    __DIR__ . '/../includes/config.php',
    __DIR__ . '/default_config.php',
    __DIR__ . '/install_functions.php',
    __DIR__ . '/sql/upgrade_matrix.php',
    __DIR__ . '/sql/tables-sqlite3.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        echo "Error: Missing required file: $file" . PHP_EOL;
        ob_end_flush();
        exit(1);
    }
    debug_echo("Including file: $file");
    include_once $file;
}

define('__WC_BASEDIR', __DIR__ . '/../');
$fileDir = __WC_BASEDIR . 'includes';
$file = $fileDir . '/settings.php';
chdir(__WC_BASEDIR);

if (!file_exists($file)) {
    echo "Error: settings.php not found at $file" . PHP_EOL;
    ob_end_flush();
    exit(1);
}

// We need the $_SESSION superglobal to pass data to and from some of the update
// functions. Sessions are basically useless in CLI mode, but technically the
// session functions *do* work.
debug_echo("Starting session...");
session_name(getSessionName());
session_start();

// Load the settings.php file or get settings from env vars.
debug_echo("Loading configuration...");
do_config(true);

// We'll grab database settings from settings.php.
$db_database = $settings['db_database'] ?? '';
$db_host     = $settings['db_host'] ?? '';
$db_login    = $settings['db_login'] ?? '';
$db_password = (empty($settings['db_password']) ? '' : $settings['db_password']);
$db_persistent = false;
$db_type       = $settings['db_type'] ?? '';
$real_db       = ($db_type == 'sqlite' || $db_type == 'sqlite3'
    ? get_full_include_path($db_database) : $db_database);

if (empty($db_type)) {
    echo "Error: db_type not set in settings.php" . PHP_EOL;
    ob_end_flush();
    exit(1);
}

debug_echo("Database settings: type=$db_type, db=$real_db, host=$db_host, login=$db_login");

// Can we connect?
$c = null;
$dbVersion = null;
$detectedDbVersion = 'Unknown';
$canConnectDb = false;
$connectError = '';
try {
    debug_echo("Attempting database connection...");
    $c = dbi_connect($db_host, $db_login, $db_password, $real_db, false);
    if ($c) {
        $dbVersion = $detectedDbVersion = getDatabaseVersionFromSchema();
        $canConnectDb = true;
        debug_echo("Database connection successful. Detected version: $detectedDbVersion");
    } else {
        $connectError = dbi_error();
        echo "Error: Failed to connect to database: $connectError" . PHP_EOL;
        ob_end_flush();
        exit(1);
    }
} catch (Exception $e) {
    $connectError = $e->getMessage();
    echo "Error: Database connection exception: $connectError" . PHP_EOL;
    ob_end_flush();
    exit(1);
}

$emptyDatabase = $canConnectDb ? isEmptyDatabase() : true;
debug_echo("Empty database check: $emptyDatabase, db_type: $db_type, install_file: " . ($_SESSION['install_file'] ?? 'not set'));

if ($c && $emptyDatabase && $db_type === 'sqlite3') {
    $install_filename = $_SESSION['install_file'] ?? 'install/sql/tables-sqlite3.php';
    echo "Executing SQLite3 installation: $install_filename" . PHP_EOL;
    $resolved_path = realpath($install_filename) ?: $install_filename;
    debug_echo("Resolved install file path: $resolved_path");
    if (!file_exists($install_filename)) {
        echo "Error: Install file $install_filename not found" . PHP_EOL;
        ob_end_flush();
        exit(1);
    }
    try {
        debug_echo("Starting table creation...");
        populate_sqlite_db($real_db, $c);
        echo "SQLite database tables created successfully" . PHP_EOL;
        // Verify table creation
        $tables = dbi_query("SELECT name FROM sqlite_master WHERE type='table' AND name='webcal_user';");
        if ($tables && dbi_fetch_row($tables)) {
            debug_echo("Verified: webcal_user table exists");
        } else {
            echo "Error: webcal_user table not created" . PHP_EOL;
            ob_end_flush();
            exit(1);
        }
        // Set initial version for new database
        debug_echo("Setting initial database version...");
        if (!isset($PROGRAM_VERSION)) {
            $PROGRAM_VERSION = 'v1.9.12'; // Match latest version
            echo "Warning: PROGRAM_VERSION not set, using default: $PROGRAM_VERSION" . PHP_EOL;
        }
        updateVersionInDatabase();
        $detectedDbVersion = getDatabaseVersionFromSchema();
        echo "Version set to: $detectedDbVersion" . PHP_EOL;
    } catch (Exception $e) {
        echo "Error: Failed to populate SQLite database: " . $e->getMessage() . PHP_EOL;
        echo "Last SQL error: " . dbi_error() . PHP_EOL;
        ob_end_flush();
        exit(1);
    }
} elseif ($c && !empty($_SESSION['install_file'])) {
    $install_filename = (str_starts_with($dbVersion, "v")) ? "upgrade-" : "tables-";
    switch ($db_type) {
        case 'ibase':
        case 'mssql':
        case 'oracle':
            $install_filename .= $db_type . '.sql';
            break;
        case 'ibm_db2':
            $install_filename .= 'db2.sql';
            break;
        case 'odbc':
            $install_filename .= $_SESSION['odbc_db'] . '.sql';
            break;
        case 'postgresql':
            $install_filename .= 'postgres.sql';
            break;
        default:
            $install_filename .= 'mysql.sql';
    }
    debug_echo("Executing SQL file: $install_filename");
    executeSqlFromFile($install_filename);
}

// Convert passwords to secure hashes if needed.
// TODO: Move this into a function we specify in upgrade-sql.php.
echo "Checking passwords...\n";
$res = dbi_execute(
    'SELECT cal_login, cal_passwd FROM webcal_user',
    [],
    false,
    true
);
if ($res) {
    while ($row = dbi_fetch_row($res)) {
        if (strlen($row[1]) < 30) {
            debug_echo("Updating password for user: {$row[0]}");
            dbi_execute('UPDATE webcal_user SET cal_passwd = ? WHERE cal_login = ?',
                [password_hash($row[1], PASSWORD_DEFAULT), $row[0]]);
        }
    }
    dbi_free_result($res);
} else {
    echo "Error: Failed to query webcal_user: " . dbi_error() . PHP_EOL;
    ob_end_flush();
    exit(1);
}

// If new install, run 0 GMT offset
// just to set webcal_config.WEBCAL_TZ_CONVERSION.
// Commenting out since this was 15+ years ago
// convert_server_to_GMT();

require_once "sql/upgrade-sql.php";

$error = '';

echo "Detected database schema version: $detectedDbVersion\n";
try {
    $success = true;
    if (empty($error)) {
        if ($emptyDatabase && $db_type === 'sqlite3') {
            echo "New SQLite3 database, skipping upgrades...\n";
            // Version already set, no upgrades needed
        } else {
            $sqlLines = getSqlUpdates($detectedDbVersion, $db_type, true);
            foreach ($sqlLines as $sql) {
                if (str_starts_with($sql, "function:")) {
                    list(, $functionName) = explode(':', $sql);
                    if (function_exists($functionName)) {
                        debug_echo("Executing function: $functionName");
                        $functionName();
                    } else {
                        $error = "Function $functionName does not exist.";
                        echo "Error: $error\n";
                        $success = false;
                    }
                } else {
                    // Skip MySQL-specific MODIFY COLUMN for SQLite
                    if ($db_type === 'sqlite3' && preg_match('/ALTER TABLE.*MODIFY COLUMN/i', $sql)) {
                        debug_echo("Skipping MySQL-specific SQL for SQLite: $sql");
                        continue;
                    }
                    debug_echo("Executing SQL: $sql");
                    $ret = dbi_execute($sql, [], false, true);
                    if (!$ret) {
                        $success = false;
                        $error = dbi_error();
                        echo "Error: SQL execution failed: $error\n";
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    echo "Error: Exception during SQL updates: $error\n";
}
if (empty($error)) {
    updateVersionInDatabase();
    $msg = translate('Database successfully migrated from XXX to YYY');
    $msg = str_replace('XXX', $detectedDbVersion, $msg);
    $msg = str_replace('YYY', $PROGRAM_VERSION, $msg);
    echo $msg . "\n";
}
if (empty($error)) {
    echo "Success.\n";
} else {
    echo "Error: $error\n";
    exit(1);
}

ob_end_flush();
?>