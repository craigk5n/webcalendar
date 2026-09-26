<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

require_once __DIR__ . '/../includes/classes/Diagnostics/ConfigPolicy.php';

use WebCalendar\Diagnostics\ConfigPolicy;

/**
 * `bin/webcal.php config` reads and writes webcal_config, the table behind
 * Admin > Settings, for the case that page cannot reach: a setting that stops
 * the administrator getting to it.
 *
 * The behaviour is verified against a throwaway SQLite installation. These
 * pin the parts that would fail quietly.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class CliConfigTest extends TestCase
{
  private function cli(): string
  {
    $source = file_get_contents(__DIR__ . '/../bin/webcal.php');
    self::assertIsString($source);

    return $source;
  }

  /**
   * cal_setting is the primary key, so an INSERT on an existing row fails.
   * admin.php and load_global_settings() both delete first, the latter after
   * issue #734 made empty rows reachable.
   */
  public function testTheWriteDeletesBeforeInserting(): void
  {
    $src = $this->cli();

    $delete = strpos($src, 'DELETE FROM webcal_config');
    $insert = strpos($src, 'INSERT INTO webcal_config');

    self::assertNotFalse($delete, 'config set must clear the old row');
    self::assertNotFalse($insert);
    self::assertLessThan($insert, $delete,
      'the DELETE has to come first: cal_setting is the primary key');
  }

  /**
   * The row is written back even when the value is empty. Leaving no row is a
   * different state, and call sites disagreed about what it meant (#734).
   */
  public function testClearingASettingStoresAnEmptyValueRatherThanNoRow(): void
  {
    self::assertStringNotContainsString("config unset", $this->cli(),
      'there is no unset: clearing stores an empty value, as admin.php does');
  }

  /**
   * WEBCAL_PROGRAM_VERSION is the schema version the installation wizard
   * reads to decide which upgrades to apply, not a preference.
   */
  public function testTheSchemaVersionIsProtected(): void
  {
    $src = $this->cli();

    self::assertStringContainsString("\$name === 'WEBCAL_PROGRAM_VERSION'", $src,
      'config set must refuse the schema version unless forced');
    self::assertMatchesRegularExpression(
      "/'WEBCAL_PROGRAM_VERSION'\s*&&\s*!\\\$forced/",
      $src,
      'the refusal has to be the one --force lifts'
    );
  }

  /**
   * do_config(true) skips dbi_init_cache(), so a command line process did not
   * know where the query cache lived and dbi_execute()'s invalidation -- it
   * clears the cache on anything that is not a SELECT -- had nothing to clear.
   * On an installation with db_cachedir set, a setting written here stayed
   * invisible to the web server. Measured: the value changed in the database
   * and a page still read the old one.
   */
  public function testTheQueryCacheIsInitialisedBeforeAnythingIsWritten(): void
  {
    $src = $this->cli();

    self::assertStringContainsString('function wc_init_query_cache', $src);

    $bootstrap = strpos($src, 'function wc_bootstrap');
    self::assertNotFalse($bootstrap);
    $call = strpos($src, 'wc_init_query_cache();', $bootstrap);
    self::assertNotFalse($call,
      'wc_bootstrap() must initialise the cache, so that every command that '
      . 'writes invalidates it -- config set, user reset-password and import');

    // Both spellings, in the order do_config() prefers them.
    $dbCacheDir = strpos($src, "\$settings['db_cachedir']");
    $cacheDir = strpos($src, "\$settings['cachedir']");
    self::assertNotFalse($dbCacheDir);
    self::assertNotFalse($cacheDir);
    self::assertLessThan($cacheDir, $dbCacheDir,
      'db_cachedir takes precedence, as it does in includes/config.php');
  }

  /**
   * A mistyped name would otherwise store a row nothing reads, and the
   * administrator would be left wondering why the change did nothing.
   */
  public function testCloseNamesAreSuggested(): void
  {
    $this->loadNearMatches();

    $candidates = ['SEND_EMAIL', 'SEND_REMINDERS', 'APPLICATION_NAME',
      'ALLOW_COMMENTS'];

    self::assertContains('SEND_EMAIL',
      wc_config_near_matches('SEND_EMAILS', $candidates));
    self::assertContains('APPLICATION_NAME',
      wc_config_near_matches('APPLICATION_NAM', $candidates));
  }

  public function testNothingIsSuggestedForANameUnlikeAnyOther(): void
  {
    $this->loadNearMatches();

    self::assertSame([], wc_config_near_matches('ZZZZZZZZZZZZZZZZZZ',
      ['SEND_EMAIL', 'APPLICATION_NAME']));
  }

  /**
   * config list shows every setting an installation has, so it needs to hide
   * only secret values -- not the short list classify() keeps for bug
   * reports, which leaves out colours and fonts.
   */
  public function testSecretsAreRecognisedByName(): void
  {
    self::assertTrue(ConfigPolicy::isSecret('SMTP_PASSWORD'));
    self::assertTrue(ConfigPolicy::isSecret('REMINDER_WEB_TRIGGER_TOKEN'));
    self::assertTrue(ConfigPolicy::isSecret('SOME_API_KEY'));

    self::assertFalse(ConfigPolicy::isSecret('APPLICATION_NAME'));
    self::assertFalse(ConfigPolicy::isSecret('SEND_EMAIL'));
    self::assertFalse(ConfigPolicy::isSecret('BGCOLOR'));
  }

  /**
   * isSecret() has to stay the one rule. Two copies of "what counts as a
   * secret" is how one of them ends up missing a name.
   */
  public function testTheCommandUsesTheSharedPolicyRatherThanItsOwnRule(): void
  {
    $src = $this->cli();

    self::assertStringContainsString('ConfigPolicy::isSecret', $src);
    self::assertStringNotContainsString('PASSWORD|TOKEN', $src,
      'the secret-name pattern belongs in ConfigPolicy, not here');
  }

  /**
   * Lifted out of bin/webcal.php, which is a script: including it would run
   * the dispatcher.
   */
  private function loadNearMatches(): void
  {
    if (function_exists('wc_config_near_matches')) {
      return;
    }

    $src = $this->cli();
    $fn = SourceText::phpFunction($src, 'wc_config_near_matches');
    self::assertIsString($fn,
      'bin/webcal.php must define wc_config_near_matches()');

    eval($fn);
  }
}
