<?php
/**
 * WizardState - State management for the WebCalendar install wizard
 * 
 * Uses PHP8 typed properties for type safety
 * Manages all configuration settings during the installation process
 */

class WizardState
{
  public string $step = 'welcome';
  public bool $isInitialized = false;
  public bool $isValidUser = false;
  
  // Authentication settings
  public ?string $installPassword = null;
  public ?string $installPasswordHint = null;
  
  // Application settings
  public string $userAuth = 'web';  // web, http, none (single-user)
  public string $userDb = 'user.php';  // user.php, user-ldap.php, user-nis.php, user-imap.php, user-joomla.php
  public ?string $singleUserLogin = null;
  public bool $readonly = false;
  public string $runMode = 'prod';  // prod, dev
  
  // Database settings
  public string $dbType = '';
  public string $dbHost = '';
  public string $dbLogin = '';
  public string $dbPassword = '';
  public string $dbDatabase = '';
  public ?string $dbCacheDir = null;
  public bool $dbDebug = false;
  
  // PHP Settings acknowledgment
  public bool $phpSettingsAcked = false;
  
  // Database status
  public bool $dbConnectionSuccess = false;
  public ?string $dbConnectionError = null;
  public bool $databaseExists = false;
  public bool $databaseIsEmpty = false;
  public ?string $detectedDbVersion = null;
  public string $programVersion = '';
  public int $adminUserCount = 0;

  /**
   * Constructor - initializes program version
   */
  public function __construct()
  {
    $this->loadProgramVersion();
  }

  /**
   * Load program version from upgrade_matrix.php
   */
  private function loadProgramVersion(): void
  {
    $matrixFile = __DIR__ . '/shared/upgrade_matrix.php';
    if (file_exists($matrixFile)) {
      include $matrixFile;
      if (isset($PROGRAM_VERSION)) {
        $this->programVersion = $PROGRAM_VERSION;
      }
    }

    if (empty($this->programVersion)) {
      $this->programVersion = 'v1.9.15'; // Fallback
    }
  }
  
  // Upgrade/install status
  public bool $isUpgrade = false;
  public array $upgradeSqlCommands = [];
  public bool $upgradeComplete = false;
  
  // Settings modified flag
  public bool $appSettingsModified = false;
  public bool $dbSettingsModified = false;

  // Quick upgrade mode (keep existing settings.php, just upgrade DB)
  public bool $quickUpgrade = false;

  // Using environment variables
  public bool $usingEnv = false;
  
  // Error messages
  public ?string $errorMessage = null;
  public ?string $successMessage = null;
  
