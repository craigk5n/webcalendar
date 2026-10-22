<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Issue #734 -- a setting's default must mean the same thing everywhere.
 *
 * Defaults live in includes/default_config.php, but they were only written
 * into webcal_config on a *fresh* install. load_global_settings() read that
 * table and nothing else, so on an upgraded site any setting added since the
 * original install had no row and its global stayed undefined -- leaving
 * ~160 call sites to each invent a meaning for "unset". They disagreed, and
 * several of them guessed permissively.
 *
 * These tests lock down the three properties that fix relies on:
 *   1. an absent row resolves to the documented default;
 *   2. a stored value -- including a deliberately empty one -- still wins;
 *   3. values derived at request time still beat the static defaults.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ConfigDefaultsTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  private string $dbFile;

  protected function setUp(): void
  {
    $this->dbFile = tempnam(sys_get_temp_dir(), 'wc_cfgdef_');

    $GLOBALS['db_type'] = 'sqlite3';
    $GLOBALS['db_persistent'] = false;

    require_once self::ROOT . '/includes/dbi4php.php';
    require_once self::ROOT . '/includes/functions.php';
    require_once self::ROOT . '/includes/default_config.php';

    dbi_connect('', '', '', $this->dbFile, false);
    dbi_execute('CREATE TABLE webcal_config ( cal_setting VARCHAR(50)
      NOT NULL, cal_value VARCHAR(100) NULL, PRIMARY KEY ( cal_setting ) )');
  }

  protected function tearDown(): void
  {
    if (isset($GLOBALS['sqlite3_c']) && $GLOBALS['sqlite3_c'] instanceof SQLite3) {
      @$GLOBALS['sqlite3_c']->close();
    }
    if (!empty($this->dbFile) && file_exists($this->dbFile)) {
      @unlink($this->dbFile);
    }
  }

  private function storeSetting(string $name, string $value): void
  {
    dbi_execute('INSERT INTO webcal_config ( cal_setting, cal_value )
      VALUES ( ?, ? )', [$name, $value]);
  }

  /**
   * The upgraded-site case: no row at all. Before the fix the global stayed
   * undefined and each call site guessed; now it resolves to the documented
   * default, so the site behaves like a fresh install.
   */
  public function testAbsentRowResolvesToDocumentedDefault(): void
  {
    load_global_settings();

    self::assertSame('Y', $GLOBALS['ALLOW_VIEW_OTHER'] ?? null);
    self::assertSame('N', $GLOBALS['PUBLIC_ACCESS_VIEW_PART'] ?? null);
    // Added to the defaults by #734 -- previously undefined on every install.
    self::assertSame('N', $GLOBALS['MCP_SERVER_ENABLED'] ?? null);
    self::assertSame('100', $GLOBALS['MCP_RATE_LIMIT'] ?? null);
  }

  /**
   * A stored value must always beat the default, or the fallback would
   * silently undo an administrator's choice.
   */
  public function testStoredValueWinsOverDefault(): void
  {
    // Opposite of the documented default in both directions.
    $this->storeSetting('ALLOW_VIEW_OTHER', 'N');
    $this->storeSetting('PUBLIC_ACCESS_VIEW_PART', 'Y');

    load_global_settings();

    self::assertSame('N', $GLOBALS['ALLOW_VIEW_OTHER']);
    self::assertSame('Y', $GLOBALS['PUBLIC_ACCESS_VIEW_PART']);
  }

  /**
   * An admin who clears a field means "empty", which is not the same as
   * "never configured". admin.php now stores '' rather than dropping the
   * row, so the fallback must use isset() and leave '' alone.
   */
  public function testStoredEmptyValueIsNotReplacedByDefault(): void
  {
    $this->storeSetting('EMAIL_FALLBACK_FROM', '');

    load_global_settings();

    self::assertSame('', $GLOBALS['EMAIL_FALLBACK_FROM']);
  }

  /**
   * load_global_settings() derives some values from the request and the
   * active language. Those must win over the static defaults, which is why
   * the fallback runs last. FONTS is the clearest case: Japanese prepends
   * Osaka, and a naive fallback would clobber it with the plain default.
   */
  public function testRequestDerivedValuesWinOverStaticDefaults(): void
  {
    $GLOBALS['LANGUAGE'] = 'Japanese';

    load_global_settings();

    self::assertStringStartsWith('Osaka, ', $GLOBALS['FONTS']);
    self::assertNotSame(
      webcal_config_defaults()['FONTS'],
      $GLOBALS['FONTS'],
      'The static FONTS default must not overwrite the language-derived one.'
    );
  }

  /**
   * webcal_config_defaults() exists precisely so callers do not depend on
   * the scope the file was required from: `require` inside a function makes
   * $webcalConfig a local of that function. Requiring it here, inside a
   * method, and still reaching the defaults is the property under test.
   */
  public function testDefaultsAreReachableWhenRequiredInsideAFunction(): void
  {
    require_once self::ROOT . '/includes/default_config.php';

    self::assertTrue(function_exists('webcal_config_defaults'));

    $defaults = webcal_config_defaults();
    self::assertIsArray($defaults);
    self::assertArrayHasKey('WEBCAL_PROGRAM_VERSION', $defaults);
    self::assertArrayHasKey('ALLOW_VIEW_OTHER', $defaults);
  }

  /**
   * Anything an administrator can set must have a documented default, or it
   * lands back in the undefined-global case this issue is about.
   */
  public function testEveryAdminSettableSettingHasADocumentedDefault(): void
  {
    // Not settings: SERVER_URL is computed and stored by
    // load_global_settings() at request time, and 'SERVER_' is a field-name
    // prefix handed to print_timezone_select_html(), not a setting name.
    $notSettings = ['SERVER_URL', 'SERVER_'];

    $admin = (string) file_get_contents(self::ROOT . '/admin.php');
    preg_match_all('/admin_([A-Z][A-Z0-9_]{2,})/', $admin, $m);

    $exposed = array_diff(array_unique($m[1]), $notSettings);
    self::assertNotEmpty($exposed, 'Found no admin_ fields to check.');

    $defaults = webcal_config_defaults();
    $missing = array_values(array_diff($exposed, array_keys($defaults)));

    self::assertSame([], $missing, 'admin.php exposes these settings but '
      . 'includes/default_config.php does not define a default for them, so '
      . 'they are undefined until an admin saves the page: '
      . implode(', ', $missing));
  }

  /**
   * Permission gates must fail closed. `== 'N'` and `!= 'N'` both treat an
   * unset value as permission granted; `== 'Y'` / `!= 'Y'` do not. The
   * fallback above should keep these defined, but a gate that only works
   * because something else populated the global is one refactor away from
   * being wrong again.
   *
   * @dataProvider failClosedGateProvider
   */
  public function testPermissionGatesFailClosed(string $file, string $forbidden): void
  {
    $source = (string) file_get_contents(self::ROOT . '/' . $file);

    self::assertStringNotContainsString($forbidden, $source,
      $file . ' must not gate access with ' . $forbidden . ': an unset value '
      . 'passes that test. Compare against \'Y\' instead (issue #734).');
  }

  /**
   * @return array<string,array{0:string,1:string}>
   */
  public function failClosedGateProvider(): array
  {
    return [
      'availability.php honours ALLOW_VIEW_OTHER' => [
        'availability.php', "\$ALLOW_VIEW_OTHER == 'N'",
      ],
      'event popup hides participants from public' => [
        'includes/functions.php', "\$PUBLIC_ACCESS_VIEW_PART == 'N'",
      ],
      'view_entry hides participants from public' => [
        'view_entry.php', "\$PUBLIC_ACCESS_VIEW_PART == 'N'",
      ],
      'approve_entry only mails when enabled' => [
        'approve_entry.php', "\$SEND_EMAIL != 'N'",
      ],
      'del_entry only mails when enabled' => [
        'del_entry.php', "\$SEND_EMAIL != 'N'",
      ],
      'edit_entry_handler only mails when enabled' => [
        'edit_entry_handler.php', "\$SEND_EMAIL != 'N'",
      ],
      'reject_entry only mails when enabled' => [
        'reject_entry.php', "\$SEND_EMAIL != 'N'",
      ],
    ];
  }
}
