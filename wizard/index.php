<?php
/**
 * WebCalendar Install Wizard - Single Page Application
 * 
 * Bootstrap 5-based installer that supports both new installations and database upgrades
 */

// Prevent direct access to non-AJAX requests without proper initialization
if (!defined('WIZARD_ENTRY')) {
  define('WIZARD_ENTRY', true);
}
// Load required files
require_once __DIR__ . '/WizardState.php';
require_once __DIR__ . '/WizardValidator.php';
require_once __DIR__ . '/WizardDatabase.php';
require_once __DIR__ . '/shared/upgrade-sql.php';
require_once __DIR__ . '/shared/upgrade_matrix.php'; // Load $PROGRAM_VERSION

// Constants
const PROGRAM_VERSION = 'v1.9.14'; // Fallback will be updated by bump_version.sh
const WIZARD_NAME = 'WebCalendar Install Wizard';

// Initialize session
$sessionName = 'WebCalendar-Wizard-' . md5(__DIR__);
ini_set('session.cookie_lifetime', 3600);
session_name($sessionName);
session_start();

// Initialize state
$state = new WizardState();

// Load existing settings and environment variables
$settingsPath = __DIR__ . '/../includes/settings.php';
$state->loadFromSettingsFile($settingsPath);

if (isset($_SESSION['wizard_state'])) {
  $state->loadFromSession();
}

// Environment variables should always take precedence
$state->loadFromEnv();

if (!isset($_SESSION['wizard_state'])) {
  $state->saveToSession();
}

// Initialize validator
$validator = new WizardValidator();

// Check for environment variable configuration
$state->usingEnv = !empty(getenv('WEBCALENDAR_USE_ENV'));

// Get current step from request or use stored step
$currentStep = $_GET['step'] ?? $state->step ?? 'welcome';

// Auth guard: unauthenticated users can only see welcome and auth.
// This prevents stale session steps from stranding users on later pages.
if (!$state->isValidUser && !in_array($currentStep, ['welcome', 'auth'])) {
  $currentStep = 'welcome';
}

// Handle phpinfo request
if (isset($_GET['action']) && $_GET['action'] === 'phpinfo') {
  phpinfo();
  exit;
}

/**
 * Handle API requests
 */
