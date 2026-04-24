<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ScanEntryKind;
use WebCalendar\Security\ScannedFile;
use WebCalendar\Security\ScanReport;
use WebCalendar\Security\ScanReportFilter;

require_once __DIR__ . '/../includes/classes/Security/ScanEntryKind.php';
require_once __DIR__ . '/../includes/classes/Security/ScannedFile.php';
require_once __DIR__ . '/../includes/classes/Security/ScanReport.php';
require_once __DIR__ . '/../includes/classes/Security/Severity.php';
require_once __DIR__ . '/../includes/classes/Security/SeverityClassifier.php';
require_once __DIR__ . '/../includes/classes/Security/ScanReportFilter.php';

/**
 * Story 4.2 — Noise filter admin setting.
 *
 * ScanReportFilter drops ScannedFile entries below the configured
 * severity threshold while preserving matchedCount (the "how many
 * files were clean" total stays correct regardless of filter mode).
 */
final class ScanReportFilterTest extends TestCase
{
  /** Build a mixed report covering all three severities. */
  private function mixedReport(): ScanReport
  {
    return new ScanReport(
      modified: [
        // MODIFIED → always WARN regardless of extension.
        new ScannedFile('admin.php', ScanEntryKind::MODIFIED, 'a', 'b'),
      ],
      missing: [
        new ScannedFile('login.php', ScanEntryKind::MISSING, 'a', null),
      ],
      extra: [
        // CRITICAL: php extension EXTRA.
        new ScannedFile('shell.php', ScanEntryKind::EXTRA),
        // WARN: unknown extension EXTRA.
        new ScannedFile('mystery.xyz', ScanEntryKind::EXTRA),
        // INFO: known static asset EXTRA.
        new ScannedFile('theme.css', ScanEntryKind::EXTRA),
      ],
      matchedCount: 42
    );
  }

  // -- Constants sanity ----------------------------------------------------

  public function testModeConstantsHaveExpectedValues(): void
  {
    self::assertSame('all', ScanReportFilter::ALL);
    self::assertSame('warn_and_above', ScanReportFilter::WARN_AND_ABOVE);
    self::assertSame('critical_only', ScanReportFilter::CRITICAL_ONLY);
  }

  // -- ALL is the identity function ----------------------------------------

  public function testAllModePreservesEveryEntry(): void
  {
    $in = $this->mixedReport();
    $out = ScanReportFilter::filter($in, ScanReportFilter::ALL);

    self::assertCount(1, $out->modified);
    self::assertCount(1, $out->missing);
    self::assertCount(3, $out->extra); // all three kept
    self::assertSame(42, $out->matchedCount);
  }

  public function testAllModeOnEmptyReportReturnsEmpty(): void
  {
    $empty = new ScanReport([], [], [], 0);
    $out = ScanReportFilter::filter($empty, ScanReportFilter::ALL);
    self::assertSame([], $out->modified);
    self::assertSame([], $out->missing);
    self::assertSame([], $out->extra);
    self::assertSame(0, $out->matchedCount);
  }

  // -- WARN_AND_ABOVE: INFO dropped ----------------------------------------

  public function testWarnAndAboveDropsInfoEntries(): void
  {
    $in = $this->mixedReport();
    $out = ScanReportFilter::filter($in, ScanReportFilter::WARN_AND_ABOVE);

    // MODIFIED is WARN → kept.
    self::assertCount(1, $out->modified);
    // MISSING is WARN → kept.
    self::assertCount(1, $out->missing);
    // EXTRA: shell.php (CRITICAL) kept, mystery.xyz (WARN) kept,
    //        theme.css (INFO) dropped.
    self::assertCount(2, $out->extra);
    $extraPaths = array_map(fn(ScannedFile $f) => $f->path, $out->extra);
    self::assertContains('shell.php', $extraPaths);
    self::assertContains('mystery.xyz', $extraPaths);
    self::assertNotContains('theme.css', $extraPaths);
  }

  // -- CRITICAL_ONLY: only CRITICAL kept -----------------------------------

  public function testCriticalOnlyDropsWarnAndInfo(): void
  {
    $in = $this->mixedReport();
    $out = ScanReportFilter::filter($in, ScanReportFilter::CRITICAL_ONLY);

    // MODIFIED/MISSING are WARN → dropped.
    self::assertSame([], $out->modified);
    self::assertSame([], $out->missing);
    // Only shell.php survives.
    self::assertCount(1, $out->extra);
    self::assertSame('shell.php', $out->extra[0]->path);
  }

  // -- matchedCount preserved across all modes -----------------------------

  public function testMatchedCountUnchangedAcrossModes(): void
  {
    $in = $this->mixedReport();
    foreach ([
      ScanReportFilter::ALL,
      ScanReportFilter::WARN_AND_ABOVE,
      ScanReportFilter::CRITICAL_ONLY,
    ] as $mode) {
      self::assertSame(
        42,
        ScanReportFilter::filter($in, $mode)->matchedCount,
        "matchedCount must survive mode=$mode"
      );
    }
  }

  // -- Unknown mode falls back to ALL --------------------------------------

  public function testUnknownModeFallsBackToAll(): void
  {
    $in = $this->mixedReport();
    $out = ScanReportFilter::filter($in, 'garbage-value');

    // Same shape as ALL.
    self::assertCount(1, $out->modified);
    self::assertCount(1, $out->missing);
    self::assertCount(3, $out->extra);
  }

  public function testEmptyModeFallsBackToAll(): void
  {
    $in = $this->mixedReport();
    $out = ScanReportFilter::filter($in, '');
    self::assertCount(3, $out->extra);
  }

  // -- Returned report is a fresh instance (immutability) ------------------

  public function testReturnsNewReportInstance(): void
  {
    $in = $this->mixedReport();
    $out = ScanReportFilter::filter($in, ScanReportFilter::ALL);
    self::assertNotSame($in, $out, 'filter must return a fresh ScanReport');
    // But the data is structurally equivalent for ALL:
    self::assertSame($in->matchedCount, $out->matchedCount);
  }

  // -- Lists are list<> (zero-indexed, contiguous) after filtering ---------

  public function testFilteredListsAreContiguous(): void
  {
    $in = new ScanReport(
      [],
      [],
      [
        new ScannedFile('a.css', ScanEntryKind::EXTRA),    // INFO
        new ScannedFile('b.php', ScanEntryKind::EXTRA),    // CRITICAL
        new ScannedFile('c.html', ScanEntryKind::EXTRA),   // INFO
      ],
      0
    );

    $out = ScanReportFilter::filter($in, ScanReportFilter::WARN_AND_ABOVE);

    // b.php is the only survivor; ensure the remaining array is zero-indexed.
    self::assertSame(array_keys($out->extra), range(0, count($out->extra) - 1));
    self::assertSame('b.php', $out->extra[0]->path);
  }
}
