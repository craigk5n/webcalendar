<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wizard/WizardState.php';
require_once __DIR__ . '/SourceText.php';

/**
 * Regression tests for the wizard/index.php authorization hardening.
 *
 * handleApiRequest() dispatched any POSTed 'action' with no authentication
 * check, so an anonymous caller on a live install could create an admin
 * (create-admin-user), rewrite settings.php (save-settings-file), or
 * re-authenticate via save-install-password. The GET guard at index.php:58
 * never applied to the POST API. The decisions are extracted onto
 * WizardState so they can be exercised without loading the side-effecting
 * script.
 *
 * Covers security-audit run-1:
 *   wizard/index.php:handleApiRequest:missing-isValidUser-authorization (critical)
 *   wizard/index.php:generateSettingsFile:php-comment-breakout (high)
 *   wizard/index.php:handleApiRequest:save-install-password-overwrites-existing (high)
 *   wizard-install-session-not-rotated-on-auth (low)
 *
 * @covers WizardState::apiActionAllowed
 * @covers WizardState::canSetInstallPassword
 * @covers WizardState::isSafeSettingValue
 */
final class WizardApiAuthTest extends TestCase
{
  private const MUTATING = [
    'save-php-settings-ack', 'save-app-settings', 'save-db-settings',
    'test-db-connection', 'create-database', 'create-admin-user',
    'save-settings-file', 'execute-upgrade', 'get-upgrade-sql',
    'continue-db-readonly',
  ];

  private const BOOTSTRAP = [
    'validate-field', 'login', 'save-install-password',
    'welcome-continue', 'welcome-quick-upgrade', 'logout',
  ];

  private function index(): string
  {
    return file_get_contents(__DIR__ . '/../wizard/index.php');
  }

  public function test_unauthenticated_cannot_run_mutating_actions(): void
  {
    $state = new WizardState();
    $state->isValidUser = false;
    foreach (self::MUTATING as $action) {
      $this->assertFalse(
        $state->apiActionAllowed($action),
        "mutating action must be blocked before auth: $action"
      );
    }
  }

  public function test_unauthenticated_may_run_bootstrap_actions(): void
  {
    $state = new WizardState();
    $state->isValidUser = false;
    foreach (self::BOOTSTRAP as $action) {
      $this->assertTrue(
        $state->apiActionAllowed($action),
        "bootstrap action must be allowed before auth: $action"
      );
    }
  }

  public function test_authenticated_user_may_run_any_action(): void
  {
    $state = new WizardState();
    $state->isValidUser = true;
    foreach (self::MUTATING as $action) {
      $this->assertTrue($state->apiActionAllowed($action));
    }
  }

  public function test_can_set_install_password_only_on_fresh_instance(): void
  {
    $this->assertTrue((new WizardState())->canSetInstallPassword());

    $hasPassword = new WizardState();
    $hasPassword->setInstallPassword('secret123');
    $this->assertFalse(
      $hasPassword->canSetInstallPassword(),
      'must refuse a new install password once one is configured'
    );

    $installed = new WizardState();
    $installed->isInitialized = true;
    $this->assertFalse(
      $installed->canSetInstallPassword(),
      'must refuse on an already-initialized instance'
    );
  }

  public function test_rejects_php_comment_and_tag_breakout_values(): void
  {
    foreach (['*/ evil', 'x */ y', '<?php echo 1;', 'a ?> b', "a\nb", "a\r\nb"]
      as $value) {
      $this->assertFalse(
        WizardState::isSafeSettingValue($value),
        'settings.php breakout must be rejected: ' . addslashes($value)
      );
    }
  }

  public function test_accepts_ordinary_setting_values(): void
  {
    foreach (['localhost', 'mydb', 'webcalendar', 'P@ssw0rd!', ''] as $value) {
      $this->assertTrue(
        WizardState::isSafeSettingValue($value),
        "ordinary value must be accepted: $value"
      );
    }
  }

  public function test_authentication_rotates_session_id(): void
  {
    $body = SourceText::phpFunction($this->index(), 'handleApiRequest');
    $this->assertNotNull($body, 'handleApiRequest() must exist');
    $this->assertStringContainsString(
      'session_regenerate_id',
      $body,
      'authentication transitions must rotate the session id (fixation)'
    );
  }

  public function test_session_strict_mode_enabled(): void
  {
    $this->assertStringContainsString(
      'session.use_strict_mode',
      SourceText::php($this->index())
    );
  }

  public function test_generate_settings_file_validates_values(): void
  {
    $body = SourceText::phpFunction($this->index(), 'generateSettingsFile');
    $this->assertNotNull($body, 'generateSettingsFile() must exist');
    $this->assertStringContainsString(
      'isSafeSettingValue',
      $body,
      'generateSettingsFile() must validate each value before writing'
    );
  }
}
