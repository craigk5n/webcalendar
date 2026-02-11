<?php
/**
 * Admin User Step - Create default admin user
 */

$hasAdmin = $state->adminUserCount > 0;
$defaultAdminExists = false;

// Check if admin user exists if we have a connection
if ($state->dbConnectionSuccess) {
  require_once __DIR__ . '/../WizardDatabase.php';
  $db = new WizardDatabase($state);
  if ($db->testConnection()) {
    // Try to check for admin user
    try {
      if ($state->dbType === 'mysqli') {
        // Would need to query here
      }
    } catch (Exception $e) {
      // Ignore errors
    }
    $db->closeConnection();
  }
}
?>

<div class="mb-4">
  <h4><i class="bi bi-person-plus me-2"></i>Admin User</h4>
  <p class="text-muted">Create an administrator account for WebCalendar.</p>
</div>

<?php if ($hasAdmin): ?>
<!-- Admin users already exist -->
<div class="card mb-4 border-success">
  <div class="card-header bg-success text-white">
    <i class="bi bi-check-circle me-2"></i>Admin Users Exist
  </div>
  <div class="card-body">
    <div class="text-center mb-4">
      <i class="bi bi-people display-1 text-success"></i>
    </div>
    <h5 class="text-center">Administrator Accounts Already Present</h5>
    <p class="text-center">
      There are currently <strong><?php echo $state->adminUserCount; ?></strong> admin user(s) in the database.
    </p>
    
    <?php if (!$defaultAdminExists): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      No default "admin" user exists. You can create one below, or continue to the next step to use existing admin accounts.
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="d-grid gap-2 d-md-flex justify-content-md-end">
  <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('dbtables')"><i class="bi bi-arrow-left me-2"></i>Back</button>
  <button type="button" class="btn btn-outline-secondary"
    onclick="window.wizard.navigateToStep('<?= $state->quickUpgrade ? 'finish' : 'summary' ?>')">
    <i class="bi bi-arrow-right me-2"></i>Skip - Use Existing Admins
  </button>
</div>

<hr class="my-4">

<h5 class="mb-3">Create Additional Admin User</h5>

<?php endif; ?>

<?php if (!$hasAdmin || !$defaultAdminExists): ?>
<!-- Create admin user form -->
<div class="card">
  <div class="card-header bg-light">
    <i class="bi bi-person-gear me-2"></i>Create Admin Account
  </div>
  <div class="card-body">
    <form data-action="create-admin-user">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="admin_login" class="form-label">Login Name</label>
          <input type="text" 
                 class="form-control" 
                 id="admin_login" 
                 name="admin_login"
                 value="admin"
                 required
                 data-validate="admin_login"
                 placeholder="admin">
          <div class="form-text">The username for logging into WebCalendar</div>
        </div>
        
        <div class="col-md-6 mb-3">
          <label for="admin_email" class="form-label">Email Address</label>
          <input type="email" 
                 class="form-control" 
                 id="admin_email" 
                 name="admin_email"
                 data-validate="admin_email"
                 placeholder="admin@example.com">
          <div class="form-text">Optional - used for notifications</div>
        </div>
        
        <div class="col-md-6 mb-3">
          <label for="admin_password" class="form-label">Password</label>
          <input type="password" 
                 class="form-control" 
                 id="admin_password" 
                 name="admin_password"
                 required
                 minlength="8"
                 data-validate="admin_password"
                 placeholder="Enter password">
          <div class="form-text">Minimum 8 characters</div>
        </div>
        
        <div class="col-md-6 mb-3">
          <label for="admin_password2" class="form-label">Confirm Password</label>
          <input type="password" 
                 class="form-control" 
                 id="admin_password2" 
                 name="admin_password2"
                 required
                 data-validate="admin_password2"
                 placeholder="Re-enter password">
        </div>
      </div>
      
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Note:</strong> This user will have full administrative privileges including the ability to manage users, settings, and all calendar events.
      </div>
      
      <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('dbtables')"><i class="bi bi-arrow-left me-2"></i>Back</button>
        <button type="submit" class="btn btn-primary btn-lg">
          <i class="bi bi-person-plus me-2"></i>Create Admin User
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
