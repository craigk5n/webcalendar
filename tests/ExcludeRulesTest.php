<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ExcludeRules;

require_once __DIR__ . '/../includes/classes/Security/ExcludeRules.php';

/**
 * Story 4.1 — ExcludeRules configuration source.
 *
 * The Story 3.3 minimal matcher is now extended with:
 *   - a DEFAULT_PATTERNS const that encodes the D9 exclude set;
 *   - a withDefaults() factory that unions the defaults with user-
 *     supplied extras from the SECURITY_AUDIT_EXTRA_EXCLUDES setting.
 *
 * The constructor + matches() behavior tested by Story 3.3 is
 * preserved and exercised again here (via default construction).
 */
final class ExcludeRulesTest extends TestCase
{
  // -- DEFAULT_PATTERNS constant -------------------------------------------

  public function testDefaultPatternsConstIsPubliclyAccessible(): void
  {
    self::assertIsArray(ExcludeRules::DEFAULT_PATTERNS);
    self::assertNotEmpty(ExcludeRules::DEFAULT_PATTERNS);
  }

  public function testDefaultPatternsIncludesAllAcRequired(): void
  {
    $required = [
      'includes/settings.php',
      'includes/site_extras.php',
      'MANIFEST.sha256',
      'MANIFEST.sha256.sig',
      'tools/',
      'tests/',
      'docs/',
      'vendor/',
      '.git/',
      '.github/',
    ];
    foreach ($required as $p) {
      self::assertContains(
        $p,
        ExcludeRules::DEFAULT_PATTERNS,
        "DEFAULT_PATTERNS must include '$p' (Story 4.1 AC)."
      );
    }
  }

  // -- withDefaults() factory ----------------------------------------------

  public function testWithDefaultsAndNoExtrasExcludesSettingsPhp(): void
  {
    $rules = ExcludeRules::withDefaults(null);
    self::assertTrue($rules->matches('includes/settings.php'));
  }

  public function testWithDefaultsDoesNotExcludeLegitimateAppFiles(): void
  {
    $rules = ExcludeRules::withDefaults(null);
    self::assertFalse(
      $rules->matches('includes/init.php'),
      'includes/init.php is core app code, must not be excluded.'
    );
    self::assertFalse(
      $rules->matches('admin.php'),
      'admin.php ships in releases, must not be excluded.'
    );
    self::assertFalse(
      $rules->matches('pub/bootstrap.min.css'),
      'pub/bootstrap.min.css is shipped vendor asset, must not be excluded by default.'
    );
  }

  public function testWithDefaultsExcludesManifestFilesThemselves(): void
  {
    // The MANIFEST files ship inside their own zip but are generated,
    // so they SHOULD be excluded from the scan against their own
    // manifest (otherwise they'd show as EXTRA since they aren't
    // listed in themselves).
    $rules = ExcludeRules::withDefaults(null);
    self::assertTrue($rules->matches('MANIFEST.sha256'));
    self::assertTrue($rules->matches('MANIFEST.sha256.sig'));
  }

  public function testWithDefaultsExcludesDirectoryPrefixes(): void
  {
    $rules = ExcludeRules::withDefaults(null);
    self::assertTrue($rules->matches('tests/ManifestBuilderTest.php'));
    self::assertTrue($rules->matches('tests/fixtures/whatever.txt'));
    self::assertTrue($rules->matches('docs/index.md'));
    self::assertTrue($rules->matches('.git/HEAD'));
    self::assertTrue($rules->matches('.github/workflows/release.yml'));
    self::assertTrue($rules->matches('vendor/composer/autoload_real.php'));
  }

  public function testWithDefaultsDoesNotExcludeSimilarPrefixes(): void
  {
    // tools/ is excluded; 'tools.txt' at root should NOT be.
    // Ensure trailing-slash prefix matching is STRICT.
    $rules = ExcludeRules::withDefaults(null);
    self::assertFalse($rules->matches('tools.txt'));
    self::assertFalse($rules->matches('toolsomething.php'));
  }

