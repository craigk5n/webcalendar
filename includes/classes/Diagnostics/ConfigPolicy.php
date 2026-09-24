<?php

declare(strict_types=1);

namespace WebCalendar\Diagnostics;

/**
 * Decides what a diagnostic report may say about a configuration value.
 *
 * The report exists to be pasted into a public GitHub issue, so the rule is
 * an allowlist rather than a denylist: a key nobody has classified is left
 * out entirely. A denylist would leak the next setting someone adds that
 * happens to hold a credential, and `webcal_config` already carries two
 * (SMTP_PASSWORD, REMINDER_WEB_TRIGGER_TOKEN).
 *
 * Three outcomes:
 *   SHOWN    — value is reported verbatim. Feature flags and formats; knowing
 *              the value is the point.
 *   PRESENCE — only "set" or "not set". Credentials, hostnames, addresses and
 *              free text an admin may have put anything into.
 *   omitted  — everything else, including every key added after this list.
 */
final class ConfigPolicy
{
  public const SHOWN = 'shown';
  public const PRESENCE = 'presence';
  public const OMITTED = 'omitted';

  /**
   * Settings worth seeing in a bug report. Deliberately not the full 166:
   * colours, fonts and per-view display preferences have never explained a
   * defect, and every extra row makes the paste harder to read.
   *
   * @var list<string>
   */
  private const SHOW = [
    // Identity and locale, first because almost every report needs them.
    'WEBCAL_PROGRAM_VERSION', 'LANGUAGE', 'TIMEZONE', 'SERVER_TIMEZONE',
    'GENERAL_USE_GMT', 'DATE_FORMAT', 'TIME_FORMAT', 'WEEK_START',
    // Access control and authentication.
    'UAC_ENABLED', 'ADMIN_OVERRIDE_UAC', 'PUBLIC_ACCESS',
    'ALLOW_SELF_REGISTRATION', 'SELF_REGISTRATION_FULL',
    'ALLOW_EXTERNAL_USERS', 'REQUIRE_APPROVALS', 'CSRF_PROTECTION', 'CSP',
    'ENABLE_CAPTCHA', 'DEMO_MODE',
    // Mail. The transport matters; the credentials do not appear here.
    'SEND_EMAIL', 'EMAIL_MAILER', 'EMAIL_REMINDER', 'SMTP_PORT', 'SMTP_AUTH',
    'SMTP_STARTTLS',
    // Optional subsystems, the usual suspects in "feature X does nothing".
    'CATEGORIES_ENABLED', 'GROUPS_ENABLED', 'NONUSER_ENABLED',
    'REMOTES_ENABLED', 'REPORTS_ENABLED', 'RSS_ENABLED', 'PUBLISH_ENABLED',
    'FREEBUSY_ENABLED', 'PLUGINS_ENABLED', 'ALLOW_ATTACH', 'ALLOW_COMMENTS',
    'ALLOW_HTML_DESCRIPTION',
    // MCP server.
    'MCP_SERVER_ENABLED', 'MCP_WRITE_ACCESS', 'MCP_RATE_LIMIT',
    'MCP_CORS_ORIGINS',
    // Security audit.
    'SECURITY_AUDIT_NOISE_FILTER',
  ];

  /**
   * Reported as set or not set. SMTP_PASSWORD and REMINDER_WEB_TRIGGER_TOKEN
   * are credentials. The rest are hostnames, addresses, paths or free text an
   * admin can put arbitrary content into, none of which belongs in a public
   * issue even though whether they are configured usually matters.
   *
   * @var list<string>
   */
  private const PRESENCE_ONLY = [
    'SMTP_PASSWORD', 'SMTP_USERNAME', 'SMTP_HOST',
    'REMINDER_WEB_TRIGGER_TOKEN',
    'EMAIL_FALLBACK_FROM',
    'SELF_REGISTRATION_BLACKLIST', 'SECURITY_AUDIT_EXTRA_EXCLUDES',
    'CUSTOM_HEADER', 'CUSTOM_SCRIPT', 'CUSTOM_TRAILER',
    'OVERRIDE_PUBLIC_TEXT', 'HOME_LINK', 'APPLICATION_NAME',
  ];

  /**
   * Last line of defence. A key whose name reads like a credential is never
   * shown verbatim even if someone adds it to SHOW by mistake.
   */
  private const SECRET_NAME = '/(PASS|PASSWORD|TOKEN|SECRET|APIKEY|API_KEY|PRIVKEY|SALT|CRYPT)/i';

  public static function classify(string $key): string
  {
    if (in_array($key, self::PRESENCE_ONLY, true)) {
      return self::PRESENCE;
    }
    if (!in_array($key, self::SHOW, true)) {
      return self::OMITTED;
    }

    return preg_match(self::SECRET_NAME, $key) === 1
      ? self::PRESENCE
      : self::SHOWN;
  }

  /**
   * Applies the policy to a whole `webcal_config` map.
   *
   * @param array<string, string> $settings
   * @return array{shown: array<string, string>, omitted: int}
   */
  public static function apply(array $settings): array
  {
    $shown = [];
    $omitted = 0;

    foreach ($settings as $key => $value) {
      switch (self::classify((string) $key)) {
        case self::SHOWN:
          $shown[$key] = (string) $value;
          break;
        case self::PRESENCE:
          $shown[$key] = ((string) $value) === '' ? '(not set)' : '(set)';
          break;
        default:
          $omitted++;
      }
    }

    ksort($shown);
    return ['shown' => $shown, 'omitted' => $omitted];
  }
}
