<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";

/**
 * Regression tests for configuration-trust fixes.
 *
 *   - load_global_settings() built SERVER_URL from the request Host header and
 *     URI and PERSISTED it to webcal_config, so one unauthenticated request
 *     could poison the URL used in everyone's emails, feeds and redirects. It
 *     now uses a per-request fallback from a validated host and never persists.
 *   - A user could store pref_<NAME> rows (e.g. pref_ALLOW_VIEW_OTHER,
 *     pref_UAC_ENABLED) that the preference loaders assigned to their global,
 *     overriding admin-only system settings for the session.
 *     is_system_only_setting() now blocks these at read and write time.
 *
 * Covers security-audit run-1:
 *   webcalendar:...load_global_settings:persisted-request-derived-SERVER_URL (medium)
 *   pref.php:save_pref:user-pref-overrides-system-setting (medium)
 *
 * @covers ::is_system_only_setting
 */
final class ConfigTrustTest extends TestCase
{
  /** @dataProvider systemOnly */
  public function test_system_settings_are_not_user_overridable(string $name): void
  {
    self::assertTrue(is_system_only_setting($name), "$name must be system-only");
  }

  public static function systemOnly(): array
  {
    return [['UAC_ENABLED'], ['ALLOW_VIEW_OTHER'], ['PUBLIC_ACCESS'],
      ['REQUIRE_APPROVALS'], ['ALLOW_EXTERNAL_HEADER'], ['SERVER_URL'],
      ['USER_SEES_ONLY_HIS_GROUPS']];
  }

  /** @dataProvider userPrefs */
  public function test_real_user_preferences_are_allowed(string $name): void
  {
    self::assertFalse(is_system_only_setting($name), "$name is a user pref");
  }

  public static function userPrefs(): array
  {
    return [['DATE_FORMAT'], ['TIMEZONE'], ['BGCOLOR'], ['LANGUAGE'],
      ['DISPLAY_UNAPPROVED']];
  }

  public function test_preference_loader_skips_system_settings(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/functions.php');
    self::assertMatchesRegularExpression(
      '/is_system_only_setting\s*\(\s*\$setting\s*\)/',
      $src,
      'load_user_preferences must skip system-only settings.'
    );
  }

  public function test_server_url_is_not_persisted_from_request(): void
  {
    $src = file_get_contents(__DIR__ . '/../includes/functions.php');
    self::assertDoesNotMatchRegularExpression(
      "/INSERT INTO webcal_config[\s\S]{0,80}'SERVER_URL'/",
      $src,
      'SERVER_URL must not be derived from the request and persisted.'
    );
    self::assertStringContainsString(
      "preg_match ( '/^[A-Za-z0-9.-]+(:[0-9]{1,5})?\$/', \$HTTP_HOST )",
      $src,
      'the per-request host fallback must be validated.'
    );
  }
}
