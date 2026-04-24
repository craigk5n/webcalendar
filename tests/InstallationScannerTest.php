<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ExcludeRules;
use WebCalendar\Security\InstallationScanner;
use WebCalendar\Security\ManifestData;
use WebCalendar\Security\ScanEntryKind;
use WebCalendar\Security\ScannedFile;
use WebCalendar\Security\ScanReport;

require_once __DIR__ . '/../includes/classes/Security/ManifestData.php';
require_once __DIR__ . '/../includes/classes/Security/ScanEntryKind.php';
require_once __DIR__ . '/../includes/classes/Security/ScannedFile.php';
require_once __DIR__ . '/../includes/classes/Security/ScanReport.php';
require_once __DIR__ . '/../includes/classes/Security/ExcludeRules.php';
require_once __DIR__ . '/../includes/classes/Security/InstallationScanner.php';

/**
 * Story 3.3 — InstallationScanner class.
 *
 * Walks the filesystem against a ManifestData and classifies every
 * observed file as MATCH / MODIFIED / MISSING / EXTRA. Each test sets
 * up a fresh install tree under /tmp/wc_scan_* and mutates it to
 * exercise one failure mode.
 */
final class InstallationScannerTest extends TestCase
{
  private string $root;

  protected function setUp(): void
  {
    $this->root = sys_get_temp_dir() . '/wc_scan_' . bin2hex(random_bytes(6));
    mkdir($this->root, 0755, true);
  }

