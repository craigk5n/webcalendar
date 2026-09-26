<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * `bin/webcal.php db check` answers the question do_config() answers when it
 * decides whether to send a browser to the wizard -- is the schema behind the
 * code -- with an exit status instead of a redirect, and applies nothing.
 *
 * Verified against a throwaway SQLite installation by moving the stored
 * version: matching versions exit 0, a version behind exits 1, a version
 * ahead exits 2, a missing row exits 2, and removing wizard/ still produces
 * an answer rather than a fatal.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class CliDbCheckTest extends TestCase
{
  /**
   * Just the body of wc_db_check(), so a write added elsewhere in the file
   * does not mask one added here.
   */
  private function checkFunction(): string
  {
    $src = file_get_contents(__DIR__ . '/../bin/webcal.php');
    self::assertIsString($src);

    $fn = SourceText::phpFunction($src, 'wc_db_check');
    self::assertIsString($fn, 'bin/webcal.php must define wc_db_check()');

    return $fn;
  }

  /**
   * The point of the command is that it is safe to run on a production
   * installation whose schema is behind: it reports, and the administrator
   * decides. A statement that changes anything does not belong in it.
   */
  public function testTheCheckChangesNothing(): void
  {
    $body = $this->checkFunction();

    foreach (['INSERT', 'UPDATE ', 'DELETE', 'ALTER', 'CREATE', 'DROP'] as $verb) {
      self::assertStringNotContainsStringIgnoringCase($verb, $body,
        "db check must not issue $verb: it reports and applies nothing");
    }
    self::assertStringContainsString('SELECT cal_value', $body,
      'it does have to read the stored version');
  }

  /**
   * dbi_get_cached_rows() persists SELECT results to {db_cachedir}/*.dat, and
   * a stale file pinned the old version so the administrator was sent back to
   * the wizard on every request (#639). includes/config.php reads this one
   * value uncached and says why; so does this.
   */
  public function testTheStoredVersionIsReadWithoutTheQueryCache(): void
  {
    $body = $this->checkFunction();

    self::assertStringNotContainsString('dbi_get_cached_rows', $body,
      'the schema version has to be read uncached, as config.php does');
    self::assertStringContainsString('dbi_execute(', $body);
  }

  /**
   * A deployment script reads the status, not the prose.
   */
  public function testTheThreeOutcomesAreDistinctExitCodes(): void
  {
    $body = $this->checkFunction();

    // 0 up to date, 1 pending, 2 cannot tell.
    foreach (['return 0;', 'return 1;', 'return 2;'] as $code) {
      self::assertStringContainsString($code, $body,
        "db check must be able to $code");
    }
  }

  public function testTheExitCodesAreDocumentedInTheUsage(): void
  {
    $src = file_get_contents(__DIR__ . '/../bin/webcal.php');
    self::assertIsString($src);

    $usage = substr($src, (int) strpos($src, 'function wc_usage'), 2500);

    self::assertStringContainsString('db check', $usage);
    self::assertMatchesRegularExpression('/0 up to date.*1 pending.*2/s', $usage,
      'the usage text has to say what the exit codes mean');
  }

  /**
   * Administrators are told they may remove wizard/ once installed, so a
   * command that talks about upgrades must not need it present.
   * upgrade_requires_db_changes() answers conservatively when it is gone, and
   * the command says which of the two happened -- but it may only look, never
   * require.
   */
  public function testTheWizardDirectoryIsOnlyLookedFor(): void
  {
    $body = $this->checkFunction();

    self::assertStringContainsString('is_file($upgradeSql)', $body,
      'presence of wizard/ is a question, not a dependency');
    self::assertDoesNotMatchRegularExpression(
      '/\b(?:require|include)(?:_once)?\b[^;]{0,300}wizard\//',
      $body,
      'db check must not require anything from wizard/'
    );
  }
}
