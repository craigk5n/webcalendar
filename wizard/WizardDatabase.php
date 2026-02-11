<?php
/**
 * WizardDatabase - Database operations for the WebCalendar install wizard
 * 
 * Handles database connections, version detection, upgrades, and table creation
 * Uses PHP8 typed properties and return types
 */

class WizardDatabase
{
  private WizardState $state;
  /** @var resource|object|null */
  private $connection = null;
  private string $error = '';
  
  /**
   * Constructor
   */
  public function __construct(WizardState $state)
  {
    $this->state = $state;
  }
  
  /**
   * Re-establish database connection
   */
  public function reconnect(): bool
  {
    if ($this->testConnection()) {
      if ($this->state->databaseExists && !empty($this->state->dbDatabase)) {
        $this->selectDatabase($this->state->dbDatabase);
      }
      return true;
    }
    return false;
  }

  /**
   * Select a database
   */
  public function selectDatabase(string $dbName): bool
  {
    if (!$this->connection) {
      return false;
    }

    try {
      if ($this->state->dbType === 'mysqli' || $this->state->dbType === 'mysql') {
        return $this->connection->select_db($dbName);
      }
      // PostgreSQL selects database during connection
      return true;
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }
  
  /**
   * Try to connect to database with current settings
   * @return bool True if connection successful
   */
  public function testConnection(): bool
  {
    $this->error = '';
    
    try {
      // Close any existing connection
      $this->closeConnection();
      
      $dbType = $this->state->dbType;
      
      // Special handling for different database types
      switch ($dbType) {
        case 'mysqli':
          return $this->connectMysqli();
          
        case 'postgresql':
          return $this->connectPostgresql();
          
        case 'sqlite3':
          return $this->connectSqlite3();
          
        case 'oracle':
          return $this->connectOracle();
          
        case 'ibm_db2':
          return $this->connectDb2();
          
        case 'odbc':
          return $this->connectOdbc();
          
        case 'ibase':
          return $this->connectInterbase();
          
        default:
          // Fall back to dbi_connect
          return $this->connectViaDbi();
      }
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }
  
  /**
   * Connect to MySQLi
   */
  private function connectMysqli(): bool
  {
    $mysqli = @new mysqli($this->state->dbHost, $this->state->dbLogin, $this->state->dbPassword);
    
    if ($mysqli->connect_error) {
      $this->error = $mysqli->connect_error;
      return false;
    }
    
    // Don't require database to exist for initial connection test
    $this->connection = $mysqli;
    return true;
  }
  
  /**
   * Connect to PostgreSQL
   */
  private function connectPostgresql(): bool
  {
    // Try connecting to the specified database first
    $connString = sprintf(
      "host=%s dbname=%s user=%s password=%s",
      $this->state->dbHost,
      $this->state->dbDatabase,
      $this->state->dbLogin,
      $this->state->dbPassword
    );
    
    $c = @pg_connect($connString);
    if ($c) {
      $this->connection = $c;
      return true;
    }
    
    // If that fails, try connecting to the postgres database
    $connString = sprintf(
      "host=%s dbname=postgres user=%s password=%s",
      $this->state->dbHost,
      $this->state->dbLogin,
      $this->state->dbPassword
    );
    
    $c = @pg_connect($connString);
    if ($c) {
      $this->connection = $c;
      return true;
    }
    
    $this->error = 'Unable to connect to PostgreSQL server';
    return false;
  }
  
  /**
   * Connect to SQLite3
   */
  private function connectSqlite3(): bool
  {
    try {
      // SQLite3 just needs the file path
      $sqlite = new SQLite3($this->state->dbDatabase);
      if ($sqlite) {
        $this->connection = $sqlite;
        return true;
      }
    } catch (Exception $e) {
      $this->error = $e->getMessage();
    }
    return false;
  }
  
  /**
   * Connect to Oracle
   */
  private function connectOracle(): bool
  {
    $c = @oci_connect(
      $this->state->dbLogin,
      $this->state->dbPassword,
      $this->state->dbDatabase
    );
    
    if ($c) {
      $this->connection = $c;
      return true;
    }
    
    $this->error = 'Unable to connect to Oracle database';
    return false;
  }
  
  /**
   * Connect to IBM DB2
   */
  private function connectDb2(): bool
  {
    $c = @db2_connect(
      $this->state->dbDatabase,
      $this->state->dbLogin,
      $this->state->dbPassword
    );
    
    if ($c) {
      $this->connection = $c;
      return true;
    }
    
    $this->error = 'Unable to connect to DB2 database';
    return false;
  }
  
  /**
   * Connect via ODBC
   */
  private function connectOdbc(): bool
  {
    $dsn = $this->state->dbDatabase; // For ODBC, db_database is the DSN
    $c = @odbc_connect($dsn, $this->state->dbLogin, $this->state->dbPassword);
    
    if ($c) {
      $this->connection = $c;
      return true;
    }
    
    $this->error = 'Unable to connect via ODBC';
    return false;
  }
  
  /**
   * Connect to InterBase/Firebird
   */
  private function connectInterbase(): bool
  {
    $c = @ibase_connect(
      $this->state->dbDatabase,
      $this->state->dbLogin,
      $this->state->dbPassword
    );
    
    if ($c) {
      $this->connection = $c;
      return true;
    }
    
    $this->error = 'Unable to connect to InterBase database';
    return false;
  }
  
  /**
   * Connect via dbi4php abstraction layer
   */
  private function connectViaDbi(): bool
  {
    // This requires the dbi4php functions to be available
    global $dbi4phpLoaded;
    
    if (!$dbi4phpLoaded) {
      $this->error = 'Database abstraction layer not available';
      return false;
    }
    
    $c = @dbi_connect(
      $this->state->dbHost,
      $this->state->dbLogin,
      $this->state->dbPassword,
      $this->state->dbDatabase,
      false
    );
    
    if ($c) {
      $this->connection = $c;
      return true;
    }
    
    $this->error = dbi_error();
    return false;
  }
  
  /**
   * Check if database exists and determine its version
   */
  public function checkDatabase(): void
  {
    if (!$this->connection) {
      return;
    }

    $this->state->databaseExists = false;
    $this->state->databaseIsEmpty = true;
    $this->state->detectedDbVersion = null;

    // For MySQL, the initial connection is made without selecting a
    // database so that we can test credentials independently.  We need
    // to select the database now before we can inspect its tables.
    if ($this->state->dbType === 'mysqli') {
      if (!$this->connection->select_db($this->state->dbDatabase)) {
        // Database itself does not exist yet.
        return;
      }
      $this->state->databaseExists = true;
    }

    // Try to get version from webcal_config
    $version = $this->getDbVersionFromConfig();
    if ($version !== null) {
      $this->state->detectedDbVersion = $version;
      $this->state->databaseExists = true;
      $this->state->databaseIsEmpty = false;
      
      // Check if upgrade is needed
      if ($version !== $this->state->programVersion) {
        $this->state->isUpgrade = true;
        $this->loadUpgradeCommands($version);
      }
      
      // Count admin users
      $this->state->adminUserCount = $this->getAdminUserCount();
      return;
    }
    
    // Try to detect version from schema
    $version = $this->getDatabaseVersionFromSchema();
    if ($version !== null && $version !== 'Unknown') {
      $this->state->detectedDbVersion = $version;
      $this->state->databaseExists = true;
      $this->state->databaseIsEmpty = false;
      
      if ($version !== $this->state->programVersion) {
        $this->state->isUpgrade = true;
        $this->loadUpgradeCommands($version);
      }
      
      $this->state->adminUserCount = $this->getAdminUserCount();
      return;
    }
    
    // Check if database is empty
    $this->state->databaseIsEmpty = $this->isEmptyDatabase();
    if (!$this->state->databaseIsEmpty) {
      $this->state->databaseExists = true;
    }
  }
  
  /**
   * Get version from webcal_config
   */
  private function getDbVersionFromConfig(): ?string
  {
    try {
      if ($this->state->dbType === 'mysqli') {
        $result = $this->connection->query("SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'");
        if ($result && $row = $result->fetch_row()) {
          return $row[0];
        }
      } elseif ($this->state->dbType === 'postgresql') {
        $result = pg_query($this->connection, "SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'");
        if ($result && $row = pg_fetch_row($result)) {
          return $row[0];
        }
      } elseif ($this->state->dbType === 'sqlite3') {
        $result = $this->connection->query("SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'");
        if ($result && $row = $result->fetchArray(SQLITE3_NUM)) {
          return $row[0];
        }
      }
    } catch (Exception $e) {
      // Table doesn't exist or other error
    }
    return null;
  }
  
  /**
   * Get version from schema testing (for old databases)
   */
  private function getDatabaseVersionFromSchema(): string
  {
    global $database_upgrade_matrix;
    require_once __DIR__ . '/shared/upgrade_matrix.php';
    
    $version = 'Unknown';
    $success = true;
    
    for ($i = 0; $i < count($database_upgrade_matrix); $i++) {
      $testSql = $database_upgrade_matrix[$i][0];
      
      if (empty($testSql)) {
        if ($success) {
          $version = $database_upgrade_matrix[$i][2];
        }
        break;
      }
      
      try {
        if ($this->executeTestQuery($testSql)) {
          $version = $database_upgrade_matrix[$i][2];
          // Clean up test data
          $cleanupSql = $database_upgrade_matrix[$i][1];
          if (!empty($cleanupSql)) {
            $this->executeTestQuery($cleanupSql);
          }
        } else {
          $success = false;
          break;
        }
      } catch (Exception $e) {
        $success = false;
        break;
      }
    }
    
    return $version;
  }
  
  /**
   * Execute a test query (for schema detection)
   */
  private function executeTestQuery(string $sql): bool
  {
    try {
      if ($this->state->dbType === 'mysqli') {
        return $this->connection->query($sql) !== false;
      } elseif ($this->state->dbType === 'postgresql') {
        return pg_query($this->connection, $sql) !== false;
      } elseif ($this->state->dbType === 'sqlite3') {
        return $this->connection->query($sql) !== false;
      }
    } catch (Exception $e) {
      return false;
    }
    return false;
  }
  
  /**
   * Check if database is empty
   */
  private function isEmptyDatabase(): bool
  {
    try {
      if ($this->state->dbType === 'mysqli') {
        $result = $this->connection->query("SELECT COUNT(*) FROM webcal_config");
        if ($result && $row = $result->fetch_row()) {
          return $row[0] == 0;
        }
      } elseif ($this->state->dbType === 'postgresql') {
        $result = pg_query($this->connection, "SELECT COUNT(*) FROM webcal_config");
        if ($result && $row = pg_fetch_row($result)) {
          return $row[0] == 0;
        }
      } elseif ($this->state->dbType === 'sqlite3') {
        $result = $this->connection->query("SELECT COUNT(*) FROM webcal_config");
        if ($result && $row = $result->fetchArray(SQLITE3_NUM)) {
          return $row[0] == 0;
        }
      }
    } catch (Exception $e) {
      // Table doesn't exist
    }
    return true;
  }
  
  /**
   * Count admin users
   */
  private function getAdminUserCount(): int
  {
    try {
      if ($this->state->dbType === 'mysqli') {
        $result = $this->connection->query("SELECT COUNT(*) FROM webcal_user WHERE cal_is_admin = 'Y'");
        if ($result && $row = $result->fetch_row()) {
          return (int) $row[0];
        }
      } elseif ($this->state->dbType === 'postgresql') {
        $result = pg_query($this->connection, "SELECT COUNT(*) FROM webcal_user WHERE cal_is_admin = 'Y'");
        if ($result && $row = pg_fetch_row($result)) {
          return (int) $row[0];
        }
      } elseif ($this->state->dbType === 'sqlite3') {
        $result = $this->connection->query("SELECT COUNT(*) FROM webcal_user WHERE cal_is_admin = 'Y'");
        if ($result && $row = $result->fetchArray(SQLITE3_NUM)) {
          return (int) $row[0];
        }
      }
    } catch (Exception $e) {
      // Table doesn't exist
    }
    return 0;
  }
  
  /**
   * Load upgrade SQL commands
   */
  private function loadUpgradeCommands(string $fromVersion): void
  {
    require_once __DIR__ . '/shared/upgrade-sql.php';
    
    $dbType = $this->state->dbType === 'mysqli' ? 'mysql' : $this->state->dbType;
    $this->state->upgradeSqlCommands = getSqlUpdates($fromVersion, $dbType, true);
  }
  
  /**
   * Create database (if needed)
   */
  public function createDatabase(): bool
  {
    if ($this->state->databaseExists) {
      return true;
    }
    
    try {
      if ($this->state->dbType === 'mysqli') {
        return $this->connection->query("CREATE DATABASE IF NOT EXISTS {$this->state->dbDatabase}");
      } elseif ($this->state->dbType === 'postgresql') {
        $result = pg_query($this->connection, "CREATE DATABASE {$this->state->dbDatabase}");
        return $result !== false;
      }
      // SQLite3 and other databases don't need separate database creation
      return true;
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }
  
  /**
   * Execute SQL commands to create/upgrade tables
   */
  public function executeUpgrade(): bool
  {
    if (empty($this->state->upgradeSqlCommands)) {
      // Check if we need to create tables from scratch
      if ($this->state->databaseIsEmpty) {
        return $this->createTablesFromScratch();
      }
      $this->updateVersionInDb();
      return true;
    }
    
    foreach ($this->state->upgradeSqlCommands as $sql) {
      if (str_starts_with($sql, 'function:')) {
        // Execute PHP upgrade function
        $functionName = substr($sql, 9);
        if (function_exists($functionName)) {
          try {
            $functionName();
          } catch (Exception $e) {
            $this->error = "Upgrade function {$functionName} failed: " . $e->getMessage();
            return false;
          }
        }
      } else {
        // Execute SQL
        try {
          if (!$this->executeSql($sql)) {
            return false;
          }
        } catch (Exception $e) {
          $this->error = "SQL execution failed: " . $e->getMessage();
          return false;
        }
      }
    }
    
    $this->state->upgradeComplete = true;
    // Update version in DB so config.php won't redirect back to installer
    $this->updateVersionInDb();
    return true;
  }

  /**
   * Create tables from scratch (new installation)
   */
  private function createTablesFromScratch(): bool
  {
    $sqlFile = __DIR__ . '/shared/tables-' . $this->state->dbType . '.sql';

    if (!file_exists($sqlFile)) {
      // Try with .php extension for dynamic SQL generation
      $phpFile = __DIR__ . '/shared/tables-' . $this->state->dbType . '.php';
      if (file_exists($phpFile)) {
        $commands = include $phpFile;
        if (is_array($commands)) {
          foreach ($commands as $command) {
            if (!empty(trim($command)) && !$this->executeSql($command)) {
              return false;
            }
          }
        }
        $this->updateVersionInDb();
        return true;
      }
      $this->error = "SQL file not found: {$sqlFile}";
      return false;
    }

    try {
      $sql = file_get_contents($sqlFile);
      $commands = array_filter(array_map('trim', explode(';', $sql)));

      foreach ($commands as $command) {
        if (!empty($command) && !$this->executeSql($command)) {
          return false;
        }
      }

      // Set version in DB so config.php won't redirect back to installer
      $this->updateVersionInDb();
      return true;
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }

  /**
   * Insert or update WEBCAL_PROGRAM_VERSION in webcal_config.
   * This is critical — without it, config.php redirects users back
   * to the installer on every page load.
   */
  private function updateVersionInDb(): bool
  {
    $version = $this->state->programVersion;
    error_log("Attempting to update WEBCAL_PROGRAM_VERSION to {$version}");

    try {
      // Ensure database is selected for MySQL if not already
      if (($this->state->dbType === 'mysqli' || $this->state->dbType === 'mysql') && $this->connection) {
        if (is_object($this->connection) && method_exists($this->connection, 'select_db')
          && $this->connection->real_escape_string($this->state->dbDatabase) !== $this->connection->current_db ) {
             error_log("Selecting database " . $this->state->dbDatabase);
             if (!$this->connection->select_db($this->state->dbDatabase)) {
                $this->error = "Failed to select database '{$this->state->dbDatabase}': " . $this->connection->error;
                error_log($this->error);
                return false;
             }
        }
      }

      $delSql = "DELETE FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'";
      $insSql = "INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('WEBCAL_PROGRAM_VERSION', ?)";

      if ($this->state->dbType === 'mysqli' || $this->state->dbType === 'mysql') {
        if (!$this->connection->query($delSql)) {
          $this->error = "Failed to delete old version: " . $this->connection->error;
          error_log($this->error);
          return false;
        }
        $stmt = $this->connection->prepare($insSql);
        if (!$stmt) {
          $this->error = "Failed to prepare INSERT statement: " . $this->connection->error;
          error_log($this->error);
          return false;
        }
        $stmt->bind_param('s', $version);
        if (!$stmt->execute()) {
          $this->error = "Failed to execute INSERT statement: " . $stmt->error;
          error_log($this->error);
          return false;
        }
        error_log("Successfully updated WEBCAL_PROGRAM_VERSION for mysqli/mysql.");
        return true;
      } elseif ($this->state->dbType === 'postgresql' || $this->state->dbType === 'postgres') {
        if (!pg_query($this->connection, $delSql)) {
          $this->error = "Failed to delete old version: " . pg_last_error($this->connection);
          error_log($this->error);
          return false;
        }
        $result = pg_query_params($this->connection,
          "INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('WEBCAL_PROGRAM_VERSION', \$1)",
          [$version]);
        if (!$result) {
          $this->error = "Failed to insert new version: " . pg_last_error($this->connection);
          error_log($this->error);
          return false;
        }
        error_log("Successfully updated WEBCAL_PROGRAM_VERSION for postgresql.");
        return true;
      } elseif ($this->state->dbType === 'sqlite3' || $this->state->dbType === 'sqlite') {
        if (!$this->connection->exec($delSql)) {
          $this->error = "Failed to delete old version: " . $this->connection->lastErrorMsg();
          error_log($this->error);
          return false;
        }
        $stmt = $this->connection->prepare(
          "INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('WEBCAL_PROGRAM_VERSION', :version)"
        );
        if (!$stmt) {
          $this->error = "Failed to prepare INSERT statement: " . $this->connection->lastErrorMsg();
          error_log($this->error);
          return false;
        }
        $stmt->bindValue(':version', $version);
        if (!$stmt->execute()) {
          $this->error = "Failed to execute INSERT statement: " . $this->connection->lastErrorMsg();
          error_log($this->error);
          return false;
        }
        error_log("Successfully updated WEBCAL_PROGRAM_VERSION for sqlite3.");
        return true;
      }

      $this->error = "Unsupported database type for version update: " . $this->state->dbType;
      error_log($this->error);
      return false;
    } catch (Exception $e) {
      $this->error = 'Failed to update version in database: ' . $e->getMessage();
      error_log($this->error);
      return false;
    }
  }
  
  /**
   * Execute a single SQL command
   */
  private function executeSql(string $sql): bool
  {
    if (empty($sql)) {
      return true;
    }
    
    try {
      if ($this->state->dbType === 'mysqli') {
        return $this->connection->query($sql) !== false;
      } elseif ($this->state->dbType === 'postgresql') {
        return pg_query($this->connection, $sql) !== false;
      } elseif ($this->state->dbType === 'sqlite3') {
        return $this->connection->exec($sql) !== false;
      }
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
    
    return false;
  }
  
  /**
   * Create default admin user
   */
  public function createAdminUser(string $login, string $password, string $email = ''): bool
  {
    $hashedPassword = md5($password);

    try {
      // Remove default admin inserted by schema file (if any)
      $delSql = "DELETE FROM webcal_user WHERE cal_login = ?";
      if ($this->state->dbType === 'mysqli') {
        $del = $this->connection->prepare($delSql);
        $del->bind_param('s', $login);
        $del->execute();
      } elseif ($this->state->dbType === 'postgresql') {
        pg_query_params($this->connection, $delSql, [$login]);
      } elseif ($this->state->dbType === 'sqlite3') {
        $del = $this->connection->prepare(
          "DELETE FROM webcal_user WHERE cal_login = :login"
        );
        $del->bindValue(':login', $login);
        $del->execute();
      }

      $sql = "INSERT INTO webcal_user (cal_login, cal_passwd, cal_email, cal_firstname, cal_lastname, cal_is_admin)
              VALUES (?, ?, ?, 'Administrator', 'Default', 'Y')";

      if ($this->state->dbType === 'mysqli') {
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('sss', $login, $hashedPassword, $email);
        return $stmt->execute();
      } elseif ($this->state->dbType === 'postgresql') {
        $result = pg_query_params($this->connection, $sql, [$login, $hashedPassword, $email]);
        return $result !== false;
      } elseif ($this->state->dbType === 'sqlite3') {
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $login);
        $stmt->bindValue(2, $hashedPassword);
        $stmt->bindValue(3, $email);
        return $stmt->execute();
      }
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
    
    return false;
  }
  
  /**
   * Close database connection
   */
  public function closeConnection(): void
  {
    if ($this->connection) {
      if ($this->state->dbType === 'mysqli') {
        $this->connection->close();
      } elseif ($this->state->dbType === 'postgresql') {
        pg_close($this->connection);
      } elseif ($this->state->dbType === 'sqlite3') {
        $this->connection->close();
      }
      $this->connection = null;
    }
  }
  
  /**
   * Get last error message
   */
  public function getError(): string
  {
    return $this->error;
  }
  
  /**
   * Get upgrade SQL commands for display
   */
  public function getUpgradeSqlCommands(): array
  {
    return $this->state->upgradeSqlCommands;
  }
}