function handleApiRequest(string $action, WizardState $state, WizardValidator $validator): void
{
  $response = ['success' => false, 'message' => ''];
  
  switch ($action) {
    case 'validate-field':
      $field = $_POST['field'] ?? '';
      $value = $_POST['value'] ?? '';
      $context = $_POST['context'] ?? [];
      if ( is_string ( $context ) ) {
        $context = json_decode ( $context, true ) ?: [];
      }
      $result = $validator->validateField($field, $value, $context);
      $response = [
        'success' => $result['valid'],
        'valid' => $result['valid'],
        'fieldErrors' => $result['fieldErrors'],
        'errors' => $result['errors'],
      ];
      break;
      
    case 'save-install-password':
      $password = $_POST['password'] ?? '';
      $confirm = $_POST['password2'] ?? '';
      $hint = $_POST['hint'] ?? '';

      $result = $validator->validateInstallPassword($password, $confirm);
      if ($result['valid']) {
        $state->setInstallPassword($password);
        $state->installPasswordHint = $hint;
        $state->isValidUser = true;
        $nextStep = routeAfterAuth($state, $validator);
        $state->step = $nextStep;
        $state->saveToSession();
        $response = ['success' => true, 'redirect' => "?step=$nextStep"];
      } else {
        $response = ['success' => false, 'errors' => $result['fieldErrors']];
      }
      break;

    case 'login':
      $password = $_POST['password'] ?? '';
      $result = $validator->validateLoginPassword($password);
      if ($result['valid'] && $state->verifyInstallPassword($password)) {
        $state->isValidUser = true;
        $nextStep = routeAfterAuth($state, $validator);
        $state->step = $nextStep;
        $state->saveToSession();
        $response = ['success' => true, 'redirect' => "?step=$nextStep"];
      } else {
        $response = ['success' => false, 'errors' => ['password' => 'Invalid password']];
      }
      break;
      
    case 'save-php-settings-ack':
      $state->phpSettingsAcked = true;
      $state->step = 'appsettings';
      $state->saveToSession();
      $response = ['success' => true, 'nextStep' => 'appsettings'];
      break;
      
    case 'save-app-settings':
      $data = [
        'user_auth' => $_POST['user_auth'] ?? 'web',
        'user_db' => $_POST['user_db'] ?? 'user.php',
        'single_user_login' => $_POST['single_user_login'] ?? '',
        'readonly' => ($_POST['readonly'] ?? '') === 'on',
        'run_mode' => $_POST['run_mode'] ?? 'prod',
      ];
      
      $result = $validator->validateAppSettings($data);
      if ($result['valid']) {
        $state->setAppSettings($data);
        $state->step = 'dbsettings';
        $state->saveToSession();
        $response = ['success' => true, 'nextStep' => 'dbsettings'];
      } else {
        $response = ['success' => false, 'errors' => $result['fieldErrors']];
      }
      break;
      
    case 'test-db-connection':
      $data = [
        'db_type' => $_POST['db_type'] ?? '',
        'db_host' => $_POST['db_host'] ?? '',
        'db_login' => $_POST['db_login'] ?? '',
        'db_password' => $_POST['db_password'] ?? '',
        'db_database' => $_POST['db_database'] ?? '',
        'db_cachedir' => $_POST['db_cachedir'] ?? '',
        'db_debug' => ($_POST['db_debug'] ?? '') === 'Y',
      ];
      
      $result = $validator->validateDbSettings($data);
      if ($result['valid']) {
        $state->setDbSettings($data);
        $db = new WizardDatabase($state);
        
        if ($db->testConnection()) {
          $state->dbConnectionSuccess = true;
          $db->checkDatabase();
          $db->closeConnection();
          $state->saveToSession();
          $response = [
            'success' => true,
            'message' => 'Database connection successful',
            'databaseExists' => $state->databaseExists,
            'databaseIsEmpty' => $state->databaseIsEmpty,
            'detectedVersion' => $state->detectedDbVersion,
            'isUpgrade' => $state->isUpgrade,
            'adminUserCount' => $state->adminUserCount,
          ];
        } else {
          $response = [
            'success' => false,
            'message' => $db->getError(),
          ];
        }
      } else {
        $response = ['success' => false, 'errors' => $result['fieldErrors']];
      }
      break;
      
    case 'save-db-settings':
      $data = [
        'db_type' => $_POST['db_type'] ?? '',
        'db_host' => $_POST['db_host'] ?? '',
        'db_login' => $_POST['db_login'] ?? '',
        'db_password' => $_POST['db_password'] ?? '',
        'db_database' => $_POST['db_database'] ?? '',
        'db_cachedir' => $_POST['db_cachedir'] ?? '',
        'db_debug' => ($_POST['db_debug'] ?? '') === 'Y',
      ];

      $state->setDbSettings($data);

      // Re-verify database state so routing uses fresh data
      // rather than potentially stale session flags.
      $db = new WizardDatabase($state);
      if ($db->testConnection()) {
        $state->dbConnectionSuccess = true;
        $db->checkDatabase();
        $db->closeConnection();
      }

      // Determine next step based on database state
      if (!$state->databaseExists || $state->databaseIsEmpty) {
        $nextStep = 'createdb';
      } elseif ($state->isUpgrade) {
        if (empty($state->upgradeSqlCommands)) {
          // No-op upgrade (only version bump), execute directly
          $db->reconnect();
          if ($db->executeUpgrade()) {
            $db->checkDatabase();
            if ($state->adminUserCount < 1) {
              $nextStep = 'adminuser';
            } else {
              $nextStep = 'summary';
            }
          } else {
            $nextStep = 'dbtables'; // Fallback if executeUpgrade fails
          }
          $db->closeConnection();
        } else {
          $nextStep = 'dbtables';
        }
      } elseif ($state->adminUserCount < 1) {
        $nextStep = 'adminuser';
      } else {
        $nextStep = 'summary';
      }

      $state->step = $nextStep;
      $state->saveToSession();
      
      $response = ['success' => true, 'nextStep' => $nextStep];
      break;
      
    case 'continue-db-readonly':
      // For env-var mode: test connection using existing state settings
      $db = new WizardDatabase($state);
      if ($db->testConnection()) {
        $state->dbConnectionSuccess = true;
        $db->checkDatabase();
        $db->closeConnection();
      }

      // Determine next step based on database state
      if (!$state->databaseExists || $state->databaseIsEmpty) {
        $nextStep = 'createdb';
      } elseif ($state->isUpgrade || $state->databaseIsEmpty) {
        $nextStep = 'dbtables';
      } elseif ($state->adminUserCount < 1) {
        $nextStep = 'adminuser';
      } else {
        $nextStep = 'summary';
      }

      $state->step = $nextStep;
      $state->saveToSession();
      $response = ['success' => true, 'nextStep' => $nextStep];
      break;

    case 'create-database':
      $db = new WizardDatabase($state);
      
      if ($db->createDatabase()) {
        $db->checkDatabase();
        $db->closeConnection();
        $state->step = 'dbtables';
        $state->saveToSession();
        $response = [
          'success' => true,
          'nextStep' => 'dbtables',
          'databaseCreated' => true,
        ];
      } else {
        $response = [
          'success' => false,
          'message' => $db->getError(),
        ];
      }
      break;
      
    case 'execute-upgrade':
      $db = new WizardDatabase($state);

      // Re-establish connection
      if (!$db->testConnection()) {
        $response = [
          'success' => false,
          'message' => 'Database connection failed: ' . $db->getError(),
        ];
        break;
      }

      // After connection is established, check database to select it
      $db->checkDatabase();
      // Don't close connection - executeUpgrade needs it

      if ($db->executeUpgrade()) {
        $db->checkDatabase();
        $db->closeConnection();
        $state->detectedDbVersion = $state->programVersion;
        $state->databaseIsEmpty = false;
        $state->isUpgrade = false;

        if ($state->adminUserCount < 1) {
          $nextStep = 'adminuser';
        } elseif ($state->quickUpgrade) {
          $nextStep = 'finish';
        } else {
          $nextStep = 'summary';
        }

        $state->step = $nextStep;
        $state->saveToSession();
        $response = [
          'success' => true,
          'nextStep' => $nextStep,
          'message' => 'Database tables created/updated successfully',
        ];
      } else {
        $response = [
          'success' => false,
          'message' => $db->getError(),
        ];
      }
      break;
      
    case 'get-upgrade-sql':
      $db = new WizardDatabase($state);
      $commands = $db->getUpgradeSqlCommands();
      $response = [
        'success' => true,
        'commands' => $commands,
      ];
      break;
      
    case 'create-admin-user':
  
      $db = new WizardDatabase($state);
      
      // Re-establish connection
      if (!$db->testConnection()) {
        $response = [
          'success' => false,
          'message' => 'Database connection failed: ' . $db->getError(),
        ];
        break;
      }
      
      $login = $_POST['admin_login'] ?? 'admin';
      $password = $_POST['admin_password'] ?? '';
      $confirm = $_POST['admin_password2'] ?? '';
      $email = $_POST['admin_email'] ?? '';
      
      $result = $validator->validateAdminUser($login, $password, $confirm, $email);
      if ($result['valid']) {
        if ($db->createAdminUser($login, $password, $email)) {
          $state->adminUserCount++;
          $nextStep = $state->quickUpgrade ? 'finish' : 'summary';
          $state->step = $nextStep;
          $state->saveToSession();
          $response = [
            'success' => true,
            'nextStep' => $nextStep,
            'message' => 'Admin user created successfully',
          ];
        } else {
          $response = [
            'success' => false,
            'message' => $db->getError(),
          ];
        }
      } else {
        $response = ['success' => false, 'errors' => $result['fieldErrors']];
      }
      $db->closeConnection();
      break;
      
    case 'save-settings-file':
      if ($state->usingEnv) {
        $response = ['success' => false, 'message' => 'Cannot save settings file when using environment variables'];
        break;
      }

      $settingsContent = generateSettingsFile($state);
      $settingsPath = __DIR__ . '/../includes/settings.php';
      
      // Backup existing file if it exists
      if (file_exists($settingsPath)) {
        $backupPath = $settingsPath . '.backup.' . date('YmdHis');
        copy($settingsPath, $backupPath);
      }
      
      if (file_put_contents($settingsPath, $settingsContent)) {
        $state->step = 'finish';
        $state->saveToSession();
        $response = [
          'success' => true,
          'nextStep' => 'finish',
          'message' => 'Settings saved successfully',
        ];
      } else {
        $response = [
          'success' => false,
          'message' => 'Failed to write settings file. Check file permissions.',
        ];
      }
      break;
      
    case 'welcome-continue':
      $state->quickUpgrade = false;
      $state->step = 'auth';
      $state->saveToSession();
      $response = ['success' => true, 'nextStep' => 'auth'];
      break;

    case 'welcome-quick-upgrade':
      $state->quickUpgrade = true;
      $state->step = 'auth';
      $state->saveToSession();
      $response = ['success' => true, 'nextStep' => 'auth'];
      break;

    case 'logout':
      session_destroy();
      $response = ['success' => true, 'redirect' => 'welcome'];
      break;

    default:
      $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
  }
  
  echo json_encode($response);
}