  /**
   * Load settings from environment variables if WEBCALENDAR_USE_ENV is set.
   */
  public function loadFromEnv(): void
  {
    if (empty(getenv('WEBCALENDAR_USE_ENV'))) {
      return;
    }

    $this->usingEnv = true;
    $this->isInitialized = true;
    
    $map = [
      'WEBCALENDAR_INSTALL_PASSWORD' => 'installPassword',
      'WEBCALENDAR_INSTALL_PASSWORD_HINT' => 'installPasswordHint',
      'WEBCALENDAR_DB_TYPE' => 'dbType',
      'WEBCALENDAR_DB_HOST' => 'dbHost',
      'WEBCALENDAR_DB_LOGIN' => 'dbLogin',
      'WEBCALENDAR_DB_PASSWORD' => 'dbPassword',
      'WEBCALENDAR_DB_DATABASE' => 'dbDatabase',
      'WEBCALENDAR_DB_CACHEDIR' => 'dbCacheDir',
      'WEBCALENDAR_USER_INC' => 'userDb',
    ];

    foreach ($map as $envKey => $prop) {
      $val = getenv($envKey);
      if ($val !== false) {
        $this->$prop = $val;
      }
    }

    // Special handling for booleans and complex types
    if (($val = getenv('WEBCALENDAR_DB_DEBUG')) !== false) {
      $this->dbDebug = filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
    if (($val = getenv('WEBCALENDAR_READONLY')) !== false) {
      $this->readonly = filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
    if (($val = getenv('WEBCALENDAR_USE_HTTP_AUTH')) !== false) {
      if (filter_var($val, FILTER_VALIDATE_BOOLEAN)) {
        $this->userAuth = 'http';
      }
    }
    if (($val = getenv('WEBCALENDAR_SINGLE_USER')) !== false) {
      if (filter_var($val, FILTER_VALIDATE_BOOLEAN)) {
        $this->userAuth = 'none';
        if (($val2 = getenv('WEBCALENDAR_SINGLE_USER_LOGIN')) !== false) {
          $this->singleUserLogin = $val2;
        }
      }
    }
    if (($val = getenv('WEBCALENDAR_MODE')) !== false) {
      $this->runMode = $val === 'dev' ? 'dev' : 'prod';
    }
  }

  /**
   * Load state from PHP session
   */
  public function loadFromSession(): void
  {
    if (isset($_SESSION['wizard_state'])) {
      $data = $_SESSION['wizard_state'];
      
      // Load all properties from session data
      foreach ($data as $key => $value) {
        if ($key === 'programVersion') continue; // Always use current version from file
        if (property_exists($this, $key)) {
          $this->$key = $this->castValue($key, $value);
        }
      }
      $this->loadProgramVersion(); // Ensure it's set correctly
    }
  }
  
  /**
   * Save state to PHP session
   */
  public function saveToSession(): void
  {
    $_SESSION['wizard_state'] = [
      'step' => $this->step,
      'isInitialized' => $this->isInitialized,
      'isValidUser' => $this->isValidUser,
      'installPassword' => $this->installPassword,
      'installPasswordHint' => $this->installPasswordHint,
      'userAuth' => $this->userAuth,
      'userDb' => $this->userDb,
      'singleUserLogin' => $this->singleUserLogin,
      'readonly' => $this->readonly,
      'runMode' => $this->runMode,
      'dbType' => $this->dbType,
      'dbHost' => $this->dbHost,
      'dbLogin' => $this->dbLogin,
      'dbPassword' => $this->dbPassword,
      'dbDatabase' => $this->dbDatabase,
      'dbCacheDir' => $this->dbCacheDir,
      'dbDebug' => $this->dbDebug,
      'phpSettingsAcked' => $this->phpSettingsAcked,
      'dbConnectionSuccess' => $this->dbConnectionSuccess,
      'dbConnectionError' => $this->dbConnectionError,
      'databaseExists' => $this->databaseExists,
      'databaseIsEmpty' => $this->databaseIsEmpty,
      'detectedDbVersion' => $this->detectedDbVersion,
      'programVersion' => $this->programVersion,
      'adminUserCount' => $this->adminUserCount,
      'isUpgrade' => $this->isUpgrade,
      'upgradeSqlCommands' => $this->upgradeSqlCommands,
      'upgradeComplete' => $this->upgradeComplete,
      'appSettingsModified' => $this->appSettingsModified,
      'dbSettingsModified' => $this->dbSettingsModified,
      'usingEnv' => $this->usingEnv,
      'quickUpgrade' => $this->quickUpgrade,
    ];
  }

  /**
   * Cast value to the correct type based on property type
   */
  private function castValue(string $property, mixed $value): mixed
  {
    $reflection = new ReflectionClass($this);
    $type = $reflection->getProperty($property)->getType();
    
    if ($type instanceof ReflectionNamedType) {
      $typeName = $type->getName();
      
      switch ($typeName) {
        case 'bool':
          return (bool) $value;
        case 'int':
          return (int) $value;
        case 'string':
          return (string) $value;
        case 'array':
          return is_array($value) ? $value : [];
        case '?bool':
          return $value === null ? null : (bool) $value;
        case '?int':
          return $value === null ? null : (int) $value;
        case '?string':
          return $value === null ? null : (string) $value;
      }
    }
    
    return $value;
  }
  
  /**
   * Get array representation for API responses
   */
  public function toArray(): array
  {
    return [
      'step' => $this->step,
      'isInitialized' => $this->isInitialized,
      'isValidUser' => $this->isValidUser,
      'dbConnectionSuccess' => $this->dbConnectionSuccess,
      'dbConnectionError' => $this->dbConnectionError,
      'databaseExists' => $this->databaseExists,
      'databaseIsEmpty' => $this->databaseIsEmpty,
      'detectedDbVersion' => $this->detectedDbVersion,
      'programVersion' => $this->programVersion,
      'adminUserCount' => $this->adminUserCount,
      'isUpgrade' => $this->isUpgrade,
      'upgradeComplete' => $this->upgradeComplete,
      'usingEnv' => $this->usingEnv,
    ];
  }
  
  /**
   * Get current settings for the settings.php file
   */
  public function getSettingsArray(): array
  {
    return [
      'db_type' => $this->dbType,
      'db_host' => $this->dbHost,
      'db_login' => $this->dbLogin,
      'db_password' => $this->dbPassword,
      'db_database' => $this->dbDatabase,
      'db_cachedir' => $this->dbCacheDir,
      'db_debug' => $this->dbDebug ? 'true' : 'false',
      'install_password' => $this->installPassword,
      'install_password_hint' => $this->installPasswordHint,
      'readonly' => $this->readonly ? 'true' : 'false',
      'single_user' => $this->userAuth === 'none' ? 'true' : 'false',
      'single_user_login' => $this->singleUserLogin,
      'use_http_auth' => $this->userAuth === 'http' ? 'true' : 'false',
      'user_inc' => $this->userAuth === 'none' ? 'user.php' : $this->userDb,
      'mode' => $this->runMode,
    ];
  }
  
  /**
   * Set database settings from form input
   */
  public function setDbSettings(array $data): void
  {
    if (isset($data['db_type'])) $this->dbType = (string) $data['db_type'];
    if (isset($data['db_host'])) $this->dbHost = (string) $data['db_host'];
    if (isset($data['db_login'])) $this->dbLogin = (string) $data['db_login'];
    if (isset($data['db_password'])) $this->dbPassword = (string) $data['db_password'];
    if (isset($data['db_database'])) $this->dbDatabase = (string) $data['db_database'];
    if (isset($data['db_cachedir'])) $this->dbCacheDir = (string) $data['db_cachedir'] ?: null;
    if (isset($data['db_debug'])) $this->dbDebug = filter_var($data['db_debug'], FILTER_VALIDATE_BOOLEAN);
    
    $this->dbSettingsModified = true;
    $this->dbConnectionSuccess = false;
    $this->dbConnectionError = null;
  }
  
  /**
   * Set application settings from form input
   */
  public function setAppSettings(array $data): void
  {
    if (isset($data['user_auth'])) $this->userAuth = (string) $data['user_auth'];
    if (isset($data['user_db'])) $this->userDb = (string) $data['user_db'];
    if (isset($data['single_user_login'])) $this->singleUserLogin = (string) $data['single_user_login'] ?: null;
    if (isset($data['readonly'])) $this->readonly = filter_var($data['readonly'], FILTER_VALIDATE_BOOLEAN);
    if (isset($data['run_mode'])) $this->runMode = (string) $data['run_mode'];
    
    $this->appSettingsModified = true;
  }
  
  /**
   * Reset database connection state
   */
  public function resetDbConnection(): void
  {
    $this->dbConnectionSuccess = false;
    $this->dbConnectionError = null;
    $this->databaseExists = false;
    $this->databaseIsEmpty = false;
    $this->detectedDbVersion = null;
    $this->adminUserCount = 0;
    $this->isUpgrade = false;
    $this->upgradeSqlCommands = [];
    $this->upgradeComplete = false;
  }
  
  /**
   * Clear all messages
   */
  public function clearMessages(): void
  {
    $this->errorMessage = null;
    $this->successMessage = null;
  }
  
  /**
   * Check if wizard is complete
   */
  public function isComplete(): bool
  {
    return $this->dbConnectionSuccess && 
           $this->databaseExists && 
           !$this->databaseIsEmpty && 
           $this->adminUserCount > 0;
  }
  
  /**
   * Load settings from the existing includes/settings.php file.
   * The file uses colon-separated format: "key: value"
   */
  public function loadFromSettingsFile(string $settingsPath): void
  {
    if (!file_exists($settingsPath)) {
      return;
    }

    $content = @file_get_contents($settingsPath);
    if (empty($content)) {
      return;
    }

    $this->isInitialized = true;

    $map = [
      'install_password' => 'installPassword',
      'install_password_hint' => 'installPasswordHint',
      'db_type' => 'dbType',
      'db_host' => 'dbHost',
      'db_login' => 'dbLogin',
      'db_password' => 'dbPassword',
      'db_database' => 'dbDatabase',
      'db_cachedir' => 'dbCacheDir',
      'user_inc' => 'userDb',
    ];

    foreach ($map as $fileKey => $prop) {
      if (preg_match('/' . $fileKey . ':\s*(.*)/', $content, $m)) {
        $val = trim($m[1]);
        if ($val !== '') {
          $this->$prop = $val;
        }
      }
    }

    // Boolean / special fields
    if (preg_match('/db_debug:\s*(.*)/', $content, $m)) {
      $this->dbDebug = preg_match('/(1|true|yes|on)/i', trim($m[1])) === 1;
    }
    if (preg_match('/readonly:\s*(.*)/', $content, $m)) {
      $this->readonly = preg_match('/(1|true|yes|on|Y)/i', trim($m[1])) === 1;
    }
    if (preg_match('/use_http_auth:\s*(.*)/', $content, $m)) {
      if (preg_match('/(1|true|yes|on)/i', trim($m[1]))) {
        $this->userAuth = 'http';
      }
    }
    if (preg_match('/single_user:\s*(.*)/', $content, $m)) {
      if (preg_match('/(1|true|yes|on|Y)/i', trim($m[1]))) {
        $this->userAuth = 'none';
        if (preg_match('/single_user_login:\s*(.*)/', $content, $m2)) {
          $this->singleUserLogin = trim($m2[1]);
        }
      }
    }
    if (preg_match('/mode:\s*(.*)/', $content, $m)) {
      $this->runMode = trim($m[1]) === 'dev' ? 'dev' : 'prod';
    }
  }

  /**
   * Generate install password hash
   */
  public function setInstallPassword(string $password): void
  {
    $this->installPassword = md5($password);
  }

  /**
   * Verify install password against stored hash.
   * Supports legacy MD5 hashes (32 hex chars) and bcrypt.
   */
  public function verifyInstallPassword(string $password): bool
  {
    if (empty($this->installPassword)) {
      return false;
    }
    // Legacy MD5 hash (32 hex characters)
    if (preg_match('/^[a-f0-9]{32}$/i', $this->installPassword)) {
      return md5($password) === $this->installPassword;
    }
    // bcrypt / password_hash format
    return password_verify($password, $this->installPassword);
  }
}
