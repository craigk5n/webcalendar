<?php
/**
 * PHP Settings Step - Check PHP environment and requirements
 */

$phpSettings = $validator->getPhpSettings();
$allCorrect = $validator->arePhpSettingsCorrect();
$hasErrors = false;

foreach ($phpSettings as $setting) {
  if ($setting['status'] === 'error') {
    $hasErrors = true;
    break;
  }
}
?>

<div class="mb-4">
  <h4><i class="bi bi-code-slash me-2"></i>PHP Environment Check</h4>
  <p class="text-muted">Checking your PHP configuration for compatibility with WebCalendar.</p>
</div>

<div class="table-responsive">
  <table class="table table-hover php-settings-table">
    <thead class="table-light">
      <tr>
        <th>Setting</th>
        <th>Required</th>
        <th>Current</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($phpSettings as $setting): ?>
      <tr>
        <td>
          <strong><?php echo htmlspecialchars($setting['name']); ?></strong>
          <?php if (!empty($setting['message'])): ?>
          <br><small class="text-muted"><?php echo htmlspecialchars($setting['message']); ?></small>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($setting['required']); ?></td>
        <td><code><?php echo htmlspecialchars($setting['current']); ?></code></td>
        <td>
          <?php if ($setting['status'] === 'ok'): ?>
            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>OK</span>
          <?php elseif ($setting['status'] === 'warning'): ?>
            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Warning</span>
          <?php else: ?>
            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Error</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($hasErrors): ?>
<div class="alert alert-danger">
  <i class="bi bi-exclamation-circle me-2"></i>
  <strong>Critical Issues Found:</strong> Please address the errors above before continuing. WebCalendar may not function correctly without these requirements.
</div>
<?php endif; ?>

<?php if (!$allCorrect && !$hasErrors): ?>
<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle me-2"></i>
  <strong>Non-Critical Warnings:</strong> Some optional features may not be available. You can still proceed, but some functionality may be limited.
</div>
<?php endif; ?>

<div class="card mt-4">
  <div class="card-header bg-light">
    <i class="bi bi-info-circle me-2"></i>Detailed PHP Information
  </div>
  <div class="card-body">
    <p>View complete PHP configuration to troubleshoot any issues:</p>
    <a href="?action=phpinfo" target="_blank" class="btn btn-outline-secondary">
      <i class="bi bi-box-arrow-up-right me-2"></i>View phpinfo()
    </a>
  </div>
</div>

<div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
  <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('welcome')"><i class="bi bi-arrow-left me-2"></i>Back</button>
  <?php if ($allCorrect): ?>
    <button type="button" class="btn btn-success btn-lg" data-action="save-php-settings-ack">
      <i class="bi bi-check-circle me-2"></i>All Requirements Met - Continue
    </button>
  <?php elseif ($hasErrors): ?>
    <button type="button" class="btn btn-danger btn-lg" disabled>
      <i class="bi bi-x-circle me-2"></i>Fix Errors to Continue
    </button>
  <?php else: ?>
    <button type="button" class="btn btn-warning btn-lg"
            data-bs-toggle="modal" data-bs-target="#phpWarningModal">
      <i class="bi bi-exclamation-triangle me-2"></i>Acknowledge Warnings & Continue
    </button>
  <?php endif; ?>
</div>

<!-- Warning Confirmation Modal -->
<div class="modal fade" id="phpWarningModal" tabindex="-1" aria-labelledby="phpWarningModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="phpWarningModalLabel">
          <i class="bi bi-exclamation-triangle me-2"></i>Continue with Warnings?
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Some optional PHP features are not available on your server. This means certain WebCalendar functionality may be limited.</p>
        <p class="mb-0">Are you sure you want to continue?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Go Back</button>
        <button type="button" class="btn btn-warning" onclick="bootstrap.Modal.getInstance(document.getElementById('phpWarningModal')).hide(); window.wizard.handleAction('save-php-settings-ack', this);">
          Continue Anyway
        </button>
      </div>
    </div>
  </div>
</div>
