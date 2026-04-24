<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Filters a `ScanReport` by severity threshold (signed-manifest
 * feature, GitHub issue #233, Story 4.2).
 *
 * Driven by the `SECURITY_AUDIT_NOISE_FILTER` admin setting. Pure
 * function: takes a `ScanReport` in, returns a fresh `ScanReport`
 * with entries below the threshold dropped. `matchedCount` is
 * always preserved — filtering hides findings, not the "how many
 * files matched" summary.
 *
 * Unknown / empty modes fall back to `ALL` (identity function).
 */
final class ScanReportFilter
{
  public const ALL = 'all';
  public const WARN_AND_ABOVE = 'warn_and_above';
  public const CRITICAL_ONLY = 'critical_only';

  public static function filter(ScanReport $report, string $mode): ScanReport
  {
    $keep = self::severitiesToKeep($mode);

    return new ScanReport(
      self::filterList($report->modified, $keep),
      self::filterList($report->missing, $keep),
      self::filterList($report->extra, $keep),
      $report->matchedCount
    );
  }

  /** @return list<Severity> */
  private static function severitiesToKeep(string $mode): array
  {
    switch ($mode) {
      case self::CRITICAL_ONLY:
        return [Severity::CRITICAL];
      case self::WARN_AND_ABOVE:
        return [Severity::CRITICAL, Severity::WARN];
      case self::ALL:
      default:
        return [Severity::CRITICAL, Severity::WARN, Severity::INFO];
    }
  }

  /**
   * @param list<ScannedFile> $files
   * @param list<Severity> $keep
   * @return list<ScannedFile>
   */
  private static function filterList(array $files, array $keep): array
  {
    $out = [];
    foreach ($files as $f) {
      if (in_array(SeverityClassifier::classify($f), $keep, true)) {
        $out[] = $f;
      }
    }
    return $out;
  }
}
