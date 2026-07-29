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
  private const RELEASE_FILES_EXCLUDED = __DIR__ . '/../release-files-excluded';
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

  /**
   * Inverse drift detector: every git-tracked file must be classified —
   * either it ships (listed in release-files) or it is intentionally
   * excluded (matches a pattern in release-files-excluded). New files
   * committed without a classification fail here, which is what used to
   * silently produce broken releases (e.g. translations renamed in git
   * but never re-added to release-files, export_wordpress.php linked
   * from adminhome.php but absent from the ZIP).
   */
  public function testEveryTrackedFileIsShippedOrExcluded(): void
  {
    $tracked = $this->gitTrackedFiles();
    if ($tracked === null) {
      self::markTestSkipped('git ls-files unavailable; cannot enumerate tracked files.');
    }

    $shipped = [];
    foreach ($this->listedPaths() as $rel) {
      $shipped[$rel] = true;
    }
    $patterns = $this->excludedPatterns();

    $unclassified = [];
    foreach (array_keys($tracked) as $rel) {
      if (isset($shipped[$rel])) {
        continue;
      }
      if ($this->matchesAny($rel, $patterns)) {
        continue;
      }
      $unclassified[] = $rel;
    }

    self::assertSame(
      [],
      $unclassified,
      count($unclassified) . " git-tracked file(s) are neither in release-files "
      . "nor matched by release-files-excluded. Decide for each: does it belong "
      . "in the release ZIP? If yes, add it to release-files; if no, add it (or "
      . "a covering pattern) to release-files-excluded. Unclassified:\n  "
      . implode("\n  ", $unclassified)
    );
  }

  /**
   * A file both listed in release-files AND matched by an exclusion
   * pattern is ambiguous — the two manifests disagree. Keep exclusion
   * patterns precise enough that this never happens.
   */
  public function testNoFileIsBothShippedAndExcluded(): void
  {
    $patterns = $this->excludedPatterns();
    $conflicts = [];
    foreach ($this->listedPaths() as $lineNumber => $rel) {
      if ($this->matchesAny($rel, $patterns)) {
        $conflicts[] = "release-files line $lineNumber: $rel";
      }
    }
    self::assertSame(
      [],
      $conflicts,
      "Entry(ies) in release-files also match release-files-excluded patterns. "
      . "Narrow the pattern or drop the entry:\n  " . implode("\n  ", $conflicts)
    );
  }

  /**
   * Exclusion patterns that match no tracked file are stale — usually a
   * typo or a leftover after a file was deleted/renamed. Same failure
   * mode the stale-entry test catches for release-files.
   */
  public function testNoStaleExclusionPatterns(): void
  {
    $tracked = $this->gitTrackedFiles();
    if ($tracked === null) {
      self::markTestSkipped('git ls-files unavailable; cannot enumerate tracked files.');
    }

    $stale = [];
    foreach ($this->excludedPatterns() as $lineNumber => $pattern) {
      $regex = $this->patternToRegex($pattern);
      $hit = false;
      foreach (array_keys($tracked) as $rel) {
        if (preg_match($regex, $rel) === 1) {
          $hit = true;
          break;
        }
      }
      // release-files-excluded lists itself but may not be git-tracked
      // yet on the branch that introduces it.
      if (!$hit && $pattern !== 'release-files-excluded') {
        $stale[] = "line $lineNumber: $pattern";
      }
    }
    self::assertSame(
      [],
      $stale,
      "Stale pattern(s) in release-files-excluded match no tracked file:\n  "
      . implode("\n  ", $stale)
    );
  }

  /** @return array<int, string>  line-number => pattern */
  private function excludedPatterns(): array
  {
    self::assertFileExists(self::RELEASE_FILES_EXCLUDED);
    $contents = file_get_contents(self::RELEASE_FILES_EXCLUDED);
    self::assertNotFalse($contents);

    $patterns = [];
    foreach (explode("\n", $contents) as $i => $raw) {
      $line = trim($raw);
      if ($line === '' || $line[0] === '#') {
        continue;
      }
      $patterns[$i + 1] = $line;
    }
    return $patterns;
  }

  /** @param array<int, string> $patterns */
  private function matchesAny(string $rel, array $patterns): bool
  {
    foreach ($patterns as $pattern) {
      if (preg_match($this->patternToRegex($pattern), $rel) === 1) {
        return true;
      }
    }
    return false;
  }

  /**
   * Glob-to-regex: '**' spans '/', '*' and '?' do not. Literal paths
   * (no wildcards) match exactly.
   */
  private function patternToRegex(string $pattern): string
  {
    static $cache = [];
    if (!isset($cache[$pattern])) {
      $regex = preg_quote($pattern, '#');
      $regex = str_replace('\*\*', '.*', $regex);
      $regex = str_replace('\*', '[^/]*', $regex);
      $regex = str_replace('\?', '[^/]', $regex);
      $cache[$pattern] = '#^' . $regex . '$#';
    }
    return $cache[$pattern];
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
