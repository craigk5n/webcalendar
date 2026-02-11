<?php
/**
 * Authentication Step - Set or enter install password
 * 
 * First-time users set a password, returning users enter it
 */

$hasPassword = !empty($state->installPassword);
?>

<?php if (!$hasPassword): ?>
  <!-- First-time setup: Create install password -->
  <div class="mb-4">
    <div class="alert alert-info">
      <i class="bi bi-info-circle me-2"></i>
      <strong>Secure Your Installation:</strong> Please set a password to protect the installation wizard. You'll need this password if you return to upgrade WebCalendar later.
    </div>
  </div>

  <form data-action="save-install-password">
    <div class="mb-3">
      <label for="hint" class="form-label">
        <i class="bi bi-question-circle me-2"></i>Password Hint
      </label>
      <input type="text" 
             class="form-control" 
             id="hint" 
             name="hint" 
             placeholder="Enter a hint to help you remember this password"
             data-validate="hint">
      <div class="form-text">This hint will be shown when you return to the wizard.</div>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">
        <i class="bi bi-lock me-2"></i>Installation Password
      </label>
      <input type="password" 
             class="form-control" 
             id="password" 
             name="password" 
             required
             minlength="8"
             data-validate="password">
      <div class="form-text">Minimum 8 characters. Keep this password safe!</div>
    </div>

    <div class="mb-3">
      <label for="password2" class="form-label">
        <i class="bi bi-lock-fill me-2"></i>Confirm Password
      </label>
      <input type="password" 
             class="form-control" 
             id="password2" 
             name="password2" 
             required
<<<<<<< HEAD
             data-validate="password">
=======
             data-validate="password2">
>>>>>>> dev
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
      <button type="submit" class="btn btn-primary btn-lg">
        <i class="bi bi-check-circle me-2"></i>Save Password & Continue
      </button>
    </div>
  </form>

<?php else: ?>
  <!-- Returning user: Enter install password -->
  <div class="mb-4 text-center">
    <h4><i class="bi bi-shield-lock text-primary me-2"></i>Authentication Required</h4>
    <p class="text-muted">Please enter your installation password to continue.</p>
  </div>

  <?php if ($state->installPasswordHint): ?>
  <div class="alert alert-light border mb-4">
    <i class="bi bi-question-circle me-2"></i>
    <strong>Hint:</strong> <?php echo htmlspecialchars($state->installPasswordHint); ?>
  </div>
  <?php endif; ?>

  <form data-action="login" class="max-width-400 mx-auto">
    <div class="mb-3">
      <label for="password" class="form-label">
        <i class="bi bi-key me-2"></i>Installation Password
      </label>
      <input type="password" 
             class="form-control form-control-lg" 
             id="password" 
             name="password" 
             required
             autocomplete="current-password"
             autofocus>
    </div>

    <div class="d-grid gap-2 mt-4">
      <button type="submit" class="btn btn-primary btn-lg">
        <i class="bi bi-box-arrow-in-right me-2"></i>Continue
      </button>
    </div>
  </form>

  <div class="text-center mt-4">
    <small class="text-muted">
      <i class="bi bi-info-circle me-1"></i>
      Forgot your password? You'll need to manually edit or delete the settings.php file to reset it.
    </small>
  </div>
<?php endif; ?>
