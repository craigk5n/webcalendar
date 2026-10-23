<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/SourceText.php";

/**
 * Regression tests for the custom-template (header/script/trailer) subsystem.
 *
 *  - edit_template.php gated __system__ writes with assert(), a no-op under
 *    production zend.assertions, and applied no owner check at all to
 *    per-user templates, so any logged-in user could rewrite the site-wide
 *    or any victim's template (stored XSS). Authorization is now decided by
 *    template_edit_allowed().
 *  - load_template() include()d a DB-stored path as PHP when
 *    ALLOW_EXTERNAL_HEADER=Y (reachable unauthenticated via login.php),
 *    giving local file read and code execution. It now resolves the path
 *    through template_external_file() and reads it, never includes it.
 *
 * Covers security-audit run-1:
 *   edit_template.php:system-template-authz-predicate-always-true (high)
 *   functions.php:load_template:external-header-include-of-template-text (high)
 *   edit_template.php:user-template-missing-owner-check (medium)
 *
 * @covers ::template_edit_allowed
 * @covers ::template_external_file
 */
final class TemplateEditAuthTest extends TestCase
{
  // ---- template_edit_allowed: __system__ template ----

  public function test_system_template_requires_admin_when_uac_off(): void
  {
    // UAC off: only a real admin may edit the system template. The old
    // assert() passed for everyone because access_is_enabled() was false.
    $this->assertTrue(template_edit_allowed('__system__', 'bob', true, false, false, false));
    $this->assertFalse(template_edit_allowed('__system__', 'bob', false, false, false, false));
  }

  public function test_system_template_requires_system_settings_when_uac_on(): void
  {
    $this->assertTrue(template_edit_allowed('__system__', 'bob', false, true, true, false));
    $this->assertFalse(template_edit_allowed('__system__', 'bob', true, true, false, false));
  }

  // ---- template_edit_allowed: per-user template ----

  public function test_user_may_edit_own_template(): void
  {
    $this->assertTrue(template_edit_allowed('bob', 'bob', false, false, false, false));
  }

  public function test_user_cannot_edit_another_users_template(): void
  {
    $this->assertFalse(
      template_edit_allowed('alice', 'bob', false, false, false, false),
      'bob must not edit alice\'s template'
    );
  }

  public function test_admin_or_nonuser_admin_may_edit_user_template(): void
  {
    $this->assertTrue(template_edit_allowed('alice', 'bob', true, false, false, false));
    $this->assertTrue(template_edit_allowed('alice', 'bob', false, false, false, true));
  }

  // ---- template_external_file: confinement ----

  private string $tmp = '';

  protected function setUp(): void
  {
    $this->tmp = sys_get_temp_dir() . '/wc_tpl_' . uniqid();
    mkdir($this->tmp);
    file_put_contents($this->tmp . '/header.html', '<b>hi</b>');
  }

  protected function tearDown(): void
  {
    @unlink($this->tmp . '/header.html');
    @rmdir($this->tmp);
  }

  public function test_external_file_accepts_contained_file(): void
  {
    $got = template_external_file($this->tmp . '/header.html', $this->tmp);
    $this->assertSame(realpath($this->tmp . '/header.html'), $got);
  }

  public function test_external_file_rejects_outside_base(): void
  {
    $this->assertFalse(template_external_file('/etc/passwd', $this->tmp));
    $this->assertFalse(template_external_file($this->tmp . '/../header.html', $this->tmp));
  }

  public function test_external_file_rejects_empty_null_and_missing(): void
  {
    $this->assertFalse(template_external_file('', $this->tmp));
    $this->assertFalse(template_external_file("x\0.html", $this->tmp));
    $this->assertFalse(template_external_file($this->tmp . '/nope.html', $this->tmp));
  }

  // ---- source structure: the dangerous idioms are gone ----

  public function test_edit_template_uses_enforced_authorization(): void
  {
    $src = SourceText::php(file_get_contents(__DIR__ . '/../edit_template.php'));
    $this->assertStringContainsString('template_edit_allowed', $src);
    $this->assertStringNotContainsString(
      'assert (',
      $src,
      'authorization must not depend on assert() (a no-op in production)'
    );
  }

  public function test_load_template_does_not_include_db_path(): void
  {
    $body = SourceText::phpFunction(
      file_get_contents(__DIR__ . '/../includes/functions.php'),
      'load_template'
    );
    $this->assertNotNull($body);
    $this->assertStringContainsString('template_external_file', $body);
    $this->assertStringNotContainsString(
      'include "$file"',
      $body,
      'load_template() must never include a DB-stored path as PHP'
    );
  }
}
