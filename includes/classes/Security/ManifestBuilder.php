<?php

declare(strict_types=1);

namespace WebCalendar\Security;

use DateTimeImmutable;
use RuntimeException;

/**
 * Builds the `MANIFEST.sha256` content used by the signed-manifest
 * feature (GitHub issue #233).
 *
 * Output format (matches GNU `sha256sum` plus a three-line header):
 *
 *     # webcalendar-version: 1.9.13
 *     # build-timestamp: 2026-04-23T12:00:00Z
 *     # git-sha: 7b469061abcdef...
 *     <64-hex>  <relative-path>
 *     <64-hex>  <relative-path>
 *     ...
 *
 * Paths are sorted byte-lexicographic (equivalent to LC_ALL=C sort)
 * so the output is reproducible across platforms. Line endings are LF
 * throughout with a single trailing LF. The header is part of the
 * signed bytes — tampering with any header field will break signature
 * verification.
 */
final class ManifestBuilder
{
  public const HEADER_VERSION_KEY = 'webcalendar-version';
  public const HEADER_TIMESTAMP_KEY = 'build-timestamp';
  public const HEADER_GIT_SHA_KEY = 'git-sha';

  /**
   * Build the manifest text.
   *
   * @param list<string> $relativePaths
   * @throws RuntimeException if any listed file is missing or unreadable.
   */
  public static function build(
    string $treeRoot,
    array $relativePaths,
    string $version,
    DateTimeImmutable $buildTimestamp,
    string $gitSha
  ): string {
    // Dedupe + sort byte-lexicographic (SORT_STRING uses strcmp, which
    // is LC_ALL=C-equivalent byte comparison).
    $paths = array_values(array_unique($relativePaths));
    sort($paths, SORT_STRING);

    $lines = [];

    // Header — single space after '#:' to keep grep-able, LF-only.
    $lines[] = '# ' . self::HEADER_VERSION_KEY . ': ' . $version;
    $lines[] = '# ' . self::HEADER_TIMESTAMP_KEY . ': '
      . $buildTimestamp->format('Y-m-d\TH:i:s\Z');
    $lines[] = '# ' . self::HEADER_GIT_SHA_KEY . ': ' . $gitSha;

    // Body — one hash line per path.
    $normalisedRoot = rtrim($treeRoot, '/');
    foreach ($paths as $relPath) {
      $lines[] = self::hashLine($normalisedRoot, $relPath);
    }

    // Join with LF and append final LF.
    return implode("\n", $lines) . "\n";
  }

  /**
   * @return string  "<64-hex>  <relPath>"
   */
  private static function hashLine(string $treeRoot, string $relPath): string
  {
    $fullPath = $treeRoot . '/' . $relPath;

    if (!is_file($fullPath)) {
      throw new RuntimeException(
        "Cannot hash '$relPath': file does not exist at '$fullPath'."
      );
    }

    // @ suppresses the E_WARNING on permission failure; we surface a
    // RuntimeException instead so the caller gets one consistent error channel.
    $hash = @hash_file('sha256', $fullPath);
    if ($hash === false) {
      throw new RuntimeException(
        "Cannot hash '$relPath': file is not readable at '$fullPath'."
      );
    }

    return $hash . '  ' . $relPath;
  }
}
