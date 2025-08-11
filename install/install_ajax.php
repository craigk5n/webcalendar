<?php
require_once '../includes/config.php';
require_once '../includes/dbi4php.php';
require_once '../includes/formvars.php';
require_once '../includes/load_assets.php';
require_once '../includes/translate.php';
require_once 'default_config.php';
require_once 'install_functions.php';
require_once 'sql/upgrade_matrix.php';

Header('Content-Type: application/json');

global $error_msg;

// Test to see if the we have the right settings to connect to the db.
// Ideally, we avoid asking about the database in case it does not
// exist yet, but some require it as part of the open call.
function testDbConnection($host, $login, $password, $database)
{
  global $error_msg;
  $ret = false;

  try {
    if ($_POST['dbType'] == 'mysqli') {
      $c = new mysqli($host, $login, $password); // don't specify db
      $ret = ($c->connect_errno == 0);
      $error_msg = $c->connect_error . ", login=$login, password=$password, host=$host";
      $c->close();
    } elseif ($_POST['dbType'] == 'sqlite3') {
      $c = new SQLite3($database, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
      $ret = true;
      $c->close();
    } elseif ($_POST['dbType'] == 'postgresql') {
      $c = @pg_connect("host=$host dbname=$database user=$login password=$password");
      $ret = ($c !== false);
      if (!$ret) {
        $c = @pg_connect("host=$host dbname=postgres user=$login password=$password");
        $ret = ($c !== false);
      }
      if ($c) {
        pg_close($c);
      }
    } elseif ($_POST['dbType'] == 'ibase') {
      $c = ibase_connect($database, $login, $password);
      $ret = ($c !== false);
      $error_msg = ibase_errmsg();
      ibase_close($c);
    } elseif ($_POST['dbType'] == 'ibm_db2') {
      $c = db2_connect($database, $login, $password);
      $ret = ($c !== false);
      $error_msg = db2_conn_errormsg($c);
      db2_close($c);
    } elseif ($_POST['dbType'] == 'odbc') {
      $c = odbc_connect("Driver={ODBC Driver 17 for SQL Server};Server=$host;Database=$database;", $login, $password);
      $ret = ($c !== false);
      $error_msg = odbc_errormsg($c);
      odbc_close($c);
    } elseif ($_POST['dbType'] == 'oracle') {
      $c = oci_connect($login, $password, $host);
      $ret = ($c !== false);
      $e = oci_error();
      $error_msg = $e['message'];
      oci_close($c);
    } else {
      // Fallback to your original method
      $c = @dbi_connect($host, $login, $password, $database, false);
      $ret = !empty($c);
      $error_msg = dbi_error();
      // might want to capture the error message for this as well, depending on the dbi_connect function.
    }
  } catch (Exception $e) {
    $error_msg = $_POST['dbType'] . " exception: " . $e->getMessage();
  }

  return $ret;
}


$response = [];
ini_set('session.cookie_lifetime', 3600);  // 3600 seconds = 1 hour

$sessionName = 'WebCalendar-Install-' . str_replace(
        [
            '=',
            ',',
            ';',
            '.',
            '[',
            "\t",
            "\r",
            "\n",
            "\013",
            "\014",
        ],
        '_',
        __DIR__);
session_name($sessionName);

session_start();

$errorResponse = [];
$errorResponse['status'] = "error";
$errorResponse['error'] = translate('Invalid test connection request');

if (empty($_POST['csrf_form_key'])) {
  $errorResponse['error'] = translate('Your form post was either missing a required session token or timed out.');
  echo json_encode($errorResponse);
  exit;
}

// Only allow post
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $errorResponse['error'] .= "\n" . 'GET not supported';
  echo json_encode($errorResponse);
  exit;
}
// Must be a valid PHP session already established
if (empty($_SESSION['initialized'])) {
  $errorResponse['error'] .= "\n" . 'Invalid session';
  echo json_encode($errorResponse);
  exit;
}
$validUser = ( isset ( $_SESSION['validUser'] ) && ! empty ( $_SESSION['validUser'] ) );

if (!$validUser) { // User must be logged in to install pages
  $errorResponse['error'] .= "\n" . 'Not logged in';
  echo json_encode($errorResponse);
  exit;
}

$requestType = $_POST['request'];

if ($requestType == 'test-db-connection') {
  // Get posted data
  $db_type = $_POST['dbType'];
  $server = $_POST['server'];
  $login = $_POST['login'];
  $password = $_POST['password'];
  $dbName = $_POST['dbName'];
  $dbCacheDir = $_POST['dbCacheDir'];

  $canConnect = testDbConnection($server, $login, $password, $dbName);

  if ($canConnect) {
    $response['status'] = "ok";
  } else {
    $response['status'] = "error";
    $response['error'] = $error_msg;
  }
} else {
  $response['status'] = "error";
  $response['error'] = "Unknown request type";
}

echo json_encode($response);
