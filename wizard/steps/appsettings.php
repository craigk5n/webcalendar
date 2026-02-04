<?php
/**
 * Application Settings Step - Configure user authentication and general settings
 */

// Available user authentication methods
$userAuthMethods = [
  'web' => ['name' => 'WebCalendar (default)', 'icon' => 'calendar'],
  'http' => ['name' => 'Web Server HTTP Auth', 'icon' => 'globe', 'disabled' => empty($_SERVER['PHP_AUTH_USER'])],
  'none' => ['name' => 'Single User Mode', 'icon' => 'person'],
];

// Available user database backends
$userDbBackends = [
  'user.php' => ['name' => 'WebCalendar (default)', 'icon' => 'database'],
  'user-ldap.php' => ['name' => 'LDAP', 'icon' => 'diagram-3', 'disabled' => !function_exists('ldap_connect')],
  'user-nis.php' => ['name' => 'NIS', 'icon' => 'hdd-network', 'disabled' => !function_exists('yp_match')],
  'user-imap.php' => ['name' => 'IMAP', 'icon' => 'envelope', 'disabled' => !function_exists('imap_open')],
  'user-joomla.php' => ['name' => 'Joomla', 'icon' => 'joomla'],
];

$readonly = $state->usingEnv;
$disabledAttr = $readonly ? 'disabled' : '';
?>

<div class="mb-4">
  <h4><i class="bi bi-gear me-2"></i>Application Settings</h4>
  <p class="text-muted">Configure how users will access WebCalendar.</p>
</div>

<?php if ($readonly): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Using Environment Variables:</strong> These settings are configured via environment variables and cannot be changed through this wizard.
</div>
<?php endif; ?>

