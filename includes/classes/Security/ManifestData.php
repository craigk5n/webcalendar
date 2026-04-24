<?php

declare(strict_types=1);

namespace WebCalendar\Security;

use DateTimeImmutable;

/**
 * Immutable parsed view of a MANIFEST.sha256 file (signed-manifest
 * feature, GitHub issue #233). Produced by `ManifestParser::parse()`.
 *
 * `hashes` maps relative-path → lowercase-hex sha256.
 *
 * Uses `final class` + per-property `readonly` (PHP 8.1-compatible
 * equivalent of `final readonly class`, which is PHP 8.2+) for the
 * same reason as `VerifyResult`.
 */
final class ManifestData
{
  /** @param array<string, string> $hashes  relpath → sha256hex */
  public function __construct(
    public readonly string $version,
    public readonly DateTimeImmutable $buildTimestamp,
    public readonly string $gitSha,
    public readonly array $hashes
  ) {}
}