  public function testCssUnderPubIsNotExcludedByDefault(): void
  {
    // Per D9 — flag everything by default. Admins who customize CSS
    // can add 'pub/css/*.css' to SECURITY_AUDIT_EXTRA_EXCLUDES.
    $rules = ExcludeRules::withDefaults(null);
    self::assertFalse(
      $rules->matches('pub/css/custom.css'),
      'CSS under pub/ is NOT excluded by default per D9.'
    );
  }

  // -- Extras from the admin config ----------------------------------------

  public function testWithDefaultsAndSingleGlobExtra(): void
  {
    $rules = ExcludeRules::withDefaults("pub/css/*.css");
    self::assertTrue($rules->matches('pub/css/custom.css'));
    self::assertTrue($rules->matches('pub/css/theme.css'));
    self::assertFalse(
      $rules->matches('pub/js/app.js'),
      'Extra glob should not over-match.'
    );
  }

  public function testWithDefaultsAndMultipleNewlineSeparatedExtras(): void
  {
    $extras = "pub/css/*.css\npub/logo.png\nimages/";
    $rules = ExcludeRules::withDefaults($extras);

    // Each extra matches what it should
    self::assertTrue($rules->matches('pub/css/custom.css'));
    self::assertTrue($rules->matches('pub/logo.png'));
    self::assertTrue($rules->matches('images/admin/icon.gif'));

    // Defaults still active
    self::assertTrue($rules->matches('includes/settings.php'));
    self::assertTrue($rules->matches('tests/foo.php'));
  }

  public function testWithDefaultsHandlesCrlfLineEndings(): void
  {
    // Admin might paste from a Windows editor — handle CRLF too.
    $rules = ExcludeRules::withDefaults("pub/css/*.css\r\npub/js/*.js");
    self::assertTrue($rules->matches('pub/css/custom.css'));
    self::assertTrue($rules->matches('pub/js/app.js'));
  }

  public function testWithDefaultsSkipsBlankLinesAndComments(): void
  {
    $extras = "# this is a comment\n\npub/css/*.css\n  # indented comment\n\n";
    $rules = ExcludeRules::withDefaults($extras);
    self::assertTrue($rules->matches('pub/css/theme.css'));
    // Comments and blanks shouldn't somehow become patterns that match '' etc.
    self::assertFalse($rules->matches(''));
  }

  public function testWithDefaultsTrimsWhitespaceFromEachLine(): void
  {
    // Leading / trailing spaces on admin-pasted lines shouldn't break matching.
    $rules = ExcludeRules::withDefaults("  pub/css/*.css  \n\tpub/logo.png\t");
    self::assertTrue($rules->matches('pub/css/custom.css'));
    self::assertTrue($rules->matches('pub/logo.png'));
  }

  public function testWithDefaultsAndEmptyStringBehaveLikeNoExtras(): void
  {
    $a = ExcludeRules::withDefaults(null);
    $b = ExcludeRules::withDefaults('');
    $c = ExcludeRules::withDefaults('   ');
    // All three should have identical behavior: defaults only.
    foreach (['includes/settings.php', 'tests/foo.php', '.git/HEAD'] as $p) {
      self::assertTrue($a->matches($p));
      self::assertTrue($b->matches($p));
      self::assertTrue($c->matches($p));
    }
    self::assertFalse($a->matches('pub/css/custom.css'));
    self::assertFalse($b->matches('pub/css/custom.css'));
    self::assertFalse($c->matches('pub/css/custom.css'));
  }

  public function testUserExtrasAreUnionedNotReplaced(): void
  {
    // Even if the admin only supplies a CSS glob, the default
    // excludes must remain in effect.
    $rules = ExcludeRules::withDefaults("pub/css/*.css");
    self::assertTrue(
      $rules->matches('includes/settings.php'),
      'Defaults must union with extras, not be replaced.'
    );
  }

  // -- Plain constructor still works (Story 3.3 backwards compat) ---------

  public function testPlainConstructorStillWorksWithEmptyList(): void
  {
    $rules = new ExcludeRules([]);
    self::assertFalse($rules->matches('anything'));
  }

  public function testPlainConstructorAllowsCustomSets(): void
  {
    $rules = new ExcludeRules(['foo.txt', 'bar/']);
    self::assertTrue($rules->matches('foo.txt'));
    self::assertTrue($rules->matches('bar/baz.php'));
    self::assertFalse($rules->matches('includes/settings.php'));
  }
}
