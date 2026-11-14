<?php
/**
 * TinyMCE ships inside the tree, in pub/tinymce/, copied out of
 * vendor/tinymce/tinymce by the Makefile (`make`). The release zip and the
 * Docker image do not run Composer, so pub/tinymce/ is what actually loads in
 * the browser (includes/init.php references pub/tinymce/tinymce.min.js).
 *
 * These tests keep that copy honest without needing a browser:
 *  - The bundle version must match composer.lock, so a Composer bump that was
 *    not followed by `make` (stale pub/tinymce/) fails the build instead of
 *    shipping a mismatched editor (testBundleVersionMatchesComposerLock).
 *  - The specific files includes/init.php's tinymce.init() needs must be
 *    present, so trimming the Makefile's TINYMCE_FILES list in a way that
 *    breaks the editor fails here rather than only in the browser
 *    (testRequiredRuntimeFilesPresent).
 *
 * The browser-level counterpart — that the editor actually initialises — is
 * covered by the Selenium smoke tests (tests/web-install-*.py), which wait for
 * the .tox-tinymce container on edit_entry.php.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TinyMceBundleTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';
  private const BUNDLE = self::ROOT . '/pub/tinymce';

  public function testBundleVersionMatchesComposerLock(): void
  {
    $lock = json_decode(
      (string) file_get_contents(self::ROOT . '/composer.lock'), true);
    $locked = null;
    foreach ($lock['packages'] as $p) {
      if ($p['name'] === 'tinymce/tinymce') {
        $locked = ltrim($p['version'], 'v');
        break;
      }
    }
    self::assertNotNull($locked, 'tinymce/tinymce not found in composer.lock');

    // pub/tinymce/CHANGELOG.md is copied verbatim from the package; its first
    // "## X.Y.Z" heading is the version of the shipped bundle.
    $changelog = (string) file_get_contents(self::BUNDLE . '/CHANGELOG.md');
    self::assertSame(1,
      preg_match('/^##\s+(\d+\.\d+\.\d+)/m', $changelog, $m),
      'Could not read the bundled TinyMCE version from pub/tinymce/CHANGELOG.md');

    self::assertSame($locked, $m[1],
      'pub/tinymce/ is stale relative to composer.lock (' . $locked . '). '
      . 'Run `make` and commit pub/tinymce/.');
  }

  public function testRequiredRuntimeFilesPresent(): void
  {
    // The files includes/init.php:455 tinymce.init() depends on: the core
    // script, the silver theme, the dom model, the default icon pack, the
    // default oxide UI + content skin, and the five configured plugins
    // (lists link table code autolink).
    $required = [
      'tinymce.min.js',
      'themes/silver/theme.min.js',
      'models/dom/model.min.js',
      'icons/default/icons.min.js',
      'skins/ui/oxide/skin.min.css',
      'skins/ui/oxide/content.min.css',
      'skins/content/default/content.min.css',
      'plugins/lists/plugin.min.js',
      'plugins/link/plugin.min.js',
      'plugins/table/plugin.min.js',
      'plugins/code/plugin.min.js',
      'plugins/autolink/plugin.min.js',
    ];

    $missing = [];
    foreach ($required as $rel) {
      if (!is_file(self::BUNDLE . '/' . $rel)) {
        $missing[] = 'pub/tinymce/' . $rel;
      }
    }

    self::assertSame([], $missing,
      "TinyMCE runtime files the editor needs are missing from pub/tinymce/:\n  "
      . implode("\n  ", $missing)
      . "\nRun `make` and check the Makefile TINYMCE_FILES list.");
  }
}
