<?php

declare(strict_types=1);

namespace WebCalendar\Security;

use FilesystemIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Walks an installation directory and compares every observed file
 * against a (verified) `ManifestData`, classifying each as MATCH,
 * MODIFIED, MISSING, or EXTRA (signed-manifest feature, issue #233).
 *
 * Symlinks are NOT followed as traversal targets — a symlink that
 * points to a directory outside the scan root will NOT cause the
 * scanner to descend. A symlink at a leaf position is treated like
 * any other file entry (`hash_file()` reads through to the target
 * bytes, so a manifest-listed symlink with an unchanged target stays
 * MATCH).
 *
 * `RecursiveDirectoryIterator` by default descends into symlinked
 * directories. We implement our own recursive walk instead, using
 * `SplFileInfo::isLink()` to decide whether to recurse. Clear intent,
 * fewer surprises than subclassing.
 */
final class InstallationScanner
{
  public static function scan(
    ManifestData $manifest,
    string $installRoot,
    ExcludeRules $excludes
  ): ScanReport {
    if (!is_dir($installRoot)) {
      throw new RuntimeException(
        "Install root does not exist at $installRoot."
      );
    }

    $scanner = new self($manifest, rtrim($installRoot, '/'), $excludes);
    return $scanner->run();
  }

  /** @var array<string, bool> */
  private array $seenManifestPaths = [];

  /** @var list<ScannedFile> */
  private array $modified = [];

  /** @var list<ScannedFile> */
  private array $extra = [];

  private int $matchedCount = 0;

  private function __construct(
    private readonly ManifestData $manifest,
    private readonly string $absRoot,
    private readonly ExcludeRules $excludes
  ) {}

  private function run(): ScanReport
  {
    $this->walk($this->absRoot, '');

    // Everything in the manifest not seen on disk AND not excluded.
    $missing = [];
    foreach ($this->manifest->hashes as $relPath => $expected) {
      if (isset($this->seenManifestPaths[$relPath])) {
        continue;
      }
      if ($this->excludes->matches($relPath)) {
        continue;
      }
      $missing[] = new ScannedFile(
        $relPath,
        ScanEntryKind::MISSING,
        $expected,
        null
      );
    }

    return new ScanReport(
      $this->modified,
      $missing,
      $this->extra,
      $this->matchedCount
    );
  }

  private function walk(string $absDir, string $relPrefix): void
  {
    $iter = new FilesystemIterator(
      $absDir,
      FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
    );

    foreach ($iter as $info) {
      /** @var SplFileInfo $info */
      $relPath = $relPrefix . $info->getFilename();

      // Symlinks are leaves regardless of target type. We handle
      // them BEFORE the isDir() branch so we don't follow symlinked
      // directories.
      if ($info->isLink()) {
        $this->classifyLeaf($relPath, $info->getPathname());
        continue;
      }

      if ($info->isDir()) {
        $this->walk($info->getPathname(), $relPath . '/');
        continue;
      }

      // Regular file (including sockets/fifos/etc — hash_file will
      // fail on those and we'll surface MODIFIED with a null actual).
      $this->classifyLeaf($relPath, $info->getPathname());
    }
  }

  private function classifyLeaf(string $relPath, string $fullPath): void
  {
    if ($this->excludes->matches($relPath)) {
      // Excluded disk file that's ALSO in the manifest: mark as seen
      // so it doesn't later surface as MISSING.
      if (isset($this->manifest->hashes[$relPath])) {
        $this->seenManifestPaths[$relPath] = true;
      }
      return;
    }

    if (!isset($this->manifest->hashes[$relPath])) {
      $this->extra[] = new ScannedFile($relPath, ScanEntryKind::EXTRA);
      return;
    }

    $this->seenManifestPaths[$relPath] = true;
    $expected = $this->manifest->hashes[$relPath];

    // @ quiets permission warnings; false is surfaced as MODIFIED.
    $actual = @hash_file('sha256', $fullPath);
    if ($actual === false) {
      $this->modified[] = new ScannedFile(
        $relPath,
        ScanEntryKind::MODIFIED,
        $expected,
        null
      );
      return;
    }

    if ($actual === $expected) {
      $this->matchedCount++;
      return;
    }

    $this->modified[] = new ScannedFile(
      $relPath,
      ScanEntryKind::MODIFIED,
      $expected,
      $actual
    );
  }
}