// Handle AJAX API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  ini_set('display_errors', '0');
  while (ob_get_level()) {
    ob_end_clean();
  }
  ob_start();
  handleApiRequest($_POST['action'], $state, $validator);
  $output = ob_get_clean();
  
  // Clean up any leading/trailing junk
  $output = trim($output);
  $jsonStart = strpos($output, '{');
  if ($jsonStart !== 0 && $jsonStart !== false) {
    $output = substr($output, $jsonStart);
  }

  header('Content-Type: application/json');
  echo $output;
  exit;
}

// Handle AJAX GET requests for step content
if (isset($_GET['ajax']) && $_GET['ajax'] === 'step') {
  header('Content-Type: text/html');
  $stepFile = __DIR__ . '/steps/' . $currentStep . '.php';
  if (file_exists($stepFile)) {
    include $stepFile;
  } else {
    echo "<div class='alert alert-danger'>Step not found: " . htmlspecialchars($currentStep) . "</div>";
  }
  exit;
}

// Handle AJAX GET requests for wizard state
if (isset($_GET['ajax']) && $_GET['ajax'] === 'state') {
  header('Content-Type: application/json');
  echo json_encode([
    'success' => true,
    'state' => $state->toArray(),
    'phpSettings' => $validator->getPhpSettings(),
  ]);
  exit;
}

