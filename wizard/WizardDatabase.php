<?php
/**
 * Wizard Database helper class
 */

require_once __DIR__ . '/WizardState.php';
require_once __DIR__ . '/shared/upgrade-sql.php';

class WizardDatabase
{
  private WizardState $state;
  private $connection = null;
  private ?string $error = null;

  public function __construct(WizardState $state)
  {
    $this->state = $state;
  }

  public function getError(): ?string
  {
    return $this->error;
  }

  public function testConnection(): bool
  {
    switch ($this->state->dbType) {
      case 'mysqli':
        return $this->connectMysqli();
      case 'postgresql':
        return $this->connectPostgresql();
      case 'sqlite3':
        return $this->connectSqlite3();
      default:
        $this->error = "Unsupported database type: " . $this->state->dbType;
        return false;
    }
  }

  public function reconnect(): bool
  {
    $this->closeConnection();
    return $this->testConnection();
  }

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

  private function connectMysqli(): bool
  {
    $mysqli = @new mysqli($this->state->dbHost, $this->state->dbLogin, $this->state->dbPassword);
    if ($mysqli->connect_error) {
      $this->error = $mysqli->connect_error;
      return false;
    }
    $this->connection = $mysqli;
    return true;
  }

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

    // Fallback: connect to 'postgres' to see if DB needs to be created
    $connStringFallback = sprintf(
      "host=%s dbname=postgres user=%s password=%s",
      $this->state->dbHost,
      $this->state->dbLogin,
      $this->state->dbPassword
    );
    $c = @pg_connect($connStringFallback);
    if ($c) {
      $this->connection = $c;
      return true;
    }

