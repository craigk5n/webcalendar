<?php
/**
 * Summary Step - Review all settings before final save
 */

$settings = $state->getSettingsArray();
$settingsPath = __DIR__ . '/../../includes/settings.php';
$existingFileExists = !$state->usingEnv && file_exists($settingsPath);
$settingsMatch = $existingFileExists && compareSettingsWithExisting($state);
?>

<div class="mb-4">
  <h4><i class="bi bi-clipboard-check me-2"></i>Installation Summary</h4>
  <p class="text-muted">Review your configuration before completing the installation.</p>
</div>

<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <i class="bi bi-check-circle me-2"></i>Ready to Complete
  </div>
  <div class="card-body">
    <div class="text-center mb-4">
      <i class="bi bi-clipboard-data display-1 text-primary"></i>
    </div>

    <h5 class="text-center mb-4">Review Your Configuration</h5>

    <div class="row">
      <div class="col-md-6">
        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-gear me-2"></i>Application Settings</h6>
        <ul class="list-unstyled mb-4">
          <li class="mb-2 d-flex justify-content-between">
            <span>Authentication:</span>
            <span class="badge bg-secondary">
              <?php
                $authLabels = ['web' => 'WebCalendar', 'http' => 'HTTP Auth', 'none' => 'Single User'];
                echo $authLabels[$state->userAuth] ?? $state->userAuth;
              ?>
            </span>
          </li>
          <li class="mb-2 d-flex justify-content-between">
            <span>User Database:</span>
            <span class="badge bg-secondary"><?php echo ucfirst(str_replace(['user-', '.php'], '', $state->userDb)); ?></span>
          </li>
          <li class="mb-2 d-flex justify-content-between">
            <span>Run Mode:</span>
            <span class="badge bg-<?php echo $state->runMode === 'prod' ? 'success' : 'warning'; ?>">
              <?php echo $state->runMode === 'prod' ? 'Production' : 'Development'; ?>
            </span>
          </li>
          <li class="mb-2 d-flex justify-content-between">
            <span>Read-Only:</span>
            <span class="badge bg-<?php echo $state->readonly ? 'warning' : 'secondary'; ?>">
              <?php echo $state->readonly ? 'Yes' : 'No'; ?>
            </span>
          </li>
        </ul>
      </div>

      <div class="col-md-6">
        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-database me-2"></i>Database Settings</h6>
        <ul class="list-unstyled mb-4">
          <li class="mb-2 d-flex justify-content-between">
            <span>Database Type:</span>
            <span class="badge bg-secondary"><?php echo ucfirst($state->dbType); ?></span>
          </li>
          <li class="mb-2 d-flex justify-content-between">
            <span>Server:</span>
            <span class="font-monospace"><?php echo htmlspecialchars($state->dbHost); ?></span>
          </li>
          <li class="mb-2 d-flex justify-content-between">
            <span>Database:</span>
            <span class="font-monospace"><?php echo htmlspecialchars($state->dbDatabase); ?></span>
          </li>
          <li class="mb-2 d-flex justify-content-between">
            <span>Version:</span>
            <span class="badge bg-success"><?php echo htmlspecialchars($state->detectedDbVersion ?? PROGRAM_VERSION); ?></span>
          </li>
          <li class="mb-2 d-flex justify-content-between">
            <span>Admin Users:</span>
            <span class="badge bg-success"><?php echo $state->adminUserCount; ?></span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php if ($state->usingEnv): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Environment Variables:</strong> Your settings are being managed via environment variables. The settings.php file will not be created.
</div>
<div class="d-grid gap-2 d-md-flex justify-content-md-end">
  <button type="button" class="btn btn-outline-secondary me-auto"
    onclick="window.wizard.navigateToStep('adminuser')">
    <i class="bi bi-arrow-left me-2"></i>Back
  </button>
  <button type="button" class="btn btn-success btn-lg" id="continueToFinishBtn"
    onclick="window.wizard.navigateToStep('finish')">
    <i class="bi bi-arrow-right me-2"></i>Continue
  </button>
</div>

<?php elseif ($settingsMatch): ?>
<div class="alert alert-success">
  <i class="bi bi-check-circle-fill me-2"></i>
  <strong>Settings are already up to date.</strong>
  Your <code>includes/settings.php</code> already matches the current configuration. No changes needed.
</div>
<div class="d-grid gap-2 d-md-flex justify-content-md-end">
  <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('adminuser')"><i class="bi bi-arrow-left me-2"></i>Back</button>
  <button type="button" class="btn btn-success btn-lg" id="continueToFinishBtn" onclick="if (window.wizard) window.wizard.navigateToStep('finish')">
    <i class="bi bi-arrow-right me-2"></i>Continue
  </button>
</div>
<?php else: ?>
<div class="card mb-4">
  <div class="card-header bg-light">
    <i class="bi bi-file-earmark-code me-2"></i>Settings File Preview
  </div>
  <div class="card-body">
    <p>The following will be written to <code>includes/settings.php</code>:</p>

    <div class="bg-light p-3 rounded">
      <?php
        $preview = "&lt;?php\n";
        $preview .= "/* updated using WebCalendar " . PROGRAM_VERSION . " via wizard/index.php */\n";
        foreach ($settings as $key => $value) {
          if ($value === null) continue;
          $preview .= htmlspecialchars($key) . ": " . htmlspecialchars((string) $value) . "\n";
        }
        $preview .= "# end settings.php */\n";
        $preview .= "?&gt;";
      ?>
      <pre class="mb-0"><code><?php echo $preview; ?></code></pre>
    </div>

    <?php if ($existingFileExists): ?>
    <div class="alert alert-warning mt-3 mb-0">
      <i class="bi bi-exclamation-triangle me-2"></i>
      This will overwrite your existing <code>includes/settings.php</code>. A backup copy will be created automatically.
    </div>
    <?php else: ?>
    <div class="alert alert-info mt-3 mb-0">
      <i class="bi bi-info-circle me-2"></i>
      <strong>Security Note:</strong> The settings file will contain your database password. Ensure proper file permissions (640 recommended).
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="d-grid gap-2 d-md-flex justify-content-md-end">
  <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('adminuser')"><i class="bi bi-arrow-left me-2"></i>Back</button>
  <button type="button" class="btn btn-success btn-lg" data-action="save-settings-file">
    <i class="bi bi-save me-2"></i>Save Settings & Finish
  </button>
</div>
<?php endif; ?>
