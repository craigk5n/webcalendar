<?php
/**
 * Create Database Step - Create new database if needed
 */
?>

<div class="mb-4">
  <h4><i class="bi bi-plus-circle me-2"></i>Create Database</h4>
  <p class="text-muted">Set up the database for WebCalendar.</p>
</div>

<div class="card mb-4">
  <div class="card-header bg-light">
    <i class="bi bi-hdd-stack me-2"></i>Database Status
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <table class="table table-borderless">
          <tr>
            <td><strong>Database Type:</strong></td>
            <td><?php echo htmlspecialchars($state->dbType); ?></td>
          </tr>
          <tr>
            <td><strong>Server:</strong></td>
            <td><?php echo htmlspecialchars($state->dbHost); ?></td>
          </tr>
          <tr>
            <td><strong>Database Name:</strong></td>
            <td><?php echo htmlspecialchars($state->dbDatabase); ?></td>
          </tr>
        </table>
      </div>
      <div class="col-md-6">
        <div class="text-center">
          <i class="bi bi-database-exclamation display-1 text-warning"></i>
          <h5 class="mt-3">Database Needs to be Created</h5>
          <p class="text-muted">The database '<?php echo htmlspecialchars($state->dbDatabase); ?>' does not exist yet.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Next Step:</strong> Click the button below to create the database. The installer will create the database and then proceed to create the required tables.
</div>

<?php if ($state->dbType === 'sqlite3'): ?>
<div class="alert alert-warning">
  <i class="bi bi-exclamation-triangle me-2"></i>
  <strong>Note:</strong> For SQLite3, a database file will be created at the specified path. Make sure the directory is writable by the web server.
</div>
<?php endif; ?>

<div class="d-grid gap-2 d-md-flex justify-content-md-end">
  <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('dbsettings')"><i class="bi bi-arrow-left me-2"></i>Back</button>
  <button type="button" class="btn btn-success btn-lg" data-action="create-database">
    <i class="bi bi-plus-circle me-2"></i>Create Database
  </button>
</div>