    $this->error = "PostgreSQL connection failed";
    return false;
  }

  private function connectSqlite3(): bool
  {
    try {
      $dbPath = $this->state->dbDatabase;
      if ($dbPath[0] !== '/') {
        $dbPath = __DIR__ . '/../' . $dbPath;
      }

      $dir = dirname($dbPath);
      if (!is_dir($dir)) {
        if (!@mkdir($dir, 0777, true)) {
          $this->error = "Cannot create database directory: {$dir}";
          return false;
        }
      }

      if (!is_writable($dir)) {
        $this->error = "Database directory is not writable: {$dir}";
        return false;
      }

      $db = new SQLite3($dbPath);
      $this->connection = $db;
      return true;
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }

  /**
   * Inspect the database to determine its current state
   */
  public function checkDatabase(): void
  {
    if (!$this->connection) {
      return;
    }

    // Select database if needed
    if ($this->state->dbType === 'mysqli') {
      if (!@$this->connection->select_db($this->state->dbDatabase)) {
        return;
      }
      $this->state->databaseExists = true;
    } elseif ($this->state->dbType === 'postgresql') {
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
        if (!@$this->connection->select_db($this->state->dbDatabase)) return null;
        $result = @$this->connection->query("SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'");
        if ($result && $row = $result->fetch_row()) {
          return $row[0];
        }
      } elseif ($this->state->dbType === 'postgresql') {
        $result = @pg_query($this->connection, "SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'");
        if ($result && $row = pg_fetch_row($result)) {
          return $row[0];
        }
      } elseif ($this->state->dbType === 'sqlite3') {
        $result = @$this->connection->query("SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'");
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
    include __DIR__ . '/shared/upgrade_matrix.php';

    $version = 'Unknown';
    $success = true;

    for ($i = 0; $i < count($database_upgrade_matrix); $i++) {
      $testSql = $database_upgrade_matrix[$i][0];

      if (empty($testSql)) {
        continue;
      }

      if ($this->executeTestQuery($testSql)) {
        $version = $database_upgrade_matrix[$i][2];
        // Clean up test data
        $cleanupSql = $database_upgrade_matrix[$i][1];
        if (!empty($cleanupSql)) {
          $this->executeTestQuery($cleanupSql);
        }
      } else {
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
        $result = @$this->connection->query($sql);
        if ($result === false) {
          $this->error = $this->connection->error;
          return false;
        }
        return true;
      } elseif ($this->state->dbType === 'postgresql') {
        $result = @pg_query($this->connection, $sql);
        if ($result === false) {
          $this->error = pg_last_error($this->connection);
          return false;
        }
        return true;
      } elseif ($this->state->dbType === 'sqlite3') {
        return @$this->connection->query($sql) !== false;
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
        if (!@$this->connection->select_db($this->state->dbDatabase)) return true;
        $result = @$this->connection->query("SELECT COUNT(*) FROM webcal_config");
        if ($result) {
          $row = $result->fetch_row();
          return $row[0] == 0;
        }
        return true;
      } elseif ($this->state->dbType === 'postgresql') {
        $result = @pg_query($this->connection, "SELECT COUNT(*) FROM webcal_config");
        if ($result) {
          $row = pg_fetch_row($result);
          return $row[0] == 0;
        }
        return true;
      } elseif ($this->state->dbType === 'sqlite3') {
        $result = @$this->connection->query("SELECT COUNT(*) FROM webcal_config");
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
        if (!@$this->connection->select_db($this->state->dbDatabase)) return 0;
        $result = @$this->connection->query("SELECT COUNT(*) FROM webcal_user WHERE cal_is_admin = 'Y'");
        if ($result && $row = $result->fetch_row()) {
          return (int) $row[0];
        }
      } elseif ($this->state->dbType === 'postgresql') {
        $result = @pg_query($this->connection, "SELECT COUNT(*) FROM webcal_user WHERE cal_is_admin = 'Y'");
        if ($result && $row = pg_fetch_row($result)) {
          return (int) $row[0];
        }
      } elseif ($this->state->dbType === 'sqlite3') {
        $result = @$this->connection->query("SELECT COUNT(*) FROM webcal_user WHERE cal_is_admin = 'Y'");
        if ($result && $row = $result->fetchArray(SQLITE3_NUM)) {
          return (int) $row[0];
        }
      }
    } catch (Exception $e) {
      // Table doesn't exist
    }
    return 0;
  }

  private function loadUpgradeCommands(string $fromVersion): void
  {
    $dbType = $this->state->dbType === 'mysqli' ? 'mysql' : $this->state->dbType;
    $this->state->upgradeSqlCommands = getSqlUpdates($fromVersion, $dbType, true);
  }

  /**
   * Create database (if needed)
   */
  public function createDatabase(): bool
  {
    if (!$this->connection) {
      return false;
    }

    if ($this->state->dbType === 'mysqli') {
      $sql = "CREATE DATABASE IF NOT EXISTS " . $this->state->dbDatabase;
      if ($this->connection->query($sql)) {
        return $this->connection->select_db($this->state->dbDatabase);
      }
      $this->error = $this->connection->error;
      return false;
    } elseif ($this->state->dbType === 'postgresql') {
      // Postgres database creation usually happens outside or via 'postgres' connection
      // For now, assume it exists or fail
      return true;
    }
    return true;
  }

  /**
   * Execute upgrade SQL commands (or base schema for new installs)
   */
  public function executeUpgrade(): bool
  {
    if (!$this->connection) {
      return false;
    }

    // For new installations, load base schema first
    if ($this->state->databaseIsEmpty) {
      if (!$this->loadBaseSchema()) {
        return false;
      }
    }

    foreach ($this->state->upgradeSqlCommands as $sql) {
      if (!$this->executeCommand($sql)) {
        return false;
      }
    }

    // After successful upgrade, update version in webcal_config
    return $this->updateVersionInDb();
  }

  /**
   * Load base schema for new installations from tables-*.sql / tables-*.php
   */
  private function loadBaseSchema(): bool
  {
    if ($this->state->dbType === 'sqlite3') {
      $statements = include __DIR__ . '/shared/tables-sqlite3.php';
      if (!is_array($statements)) {
        $this->error = 'Failed to load SQLite base schema';
        return false;
      }
      foreach ($statements as $sql) {
        $sql = trim($sql);
        if (empty($sql)) continue;
        if (!$this->executeCommand($sql)) {
          return false;
        }
      }
      return true;
    }

    // MySQL or PostgreSQL: load from .sql file
    $dbType = $this->state->dbType === 'mysqli' ? 'mysql' : 'postgres';
    $sqlFile = __DIR__ . "/shared/tables-{$dbType}.sql";

    if (!file_exists($sqlFile)) {
      $this->error = "Base schema file not found: tables-{$dbType}.sql";
      return false;
    }

    $content = file_get_contents($sqlFile);

    // Strip block comments /* ... */
    $content = preg_replace('/\/\*.*?\*\//s', '', $content);
    // Strip line comments starting with #
    $content = preg_replace('/^#.*$/m', '', $content);

    $statements = explode(';', $content);
    foreach ($statements as $sql) {
      $sql = trim($sql);
      if (empty($sql)) continue;
      if (!$this->executeCommand($sql)) {
        return false;
      }
    }

    return true;
  }

  private function executeCommand(string $sql): bool
  {
    if (str_starts_with($sql, 'function:')) {
      $func = substr($sql, 9);
      if (function_exists($func)) {
        try {
          return $func($this->connection, $this->state);
        } catch (Exception $e) {
          $this->error = "Error executing upgrade function $func: " . $e->getMessage();
          return false;
        }
      }
      return true;
    }

    try {
      if ($this->state->dbType === 'mysqli') {
        if (!$this->connection->query($sql)) {
          $error = $this->connection->error;
          if ($this->isIgnorableSchemaError($error)) {
            return true;
          }
          $this->error = $error;
          return false;
        }
      } elseif ($this->state->dbType === 'postgresql') {
        if (!@pg_query($this->connection, $sql)) {
          $error = pg_last_error($this->connection);
          if ($this->isIgnorableSchemaError($error)) {
            return true;
          }
          $this->error = $error;
          return false;
        }
      } elseif ($this->state->dbType === 'sqlite3') {
        if (!$this->connection->exec($sql)) {
          $error = $this->connection->lastErrorMsg();
          if ($this->isIgnorableSchemaError($error)) {
            return true;
          }
          $this->error = $error;
          return false;
        }
      }
    } catch (\Exception $e) {
      // PHP 8.1+ throws exceptions for DB errors (e.g. mysqli_sql_exception).
      // Treat expected schema errors as non-fatal during upgrades.
      if ($this->isIgnorableSchemaError($e->getMessage())) {
        return true;
      }
      $this->error = $e->getMessage();
      return false;
    }
    return true;
  }

  /**
   * Check if a database error is an expected, non-fatal schema error
   * that occurs during idempotent upgrade operations (e.g. adding a
   * column that already exists).
   */
  private function isIgnorableSchemaError(string $error): bool
  {
    $ignorable = [
      'Duplicate column name',    // MySQL: ALTER TABLE ADD on existing column
      'Duplicate key name',       // MySQL: ADD INDEX on existing index
      'already exists',           // PostgreSQL/SQLite: column/table already exists
      'duplicate column name',    // SQLite: ALTER TABLE ADD on existing column
    ];
    foreach ($ignorable as $pattern) {
      if (stripos($error, $pattern) !== false) {
        return true;
      }
    }
    return false;
  }

  private function updateVersionInDb(): bool
  {
    $version = $this->state->programVersion;

    if ($this->state->dbType === 'mysqli') {
      $sql = "INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('WEBCAL_PROGRAM_VERSION', '$version') "
        . "ON DUPLICATE KEY UPDATE cal_value = '$version'";
      $this->connection->query($sql);
    } elseif ($this->state->dbType === 'postgresql') {
      $sql = "INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('WEBCAL_PROGRAM_VERSION', '$version') "
        . "ON CONFLICT (cal_setting) DO UPDATE SET cal_value = '$version'";
      pg_query($this->connection, $sql);
    } elseif ($this->state->dbType === 'sqlite3') {
      $sql = "INSERT OR REPLACE INTO webcal_config (cal_setting, cal_value) VALUES ('WEBCAL_PROGRAM_VERSION', '$version')";
      $this->connection->exec($sql);
    }
    return true;
  }
}
