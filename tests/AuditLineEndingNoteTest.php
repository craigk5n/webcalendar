<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ExcludeRules;
use WebCalendar\Security\InstallationScanner;
use WebCalendar\Security\ManifestData;
use WebCalendar\Security\ScanEntryKind;
use WebCalendar\Security\Severity;
use WebCalendar\Security\SeverityClassifier;

/**
 * A file that matches the manifest once line endings are normalised is reported
 * as such.
 *
 * Reported as issue #788 against v1.9.24: `includes/zone.tab` shipped with CRLF
 * on all 407 lines, an extraction converted it to LF, and Reports > Security
 * Audit then reported a modified file on an installation where nothing had been
 * modified. The reporter's hash was ours with the CRs stripped, and it took two
 * hashes and a size comparison to establish that.
 *
 * The finding is deliberately *not* suppressed. The file does differ from what
 * was signed, and hiding it would be the wrong trade. What changes is that the
 * page says which kind of difference it is, because "restore from the release
 * zip" is the wrong next step here, and an unexplained warning on an untouched
 * installation is how admins learn to ignore the page.
 */
final class AuditLineEndingNoteTest extends TestCase
{
  private string $root;

  protected function setUp(): void
  {
    $this->root = sys_get_temp_dir() . '/wc-le-' . bin2hex(random_bytes(6));
    mkdir($this->root, 0700, true);
  }

  protected function tearDown(): void
  {
    if (!is_dir($this->root)) {
      return;
    }
    $it = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
      $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($this->root);
  }

  private function write(string $rel, string $contents): void
  {
    $path = $this->root . '/' . $rel;
    @mkdir(dirname($path), 0700, true);
    file_put_contents($path, $contents);
  }

  /**
   * @param array<string, string> $contentsByPath keyed by path, value hashed
   */
  private function manifestFor(array $contentsByPath): ManifestData
  {
    $hashes = [];
    foreach ($contentsByPath as $rel => $contents) {
      $hashes[$rel] = hash('sha256', $contents);
    }

    return new ManifestData(
      '1.9.24',
      new DateTimeImmutable('2026-09-26T12:07:07Z'),
      'b805e12167a0b8be1c2d2367f43b7e5193580111',
      $hashes
    );
  }

  private function scan(): WebCalendar\Security\ScanReport
  {
    return InstallationScanner::scan(
      $this->manifestFor($this->manifest),
      $this->root,
      new ExcludeRules([])
    );
  }

  /** @var array<string, string> */
  private array $manifest = [];

  /**
   * The exact shape of #788: shipped CRLF, installed LF.
   */
  public function testCrlfShippedAndLfOnDiskIsAnnotated(): void
  {
    $shipped = "AD\t+4230+00131\tEurope/Andorra\r\nAE\t+2518+05518\tAsia/Dubai\r\n";
    $this->manifest = ['includes/zone.tab' => $shipped];
    $this->write('includes/zone.tab', str_replace("\r\n", "\n", $shipped));

    $report = $this->scan();

    self::assertCount(1, $report->modified);
    self::assertSame('includes/zone.tab', $report->modified[0]->path);
    self::assertTrue($report->modified[0]->lineEndingsOnly,
      'a CRLF-shipped file converted to LF must be annotated, which is #788');
  }

  /**
   * And the other direction, which is what a Windows editor or checkout does to
   * a file shipped with LF.
   */
  public function testLfShippedAndCrlfOnDiskIsAnnotated(): void
  {
    $shipped = "<?php\n// a comment\necho 1;\n";
    $this->manifest = ['admin.php' => $shipped];
    $this->write('admin.php', str_replace("\n", "\r\n", $shipped));

    $report = $this->scan();

    self::assertCount(1, $report->modified);
    self::assertTrue($report->modified[0]->lineEndingsOnly);
  }

  /**
   * The case that must not be softened: real tampering.
   */
  public function testGenuineModificationIsNotAnnotated(): void
  {
    $this->manifest = ['admin.php' => "<?php\necho 1;\n"];
    $this->write('admin.php', "<?php\nsystem(\$_GET['c']);\n");

    $report = $this->scan();

    self::assertCount(1, $report->modified);
    self::assertFalse($report->modified[0]->lineEndingsOnly,
      'a webshell must never be described as a line-ending difference');
  }

  /**
   * Tampering that also changes line endings is still tampering.
   */
  public function testContentChangePlusLineEndingChangeIsNotAnnotated(): void
  {
    $this->manifest = ['admin.php' => "<?php\necho 1;\n"];
    $this->write('admin.php', "<?php\r\nsystem(\$_GET['c']);\r\n");

    $report = $this->scan();

    self::assertCount(1, $report->modified);
    self::assertFalse($report->modified[0]->lineEndingsOnly);
  }

