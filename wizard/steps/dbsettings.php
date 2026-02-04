<?php
/**
 * Database Settings Step - Configure database connection
 */

// Available database types with their detection status
$dbTypes = [
  'mysqli' => ['name' => 'MySQL (mysqli)', 'icon' => 'database', 'detected' => function_exists('mysqli_connect')],
  'postgresql' => ['name' => 'PostgreSQL', 'icon' => 'database', 'detected' => function_exists('pg_connect')],
  'sqlite3' => ['name' => 'SQLite3', 'icon' => 'file', 'detected' => class_exists('SQLite3')],
  'oracle' => ['name' => 'Oracle', 'icon' => 'database', 'detected' => function_exists('oci_connect')],
  'ibm_db2' => ['name' => 'IBM DB2', 'icon' => 'database', 'detected' => function_exists('db2_connect')],
  'odbc' => ['name' => 'ODBC', 'icon' => 'database', 'detected' => function_exists('odbc_connect')],
  'ibase' => ['name' => 'InterBase/Firebird', 'icon' => 'database', 'detected' => function_exists('ibase_connect')],
];

$readonly = $state->usingEnv;
$disabledAttr = $readonly ? 'disabled' : '';

// Check if database type is set
$currentDbType = $state->dbType ?: 'mysqli';
$isSqlite = $currentDbType === 'sqlite3';
?>

<div class="mb-4">
  <h4><i class="bi bi-database me-2"></i>Database Configuration</h4>
  <p class="text-muted">Enter your database connection details.</p>
</div>

<?php if (!empty($state->dbConnectionError)): ?>
<div class="alert alert-danger">
  <i class="bi bi-exclamation-triangle me-2"></i>
  <strong>Connection Failed:</strong> <?= htmlspecialchars($state->dbConnectionError) ?>
</div>
<?php endif; ?>

<?php if ($readonly): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Using Environment Variables:</strong> Database settings are configured via environment variables and cannot be changed through this wizard.
</div>
<?php endif; ?>

