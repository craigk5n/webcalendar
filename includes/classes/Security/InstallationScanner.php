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
      $actual,
      $this->differsOnlyInLineEndings($fullPath, $expected)
    );
  }

  /**
   * Largest file in a v1.9.24 release is 1.5MB (pub/tinymce/themes/silver/
   * theme.js). The cap is generous against that and bounds the work when an
   * install has something much bigger sitting in the tree.
   */
  private const LINE_ENDING_CHECK_MAX_BYTES = 8388608;

  /**
   * True when the file on disk becomes the manifest's bytes once line endings
   * are normalised.
   *
   * Both directions are tried, because the conversion can go either way: an
   * extraction in text mode strips the CRs from a file we shipped with CRLF,
   * and a checkout or editor on Windows can add them to one we shipped with LF.
   * The manifest stores a hash, not the content, so the only way to ask the
   * question is to convert the local copy and hash each candidate.
   *
   * Cheap tests first: anything with a NUL byte is binary, where line endings
   * are not a meaningful notion, and a file with no CR and no LF at all cannot
   * be reconciled this way.
   */
  private function differsOnlyInLineEndings(
    string $fullPath,
    string $expected
  ): bool {
    $size = @filesize($fullPath);
    if ($size === false || $size > self::LINE_ENDING_CHECK_MAX_BYTES) {
      return false;
    }

    $contents = @file_get_contents($fullPath);
    if ($contents === false || $contents === '') {
      return false;
    }
    if (str_contains($contents, "\0")) {
      return false;
    }

    $lf = str_replace("\r\n", "\n", $contents);
    if (!str_contains($lf, "\n")) {
      return false;
    }

    if (hash('sha256', $lf) === $expected) {
      return true;
    }

    // Normalise to LF first so an already-CRLF file does not become CRCRLF.
    return hash('sha256', str_replace("\n", "\r\n", $lf)) === $expected;
  }
}
