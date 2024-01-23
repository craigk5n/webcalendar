<?php

// The dbi4php.php functions don't uniformly handle create database, so we have the code for it here.
$existsMessage = translate('Database XXX already exists.');
$createdMessage = translate('Created database XXX');

function createMysqlDatabase(string $hostname, string $login, string $password, string $databaseName): bool
{
  global $existsMessage;
  // Create connection without selecting a database
  $conn = new mysqli($hostname, $login, $password);

  // Check connection
  if ($conn->connect_error) {
    throw new Exception("Connection failed: " . $conn->connect_error);
  }

  // Create database
  $sql = "CREATE DATABASE " . $databaseName;
  if ($conn->query($sql) === TRUE) {
    $conn->close();
    $existsMessage = str_replace("XXX", $databaseName, $existsMessage);
    $_SESSION['alert'] = $existsMessage;
    return true;
  } else {
    throw new Exception("Error creating database: " . $conn->error);
  }
}

function createSqliteDatabase(string $filename): bool
{
  try {
    $db = new SQLite3($filename, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
    // Create a dummy table to force the file to be created
    $db->exec("CREATE TABLE IF NOT EXISTS dummy (id INTEGER)");
    $db->exec("DROP TABLE dummy");
    $db->close();
    return true;
    // TODO: Implement this...
  } catch (Exception $e) {
    throw new Exception("Error creating SQLite3 database: " . $e->getMessage());
  }
}

function createPostgresqlDatabase($hostname, $login, $password, $databaseName): bool
{
  global $existsMessage, $createdMessage;
  // Use specific query for existing database check (dbname=postgres)
  $connString = "host={$hostname} dbname=postgres user={$login} password={$password}";
  $db = pg_connect($connString);
  if (!$db) {
    throw new Exception("Connection failed");
  }
  $existsQuery = "SELECT 1 FROM information_schema.schemata WHERE schema_name = $1";
  $result = pg_query_params($db, $existsQuery, [$databaseName]);
  if ($result) {
    $row = pg_fetch_row($result);
    if ($row && $row[0] === '1') {
      // Database exists
      pg_close($db);
      $existsMessage = str_replace("XXX", $databaseName, $existsMessage);
      $_SESSION['alert'] = $existsMessage;
      return false;
    } else {
      // Database doesn't exist
    }
  }
  $result = pg_query($db, "CREATE DATABASE {$databaseName}");
  if (!$result) {
    throw new Exception("Error creating database: " . pg_last_error($db));
  }
  pg_close($db);
  $createdMessage = str_replace("XXX", $databaseName, $createdMessage);
  $_SESSION['alert'] = $createdMessage;
  return true;
}

function createIbaseDatabase($hostname, $login, $password, $databaseName): bool
{
  // Firebird/Interbase requires a full path for database creation
  $fullPath = $databaseName;
  $dir = basename($databaseName);
  if (!file_exists($dir)) {
    throw new Exception("Missing parent directory: $dir");
  }
  // The db_create function creates a new database
  if (!ibase_create_db("{$hostname}:{$databaseName}", $login, $password)) {
    throw new Exception("Error creating database: " . ibase_errmsg());
  }
  return true;
}

function createOdbcDatabase($dsn, $login, $password, $databaseName): bool
{
  // Connect to the ODBC
  $conn = odbc_connect($dsn, $login, $password);
  if (!$conn) {
    throw new Exception("Connection failed: " . odbc_errormsg());
  }

  // This SQL is generic and may not work for all ODBC sources.
  // You might need to adjust this SQL based on your actual database.
  $sql = "CREATE DATABASE " . $databaseName;

  if (!odbc_exec($conn, $sql)) {
    throw new Exception("Error creating database: " . odbc_errormsg($conn));
  }

  odbc_close($conn);
  return true;
}

function createDB2Database($hostname, $login, $password, $databaseName): bool
{
  // Connect to the database
  $conn = db2_connect($hostname, $login, $password);
  if (!$conn) {
    throw new Exception("IBM DB2 Connection failed: " . db2_conn_errormsg());
  }

  // SQL to create a new database
  $sql = "CREATE DATABASE " . $databaseName;

  $stmt = db2_exec($conn, $sql);
  if (!$stmt) {
    db2_close($conn);
    throw new Exception("Error creating database in IBM DB2: " . db2_stmt_errormsg());
  }

  db2_free_stmt($stmt);
  db2_close($conn);

  return true;
}



try {
  switch ($_SESSION['db_type']) {
    case 'mysqli':
      createMysqlDatabase($_SESSION['db_host'], $_SESSION['db_login'], $_SESSION['db_password'], $_SESSION['db_database']);
      break;
    case 'sqlite3':
      createSqliteDatabase($_SESSION['db_database']);
      break;
    case 'postgresql':
      createPostgresqlDatabase($_SESSION['db_host'], $_SESSION['db_login'], $_SESSION['db_password'], $_SESSION['db_database']);
      break;
    case 'ibase':
      createIbaseDatabase($_SESSION['db_host'], $_SESSION['db_login'], $_SESSION['db_password'], $_SESSION['db_database']);
      break;
    case 'odbc':
      createOdbcDatabase($_SESSION['db_dsn'], $_SESSION['db_login'], $_SESSION['db_password'], $_SESSION['db_database']);
      break;
    case 'oracle':
      $error = 'Creating databases is not currently supported in the installer.  Please do this manually';
      break;
    case 'ibm_db2':
      createDB2Database($_SESSION['db_host'], $_SESSION['db_login'], $_SESSION['db_password'], $_SESSION['db_database']);
      break;
    default:
      $error = 'Creating databases for ' . $_SESSION['db_type'] . ' is not yet supported.';
      break;
  }
} catch (Exception $e) {
  $error = $e->getMessage();
}
if (empty($error)) {
  redirectToNextAction();
}