<form data-action="save-db-settings" id="dbSettingsForm">
  
  <!-- Database Type -->
  <div class="card mb-4">
    <div class="card-header bg-light">
      <i class="bi bi-hdd-stack me-2"></i>Database Type
    </div>
    <div class="card-body">
      <fieldset>
      <legend class="form-label">Select your database server:</legend>
      <div class="row">
        <?php foreach ($dbTypes as $key => $db): ?>
        <div class="col-md-4 mb-3">
          <div class="form-check">
            <input class="form-check-input" 
                   type="radio" 
                   name="db_type" 
                   id="db_<?php echo $key; ?>" 
                   value="<?php echo $key; ?>"
                   <?php echo ($currentDbType === $key) ? 'checked' : ''; ?>
                   <?php echo $disabledAttr; ?>
                   <?php echo !$db['detected'] ? 'disabled' : ''; ?>>
            <label class="form-check-label" for="db_<?php echo $key; ?>">
              <i class="bi bi-<?php echo $db['icon']; ?> me-2"></i>
              <?php echo $db['name']; ?>
              <?php if ($db['detected']): ?>
                <span class="badge bg-success ms-1">Available</span>
              <?php else: ?>
                <span class="badge bg-secondary ms-1">Not installed</span>
              <?php endif; ?>
            </label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      </fieldset>
    </div>
  </div>
  
  <!-- Connection Details -->
  <div class="card mb-4">
    <div class="card-header bg-light">
      <i class="bi bi-plug me-2"></i>Connection Details
    </div>
    <div class="card-body">
      <div class="row">
        <!-- Server (hidden for SQLite3) -->
        <div class="col-md-6 mb-3" id="serverRow">
          <label for="db_host" class="form-label">Database Server</label>
          <input type="text" 
                 class="form-control" 
                 id="db_host" 
                 name="db_host"
                 value="<?php echo htmlspecialchars($state->dbHost ?? 'localhost'); ?>"
                 placeholder="localhost"
                 <?php echo $disabledAttr; ?>
                 data-validate="db_host"
                 required>
          <div class="form-text">Hostname or IP address of the database server</div>
        </div>
        
        <!-- Login (hidden for SQLite3) -->
        <div class="col-md-6 mb-3" id="loginRow">
          <label for="db_login" class="form-label">Database Login</label>
          <input type="text" 
                 class="form-control" 
                 id="db_login" 
                 name="db_login"
                 value="<?php echo htmlspecialchars($state->dbLogin ?? ''); ?>"
                 placeholder="Username"
                 <?php echo $disabledAttr; ?>
                 data-validate="db_login"
                 required>
        </div>
        
        <!-- Password (hidden for SQLite3) -->
        <div class="col-md-6 mb-3" id="passwordRow">
          <label for="db_password" class="form-label">Database Password</label>
          <input type="password" 
                 class="form-control" 
                 id="db_password" 
                 name="db_password"
                 value="<?php echo htmlspecialchars($state->dbPassword ?? ''); ?>"
                 <?php echo $disabledAttr; ?>>
          <div class="form-text">Leave blank if no password required</div>
        </div>
        
        <!-- Database Name / File Path -->
        <div class="col-md-6 mb-3">
          <label for="db_database" class="form-label" id="dbNameLabel">
            <?php echo $isSqlite ? 'SQLite3 File Path' : 'Database Name'; ?>
          </label>
          <input type="text" 
                 class="form-control" 
                 id="db_database" 
                 name="db_database"
                 value="<?php echo htmlspecialchars($state->dbDatabase ?? ''); ?>"
                 placeholder="<?php echo $isSqlite ? '/path/to/database.sqlite3' : 'webcalendar'; ?>"
                 <?php echo $disabledAttr; ?>
                 data-validate="db_database"
                 required>
          <div class="form-text" id="dbNameHelp">
            <?php if ($isSqlite): ?>
              Absolute path to the SQLite3 database file
            <?php else: ?>
              Name of the database to use (will be created if doesn't exist)
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <!-- Advanced Options -->
      <div class="mt-3">
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#advancedOptions">
          <i class="bi bi-gear me-2"></i>Advanced Options
        </button>
        
        <div class="collapse mt-3" id="advancedOptions">
          <div class="card card-body bg-light">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="db_cachedir" class="form-label">Cache Directory (Optional)</label>
                <input type="text" 
                       class="form-control" 
                       id="db_cachedir" 
                       name="db_cachedir"
                       value="<?php echo htmlspecialchars($state->dbCacheDir ?? ''); ?>"
                       placeholder="/path/to/cache"
                       <?php echo $disabledAttr; ?>>
                <div class="form-text">Directory for database caching (must be writable)</div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="db_debug" class="form-label">Database Debugging</label>
                <select class="form-select" name="db_debug" id="db_debug" <?php echo $disabledAttr; ?>>
                  <option value="N" <?php echo !$state->dbDebug ? 'selected' : ''; ?>>Disabled (recommended)</option>
                  <option value="Y" <?php echo $state->dbDebug ? 'selected' : ''; ?>>Enabled</option>
                </select>
                <div class="form-text">Enable only for troubleshooting</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Connection Status -->
  <?php if ($state->dbConnectionSuccess): ?>
  <div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    <strong>Connection Successful!</strong>
    <?php if ($state->databaseExists): ?>
      <br>Database detected: version <?php echo htmlspecialchars($state->detectedDbVersion ?? 'unknown'); ?>
      <?php if ($state->isUpgrade): ?>
        <br><span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Upgrade required from <?php echo htmlspecialchars($state->detectedDbVersion); ?> to <?php echo PROGRAM_VERSION; ?></span>
      <?php endif; ?>
    <?php else: ?>
      <br>New database will be created.
    <?php endif; ?>
  </div>
  <?php endif; ?>
  
  <?php if (!$readonly): ?>
  <div class="d-grid gap-2 d-md-flex justify-content-md-end">
    <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('appsettings')"><i class="bi bi-arrow-left me-2"></i>Back</button>
    <button type="button"
            class="btn btn-outline-primary"
            id="testDbBtn"
            onclick="window.wizard.testDbConnection(this.closest('form'))">
      <i class="bi bi-lightning-charge me-2"></i>Test Connection
    </button>
    <button type="submit"
            class="btn btn-success btn-lg"
            id="saveDbBtn"
            <?php echo !$state->dbConnectionSuccess ? 'disabled' : ''; ?>>
      <i class="bi bi-arrow-right me-2"></i>Continue
    </button>
  </div>
  <div class="form-text text-end mt-1" id="dbBtnHint"<?php echo $state->dbConnectionSuccess ? ' style="display:none;"' : ''; ?>>
    Test your database connection before continuing.
  </div>
  <?php else: ?>
  <div class="d-grid gap-2 d-md-flex justify-content-md-end">
    <button type="button" class="btn btn-outline-secondary me-auto" onclick="window.wizard.navigateToStep('appsettings')"><i class="bi bi-arrow-left me-2"></i>Back</button>
    <button type="button" class="btn btn-primary btn-lg" data-action="continue-db-readonly">
      <i class="bi bi-arrow-right me-2"></i>Continue
    </button>
  </div>
  <?php endif; ?>
  
</form>

<script>
  // Show/hide fields based on database type
  document.querySelectorAll('input[name="db_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const isSqlite = this.value === 'sqlite3';
      
      // Toggle visibility of server/login/password fields
      document.getElementById('serverRow').style.display = isSqlite ? 'none' : 'block';
      document.getElementById('loginRow').style.display = isSqlite ? 'none' : 'block';
      document.getElementById('passwordRow').style.display = isSqlite ? 'none' : 'block';
      
      // Update labels
      document.getElementById('dbNameLabel').textContent = isSqlite ? 'SQLite3 File Path' : 'Database Name';
      document.getElementById('db_database').placeholder = isSqlite ? '/path/to/database.sqlite3' : 'webcalendar';
      document.getElementById('dbNameHelp').textContent = isSqlite 
        ? 'Absolute path to the SQLite3 database file'
        : 'Name of the database to use (will be created if doesn\'t exist)';
      
      // Update required attributes
      document.getElementById('db_host').required = !isSqlite;
      document.getElementById('db_login').required = !isSqlite;
    });
  });
</script>
