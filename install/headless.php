<?php
/* This script can be used to update the database headlessly rather than using the
 * installation script.
 *
 * You must copy the settings.php file from your original installation, or create it
 * yourself in the case of a new install. This script will not prompt you for any of
 * your settings; and requires settings.php to be present and complete.
 */

if (php_sapi_name() !== 'cli') {
    echo 'This is a CLI script and should not be invoked via the web server';
    exit;
}

include_once __DIR__ . '/../includes/translate.php';
include_once __DIR__ . '/../includes/dbi4php.php';
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/default_config.php';
include_once __DIR__ . '/install_functions.php';
include_once __DIR__ . '/sql/upgrade_matrix.php';

define('__WC_BASEDIR', __DIR__ . '/../');
$fileDir = __WC_BASEDIR . 'includes';
$file    = $fileDir . '/settings.php';
chdir(__WC_BASEDIR);

// We need the $_SESSION superglobal to pass data to and from some of the update
// functions. Sessions are basically useless in CLI mode, but technically the
// session functions *do* work.
session_start();

// Load the settings.php file or get settings from env vars.
do_config(true);

// We'll grab database settings from settings.php.
$db_database = $settings['db_database'];
$db_host     = $settings['db_host'];
$db_login    = $settings['db_login'];
$db_password = (empty($settings['db_password'])
    ? '' : $settings['db_password']);
$db_persistent = false;
$db_type       = $settings['db_type'];
$real_db       = ($db_type == 'sqlite' || $db_type == 'sqlite3'
    ? get_full_include_path($db_database) : $db_database);


// Can we connect?
$c = null;
$dbVersion = null;
$detectedDbVersion = 'Unknown';
try {
    $c = dbi_connect($db_host, $db_login, $db_password, $real_db, false);
    $dbVersion = $detectedDbVersion = getDatabaseVersionFromSchema();
    $canConnectDb = true;
} catch (Exception $e) {
    // Could not connect
}
$connectError = '';
$canConnectDb = false;
if (!$canConnectDb)
    $connectError = dbi_error();
$emptyDatabase = $canConnectDb ?  isEmptyDatabase() : true;
$reportedDbVersion = 'Unknown';
$adminUserCount = 0;
$databaseExists = false;
$databaseCurrent = false;

if ($c && !empty($_SESSION['install_file'])) {
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
        case 'sqlite3':
            include_once 'sql/tables-sqlite3.php';
            populate_sqlite_db($real_db, $c);
            $install_filename = '';
            break;
        default:
            $install_filename .= 'mysql.sql';
    }
    executeSqlFromFile($install_filename);
}

// Convert passwords to secure hashes if needed.
// TODO: Move this into a function we specify in upgrade-sql.php.
echo "Checking passwords...\n";
$res = dbi_execute(
    'SELECT cal_login, cal_passwd FROM webcal_user',
    [],
    false,
    $show_all_errors
);
if ($res) {
    while ($row = dbi_fetch_row($res)) {
        if (strlen($row[1]) < 30)
            dbi_execute('UPDATE webcal_user SET cal_passwd = ?
        WHERE cal_login = ?', [password_hash($row[1], PASSWORD_DEFAULT), $row[0]]);
    }
    dbi_free_result($res);
}

// If new install, run 0 GMT offset
// just to set webcal_config.WEBCAL_TZ_CONVERSION.
// Commenting out since this was 15+ years ago
// convert_server_to_GMT();

require_once "sql/upgrade-sql.php";

$error = '';

//$detectedDbVersion = 'v1.9.0';
//echo "Install file: " . $install_filename . "<br>";
echo "Detected database schema version: $detectedDbVersion\n";
try {
    $success = true;
    if (empty($error)) {
        if ($emptyDb) {
            echo "Empty database -> creating all tables\n";
            executeSqlFromFile($install_filename);
        } else {
            if (empty($detectedDbVersion) || $detectedDbVersion == 'Unknown') {
                $error = translate('Unable to determine current database version.');
            } else {
                // Get a list of SQL commands and possibly PHP function names.
                // For any specific version, the function name should appear in this list after
                // the SQL commands allowing the upgrade function to use any new db changes.
                $sqlLines = getSqlUpdates($detectedDbVersion, $_SETTINGS['db_type'], true);
                //print_r($sqlLines); exit;
                foreach ($sqlLines as $sql) {
                    if (str_starts_with($sql, "function:")) {
                        // Need to run a PHP function
                        list(, $functionName) = explode(':', $sql);
                        if (function_exists($functionName)) {
                            echo "Executing function \"$functionName\"\n";
                            $functionName();
                        } else {
                            // Handle the error if function does not exist
                            $error = "Function $functionName does not exist.";
                        }
                    } else {
                        echo "Executing SQL: $sql \n";
                        $ret = dbi_execute($sql, [], false, true);
                        if (!$ret) {
                            $success = false;
                            $error = dbi_error();
                        }
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
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
    echo "Error: " . $error . "\n";
}
