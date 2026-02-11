<?php
/**
 * WizardValidator - Real-time validation for the WebCalendar install wizard
 * 
 * Uses PHP8 typed properties and return types
 * Provides validation for all wizard form inputs
 */

class WizardValidator
{
  private array $errors = [];
  private array $fieldErrors = [];
  
  /**
   * Validate install password
   * @return array Array with 'valid' (bool), 'errors' (array), and 'field' errors
   */
  public function validateInstallPassword(string $password, string $confirmPassword): array
  {
    $this->clearErrors();
    
    if (empty($password)) {
      $this->addFieldError('password', 'Password is required');
    } elseif (strlen($password) < 8) {
      $this->addFieldError('password', 'Password must be at least 8 characters');
    }
    
    if ($password !== $confirmPassword) {
      $this->addFieldError('password2', 'Passwords do not match');
    }
    
    return $this->getValidationResult();
  }
  
  /**
   * Validate login password (for returning to wizard)
   */
  public function validateLoginPassword(string $password): array
  {
    $this->clearErrors();
    
    if (empty($password)) {
      $this->addFieldError('password', 'Password is required');
    }
    
    return $this->getValidationResult();
  }
  
  /**
   * Validate application settings
   */
  public function validateAppSettings(array $data): array
  {
    $this->clearErrors();
    
    $validAuth = ['web', 'http', 'none'];
    if (empty($data['user_auth']) || !in_array($data['user_auth'], $validAuth, true)) {
      $this->addFieldError('user_auth', 'Invalid authentication method');
    }
    
    // Single user login validation
    if (($data['user_auth'] ?? '') === 'none') {
      if (empty($data['single_user_login'])) {
        $this->addFieldError('single_user_login', 'Single user login is required for single-user mode');
      } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['single_user_login'])) {
        $this->addFieldError('single_user_login', 'Login must contain only letters, numbers, underscores, and hyphens');
      }
    }
    
    $validModes = ['prod', 'dev'];
    if (empty($data['run_mode']) || !in_array($data['run_mode'], $validModes, true)) {
      $this->addFieldError('run_mode', 'Invalid run mode');
    }
    
    return $this->getValidationResult();
  }
  
  /**
   * Validate database settings
   */
  public function validateDbSettings(array $data): array
  {
    $this->clearErrors();
    
    $validDbTypes = ['mysqli', 'postgresql', 'sqlite3', 'oracle', 'ibm_db2', 'odbc', 'ibase'];
    if (empty($data['db_type']) || !in_array($data['db_type'], $validDbTypes, true)) {
      $this->addFieldError('db_type', 'Invalid database type');
    }
    
    // SQLite3 doesn't require host, login, password
    $isSqlite = ($data['db_type'] ?? '') === 'sqlite3';
    
    if (!$isSqlite) {
      if (empty($data['db_host'])) {
        $this->addFieldError('db_host', 'Database server is required');
      }
      
      if (empty($data['db_login'])) {
        $this->addFieldError('db_login', 'Database login is required');
      }
      
      // Password can be empty for some setups
    }
    
    if (empty($data['db_database'])) {
      $this->addFieldError('db_database', 'Database name is required');
    } elseif ($isSqlite) {
      // For SQLite3, validate file path format
      if (!preg_match('/^\/[a-zA-Z0-9_.\/\-]+$/', $data['db_database']) &&
          !preg_match('/^[a-zA-Z]:\\\\/', $data['db_database'])) {
        $this->addFieldError('db_database', 'SQLite3 file path must be an absolute path');
      }
    }
    
    // Validate cache directory if provided
    if (!empty($data['db_cachedir'])) {
      if (!is_dir($data['db_cachedir'])) {
        $this->addFieldError('db_cachedir', 'Cache directory does not exist');
      } elseif (!is_writable($data['db_cachedir'])) {
        $this->addFieldError('db_cachedir', 'Cache directory is not writable');
      }
    }
    
    return $this->getValidationResult();
  }
  
  /**
   * Validate admin user creation
   */
  public function validateAdminUser(string $login, string $password, string $confirmPassword, string $email): array
  {
    $this->clearErrors();
    
    if (empty($login)) {
      $this->addFieldError('admin_login', 'Admin login is required');
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $login)) {
      $this->addFieldError('admin_login', 'Login must contain only letters, numbers, underscores, and hyphens');
    } elseif (strlen($login) < 3) {
      $this->addFieldError('admin_login', 'Login must be at least 3 characters');
    }
    
    if (empty($password)) {
      $this->addFieldError('admin_password', 'Password is required');
    } elseif (strlen($password) < 8) {
      $this->addFieldError('admin_password', 'Password must be at least 8 characters');
    }
    
    if ($password !== $confirmPassword) {
      $this->addFieldError('admin_password2', 'Passwords do not match');
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $this->addFieldError('admin_email', 'Invalid email address');
    }
    
    return $this->getValidationResult();
  }
  
  /**
   * Validate individual field in real-time
   */
  public function validateField(string $field, mixed $value, array $context = []): array
  {
    $this->clearErrors();
    
    switch ($field) {
      case 'db_host':
        if (empty($value)) {
          $this->addFieldError($field, 'Database server is required');
        }
        break;
        
      case 'db_login':
        if (empty($value)) {
          $this->addFieldError($field, 'Database login is required');
        }
        break;
        
      case 'db_database':
        if (empty($value)) {
          $this->addFieldError($field, 'Database name is required');
        }
        break;
        
      case 'password':
      case 'install_password':
      case 'admin_password':
        if (empty($value)) {
          $this->addFieldError($field, 'Password is required');
        } elseif (strlen((string)$value) < 8) {
          $this->addFieldError($field, 'Password must be at least 8 characters');
        }
        break;

      case 'password2':
        if ($value !== ($context['password'] ?? '')) {
          $this->addFieldError($field, 'Passwords do not match');
        }
        break;

      case 'admin_password2':
        if ($value !== ($context['admin_password'] ?? '')) {
          $this->addFieldError($field, 'Passwords do not match');
        }
        break;
        
      case 'single_user_login':
        if (!empty($context['user_auth']) && $context['user_auth'] === 'none') {
          if (empty($value)) {
            $this->addFieldError($field, 'Single user login is required');
          } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', (string)$value)) {
            $this->addFieldError($field, 'Login must contain only letters, numbers, underscores, and hyphens');
          }
        }
        break;
        
      case 'admin_login':
        if (empty($value)) {
          $this->addFieldError($field, 'Admin login is required');
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', (string)$value)) {
          $this->addFieldError($field, 'Login must contain only letters, numbers, underscores, and hyphens');
        }
        break;
        
      case 'admin_email':
        if (!empty($value) && !filter_var((string)$value, FILTER_VALIDATE_EMAIL)) {
          $this->addFieldError($field, 'Invalid email address');
        }
        break;
    }
    
    return $this->getValidationResult();
  }
  
  /**
   * Get PHP settings validation
   */
  public function getPhpSettings(): array
  {
    $settings = [];
    
    // PHP Version
    $phpVersion = phpversion();
    $versionOk = version_compare($phpVersion, '8.0', '>=');
    $settings[] = [
      'name' => 'PHP Version',
      'required' => '8.0+',
      'current' => $phpVersion,
      'status' => $versionOk ? 'ok' : 'error',
      'message' => $versionOk ? 'Supported' : 'PHP 8.0 or later is required',
    ];
    
    // GD Module
    $gdOk = function_exists('gd_info');
    $settings[] = [
      'name' => 'GD Module',
      'required' => 'Installed',
      'current' => $gdOk ? 'Installed' : 'Not found',
      'status' => $gdOk ? 'ok' : 'warning',
      'message' => $gdOk ? '' : 'GD module required for gradient background colors',
    ];
    
    // File uploads
    $uploadsOk = ini_get('file_uploads') == '1' || strtolower(ini_get('file_uploads')) === 'on';
    $settings[] = [
      'name' => 'File Uploads',
      'required' => 'ON',
      'current' => $uploadsOk ? 'ON' : 'OFF',
      'status' => $uploadsOk ? 'ok' : 'warning',
      'message' => $uploadsOk ? '' : 'File uploads required for category icons',
    ];
    
    // URL fopen
    $fopenOk = ini_get('allow_url_fopen') == '1' || strtolower(ini_get('allow_url_fopen')) === 'on';
    $settings[] = [
      'name' => 'Allow URL fopen',
      'required' => 'ON',
      'current' => $fopenOk ? 'ON' : 'OFF',
      'status' => $fopenOk ? 'ok' : 'warning',
      'message' => $fopenOk ? '' : 'Required for loading remote calendars',
    ];
    
    // Safe Mode
    $safeMode = ini_get('safe_mode');
    $safeModeOk = empty($safeMode) || strtolower($safeMode) === 'off';
    $settings[] = [
      'name' => 'Safe Mode',
      'required' => 'OFF',
      'current' => $safeModeOk ? 'OFF' : 'ON',
      'status' => $safeModeOk ? 'ok' : 'warning',
      'message' => $safeModeOk ? '' : 'Safe Mode needs to be disabled for timezone settings',
    ];
    
    // Database extensions
    $dbExtensions = [
      'mysqli' => 'MySQLi',
      'pgsql' => 'PostgreSQL',
      'sqlite3' => 'SQLite3',
      'oci8' => 'Oracle',
      'ibm_db2' => 'IBM DB2',
      'odbc' => 'ODBC',
      'interbase' => 'InterBase',
    ];
    
    $hasDbExtension = false;
    foreach ($dbExtensions as $ext => $name) {
      if (extension_loaded($ext)) {
        $hasDbExtension = true;
        break;
      }
    }
    
    $settings[] = [
      'name' => 'Database Extension',
      'required' => 'At least one',
      'current' => $hasDbExtension ? 'Available' : 'None found',
      'status' => $hasDbExtension ? 'ok' : 'error',
      'message' => $hasDbExtension ? '' : 'At least one database extension is required',
    ];
    
    return $settings;
  }
  
  /**
   * Check if all PHP settings are correct
   */
  public function arePhpSettingsCorrect(): bool
  {
    $settings = $this->getPhpSettings();
    foreach ($settings as $setting) {
      if ($setting['status'] === 'error') {
        return false;
      }
    }
    return true;
  }
  
  /**
   * Add field error
   */
  private function addFieldError(string $field, string $message): void
  {
    $this->fieldErrors[$field] = $message;
    $this->errors[] = $message;
  }
  
  /**
   * Clear all errors
   */
  private function clearErrors(): void
  {
    $this->errors = [];
    $this->fieldErrors = [];
  }
  
  /**
   * Get validation result array
   */
  private function getValidationResult(): array
  {
    return [
      'valid' => empty($this->errors),
      'errors' => $this->errors,
      'fieldErrors' => $this->fieldErrors,
    ];
  }
}
