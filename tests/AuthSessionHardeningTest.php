<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/classes/captcha/captcha.php";

/**
 * Regression tests for the auth/session/crypto hardening family.
 *
 * Covers security-audit run-1:
 *   includes/user.php:user_update_user_password:no-remember-token-revocation (medium)
 *   includes/functions.php:validate_mcp_token:plaintext-fallback-accepts-stored-hash (low)
 *   captcha-check-forgeable-unlock-cookie-and-stateless-replayable-hash (low)
 *   includes/classes/WebCalMailer.php:...SMTP_STARTTLS-not-global (low)
 *   includes/formvars.php:preventHacking:get-csrf-allowlist (low)
 */
final class AuthSessionHardeningTest extends TestCase
{
  // ---- captcha: behavioral (check() is self-contained) ----

  protected function setUp(): void
  {
    $_SESSION = [];
    $_REQUEST = [];
  }

  public function test_captcha_requires_a_matching_session_answer(): void
  {
    $_SESSION['captcha_pw'] = 'Ab3Xy';
    $_REQUEST['captcha_input'] = 'wrong';
    self::assertFalse(captcha::check(), 'a wrong answer must fail');

    $_SESSION['captcha_pw'] = 'Ab3Xy';
    $_REQUEST['captcha_input'] = 'Ab3Xy';
    self::assertTrue(captcha::check(), 'the correct answer must pass');
  }

  public function test_captcha_answer_is_single_use(): void
  {
    $_SESSION['captcha_pw'] = 'Ab3Xy';
    $_REQUEST['captcha_input'] = 'Ab3Xy';
    self::assertTrue(captcha::check());
    // The solved flag persists for the session, but the answer is consumed:
    // clearing the flag and replaying the same answer must now fail.
    unset($_SESSION['captcha_solved']);
    self::assertFalse(captcha::check(), 'a captcha answer must not be replayable');
  }

  public function test_captcha_ignores_forged_cookie(): void
  {
    $_COOKIE['captcha_solved'] = (string) (int) (time() / 1000000);
    self::assertFalse(captcha::check(), 'a forged unlock cookie must not pass');
  }

  // ---- source-structure assertions for the rest ----

  public function test_mailer_applies_starttls_setting(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/classes/WebCalMailer.php');
    self::assertMatchesRegularExpression(
      '/global[^;]*\$SMTP_STARTTLS/s',
      $src,
      'SMTP_STARTTLS must be global in the constructor so it reaches PHPMailer.'
    );
  }

  public function test_mcp_token_has_no_plaintext_fallback(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/functions.php');
    self::assertSame(
      0,
      substr_count($src, '[$actualToken]'),
      'validate_mcp_token must not look the presented token up verbatim.'
    );
  }

  public function test_password_change_revokes_remember_tokens(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/user.php');
    self::assertStringContainsString("REMEMBER_TOKEN_%", $src,
      'user_update_user_password must revoke remember-me tokens.');
  }

  public function test_captcha_cookie_shortcircuit_removed(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/classes/captcha/captcha.php');
    self::assertStringNotContainsString('$_COOKIE[CAPTCHA_COOKIE]', $src,
      'the forgeable captcha unlock cookie must no longer gate access.');
    self::assertStringContainsString('hash_equals', $src);
  }

  public function test_layers_toggle_requires_post(): void
  {
    $src = file_get_contents(__DIR__ . '/../layers_ajax.php');
    self::assertMatchesRegularExpression(
      "/REQUEST_METHOD'\]\s*!==\s*'POST'/",
      $src,
      'the layer enable/disable toggle must require POST (CSRF).'
    );
  }

  public function test_pref_reset_verifies_csrf(): void
  {
    $src = file_get_contents(__DIR__ . '/../pref.php');
    self::assertMatchesRegularExpression(
      "/action == \"reset\"[\s\S]{0,400}csrf_form_key/",
      $src,
      'the GET pref reset must verify the CSRF token.'
    );
  }
}
