<?php
include_once "sql/upgrade_matrix.php";
require_once "sql/upgrade-sql.php";

$error = '';

if (empty($error)) {
  $file_base = __DIR__ . '/sql/' . ($emptyDatabase ? 'tables' : 'upgrade');
  $install_filename = $file_base . '-';
  switch ($db_type) {
    case 'ibase':
    case 'mssql':
    case 'oracle':
      $install_filename .= $db_type . '.sql';
      break;
    case 'ibm_db2':
      $install_filename .= 'db2.sql';
      break;
      // Need to add more form fields to capture odbc db type...
    case 'odbc':
      $error = 'ODBC tables must be created/updated manually';
      //  $install_filename .= $_SESSION['odbc_db'] . '.sql';
      //  break;
    case 'postgresql':
      $install_filename .= 'postgres.sql';
      break;
    case 'sqlite3':
      require_once 'sql/tables-sqlite3.php';
      populate_sqlite_db($real_db, $c);
      $install_filename = '';
      break;
    default:
      $install_filename .= 'mysql.sql';
  }
}

//$detectedDbVersion = 'v1.9.0';
//echo "Install file: " . $install_filename . "<br>";
try {
  $success = true;
  if (empty($error)) {
    if ($emptyDatabase) {
      executeSqlFromFile($install_filename);
    } else {
      if (empty($detectedDbVersion) || $detectedDbVersion == 'Unknown') {
        $error = translate('Unable to determine current database version.');
      } else {
        // Get a list of SQL commands and possibly PHP function names.
        // For any specific version, the function name should appear in this list after
        // the SQL commands allowing the upgrade function to use any new db changes.
        $sqlLines = getSqlUpdates($detectedDbVersion, $_SETTINGS['db_type'], true);
        //echo '<pre>'; print_r($sqlLines); echo "</pre>\n"; exit;
        //echo "<ul>";
        foreach ($sqlLines as $sql) {
          if (str_starts_with($sql, "function:")) {
            // Need to run a PHP function
            list(, $functionName) = explode(':', $sql);
            // echo "<li>function: $functionName () </li>\n";
            if (function_exists($functionName)) {
              $functionName();
            } else {
              // Handle the error if function does not exist
              $error = "Function $functionName does not exist.";
            }
          } else {
            //echo "<li>" . htmlentities($sql) . "</li>\n";
            $ret = dbi_execute($sql, [], false, true);
            if (!$ret) {
              $success = false;
              $error = dbi_error();
            }
          }
        }
        //echo "</ul>\n";
      }
    }
  }
} catch (Exception $e) {
  $error = $e->getMessage();
}
if (empty($error)) {
  updateVersionInDatabase();
  if ($emptyDatabase) {
    $msg = translate('Database tables successfully created');
  } else {
    $msg = translate('Database successfully migrated from XXX to YYY');
    $msg = str_replace('XXX', $detectedDbVersion, $msg);
    $msg = str_replace('YYY', $PROGRAM_VERSION, $msg);
  }
  $_SESSION['alert'] = $msg;
  redirectToNextAction();
}