/**
 * Determine the next step after successful authentication.
 *
 * For quick upgrades: connect to DB using existing settings, check the
 * schema version, and route directly to the appropriate step (dbtables,
 * adminuser, or finish) — skipping app/db settings and summary.
 *
 * For full setup: route through phpsettings or appsettings as before.
 */
function routeAfterAuth(WizardState $state, WizardValidator $validator): string
{
  if ($state->quickUpgrade || $state->usingEnv) {
    // For quick upgrades or ENV-based config: use existing/provided DB settings
    // Reset stale database state flags before checking - don't trust session data
    $state->resetDbConnection();
    $db = new WizardDatabase($state);

    if ($db->testConnection()) {
      $state->dbConnectionSuccess = true;
      $db->checkDatabase();
      $db->closeConnection();

      if ($state->quickUpgrade && (!$state->databaseExists || $state->databaseIsEmpty)) {
        // No database to upgrade — fall back to full setup
        $state->quickUpgrade = false;
        return 'dbsettings';
      } elseif ($state->isUpgrade || $state->databaseIsEmpty) {
        return 'dbtables';
      } elseif ($state->adminUserCount < 1) {
        return 'adminuser';
      } else { // If no upgrade needed and admin user exists, go to finish
        if ($state->quickUpgrade) {
          $db->reconnect();
          if (!$db->executeUpgrade()) {
            // Failed to ensure program version in DB
          }
        }
        return 'finish';
      }
    } elseif ($state->quickUpgrade) {
      // DB connection failed during quick upgrade — fall back to full setup
      $state->dbConnectionError = $db->getError();
      $state->quickUpgrade = false;
      return 'dbsettings';
    }
    // If usingEnv but connection failed, proceed to dbsettings to show the error
  }

  // Full setup: skip phpsettings if all requirements are already met
  $nextStep = $validator->arePhpSettingsCorrect() ? 'appsettings' : 'phpsettings';
  $state->phpSettingsAcked = $state->phpSettingsAcked || ($nextStep === 'appsettings');
  return $nextStep;
}

