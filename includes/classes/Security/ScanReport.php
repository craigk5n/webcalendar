<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Immutable output of `InstallationScanner::scan()` (signed-manifest
 * feature, issue #233).
 *
 * `matchedCount` is a scalar because consumers never need the per-file
 * list of MATCH entries — only the total. The three lists carry one
 * `ScannedFile` per anomaly.
 */
final class ScanReport
{
  /**
   * @param list<ScannedFile> $modified
   * @param list<ScannedFile> $missing
   * @param list<ScannedFile> $extra
   */
  public function __construct(
    public readonly array $modified,
    public readonly array $missing,
    public readonly array $extra,
    public readonly int $matchedCount
  ) {}
}
