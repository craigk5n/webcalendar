<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ManifestBuilder;

require_once __DIR__ . '/../includes/classes/Security/ManifestBuilder.php';

/**
 * Story 2.1 — tools/build-manifest.php.
 *
 * Tests the pure-logic half (ManifestBuilder::build). The CLI wrapper
 * is a thin I/O shim; its behavior is covered by smoke tests in the
 * developer workflow and by the Epic 6 integration test.
 */
final class ManifestBuilderTest extends TestCase
{
  private string $treeRoot;

  protected function setUp(): void
  {
    $this->treeRoot = sys_get_temp_dir() . '/wc_manifest_' . bin2hex(random_bytes(6));
    mkdir($this->treeRoot, 0755, true);
  }

  protected function tearDown(): void
  {
    if (is_dir($this->treeRoot)) {
      $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->treeRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
      );
      foreach ($rii as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
      }
      rmdir($this->treeRoot);
    }
  }

  private function writeFile(string $relPath, string $content): void
  {
    $full = $this->treeRoot . '/' . $relPath;
    $dir = dirname($full);
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }
    file_put_contents($full, $content);
  }

  private function fixedTimestamp(): DateTimeImmutable
  {
    return new DateTimeImmutable('2026-04-23T12:00:00Z');
  }

  // -- Header ---------------------------------------------------------------

  public function testBuildProducesHeaderCommentsWithMetadata(): void
  {
    $this->writeFile('a.txt', 'hello');

    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['a.txt'],
      '1.9.99',
      $this->fixedTimestamp(),
      'abc1234567890'
    );

    self::assertStringContainsString('# webcalendar-version: 1.9.99', $manifest);
    self::assertStringContainsString('# build-timestamp: 2026-04-23T12:00:00Z', $manifest);
    self::assertStringContainsString('# git-sha: abc1234567890', $manifest);
  }

  public function testHeaderAppearsBeforeHashLines(): void
  {
    $this->writeFile('a.txt', 'hello');

    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['a.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );

    $lines = explode("\n", rtrim($manifest, "\n"));
    // Every non-empty line before the first hash line must be a header (# prefix).
    $firstHashIdx = null;
    foreach ($lines as $i => $line) {
      if (preg_match('/^[0-9a-f]{64}  /', $line)) {
        $firstHashIdx = $i;
        break;
      }
    }
    self::assertNotNull($firstHashIdx, 'manifest should contain at least one hash line');
    for ($i = 0; $i < $firstHashIdx; $i++) {
      self::assertStringStartsWith('#', $lines[$i], "line $i should be header");
    }
  }

  // -- Hash lines -----------------------------------------------------------

  public function testBuildHashesEachFileAndEmitsSha256sumFormat(): void
  {
    $this->writeFile('a.txt', 'alpha');
    $this->writeFile('b.txt', 'bravo');

    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['a.txt', 'b.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );

    $expectedA = hash('sha256', 'alpha');
    $expectedB = hash('sha256', 'bravo');

    self::assertStringContainsString("$expectedA  a.txt\n", $manifest);
    self::assertStringContainsString("$expectedB  b.txt\n", $manifest);
  }

  public function testHashFormatIsLowercaseHexWithExactlyTwoSpaces(): void
  {
    $this->writeFile('a.txt', 'content');

    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['a.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );

    self::assertMatchesRegularExpression(
      '/^[0-9a-f]{64}  a\.txt$/m',
      $manifest
    );
  }

  // -- Sort order -----------------------------------------------------------

  public function testHashLinesAreSortedLexicographicallyByPath(): void
  {
    $this->writeFile('zzz.txt', 'z');
    $this->writeFile('aaa.txt', 'a');
    $this->writeFile('mmm.txt', 'm');

    // Deliberately disordered input
    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['mmm.txt', 'zzz.txt', 'aaa.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );

    preg_match_all('/^[0-9a-f]{64}  (.+)$/m', $manifest, $m);
    self::assertSame(['aaa.txt', 'mmm.txt', 'zzz.txt'], $m[1]);
  }

  public function testSortIsLcAllCByteOrderNotLocaleAware(): void
  {
    // LC_ALL=C: uppercase letters sort before lowercase.
    $this->writeFile('Zulu.txt', '1');
    $this->writeFile('alpha.txt', '2');

    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['alpha.txt', 'Zulu.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );

    preg_match_all('/^[0-9a-f]{64}  (.+)$/m', $manifest, $m);
    self::assertSame(['Zulu.txt', 'alpha.txt'], $m[1]);
  }

  // -- Line endings & whitespace --------------------------------------------

  public function testManifestUsesLfLineEndingsOnly(): void
  {
    $this->writeFile('a.txt', 'x');
    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['a.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );
    self::assertStringNotContainsString("\r", $manifest);
  }

  public function testManifestEndsWithSingleLf(): void
  {
    $this->writeFile('a.txt', 'x');
    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['a.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );
    self::assertStringEndsWith("\n", $manifest);
    self::assertStringNotContainsString("\n\n\n", $manifest, 'no triple-newlines');
  }

  public function testNoTrailingWhitespaceOnAnyLine(): void
  {
    $this->writeFile('a.txt', 'x');
    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['a.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );
    foreach (explode("\n", $manifest) as $line) {
      self::assertSame(rtrim($line), $line, "line has trailing whitespace: [$line]");
    }
  }

  // -- Missing files --------------------------------------------------------

  public function testBuildThrowsOnMissingFileWithPathInMessage(): void
  {
    $this->writeFile('real.txt', 'x');
    // ghost.txt was never written.

    try {
      ManifestBuilder::build(
        $this->treeRoot,
        ['real.txt', 'ghost.txt'],
        '1.0.0',
        $this->fixedTimestamp(),
        'sha'
      );
      self::fail('Expected RuntimeException for missing file');
    } catch (RuntimeException $e) {
      self::assertStringContainsString('ghost.txt', $e->getMessage());
    }
  }

  public function testBuildThrowsOnUnreadableFile(): void
  {
    $this->writeFile('locked.txt', 'x');
    chmod($this->treeRoot . '/locked.txt', 0000);

    try {
      ManifestBuilder::build(
        $this->treeRoot,
        ['locked.txt'],
        '1.0.0',
        $this->fixedTimestamp(),
        'sha'
      );
      // Tests run as root in some CI setups, where mode 0000 doesn't block
      // reads. Gracefully skip in that case.
      if (posix_getuid() === 0) {
        self::markTestSkipped('Running as root — mode 0000 does not block reads.');
      }
      self::fail('Expected RuntimeException for unreadable file');
    } catch (RuntimeException $e) {
      self::assertStringContainsString('locked.txt', $e->getMessage());
    } finally {
      chmod($this->treeRoot . '/locked.txt', 0644);
    }
  }

  // -- Reproducibility ------------------------------------------------------

  public function testTwoRunsProduceIdenticalBytes(): void
  {
    $this->writeFile('a.txt', 'alpha');
    $this->writeFile('b/c.txt', 'nested');

    $ts = $this->fixedTimestamp();
    $first = ManifestBuilder::build($this->treeRoot, ['a.txt', 'b/c.txt'], '1.0.0', $ts, 'sha');
    $second = ManifestBuilder::build($this->treeRoot, ['a.txt', 'b/c.txt'], '1.0.0', $ts, 'sha');

    self::assertSame($first, $second);
  }

  // -- Known hash regression ------------------------------------------------

  public function testHashIsSha256ComputedOverRawFileBytes(): void
  {
    // Lock down: the hash must be sha256 of the exact file bytes,
    // not utf8-mangled, not line-ending-normalized.
    $bytes = "line1\r\nline2\n"; // mixed line endings
    $this->writeFile('mixed.txt', $bytes);
    $expected = hash('sha256', $bytes);

    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['mixed.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );

    self::assertStringContainsString("$expected  mixed.txt", $manifest);
  }

  public function testHashIsLowercaseHexExactly64Chars(): void
  {
    $this->writeFile('a.txt', 'anything');
    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['a.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );
    // Every hash line's prefix must match [0-9a-f]{64}.
    preg_match_all('/^(\S+)  (.+)$/m', $manifest, $m, PREG_SET_ORDER);
    self::assertNotEmpty($m);
    foreach ($m as $row) {
      self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $row[1]);
    }
  }

  // -- Nested paths ---------------------------------------------------------

  public function testNestedPathsAreEmittedAsIsWithForwardSlashes(): void
  {
    $this->writeFile('dir/sub/file.txt', 'nested');
    $manifest = ManifestBuilder::build(
      $this->treeRoot,
      ['dir/sub/file.txt'],
      '1.0.0',
      $this->fixedTimestamp(),
      'sha'
    );
    self::assertMatchesRegularExpression(
      '/^[0-9a-f]{64}  dir\/sub\/file\.txt$/m',
      $manifest
    );
  }
}