/**
 * Generate settings.php file content.
 * Uses the colon-separated format expected by includes/config.php:
 *   key: value
 */
function generateSettingsFile(WizardState $state): string
{
  $settings = $state->getSettingsArray();
  $date = date('D, d M Y H:i:s T');

  $content = "<?php\n";
  $content .= "/* updated using WebCalendar " . PROGRAM_VERSION
    . " via wizard/index.php on " . $date . "\n";

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
 * Compare wizard settings with existing settings.php file.
 * Returns true if all managed settings are functionally equivalent
 * (ignoring comments, whitespace, and PHP tags).
 */
function compareSettingsWithExisting(WizardState $state): bool
{
  $settingsPath = __DIR__ . '/../includes/settings.php';
  if (!file_exists($settingsPath)) {
    return false;
  }

  $content = @file_get_contents($settingsPath);
  if (empty($content)) {
    return false;
  }

  $proposed = $state->getSettingsArray();

  // Values that are functionally equivalent to being absent from the file,
  // since config.php uses these as defaults when a key is missing.
  $defaults = [
    'readonly' => 'false',
    'single_user' => 'false',
    'single_user_login' => '',
    'use_http_auth' => 'false',
    'db_debug' => 'false',
    'db_cachedir' => '',
    'mode' => 'prod',
  ];

  foreach ($proposed as $key => $value) {
    if ($value === null) {
      continue;
    }

    $proposedStr = (string) $value;

    if (preg_match('/' . preg_quote($key, '/') . ':\s*(.*)/', $content, $m)) {
      $existingStr = trim($m[1]);
      if ($existingStr !== $proposedStr) {
        return false;
      }
    } else {
      // Key not in existing file — only a real difference if it's not a default
      if (!isset($defaults[$key]) || $proposedStr !== $defaults[$key]) {
        return false;
      }
    }
  }

  return true;
}

// Update stored step
$state->step = $currentStep;
$state->saveToSession();

// Get available steps
$steps = getSteps($state, $validator);

/**
 * Get wizard steps configuration
 */
function getSteps(WizardState $state, WizardValidator $validator): array
{
  $isComplete = [
    'welcome' => true,
    'auth' => $state->isValidUser || !$state->isInitialized,
    'phpsettings' => $validator->arePhpSettingsCorrect() || $state->phpSettingsAcked,
    'appsettings' => !$state->appSettingsModified || $state->usingEnv,
    'dbsettings' => $state->dbConnectionSuccess && !$state->dbSettingsModified,
    'createdb' => $state->databaseExists,
    'dbtables' => $state->databaseExists && !$state->databaseIsEmpty && !$state->isUpgrade,
    'adminuser' => $state->adminUserCount > 0,
    'summary' => $state->adminUserCount > 0,
    'finish' => $state->isComplete(),
  ];

  $steps = [
    ['id' => 'welcome', 'name' => 'Welcome', 'icon' => 'house-door'],
    ['id' => 'auth', 'name' => 'Authentication', 'icon' => 'shield-lock'],
    ['id' => 'phpsettings', 'name' => 'PHP Settings', 'icon' => 'code-slash'],
    ['id' => 'appsettings', 'name' => 'App Settings', 'icon' => 'gear'],
    ['id' => 'dbsettings', 'name' => 'Database', 'icon' => 'database'],
    ['id' => 'createdb', 'name' => 'Create DB', 'icon' => 'plus-circle'],
    ['id' => 'dbtables', 'name' => 'Tables', 'icon' => 'table'],
    ['id' => 'adminuser', 'name' => 'Admin User', 'icon' => 'person-plus'],
    ['id' => 'summary', 'name' => 'Summary', 'icon' => 'clipboard-check'],
    ['id' => 'finish', 'name' => 'Finish', 'icon' => 'check-circle'],
  ];

  // Attach completion status to each step
  foreach ($steps as &$step) {
    $step['complete'] = $isComplete[$step['id']] ?? false;
  }
  unset($step);

  return $steps;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo WIZARD_NAME; ?> - WebCalendar <?php echo PROGRAM_VERSION; ?></title>
  
  <!-- Bootstrap 5 CSS (local) -->
  <link href="wizard_assets/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link href="wizard.css" rel="stylesheet">
  
  <!-- Bootstrap Icons CDN for now - can be replaced with local if needed -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand" href="#">
        <i class="bi bi-calendar-event me-2"></i>
        <?php echo WIZARD_NAME; ?>
      </a>

      <div class="d-flex align-items-center">
        <button class="btn btn-outline-light btn-sm" id="logoutBtn">
          <i class="bi bi-arrow-counterclockwise me-1"></i>Start Over
        </button>
      </div>
    </div>
  </nav>

  <!-- Main Container -->
  <div class="container mt-4">
    <div class="row">
      <!-- Progress Sidebar -->
      <div class="col-md-3">
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Progress</h5>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush" id="progressSteps">
              <?php foreach ($steps as $index => $step):
                $isActive = $step['id'] === $currentStep;
                $stepComplete = !empty($step['complete']);
                $isClickable = $stepComplete || $isActive;
              ?>
              <button type="button"
                      class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $isActive ? 'active' : ($stepComplete ? 'list-group-item-success' : ''); ?>"
                      data-step="<?php echo $step['id']; ?>"
                      <?php echo !$isClickable ? 'disabled' : ''; ?>>
                <span>
                  <i class="bi bi-<?php echo $step['icon']; ?> me-2"></i>
                  <?php echo $step['name']; ?>
                </span>
                <?php if ($stepComplete): ?>
                  <i class="bi bi-check-circle-fill text-success"></i>
                <?php endif; ?>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        
        <!-- System Status -->
        <div class="card mt-3">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Status</h6>
          </div>
          <div class="card-body small">
            <div class="mb-2">
              <strong>Version:</strong> <?php echo PROGRAM_VERSION; ?>
            </div>
            <div class="mb-2">
              <strong>PHP:</strong> <?php echo phpversion(); ?>
            </div>
            <?php if ($state->detectedDbVersion): ?>
            <div class="mb-2">
              <strong>DB Version:</strong> <?php echo $state->detectedDbVersion; ?>
            </div>
            <?php endif; ?>
            <?php if ($state->usingEnv): ?>
            <div class="alert alert-warning py-1 px-2 mb-0">
              <small><i class="bi bi-exclamation-triangle me-1"></i>Using ENV vars</small>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Main Content Area -->
      <div class="col-md-9">
        <!-- Progress Bar -->
        <div class="progress mb-3" style="height: 8px;">
          <?php 
            $totalSteps = count($steps);
            $currentStepIndex = array_search($currentStep, array_column($steps, 'id'));
            $progressPercent = (($currentStepIndex + 1) / $totalSteps) * 100;
          ?>
          <div class="progress-bar" role="progressbar" style="width: <?php echo $progressPercent; ?>%" 
               aria-valuenow="<?php echo $progressPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        
        <!-- Step Content -->
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0" id="stepTitle">
              <?php
                $stepNames = array_column($steps, 'name', 'id');
                echo $stepNames[$currentStep] ?? 'Welcome';
              ?>
            </h4>
          </div>
          <div class="card-body">
            <!-- Alert Messages -->
            <div id="alertContainer" class="mb-3"></div>
            <div id="stepContent">
            <?php
              $stepFile = __DIR__ . '/steps/' . $currentStep . '.php';
              if (file_exists($stepFile)) {
                include $stepFile;
              } else {
                echo '<div class="alert alert-warning">Loading...</div>';
              }
            ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="mt-5 py-3 text-center text-muted">
    <small>WebCalendar <?php echo PROGRAM_VERSION; ?> &copy; <?php echo date('Y'); ?></small>
  </footer>

  <!-- Bootstrap 5 JS Bundle (local) -->
  <script src="wizard_assets/js/bootstrap.bundle.min.js"></script>
  
  <!-- Wizard JavaScript -->
  <script src="wizard.js"></script>
  
  <!-- Initialize wizard -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      window.wizard = new WebCalendarWizard({
        currentStep: '<?php echo $currentStep; ?>',
        programVersion: '<?php echo PROGRAM_VERSION; ?>',
        isUpgrade: <?php echo $state->isUpgrade ? 'true' : 'false'; ?>,
        usingEnv: <?php echo $state->usingEnv ? 'true' : 'false'; ?>
      });
    });
  </script>
</body>
</html>
