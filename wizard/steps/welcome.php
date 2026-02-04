<?php
/**
 * Welcome Step - First step of the wizard
 * Detects if this is an install or upgrade and offers appropriate paths
 */

// Check if existing settings.php exists
$hasExistingSettings = file_exists(__DIR__ . '/../../includes/settings.php');

// Check for environment variables
$hasEnvVars = !empty(getenv('WEBCALENDAR_USE_ENV'));
?>

<div class="text-center mb-4">
  <h2 class="mb-3">
    <i class="bi bi-calendar-event text-primary me-2"></i>Welcome to WebCalendar
  </h2>
  <p class="lead text-muted">
    Version <?php echo PROGRAM_VERSION; ?>
  </p>
</div>

<?php if ($hasEnvVars): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Environment Variables Detected:</strong> Configuration is being read from environment variables. Some settings cannot be modified through this wizard.
</div>
<?php endif; ?>

<?php if ($hasExistingSettings): ?>

<div class="alert alert-info mb-4">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Existing Installation Detected:</strong> A <code>settings.php</code> file was found with your current configuration.
</div>

<div class="row mb-4">
  <div class="col-md-6 mb-3">
    <div class="card h-100 border-primary">
      <div class="card-header bg-primary text-white">
        <i class="bi bi-lightning-charge me-2"></i>Quick Upgrade
      </div>
      <div class="card-body">
        <p>Keep your existing configuration and just check or upgrade the database schema.</p>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Preserves settings.php</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Upgrades database if needed</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Updates version number</li>
        </ul>
      </div>
      <div class="card-footer text-center">
        <button type="button" class="btn btn-primary" data-action="welcome-quick-upgrade">
          <i class="bi bi-lightning-charge me-2"></i>Quick Upgrade
        </button>
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-3">
    <div class="card h-100">
      <div class="card-header bg-light">
        <i class="bi bi-gear me-2"></i>Full Setup
      </div>
      <div class="card-body">
        <p>Walk through all configuration steps including database, authentication, and application settings.</p>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><i class="bi bi-pencil-square text-muted me-2"></i>Review all settings</li>
          <li class="mb-2"><i class="bi bi-pencil-square text-muted me-2"></i>Change database connection</li>
          <li class="mb-2"><i class="bi bi-pencil-square text-muted me-2"></i>Modify authentication</li>
        </ul>
      </div>
      <div class="card-footer text-center">
        <button type="button" class="btn btn-outline-secondary" data-action="welcome-continue">
          <i class="bi bi-gear me-2"></i>Full Setup
        </button>
      </div>
    </div>
  </div>
</div>

<?php else: ?>

<div class="row mb-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>What to Expect</h5>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Check PHP requirements</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Configure database connection</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Set up user authentication</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Create database tables</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Create admin account</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-shield-check me-2"></i>Requirements</h5>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>PHP 8.0 or higher</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Web server (Apache/Nginx)</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Database server access</li>
          <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Write permissions for settings</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="text-center">
  <button type="button" class="btn btn-primary btn-lg" data-action="welcome-continue">
    <i class="bi bi-arrow-right-circle me-2"></i>Start Installation
    <i class="bi bi-arrow-right ms-2"></i>
  </button>
</div>

<?php endif; ?>
