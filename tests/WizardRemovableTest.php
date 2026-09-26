<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Issue #707 — the wizard/ directory must stay removable.
 *
 * security_audit.php tells administrators they may `chmod 000 wizard/` or
 * `rm -rf wizard/` once the install is done.  Any runtime page that hard
 * requires a file from wizard/ turns that advice into a fatal error (a 500
 * on admin.php, in the reported case).
 *
 * These are source-structure regression tests: they fail the build if a
 * future change reintroduces a runtime dependency on wizard/, moves the
 * config defaults back under wizard/, or drops the new file from the
 * release manifest (which would ship a release that cannot render
 * admin.php at all).
 */
final class WizardRemovableTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  /**
   * Runtime pages -- everything a browser can reach outside wizard/ itself.
   */
  private function runtimeSources(): array
  {
    // bin/ and the class subdirectories were added after this test was
    // written. bin/webcal.php is a shipped entry point with eleven commands,
    // one of which reports on pending upgrades, so it is exactly the kind of
    // file that would be tempted to read wizard/shared/upgrade-sql.php.
    $files = array_merge(
      glob(self::ROOT . '/*.php') ?: [],
      glob(self::ROOT . '/bin/*.php') ?: [],
      glob(self::ROOT . '/includes/*.php') ?: [],
      glob(self::ROOT . '/includes/classes/*.php') ?: [],
      glob(self::ROOT . '/includes/classes/*/*.php') ?: []
    );

    // run_install.php is an explicit wrapper around the installer, so it is
    // allowed to depend on wizard/.
    return array_values(array_filter($files, static function ($f) {
      return basename($f) !== 'run_install.php';
    }));
  }

  public function testNoRuntimePageIncludesFromWizard(): void
  {
    $offenders = [];

    foreach ($this->runtimeSources() as $file) {
      $src = file_get_contents($file);
      self::assertNotFalse($src, "Could not read $file");

      // Anywhere in the statement, not just a quote immediately after the
      // keyword: every file here builds paths as WC_ROOT . '/...' or
      // __DIR__ . '/...', and the original pattern matched none of those.
      // A path held in a variable still slips through -- functions.php does
      // that deliberately, behind a file_exists() check.
      //
      // Comments are stripped first. default_config.php explains in prose
      // that wizard/WizardDatabase.php requires it, which the pattern read
      // as a dependency in the wrong direction.
      if (preg_match_all(
        '/\b(?:require|include)(?:_once)?\b[^;]{0,300}wizard\//',
        $this->executableCode($src),
        $matches
      )) {
        $offenders[] = basename($file) . ' (' . count($matches[0]) . ')';
      }
    }

    self::assertSame(
      [],
      $offenders,
      'These runtime pages include a file from wizard/, so they fatal once '
      . 'an admin follows the security audit advice to remove or chmod 000 '
      . 'the wizard/ directory (issue #707): ' . implode(', ', $offenders)
    );
  }

  /**
   * The source with comments removed, so prose about wizard/ is not read as a
   * dependency on it.
   */
  private function executableCode(string $php): string
  {
    $out = '';
    foreach (token_get_all($php) as $token) {
      if (is_array($token)) {
        if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
          continue;
        }
        $out .= $token[1];
        continue;
      }
      $out .= $token;
    }

    return $out;
  }

  public function testConfigDefaultsLiveOutsideWizard(): void
  {
    self::assertFileExists(
      self::ROOT . '/includes/default_config.php',
      'The config defaults and db_load_config() must live in includes/ so '
      . 'admin.php keeps working without wizard/.'
    );
    self::assertFileDoesNotExist(
      self::ROOT . '/wizard/shared/default_config.php',
      'default_config.php must not be duplicated back under wizard/; the '
      . 'wizard reads includes/default_config.php as the single source of '
      . 'truth.'
    );
  }

  public function testDefaultConfigDefinesSettingsAndLoader(): void
  {
    // require_once, not require: the file declares functions, so re-running
    // it in a process that already loaded it is a redeclare fatal. Read the
    // defaults through the accessor rather than the $webcalConfig variable,
    // which only exists in the scope the file was first required from.
    require_once self::ROOT . '/includes/default_config.php';

    self::assertTrue(
      function_exists('webcal_config_defaults'),
      'includes/default_config.php must expose webcal_config_defaults(); '
      . 'load_global_settings() reads the defaults through it because it '
      . 'cannot rely on the scope the file was required from.'
    );

    $webcalConfig = webcal_config_defaults();
    self::assertIsArray($webcalConfig);
    self::assertNotEmpty($webcalConfig);
    self::assertArrayHasKey('WEBCAL_PROGRAM_VERSION', $webcalConfig);
    self::assertTrue(
      function_exists('db_load_config'),
      'admin.php calls db_load_config() to seed config settings added by an '
      . 'upgrade; includes/default_config.php must define it.'
    );
  }

  public function testReleaseManifestShipsDefaultConfig(): void
  {
    $manifest = file(
      self::ROOT . '/release-files',
      FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );
    self::assertNotFalse($manifest);

    self::assertContains(
      'includes/default_config.php',
      $manifest,
      'The release ZIP ships only files listed in release-files; omitting '
      . 'includes/default_config.php would make admin.php fatal in every '
      . 'released build.'
    );
    self::assertNotContains('wizard/shared/default_config.php', $manifest);
  }

  public function testAuditTreatsUnreadableWizardAsResolved(): void
  {
    $src = file_get_contents(self::ROOT . '/security_audit.php');
    self::assertNotFalse($src);

    self::assertStringContainsString(
      "!is_dir('wizard') || !is_readable('wizard')",
      $src,
      'The audit recommends `chmod 000 wizard/`, but a 000 directory still '
      . 'passes is_dir(), so the check must also test readability or the '
      . 'recommendation can never clear the item (issue #707).'
    );
  }
}
