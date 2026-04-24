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
    // Check existence AND git-tracked-ness. A file can exist locally
    // because the dev ran `make` but be .gitignored and thus absent in
    // a fresh CI checkout — that's drift the original existence-only
    // check missed (issue #233 CI run 24891039995, PHP 8.3). Ship the
    // stricter invariant so future drift of this shape fails locally
    // too, not just on CI.
    $trackedFiles = $this->gitTrackedFiles();

    $missing = [];
    foreach ($this->listedPaths() as $lineNumber => $rel) {
      $full = self::REPO_ROOT . '/' . $rel;
      if (!is_file($full)) {
        $missing[] = "line $lineNumber: $rel (does not exist on disk)";
        continue;
      }
      if ($trackedFiles !== null && !isset($trackedFiles[$rel])) {
        $missing[] = "line $lineNumber: $rel (exists locally but NOT git-tracked — probably .gitignored + locally generated; CI checkout won't have it)";
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

  /**
   * @return array<string, true>|null  keys are tracked relpaths. Null
   * means `git ls-files` is unavailable (e.g. running from a tarball
   * without a .git dir); in that case we fall back to existence-only.
   */
  private function gitTrackedFiles(): ?array
  {
    if (!is_dir(self::REPO_ROOT . '/.git')) {
      return null;
    }
    $cmd = 'git -C ' . escapeshellarg(self::REPO_ROOT) . ' ls-files';
    $output = [];
    $status = 0;
    @exec($cmd . ' 2>/dev/null', $output, $status);
    if ($status !== 0) {
      return null;
    }
    $out = [];
    foreach ($output as $line) {
      if ($line !== '') {
        $out[$line] = true;
      }
    }
    return $out ?: null;
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
