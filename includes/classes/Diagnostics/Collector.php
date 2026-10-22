<?php

declare(strict_types=1);

namespace WebCalendar\Diagnostics;

/**
 * Gathers the facts a maintainer asks for when triaging a bug report, so a
 * user can paste one block instead of hand-typing their environment into the
 * issue template and getting half of it wrong.
 *
 * This class only gathers. Every decision about what may be disclosed lives
 * in ConfigPolicy, which is unit tested; the environment lookups here are
 * exercised against a real installation instead.
 *
 * It runs from the CLI and from security_audit.php, and must survive a
 * broken install -- a user whose calendar will not load is exactly who needs
 * it -- so every lookup degrades to a marker rather than throwing.
 */
final class Collector
{
  private const UNKNOWN = '(unknown)';

  /**
   * @param array<string, string> $settings webcal_config, already loaded
   * @param array<string, mixed> $env       overrides, for tests
   */
  public static function collect(array $settings = [], array $env = []): Report
  {
    $sections = [
      'webcalendar' => self::webcalendar($settings, $env),
      'php' => self::php(),
      'database' => self::database($env),
      'filesystem' => self::filesystem($env),
      'configuration' => [],
    ];

    $applied = ConfigPolicy::apply($settings);
    $sections['configuration'] = $applied['shown'];
    $sections['configuration']['(settings omitted by policy)'] =
      (string) $applied['omitted'];

    return new Report($sections, gmdate('Y-m-d\TH:i:s\Z'));
  }

  /**
   * @param array<string, string> $settings
   * @param array<string, mixed> $env
   * @return array<string, string|int|bool|null>
   */
  private static function webcalendar(array $settings, array $env): array
  {
    return [
      'version' => $settings['WEBCAL_PROGRAM_VERSION'] ?? self::UNKNOWN,
      'install_root' => (string) ($env['install_root'] ?? self::installRoot()),
      'settings_source' => self::settingsSource(),
      'single_user' => self::globalAsText('single_user'),
      'readonly' => self::globalAsText('readonly'),
      'run_mode' => self::globalAsText('run_mode'),
      'user_inc' => self::globalAsText('user_inc'),
      'use_http_auth' => self::globalAsText('use_http_auth'),
    ];
  }

  /**
   * @return array<string, string|int|bool|null>
   */
  private static function php(): array
  {
    $row = [
      'version' => PHP_VERSION,
      'sapi' => PHP_SAPI,
      'memory_limit' => (string) ini_get('memory_limit'),
      'max_execution_time' => (string) ini_get('max_execution_time'),
      'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
      'post_max_size' => (string) ini_get('post_max_size'),
      'date.timezone' => (string) ini_get('date.timezone'),
      'display_errors' => (string) ini_get('display_errors'),
    ];

    // Extensions WebCalendar actually reaches for. Absence explains a whole
    // class of reports ("attachments do nothing", "install cannot connect").
    foreach (['mysqli', 'pgsql', 'sqlite3', 'pdo', 'gd', 'mbstring', 'openssl',
              'sodium', 'curl', 'iconv', 'zip', 'ldap', 'imap'] as $ext) {
      $row['ext.' . $ext] = extension_loaded($ext) ? 'yes' : 'no';
    }

    return $row;
  }

  /**
   * @param array<string, mixed> $env
   * @return array<string, string|int|bool|null>
   */
  private static function database(array $env): array
  {
    $row = [
      'type' => self::globalAsText('db_type'),
      'persistent' => self::globalAsText('db_persistent'),
      // Host, name and login identify someone's infrastructure and have no
      // diagnostic value beyond being configured at all.
      'host' => self::isSetText('db_host'),
      'database' => self::isSetText('db_database'),
      'login' => self::isSetText('db_login'),
      'password' => self::isSetText('db_password'),
      'server_version' => (string) ($env['db_server_version'] ?? self::UNKNOWN),
      'schema_version' => (string) ($env['db_schema_version'] ?? self::UNKNOWN),
    ];

    return $row;
  }

  /**
   * Writability of the directories WebCalendar needs. A read-only cache or
   * attachment directory produces errors that look like anything but a
   * permissions problem.
   *
   * @param array<string, mixed> $env
   * @return array<string, string|int|bool|null>
   */
  private static function filesystem(array $env): array
  {
    $root = (string) ($env['install_root'] ?? self::installRoot());
    $row = [];

    foreach (['includes', 'includes/settings.php', 'pub', 'database'] as $rel) {
      $path = $root . '/' . $rel;
      if (!file_exists($path)) {
        $row[$rel] = 'absent';
        continue;
      }
      $row[$rel] = (is_readable($path) ? 'r' : '-')
        . (is_writable($path) ? 'w' : '-');
    }

    $row['wizard_present'] = is_dir($root . '/wizard') ? 'yes' : 'no';
    $row['manifest_present'] = is_file($root . '/MANIFEST.sha256') ? 'yes' : 'no';
    $row['open_basedir'] = ((string) ini_get('open_basedir')) === ''
      ? 'not set' : 'set';

    return $row;
  }

  private static function installRoot(): string
  {
    return dirname(__DIR__, 3);
  }

  /**
   * settings.php or environment variables. Which one is in play changes the
   * advice for nearly every configuration question.
   */
  private static function settingsSource(): string
  {
    $useEnv = getenv('WEBCALENDAR_USE_ENV');
    if (is_string($useEnv) && strtolower($useEnv) === 'true') {
      return 'environment variables';
    }

    return is_file(self::installRoot() . '/includes/settings.php')
      ? 'includes/settings.php'
      : 'none found';
  }

  private static function globalAsText(string $name): string
  {
    if (!array_key_exists($name, $GLOBALS)) {
      return self::UNKNOWN;
    }
    $value = $GLOBALS[$name];

    return is_scalar($value) ? (string) $value : self::UNKNOWN;
  }

  private static function isSetText(string $name): string
  {
    if (!array_key_exists($name, $GLOBALS)) {
      return self::UNKNOWN;
    }

    return ((string) $GLOBALS[$name]) === '' ? '(not set)' : '(set)';
  }
}
