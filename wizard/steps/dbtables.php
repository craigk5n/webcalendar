<?php
/**
 * Database Tables Step - Create or upgrade database tables
 */

require_once __DIR__ . '/../shared/upgrade-sql.php';

// Determine upgrade info
$fromVersion = $state->detectedDbVersion ?? 'none';
$isEmpty = $state->databaseIsEmpty;
$needsUpgrade = $state->isUpgrade;

if ($needsUpgrade && $fromVersion !== 'none') {
  $sqlCommands = getSqlUpdates($fromVersion, $state->dbType === 'mysqli' ? 'mysql' : $state->dbType, true);
} else {
  $sqlCommands = [];
}

$actionLabel = $needsUpgrade ? 'Upgrade' : 'Create';
?>

<div class="mb-4">
  <h4><i class="bi bi-table me-2"></i>Database Tables</h4>
  <p class="text-muted"><?php echo $actionLabel; ?> database tables for WebCalendar.</p>
</div>

<?php if ($needsUpgrade): ?>
<!-- Upgrade Scenario -->
<div class="card mb-4 border-warning">
  <div class="card-header bg-warning text-dark">
    <i class="bi bi-arrow-up-circle me-2"></i>Database Upgrade Required
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-8">
        <h5>Upgrade from <?php echo htmlspecialchars($fromVersion); ?> to <?php echo PROGRAM_VERSION; ?></h5>
        <p class="mb-3">
          Your database is currently at version <strong><?php echo htmlspecialchars($fromVersion); ?></strong>. 
          The following SQL commands will upgrade it to version <strong><?php echo PROGRAM_VERSION; ?></strong>.
        </p>
        
        <div class="mb-3">
          <span class="badge bg-primary me-2"><?php echo count($sqlCommands); ?> SQL commands</span>
          <span class="badge bg-info">Database: <?php echo htmlspecialchars($state->dbDatabase); ?></span>
        </div>
      </div>
      <div class="col-md-4 text-center">
        <i class="bi bi-database-gear display-1 text-warning"></i>
      </div>
    </div>
  </div>
</div>

<?php if (count($sqlCommands) > 0): ?>
<div class="card mb-4">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <span><i class="bi bi-code-square me-2"></i>SQL Preview</span>
    <button class="btn btn-sm btn-outline-primary" onclick="copySqlToClipboard()">
      <i class="bi bi-clipboard me-1"></i>Copy
    </button>
  </div>
  <div class="card-body p-0">
    <pre class="sql-preview mb-0" id="sqlPreview"><?php 
      $sqlText = [];
      foreach ($sqlCommands as $cmd) {
        if (str_starts_with($cmd, 'function:')) {
          $sqlText[] = '-- PHP Function: ' . substr($cmd, 9);
        } else {
          $sqlText[] = trim($cmd) . ';';
        }
      }
      echo htmlspecialchars(implode("\n", $sqlText));
    ?></pre>
  </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- New Installation Scenario -->
<div class="card mb-4">
  <div class="card-header bg-light">
    <i class="bi bi-table me-2"></i>Create Tables
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-8">
        <h5>New Database Installation</h5>
        <p>
          The installer will create all necessary tables for WebCalendar <?php echo PROGRAM_VERSION; ?>.
          This includes tables for:
        </p>
        <ul>
          <li>Users and authentication</li>
          <li>Calendar events and categories</li>
          <li>User preferences and settings</li>
          <li>Groups and permissions</li>
          <li>Reminders and notifications</li>
        </ul>
      </div>
      <div class="col-md-4 text-center">
        <i class="bi bi-database-add display-1 text-success"></i>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle me-2"></i>
  <strong>Important:</strong> 
  <?php if ($needsUpgrade): ?>
    Please backup your database before proceeding with the upgrade. The changes cannot be undone.
  <?php else: ?>
    Make sure you have proper database permissions to create tables. If this is an upgrade, existing data will be preserved.
  <?php endif; ?>
</div>

<div class="d-grid gap-2 d-md-flex justify-content-md-end">
  <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('dbsettings')"><i class="bi bi-arrow-left me-2"></i>Back</button>
  <button type="button" class="btn btn-success btn-lg" data-action="execute-upgrade">
    <i class="bi bi-<?php echo $needsUpgrade ? 'arrow-up-circle' : 'plus-circle'; ?> me-2"></i>
    <?php echo $needsUpgrade ? 'Upgrade Database' : 'Create Tables'; ?>
  </button>
</div>

<script>
  function copySqlToClipboard() {
    const sqlText = document.getElementById('sqlPreview').textContent;
    navigator.clipboard.writeText(sqlText).then(function() {
      // Show success feedback
      const btn = event.target.closest('button');
      const originalText = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-check me-1"></i>Copied!';
      setTimeout(() => {
        btn.innerHTML = originalText;
      }, 2000);
    }).catch(function(err) {
      console.error('Failed to copy: ', err);
    });
  }
</script>
