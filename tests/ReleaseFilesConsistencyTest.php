<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Story 2.5 / AC4 — drift detector for release-files.
 *
 * The release workflow reads release-files line-by-line and copies each
 * listed path into the staged release directory. If a listed file is
 * missing, today's `cp` silently skips it (the loop doesn't `set -e`).
 * tools/build-manifest.php is strict and WILL fail on missing files, so
 * once Story 2.3 wires it in, stale entries break the release.
 *
 * This test makes drift a build-break instead of a silent defect.
 */
final class ReleaseFilesConsistencyTest extends TestCase
{
  private const RELEASE_FILES = __DIR__ . '/../release-files';
  private const REPO_ROOT = __DIR__ . '/..';

  public function testReleaseFilesListExists(): void
  {
    self::assertFileExists(self::RELEASE_FILES);
  }

  public function testEveryListedEntryResolvesToARealFile(): void
  {
    $missing = [];
    foreach ($this->listedPaths() as $lineNumber => $rel) {
      $full = self::REPO_ROOT . '/' . $rel;
      if (!is_file($full)) {
        $missing[] = "line $lineNumber: $rel";
      }
    }

    self::assertSame(
      [],
      $missing,
      count($missing) . " stale entry(ies) in release-files. Every line must "
      . "resolve to a tracked file at repo root. Drift like this used to be "
      . "silently swallowed by release.yml's `cp` loop; Story 2.5 makes it "
      . "fail the build. Offenders:\n  " . implode("\n  ", $missing)
    );
  }

  public function testNoDuplicateEntries(): void
  {
    $seen = [];
    $dupes = [];
    foreach ($this->listedPaths() as $lineNumber => $rel) {
      if (isset($seen[$rel])) {
        $dupes[] = "line $lineNumber duplicates line {$seen[$rel]}: $rel";
      } else {
        $seen[$rel] = $lineNumber;
      }
    }
    self::assertSame([], $dupes, implode("\n", $dupes));
  }

  /** @return iterable<int, string>  line-number => relative path */
  private function listedPaths(): iterable
  {
    $contents = file_get_contents(self::RELEASE_FILES);
    self::assertNotFalse($contents);

    $lines = explode("\n", $contents);
    foreach ($lines as $i => $raw) {
      $line = trim($raw);
      if ($line === '' || $line[0] === '#') {
        continue;
      }
      yield ($i + 1) => $line;
    }
  }
}