<form data-action="save-app-settings" id="appSettingsForm">
  
  <!-- User Authentication Method -->
  <div class="card mb-4">
    <div class="card-header bg-light">
      <i class="bi bi-shield-lock me-2"></i>User Authentication
    </div>
    <div class="card-body">
      <fieldset>
      <legend class="form-label">How should users log in?</legend>

      <div class="row">
        <?php foreach ($userAuthMethods as $key => $method): ?>
        <div class="col-md-4 mb-3">
          <div class="form-check">
            <input class="form-check-input"
                   type="radio"
                   name="user_auth"
                   id="auth_<?php echo $key; ?>"
                   value="<?php echo $key; ?>"
                   <?php echo ($state->userAuth === $key) ? 'checked' : ''; ?>
                   <?php echo $disabledAttr; ?>
                   <?php echo !empty($method['disabled']) ? 'disabled' : ''; ?>>
            <label class="form-check-label" for="auth_<?php echo $key; ?>">
              <i class="bi bi-<?php echo $method['icon']; ?> me-2"></i>
              <?php echo $method['name']; ?>
            </label>
          </div>
          <?php if (!empty($method['disabled'])): ?>
          <small class="text-muted d-block mt-1">Not detected</small>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      </fieldset>
      
      <div class="form-text mt-2">
        Web-based authentication uses WebCalendar's built-in login page. Web Server authentication uses your server's HTTP authentication (like Apache's .htaccess).
      </div>
    </div>
  </div>
  
  <!-- Single User Login (shown when auth=none) -->
  <div class="card mb-4" id="singleUserSection" style="<?php echo $state->userAuth === 'none' ? '' : 'display: none;'; ?>">
    <div class="card-header bg-light">
      <i class="bi bi-person me-2"></i>Single User Settings
    </div>
    <div class="card-body">
      <div class="mb-3">
        <label for="single_user_login" class="form-label">Single User Login Name</label>
        <input type="text" 
               class="form-control" 
               id="single_user_login" 
               name="single_user_login"
               value="<?php echo htmlspecialchars($state->singleUserLogin ?? ''); ?>"
               <?php echo $disabledAttr; ?>
               data-validate="single_user_login"
               placeholder="Enter username for single-user mode">
        <div class="form-text">This is the username that will be used when accessing WebCalendar in single-user mode.</div>
      </div>
    </div>
  </div>
  
  <!-- User Database Backend (shown when auth=web) -->
  <div class="card mb-4" id="userDbSection" style="<?php echo $state->userAuth !== 'none' ? '' : 'display: none;'; ?>">
    <div class="card-header bg-light">
      <i class="bi bi-database me-2"></i>User Database Backend
    </div>
    <div class="card-body">
      <label class="form-label">Where should user credentials be stored?</label>
      
      <select class="form-select" name="user_db" <?php echo $disabledAttr; ?>>
        <?php foreach ($userDbBackends as $key => $backend): ?>
        <option value="<?php echo $key; ?>" 
                <?php echo ($state->userDb === $key) ? 'selected' : ''; ?>
                <?php echo !empty($backend['disabled']) ? 'disabled' : ''; ?>>
          <?php echo $backend['name']; ?>
          <?php if (!empty($backend['disabled'])): ?>
           (not available)
          <?php endif; ?>
        </option>
        <?php endforeach; ?>
      </select>
      
      <div class="form-text mt-2">
        WebCalendar's internal database is recommended for most installations. LDAP/IMAP/NIS require additional configuration in the auth-settings.php file.
      </div>
      
      <!-- Warning for external auth -->
      <div id="externalAuthWarning" class="alert alert-warning mt-3" style="display: none;">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Note:</strong> Additional configuration in auth-settings.php is required for LDAP, IMAP, NIS, or Joomla authentication to work properly.
      </div>
    </div>
  </div>
  
  <!-- Other Settings -->
  <div class="card mb-4">
    <div class="card-header bg-light">
      <i class="bi bi-sliders me-2"></i>Other Settings
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-check mb-3">
            <input class="form-check-input" 
                   type="checkbox" 
                   name="readonly" 
                   id="readonly"
                   <?php echo $state->readonly ? 'checked' : ''; ?>
                   <?php echo $disabledAttr; ?>>
            <label class="form-check-label" for="readonly">
              <strong>Read-Only Mode</strong>
            </label>
            <div class="form-text">Prevent users from adding or editing events.</div>
          </div>
        </div>
        
        <div class="col-md-6">
          <fieldset>
          <legend class="form-label">Run Environment</legend>
          <div class="form-check">
            <input class="form-check-input"
                   type="radio"
                   name="run_mode"
                   id="mode_prod"
                   value="prod"
                   <?php echo ($state->runMode === 'prod') ? 'checked' : ''; ?>
                   <?php echo $disabledAttr; ?>>
            <label class="form-check-label" for="mode_prod">
              Production
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input"
                   type="radio"
                   name="run_mode"
                   id="mode_dev"
                   value="dev"
                   <?php echo ($state->runMode === 'dev') ? 'checked' : ''; ?>
                   <?php echo $disabledAttr; ?>>
            <label class="form-check-label" for="mode_dev">
              Development
            </label>
          </div>
          <div class="form-text">Development mode shows verbose error messages.</div>
          </fieldset>
        </div>
      </div>
    </div>
  </div>
  
  <?php if (!$readonly): ?>
  <div class="d-grid gap-2 d-md-flex justify-content-md-end">
    <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('phpsettings')"><i class="bi bi-arrow-left me-2"></i>Back</button>
    <button type="submit" class="btn btn-primary btn-lg">
      <i class="bi bi-arrow-right me-2"></i>Continue
    </button>
  </div>
  <?php else: ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Settings are controlled by environment variables. Click below to continue to database configuration.
  </div>
  <div class="d-grid gap-2 d-md-flex justify-content-md-end">
    <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('phpsettings')"><i class="bi bi-arrow-left me-2"></i>Back</button>
    <button type="button" class="btn btn-primary btn-lg"
      onclick="window.wizard.navigateToStep('dbsettings')">
      <i class="bi bi-arrow-right me-2"></i>Continue
    </button>
  </div>
  <?php endif; ?>
  
</form>

<script>
  // Show/hide sections based on authentication choice
  document.querySelectorAll('input[name="user_auth"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const singleUserSection = document.getElementById('singleUserSection');
      const userDbSection = document.getElementById('userDbSection');
      
      if (this.value === 'none') {
        singleUserSection.style.display = 'block';
        userDbSection.style.display = 'none';
      } else {
        singleUserSection.style.display = 'none';
        userDbSection.style.display = 'block';
      }
    });
  });
  
  // Show warning for external auth backends
  document.querySelector('select[name="user_db"]')?.addEventListener('change', function() {
    const warning = document.getElementById('externalAuthWarning');
    const externalBackends = ['user-ldap.php', 'user-nis.php', 'user-imap.php', 'user-joomla.php'];
    
    if (externalBackends.includes(this.value)) {
      warning.style.display = 'block';
    } else {
      warning.style.display = 'none';
    }
  });
</script>
