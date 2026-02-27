/**
 * WebCalendarWizard - Single Page Application for WebCalendar Installation
 * 
 * Handles all client-side interactions, AJAX requests, validation, and UI updates
 */

class WebCalendarWizard {
  constructor(options) {
    this.currentStep = options.currentStep || 'welcome';
    this.programVersion = options.programVersion || 'v1.9.15';
    this.isUpgrade = options.isUpgrade || false;
    this.usingEnv = options.usingEnv || false;
    
    this.state = {};
    this.fieldValidation = {};
    
    this.init();
  }
  
  /**
   * Initialize the wizard
   */
  init() {
    this.bindEvents();
    this.loadWizardState();
    this.initPasswordToggles();
  }
  
  /**
   * Bind all event handlers
   */
  bindEvents() {
    // Progress step navigation
    document.querySelectorAll('#progressSteps button[data-step]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const step = e.currentTarget.dataset.step;
        if (!e.currentTarget.disabled) {
          this.navigateToStep(step);
        }
      });
    });
    
    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', () => this.logout());
    }
    
    // Real-time form validation
    document.addEventListener('input', (e) => {
      if (e.target.dataset.validate) {
        this.validateField(e.target);
      }
    });
    
    document.addEventListener('change', (e) => {
      if (e.target.dataset.validate) {
        this.validateField(e.target);
      }
    });
    
    // Form submissions
    document.addEventListener('submit', (e) => {
      if (e.target.dataset.action) {
        e.preventDefault();
        this.handleFormSubmit(e.target);
      }
    });
    
    // Button clicks for actions
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action]');
      if (btn && btn.tagName !== 'FORM') {
        e.preventDefault();
        this.handleAction(btn.dataset.action, btn);
      }
    });
  }
  
  /**
   * Load wizard state from server
   */
  async loadWizardState() {
    try {
      const response = await fetch('?ajax=state');
      const data = await response.json();
      
      if (data.success) {
        this.state = data.state;
        this.phpSettings = data.phpSettings;
        this.updateUI();
      }
    } catch (error) {
      console.error('Failed to load wizard state:', error);
    }
  }
  
  /**
   * Navigate to a specific step
   */
  async navigateToStep(step) {
    if (step === this.currentStep) return;
    
    try {
      const response = await fetch(`?step=${step}&ajax=step`);
      const html = await response.text();
      
      document.getElementById('stepContent').innerHTML = html;
      this.currentStep = step;
      this.updateUI();
      this.initPasswordToggles();
      
      // Update URL without page reload
      history.pushState({ step }, '', `?step=${step}`);
    } catch (error) {
      this.showAlert('danger', 'Failed to load step: ' + error.message);
    }
  }
  
  /**
   * Update UI based on current state
   */
  updateUI() {
    // Update progress steps
    document.querySelectorAll('#progressSteps button').forEach(btn => {
      const step = btn.dataset.step;
      const isActive = step === this.currentStep;
      const isComplete = this.state[`${step}Complete`] || false;
      
      btn.classList.toggle('active', isActive);
      btn.disabled = !isComplete && !isActive;
    });
    
    // Update progress bar
    const steps = document.querySelectorAll('#progressSteps button');
    const currentIndex = Array.from(steps).findIndex(btn => btn.dataset.step === this.currentStep);
    const progressPercent = ((currentIndex + 1) / steps.length) * 100;
    document.querySelector('.progress-bar').style.width = `${progressPercent}%`;
    
    // Update step title
    const stepTitle = document.getElementById('stepTitle');
    if (stepTitle) {
      const activeBtn = document.querySelector(`#progressSteps button[data-step="${this.currentStep}"]`);
      if (activeBtn) {
        stepTitle.textContent = activeBtn.querySelector('span').textContent.trim();
      }
    }
  }
  
  /**
   * Handle form submissions
   */
  async handleFormSubmit(form) {
    const action = form.dataset.action;
    const formData = new FormData(form);
    
    // Add action to form data
    formData.append('action', action);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    }
    
    try {
      const response = await fetch('', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.success) {
        // Only show success alert when the server sends a specific message
        if (data.message) {
          this.showAlert('success', data.message);
        }

        // Update state
        if (data.state) {
          this.state = { ...this.state, ...data.state };
        }

        // Navigate to next step
        if (data.nextStep) {
          setTimeout(() => this.navigateToStep(data.nextStep), 500);
        }

        // Handle redirect
        if (data.redirect) {
          window.location.href = data.redirect;
        }
      } else {
        this.showAlert('danger', data.message || 'An error occurred');

        // Show field errors
        if (data.errors) {
          this.showFieldErrors(data.errors);
        }
      }
    } catch (error) {
      this.showAlert('danger', 'Request failed: ' + error.message);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
      }
    }
  }
  
  /**
   * Handle button actions
   */
  async handleAction(action, btn) {
    // Store original button text
    if (!btn.dataset.originalText) {
      btn.dataset.originalText = btn.innerHTML;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    
    try {
      const formData = new FormData();
      formData.append('action', action);
      
      // Add any data attributes from the button
      Object.keys(btn.dataset).forEach(key => {
        if (key !== 'action' && key !== 'originalText') {
          formData.append(key, btn.dataset[key]);
        }
      });
      
      const response = await fetch('', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.success) {
        if (data.message) {
          this.showAlert('success', data.message);
        }

        if (data.nextStep) {
          setTimeout(() => this.navigateToStep(data.nextStep), 500);
        }

        if (data.redirect) {
          window.location.href = data.redirect;
        }
      } else {
        this.showAlert('danger', data.message || 'An error occurred');
      }
    } catch (error) {
      this.showAlert('danger', 'Request failed: ' + error.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = btn.dataset.originalText;
    }
  }
  
  /**
   * Validate a single field
   */
  async validateField(field) {
    const fieldName = field.name;
    const value = field.value;
    const context = {};
    
    // Gather context for validation
    const form = field.closest('form');
    if (form) {
      const formData = new FormData(form);
      formData.forEach((val, key) => {
        if (key !== fieldName) {
          context[key] = val;
        }
      });
    }
    
    try {
      const formData = new FormData();
      formData.append('action', 'validate-field');
      formData.append('field', fieldName);
      formData.append('value', value);
      formData.append('context', JSON.stringify(context));
      
      const response = await fetch('', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      // Update field validation state
      this.fieldValidation[fieldName] = data.valid;
      
      // Show/hide validation feedback
      this.showFieldValidation(field, data.valid, data.fieldErrors?.[fieldName]);
      
      // Enable/disable submit button based on all validations
      this.updateSubmitButtonState(form);
    } catch (error) {
      console.error('Validation error:', error);
    }
  }
  
  /**
   * Show field validation state
   */
  showFieldValidation(field, isValid, errorMessage) {
    const formGroup = field.closest('.form-group') || field.closest('.mb-3');
    if (!formGroup) return;
    
    // Remove existing validation classes
    field.classList.remove('is-valid', 'is-invalid');
    
    // Remove existing error message
    const existingFeedback = formGroup.querySelector('.invalid-feedback');
    if (existingFeedback) {
      existingFeedback.remove();
    }
    
    if (errorMessage) {
      field.classList.add('is-invalid');
      
      const feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      feedback.textContent = errorMessage;
      formGroup.appendChild(feedback);
    } else if (field.value && isValid) {
      field.classList.add('is-valid');
    }
  }
  
  /**
   * Show field errors from server
   */
  showFieldErrors(errors) {
    Object.keys(errors).forEach(fieldName => {
      const field = document.querySelector(`[name="${fieldName}"]`);
      if (field) {
        this.showFieldValidation(field, false, errors[fieldName]);
      }
    });
  }
  
  /**
   * Update submit button state based on validations
   */
  updateSubmitButtonState(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (!submitBtn) return;
    
    const allValid = Object.values(this.fieldValidation).every(v => v);
    submitBtn.disabled = !allValid;
  }
  
  /**
   * Show alert message
   */
  showAlert(type, message) {
    const container = document.getElementById('alertContainer');
    if (!container) return;

    const icons = {
      success: 'bi-check-circle-fill',
      danger: 'bi-exclamation-triangle-fill',
      warning: 'bi-exclamation-triangle-fill',
      info: 'bi-info-circle-fill',
    };
    const icon = icons[type] || 'bi-info-circle-fill';

    const alert = document.createElement('div');
    alert.className = `alert alert-${type} fade show d-flex align-items-center`;
    alert.innerHTML = `
      <i class="bi ${icon} me-2"></i>
      <div class="flex-grow-1">${message}</div>
      <button type="button" class="btn p-0 ms-2 border-0 bg-transparent" onclick="this.closest('.alert').remove()" aria-label="Close">
        <i class="bi bi-x-lg"></i>
      </button>
    `;

    container.innerHTML = '';
    container.appendChild(alert);

    // Scroll alert into view
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Auto-dismiss after 5 seconds (only for success and info)
    if (type === 'success' || type === 'info') {
      setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
      }, 5000);
    }
  }
  
  /**
   * Logout and reset wizard
   */
  async logout() {
    try {
      const formData = new FormData();
      formData.append('action', 'logout');

      await fetch('', {
        method: 'POST',
        body: formData
      });

      // Redirect to base URL (no query params) so the welcome step loads
      window.location.href = window.location.pathname;
    } catch (error) {
      console.error('Logout failed:', error);
    }
  }
  
  /**
   * Initialize password show/hide toggles for all password fields
   */
  initPasswordToggles() {
    document.querySelectorAll('input[type="password"]').forEach(input => {
      // Skip if already wrapped
      if (input.parentElement.classList.contains('input-group')) return;

      const wrapper = document.createElement('div');
      wrapper.className = 'input-group';

      input.parentElement.insertBefore(wrapper, input);
      wrapper.appendChild(input);

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-outline-secondary password-toggle-btn';
      btn.tabIndex = -1;
      btn.innerHTML = '<i class="bi bi-eye"></i>';
      btn.addEventListener('click', () => {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.innerHTML = isPassword
          ? '<i class="bi bi-eye-slash"></i>'
          : '<i class="bi bi-eye"></i>';
      });
      wrapper.appendChild(btn);
    });
  }

  /**
   * Test database connection
   */
  async testDbConnection(form) {
    const formData = new FormData(form);
    formData.append('action', 'test-db-connection');
    
    const btn = form.querySelector('#testDbBtn');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing...';
    }
    
    try {
      const response = await fetch('', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.success) {
        this.showAlert('success', `Connection successful! Database detected: ${data.detectedVersion || 'new database'}`);
        
        // Enable save button and hide hint
        const saveBtn = form.querySelector('#saveDbBtn');
        if (saveBtn) {
          saveBtn.disabled = false;
        }
        const hint = document.getElementById('dbBtnHint');
        if (hint) {
          hint.style.display = 'none';
        }
      } else {
        this.showAlert('danger', data.message || 'Connection failed');
      }
    } catch (error) {
      this.showAlert('danger', 'Test failed: ' + error.message);
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge me-2"></i>Test Connection';
      }
    }
  }
}

// Handle browser back/forward buttons
window.addEventListener('popstate', (e) => {
  if (e.state && e.state.step && window.wizard) {
    window.wizard.navigateToStep(e.state.step);
  }
});
