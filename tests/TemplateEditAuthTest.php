<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/default_config.php";
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
 *    ALLOW_EXTERNAL_HEADER=Y, with the path controllable by a non-admin (via
 *    a per-user template, or the __system__ template whose write-guard was a
 *    no-op assert()), reachable unauthenticated via login.php. Now only the
 *    admin-authored __system__ template may name an external file, decided by
 *    template_external_file(); per-user templates are never file-included.
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

  // ---- template_external_file: admin-only external include ----

  private string $tmp = '';

  protected function setUp(): void
  {
    $this->tmp = sys_get_temp_dir() . '/wc_tpl_' . uniqid();
    mkdir($this->tmp);
    file_put_contents($this->tmp . '/header.php', '<b>hi</b>');
  }

  protected function tearDown(): void
  {
    @unlink($this->tmp . '/header.php');
    @rmdir($this->tmp);
  }

  public function test_external_file_allows_system_template_when_enabled(): void
  {
    // Admin-authored __system__ template, ALLOW_EXTERNAL_HEADER on, real file.
    $this->assertSame(
      realpath($this->tmp . '/header.php'),
      template_external_file($this->tmp . '/header.php', true, 'Y')
    );
  }

  public function test_external_file_never_resolves_per_user_template(): void
  {
    // The finding #22 boundary: a per-user (non-system) template path must
    // never resolve to an includable file, even for a real file with
    // ALLOW_EXTERNAL_HEADER on. Per-user template text stays literal content.
    $this->assertFalse(
      template_external_file($this->tmp . '/header.php', false, 'Y'),
      'per-user templates must never be included as a file'
    );
  }

  public function test_external_file_requires_allow_external_header(): void
  {
    $this->assertFalse(template_external_file($this->tmp . '/header.php', true, 'N'));
    $this->assertFalse(template_external_file($this->tmp . '/header.php', true, ''));
  }

  public function test_external_file_rejects_empty_null_and_missing(): void
  {
    $this->assertFalse(template_external_file('', true, 'Y'));
    $this->assertFalse(template_external_file("x\0.php", true, 'Y'));
    $this->assertFalse(template_external_file($this->tmp . '/nope.php', true, 'Y'));
  }

  public function test_allow_external_header_php_is_off_by_default_and_system_only(): void
  {
    $defaults = webcal_config_defaults();
    $this->assertSame('N', $defaults['ALLOW_EXTERNAL_HEADER_PHP'] ?? null,
      'PHP execution in external templates must default to off');
    $this->assertTrue(is_system_only_setting('ALLOW_EXTERNAL_HEADER_PHP'),
      'ALLOW_EXTERNAL_HEADER_PHP must not be overridable by a user preference');
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

  public function test_load_template_gates_external_include_on_system_template(): void
  {
    $body = SourceText::phpFunction(
      file_get_contents(__DIR__ . '/../includes/functions.php'),
      'load_template'
    );
    $this->assertNotNull($body);
    // The external-file decision goes through the helper, which enforces
    // __system__-only + ALLOW_EXTERNAL_HEADER. load_template must pass the
    // system-template flag so a per-user template can never be included.
    $this->assertStringContainsString('template_external_file', $body);
    $this->assertMatchesRegularExpression(
      '/template_external_file\s*\(\s*\$file\s*,\s*\$fromSystem\s*,/',
      $body,
      'load_template must gate external inclusion on the __system__ flag'
    );
  }
}
