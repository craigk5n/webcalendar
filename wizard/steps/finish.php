<?php
/**
 * Finish Step - Installation complete
 */

$settingsPath = __DIR__ . '/../../includes/settings.php';
$settingsWritable = is_writable(dirname($settingsPath));
?>

<div class="text-center mb-4">
  <i class="bi bi-check-circle-fill display-1 text-success"></i>
  <h2 class="mt-3">
    <?php echo $state->isUpgrade ? 'Upgrade Complete!' : 'Installation Complete!'; ?>
  </h2>
  <p class="lead text-muted">
    WebCalendar <?php echo PROGRAM_VERSION; ?> has been successfully 
    <?php echo $state->isUpgrade ? 'upgraded' : 'installed'; ?>.
  </p>
</div>

<div class="card mb-4 border-success">
  <div class="card-header bg-success text-white">
    <i class="bi bi-check-circle me-2"></i>Success
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <h6><i class="bi bi-database me-2"></i>Database Status</h6>
        <ul class="list-unstyled">
          <li><i class="bi bi-check text-success me-2"></i>Version: <?php echo PROGRAM_VERSION; ?></li>
          <li><i class="bi bi-check text-success me-2"></i>Tables: Created/Updated</li>
          <li><i class="bi bi-check text-success me-2"></i>Admin Users: <?php echo $state->adminUserCount; ?></li>
        </ul>
      </div>
      <div class="col-md-6">
        <h6><i class="bi bi-gear me-2"></i>Configuration Status</h6>
        <ul class="list-unstyled">
          <?php if ($state->usingEnv): ?>
            <li><i class="bi bi-check text-success me-2"></i>Using environment variables</li>
          <?php else: ?>
            <li><i class="bi bi-check text-success me-2"></i>Settings file: includes/settings.php</li>
          <?php endif; ?>
          <li><i class="bi bi-check text-success me-2"></i>Authentication: 
            <?php 
              $authLabels = ['web' => 'WebCalendar', 'http' => 'HTTP', 'none' => 'Single User'];
              echo $authLabels[$state->userAuth] ?? $state->userAuth;
            ?>
          </li>
          <li><i class="bi bi-check text-success me-2"></i>Run Mode: 
            <?php echo $state->runMode === 'prod' ? 'Production' : 'Development'; ?>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php if (!$state->usingEnv && file_exists($settingsPath)): ?>
<div class="alert alert-warning">
  <i class="bi bi-shield-check me-2"></i>
  <strong>Security Reminder:</strong> Make sure the settings file is properly secured:
  <pre class="mt-2 mb-0 bg-dark text-light p-2 rounded"><code>chmod 640 <?php echo realpath($settingsPath) ?: $settingsPath; ?></code></pre>
</div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header bg-light">
    <i class="bi bi-info-circle me-2"></i>Next Steps
  </div>
  <div class="card-body">
    <ol class="mb-0">
      <li class="mb-2">
        <strong>Secure the wizard:</strong> Remove the <code>wizard/</code> directory to prevent unauthorized access.
        <pre class="mt-1 mb-2 bg-light p-2 rounded"><code>rm -rf wizard/</code></pre>
        <a class="small" data-bs-toggle="collapse" href="#securityAlternatives" role="button" aria-expanded="false" aria-controls="securityAlternatives">
          <i class="bi bi-chevron-down me-1"></i>Other options
        </a>
        <div class="collapse mt-2" id="securityAlternatives">
          <div class="card card-body bg-light small">
            <p class="mb-2">If you prefer to keep the wizard for future upgrades, these alternatives can restrict access:</p>
            <ol class="mb-0">
              <li class="mb-2">
                <strong>Restrict permissions</strong> &mdash; Prevents the web server from reading the directory.
                <pre class="mt-1 mb-0 p-2 rounded bg-dark text-light"><code>chmod 000 wizard/</code></pre>
                <span class="text-muted">Restore with <code>chmod 755 wizard/</code> when needed.</span>
              </li>
              <li>
                <strong>Move outside web root</strong> &mdash; Most secure alternative to deletion.
                <pre class="mt-1 mb-0 p-2 rounded bg-dark text-light"><code>mv wizard/ /path/outside/webroot/wizard-backup/</code></pre>
              </li>
            </ol>
          </div>
        </div>
      </li>
      <li class="mb-2">
        <strong>Access WebCalendar:</strong> Click the button below to launch WebCalendar.
      </li>
      <li class="mb-2">
        <strong>Log in:</strong> Use the admin credentials you created to log in.
      </li>
      <li>
        <strong>Customize:</strong> Explore the admin settings to configure your calendar.
      </li>
    </ol>
  </div>
</div>

<div class="d-grid gap-2 d-md-flex justify-content-md-center">
  <a href="../index.php" class="btn btn-primary btn-lg" target="_blank" rel="noopener">
    <i class="bi bi-calendar-event me-2"></i>Launch WebCalendar
  </a>
</div>
<p class="text-center text-muted mt-2">
  <small>WebCalendar will open in a new tab. You can close this wizard page.</small>
</p>

<div class="text-center mt-4">
  <p class="text-muted">
    <small>
      <i class="bi bi-info-circle me-1"></i>
      Keep your installation password safe. You'll need it for future upgrades.
    </small>
  </p>
</div>