  /**
   * Severity is unchanged. The annotation explains a finding; it does not
   * reclassify it, because the file genuinely differs from what was signed.
   */
  public function testSeverityIsUnchangedByTheAnnotation(): void
  {
    $shipped = "a\r\nb\r\n";
    $this->manifest = ['notes.txt' => $shipped];
    $this->write('notes.txt', "a\nb\n");

    $report = $this->scan();

    self::assertTrue($report->modified[0]->lineEndingsOnly);
    self::assertSame(Severity::WARN,
      SeverityClassifier::classify($report->modified[0]),
      'still a finding at the same severity; only the advice differs');
    self::assertSame(ScanEntryKind::MODIFIED, $report->modified[0]->kind);
  }

  /**
   * A matching file is still just a match — the check must not create findings.
   */
  public function testAnUnchangedFileIsStillAMatch(): void
  {
    $shipped = "a\r\nb\r\n";
    $this->manifest = ['notes.txt' => $shipped];
    $this->write('notes.txt', $shipped);

    $report = $this->scan();

    self::assertSame([], $report->modified);
    self::assertSame(1, $report->matchedCount);
  }

  /**
   * Binary files are skipped: a GIF differing in a byte that happens to be \r
   * is not a line-ending difference, and treating it as one would describe a
   * tampered image as harmless.
   */
  public function testBinaryContentIsNeverAnnotated(): void
  {
    // A NUL byte plus something that looks like a CRLF.
    $shipped = "GIF89a\x00\x01\r\n\x00payload";
    $this->manifest = ['images/x.gif' => $shipped];
    $this->write('images/x.gif', str_replace("\r\n", "\n", $shipped));

    $report = $this->scan();

    self::assertCount(1, $report->modified);
    self::assertFalse($report->modified[0]->lineEndingsOnly,
      'content with a NUL byte is binary; line endings are not meaningful');
  }

  /**
   * The advice an admin actually reads, produced by running the real function
   * rather than by matching its source.
   *
   * action_hint_for_file() is lifted out of security_audit.php by walking its
   * braces -- the technique ExportAccessFilterTest uses on includes/xcal.php --
   * with translate() stubbed to the identity, so what is asserted is the string
   * the page renders.
   *
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testTheRenderedHintDistinguishesTheTwoCases(): void
  {
    require_once __DIR__ . '/SourceText.php';
    // Separate process, global state not preserved, so the autoloader that
    // tests/bootstrap.php sets up is not in play here.
    require_once __DIR__ . '/../includes/classes/Security/ScanEntryKind.php';
    require_once __DIR__ . '/../includes/classes/Security/ScannedFile.php';

    if (!function_exists('translate')) {
      eval('function translate($s, $x = false) { return $s; }');
    }

    $src = file_get_contents(__DIR__ . '/../security_audit.php');
    self::assertIsString($src);

    foreach (['action_hint_for', 'action_hint_for_file'] as $name) {
      if (function_exists($name)) {
        continue;
      }
      $fn = SourceText::phpFunction($src, $name);
      self::assertIsString($fn, "security_audit.php must define $name()");
      eval($fn);
    }

    $converted = new WebCalendar\Security\ScannedFile(
      'includes/zone.tab', ScanEntryKind::MODIFIED, 'aaa', 'bbb', true
    );
    $tampered = new WebCalendar\Security\ScannedFile(
      'admin.php', ScanEntryKind::MODIFIED, 'aaa', 'bbb', false
    );

    $convertedHint = action_hint_for_file($converted);
    $tamperedHint = action_hint_for_file($tampered);

    self::assertNotSame($convertedHint, $tamperedHint,
      'the two cases must not read identically -- that is the whole point');
    self::assertMatchesRegularExpression('/line endings/i', $convertedHint);
    self::assertMatchesRegularExpression('/no action needed/i', $convertedHint,
      'the admin needs to be told there is nothing to do');
    self::assertMatchesRegularExpression('/restore/i', $tamperedHint,
      'a genuine modification still says to restore the file');
    self::assertDoesNotMatchRegularExpression('/line endings/i', $tamperedHint,
      'and must not mention line endings, which would excuse it');
  }

  /**
   * The table has to call the per-file hint, or the annotation never reaches
   * the page however well the scanner computes it.
   */
  public function testTheTableUsesThePerFileHint(): void
  {
    require_once __DIR__ . '/SourceText.php';

    $src = file_get_contents(__DIR__ . '/../security_audit.php');
    self::assertIsString($src);

    $body = SourceText::phpFunctionBody(SourceText::php($src),
      'render_integrity_table');
    self::assertIsString($body,
      'security_audit.php must define render_integrity_table()');

    self::assertStringContainsString('action_hint_for_file(', $body,
      'render_integrity_table() must use the per-file hint');
  }
}
