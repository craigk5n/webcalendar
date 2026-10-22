<?php

declare(strict_types=1);

namespace WebCalendar\Security;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

/**
 * Parses a MANIFEST.sha256 file (as produced by `ManifestBuilder`)
 * into an immutable `ManifestData`.
 *
 * Strict by design — rejects anything that doesn't match the
 * canonical format, and prefixes every error with a `line N:` marker
 * so bad lines are trivial to locate. Use this AFTER the signature
 * is verified (Story 3.1): garbage-in-garbage-out on a verified
 * manifest should never happen; if it does, treat it as a bug.
 */
final class ManifestParser
{
  private const HASH_LINE_REGEX = '/^([0-9a-f]{64})  (\S.*)$/';
  private const HEADER_LINE_REGEX = '/^#\s+([a-z0-9-]+):\s*(.*)$/i';

  public static function parse(string $manifestPath): ManifestData
  {
    if (!is_file($manifestPath)) {
      throw new RuntimeException(
        "MANIFEST.sha256 does not exist at $manifestPath."
      );
    }
    $bytes = @file_get_contents($manifestPath);
    if ($bytes === false) {
      throw new RuntimeException(
        "Cannot read MANIFEST.sha256 at $manifestPath."
      );
    }

    $lines = explode("\n", $bytes);
    // A canonical manifest ends with LF, producing a trailing empty
    // element. Drop it so we don't trip the blank-line check.
    if ($lines !== [] && end($lines) === '') {
      array_pop($lines);
    }

    $headers = [];
    /** @var array<string, string> $hashes */
    $hashes = [];
    /** @var array<string, int> $pathLineNumbers */
    $pathLineNumbers = [];
    $inBody = false;

    foreach ($lines as $i => $line) {
      $lineNo = $i + 1;

      if ($line === '') {
        throw new RuntimeException(
          "line $lineNo: blank lines are not permitted in the manifest."
        );
      }

      if ($line[0] === '#') {
        if ($inBody) {
          throw new RuntimeException(
            "line $lineNo: header line found after hash lines began."
          );
        }
        if (!preg_match(self::HEADER_LINE_REGEX, $line, $m)) {
          throw new RuntimeException(
            "line $lineNo: malformed header line: $line"
          );
        }
        $headers[strtolower($m[1])] = $m[2];
        continue;
      }

      // Hash line.
      $inBody = true;
      if (!preg_match(self::HASH_LINE_REGEX, $line, $m)) {
        throw new RuntimeException(
          "line $lineNo: malformed hash line (expected '<64-hex>  <path>'): $line"
        );
      }
      $hash = $m[1];
      $path = $m[2];

      if (isset($pathLineNumbers[$path])) {
        throw new RuntimeException(
          "line $lineNo: duplicate path '$path' (previously seen on "
          . "line {$pathLineNumbers[$path]})."
        );
      }
      $hashes[$path] = $hash;
      $pathLineNumbers[$path] = $lineNo;
    }

    if ($hashes === []) {
      throw new RuntimeException(
        'Manifest has no hash entries (empty body).'
      );
    }

    return new ManifestData(
      self::requireHeader($headers, 'webcalendar-version'),
      self::parseTimestamp(
        self::requireHeader($headers, 'build-timestamp')
      ),
      self::requireHeader($headers, 'git-sha'),
      $hashes
    );
  }

  /** @param array<string, string> $headers */
  private static function requireHeader(array $headers, string $key): string
  {
    if (!isset($headers[$key])) {
      throw new RuntimeException(
        "Manifest missing required header '$key'."
      );
    }
    return $headers[$key];
  }

  private static function parseTimestamp(string $raw): DateTimeImmutable
  {
    try {
      return new DateTimeImmutable($raw, new DateTimeZone('UTC'));
    } catch (Throwable $e) {
      throw new RuntimeException(
        "Manifest build-timestamp is not a parseable date: $raw"
      );
    }
  }
}