  protected function tearDown(): void
  {
    if (!is_dir($this->root)) {
      return;
    }
    $rii = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($rii as $f) {
      $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($this->root);
  }

  private function write(string $relPath, string $contents): void
  {
    $full = $this->root . '/' . $relPath;
    if (!is_dir(dirname($full))) {
      mkdir(dirname($full), 0755, true);
    }
    file_put_contents($full, $contents);
  }

  /**
   * @param array<string, string> $files  relpath => contents
   */
  private function manifestFor(array $files): ManifestData
  {
    $hashes = [];
    foreach ($files as $path => $contents) {
      $hashes[$path] = hash('sha256', $contents);
    }
    return new ManifestData(
      '1.0.0',
      new DateTimeImmutable('2026-04-23T12:00:00Z'),
      'sha',
      $hashes
    );
  }

  // -- Happy path -----------------------------------------------------------

  public function testCleanInstallProducesEmptyReport(): void
  {
    $this->write('a.php', 'alpha');
    $this->write('b.txt', 'beta');
    $this->write('nested/c.php', 'gamma');

    $manifest = $this->manifestFor([
      'a.php' => 'alpha',
      'b.txt' => 'beta',
      'nested/c.php' => 'gamma',
    ]);

    $report = InstallationScanner::scan($manifest, $this->root, new ExcludeRules([]));

    self::assertInstanceOf(ScanReport::class, $report);
    self::assertSame(3, $report->matchedCount);
    self::assertSame([], $report->modified);
    self::assertSame([], $report->missing);
    self::assertSame([], $report->extra);
  }

  // -- MISSING -------------------------------------------------------------

  public function testDeletedFileIsReportedAsMissing(): void
  {
    $this->write('a.php', 'alpha');
    // b.php listed but not written.

    $manifest = $this->manifestFor([
      'a.php' => 'alpha',
      'b.php' => 'bravo',
    ]);

    $report = InstallationScanner::scan($manifest, $this->root, new ExcludeRules([]));

    self::assertCount(1, $report->missing);
    self::assertSame('b.php', $report->missing[0]->path);
    self::assertSame(ScanEntryKind::MISSING, $report->missing[0]->kind);
    self::assertSame(hash('sha256', 'bravo'), $report->missing[0]->expectedHash);
    self::assertNull($report->missing[0]->actualHash);
    self::assertSame(1, $report->matchedCount);
  }

  // -- MODIFIED ------------------------------------------------------------

  public function testModifiedFileIsReportedAsModifiedWithBothHashes(): void
  {
    $this->write('a.php', 'SURPRISE');

    $manifest = $this->manifestFor(['a.php' => 'original']);

    $report = InstallationScanner::scan($manifest, $this->root, new ExcludeRules([]));

    self::assertCount(1, $report->modified);
    self::assertSame('a.php', $report->modified[0]->path);
    self::assertSame(ScanEntryKind::MODIFIED, $report->modified[0]->kind);
    self::assertSame(hash('sha256', 'original'), $report->modified[0]->expectedHash);
    self::assertSame(hash('sha256', 'SURPRISE'), $report->modified[0]->actualHash);
    self::assertSame(0, $report->matchedCount);
  }

  // -- EXTRA ---------------------------------------------------------------

  public function testUnexpectedFileIsReportedAsExtra(): void
  {
    $this->write('a.php', 'alpha');
    $this->write('evil.php', '<?php system($_GET["c"]); ?>');

    $manifest = $this->manifestFor(['a.php' => 'alpha']);

    $report = InstallationScanner::scan($manifest, $this->root, new ExcludeRules([]));

    self::assertCount(1, $report->extra);
    self::assertSame('evil.php', $report->extra[0]->path);
    self::assertSame(ScanEntryKind::EXTRA, $report->extra[0]->kind);
    self::assertSame(1, $report->matchedCount);
  }

  public function testExtraInNestedDirectoryReportedWithRelPath(): void
  {
    $this->write('includes/classes/legit.php', 'x');
    $this->write('includes/classes/shell.php', 'evil');

    $manifest = $this->manifestFor([
      'includes/classes/legit.php' => 'x',
    ]);

    $report = InstallationScanner::scan($manifest, $this->root, new ExcludeRules([]));

    self::assertCount(1, $report->extra);
    self::assertSame('includes/classes/shell.php', $report->extra[0]->path);
  }

  // -- Exclusions ----------------------------------------------------------

  public function testExcludedFileIsNotReportedAsExtra(): void
  {
    $this->write('a.php', 'alpha');
    $this->write('includes/settings.php', 'site-specific');

    $manifest = $this->manifestFor(['a.php' => 'alpha']);
    $excludes = new ExcludeRules(['includes/settings.php']);

    $report = InstallationScanner::scan($manifest, $this->root, $excludes);

    self::assertSame([], $report->extra);
  }

  public function testExcludedManifestEntryIsNotReportedAsMissing(): void
  {
    // If includes/settings.php is in the manifest AND excluded, absence
    // should not be reported. This shouldn't happen in practice (the
    // installer-generated settings.php isn't shipped) but we handle it
    // defensively.
    $this->write('a.php', 'alpha');
    $manifest = $this->manifestFor([
      'a.php' => 'alpha',
      'includes/settings.php' => 'would-be-shipped',
    ]);
    $excludes = new ExcludeRules(['includes/settings.php']);

    $report = InstallationScanner::scan($manifest, $this->root, $excludes);

    self::assertSame([], $report->missing);
    self::assertSame([], $report->extra);
    self::assertSame(1, $report->matchedCount);
  }

  public function testDirectoryGlobExcludesEverythingInside(): void
  {
    $this->write('a.php', 'alpha');
    $this->write('tests/some-test.php', 'test');
    $this->write('tests/fixtures/nested.txt', 'fixture');
    $this->write('.git/HEAD', 'ref: refs/heads/main');

    $manifest = $this->manifestFor(['a.php' => 'alpha']);
    $excludes = new ExcludeRules(['tests/', '.git/']);

    $report = InstallationScanner::scan($manifest, $this->root, $excludes);

    self::assertSame([], $report->extra);
  }

  public function testStarGlobExcludesMatchingExtension(): void
  {
    $this->write('a.php', 'alpha');
    $this->write('pub/css/custom.css', 'body{}');
    $this->write('pub/js/app.js', 'js');

    $manifest = $this->manifestFor(['a.php' => 'alpha']);
    $excludes = new ExcludeRules(['pub/css/*.css']);

    $report = InstallationScanner::scan($manifest, $this->root, $excludes);

    // pub/css/custom.css excluded, pub/js/app.js still shows as EXTRA.
    self::assertCount(1, $report->extra);
    self::assertSame('pub/js/app.js', $report->extra[0]->path);
  }

  // -- Symlinks ------------------------------------------------------------

  public function testSymlinkInsideTreeReportedAsExtra(): void
  {
    $this->write('a.php', 'alpha');
    // Target points outside — iterator MUST NOT follow.
    $outsideTarget = sys_get_temp_dir() . '/wc_scan_symlink_target_' . bin2hex(random_bytes(3));
    file_put_contents($outsideTarget, 'outside');
    symlink($outsideTarget, $this->root . '/evil-link');

    try {
      $manifest = $this->manifestFor(['a.php' => 'alpha']);
      $report = InstallationScanner::scan($manifest, $this->root, new ExcludeRules([]));

      self::assertCount(1, $report->extra);
      self::assertSame('evil-link', $report->extra[0]->path);
    } finally {
      @unlink($this->root . '/evil-link');
      @unlink($outsideTarget);
    }
  }

  public function testScannerDoesNotFollowSymlinkedDirectory(): void
  {
    // Create a side-tree with a PHP file in it. Symlink to that dir
    // from inside the scan root. Scanner MUST NOT descend.
    $this->write('a.php', 'alpha');
    $sideDir = sys_get_temp_dir() . '/wc_scan_side_' . bin2hex(random_bytes(3));
    mkdir($sideDir, 0755, true);
    file_put_contents($sideDir . '/trap.php', 'if i get scanned the test fails');
    symlink($sideDir, $this->root . '/side-link');

    try {
      $manifest = $this->manifestFor(['a.php' => 'alpha']);
      $report = InstallationScanner::scan($manifest, $this->root, new ExcludeRules([]));

      // side-link itself is one EXTRA entry (the symlink file), NOT
      // side-link/trap.php (which would require following the symlink).
      $extraPaths = array_map(fn(ScannedFile $f) => $f->path, $report->extra);
      self::assertContains('side-link', $extraPaths);
      self::assertNotContains('side-link/trap.php', $extraPaths);
    } finally {
      @unlink($this->root . '/side-link');
      @unlink($sideDir . '/trap.php');
      @rmdir($sideDir);
    }
  }

  // -- Mixed scenario -------------------------------------------------------

  public function testRealisticMixedReport(): void
  {
    // Clean file
    $this->write('clean.php', 'ok');
    // Modified file
    $this->write('tampered.php', 'hacked');
    // Extra file
    $this->write('shell.php', 'malware');
    // Missing: admin.php in manifest but not on disk
    // Excluded: includes/settings.php present but excluded

    $this->write('includes/settings.php', 'site');

    $manifest = $this->manifestFor([
      'clean.php' => 'ok',
      'tampered.php' => 'original',
      'admin.php' => 'admin-original',
    ]);
    $excludes = new ExcludeRules(['includes/settings.php']);

    $report = InstallationScanner::scan($manifest, $this->root, $excludes);

    self::assertSame(1, $report->matchedCount, 'only clean.php matches');
    self::assertCount(1, $report->modified);
    self::assertSame('tampered.php', $report->modified[0]->path);
    self::assertCount(1, $report->missing);
    self::assertSame('admin.php', $report->missing[0]->path);
    self::assertCount(1, $report->extra);
    self::assertSame('shell.php', $report->extra[0]->path);
  }

  // -- Install root validation ---------------------------------------------

  public function testThrowsIfInstallRootDoesNotExist(): void
  {
    $manifest = $this->manifestFor(['a.php' => 'x']);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/install root/i');
    InstallationScanner::scan(
      $manifest,
      $this->root . '/does-not-exist',
      new ExcludeRules([])
    );
  }

  public function testEmptyInstallRootReportsAllManifestEntriesAsMissing(): void
  {
    $manifest = $this->manifestFor([
      'a.php' => 'x',
      'b.php' => 'y',
    ]);

    $report = InstallationScanner::scan($manifest, $this->root, new ExcludeRules([]));

    self::assertCount(2, $report->missing);
    self::assertSame(0, $report->matchedCount);
  }

  // -- Immutability --------------------------------------------------------

  public function testScannedFileIsImmutable(): void
  {
    $f = new ScannedFile('x.php', ScanEntryKind::EXTRA);
    self::assertSame('x.php', $f->path);

    $this->expectException(Error::class);
    /** @phpstan-ignore-next-line */
    $f->path = 'y.php';
  }

  public function testScanReportIsImmutable(): void
  {
    $r = new ScanReport([], [], [], 0);
    self::assertSame(0, $r->matchedCount);

    $this->expectException(Error::class);
    /** @phpstan-ignore-next-line */
    $r->matchedCount = 99;
  }
}
