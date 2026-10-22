<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ManifestData;
use WebCalendar\Security\ManifestParser;

require_once __DIR__ . '/../includes/classes/Security/ManifestData.php';
require_once __DIR__ . '/../includes/classes/Security/ManifestParser.php';

/**
 * Story 3.2 — ManifestParser class.
 *
 * Parses a MANIFEST.sha256 file (already signature-verified by
 * Story 3.1) into an immutable ManifestData value object.
 *
 * Tests write fixtures to temp files so the full read-and-parse
 * path is exercised. Each test writes its own fixture in setUp's
 * `writeManifest()` helper.
 */
final class ManifestParserTest extends TestCase
{
  private string $tmpDir;

  protected function setUp(): void
  {
    $this->tmpDir = sys_get_temp_dir() . '/wc_parser_' . bin2hex(random_bytes(6));
    mkdir($this->tmpDir, 0755, true);
  }

  protected function tearDown(): void
  {
    if (!is_dir($this->tmpDir)) {
      return;
    }
    $rii = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(
        $this->tmpDir,
        FilesystemIterator::SKIP_DOTS
      ),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($rii as $f) {
      $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($this->tmpDir);
  }

  private function writeManifest(string $body): string
  {
    $path = $this->tmpDir . '/MANIFEST.sha256';
    file_put_contents($path, $body);
    return $path;
  }

  private function h(string $content): string
  {
    return hash('sha256', $content);
  }

  // -- Happy path -----------------------------------------------------------

  public function testParsesWellFormedManifest(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.9.16\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: deadbeef1234567890\n"
      . $this->h('a') . "  a.txt\n"
      . $this->h('b') . "  b.txt\n"
    );

    $data = ManifestParser::parse($path);

    self::assertInstanceOf(ManifestData::class, $data);
    self::assertSame('1.9.16', $data->version);
    self::assertSame(
      '2026-04-23T12:00:00+00:00',
      $data->buildTimestamp->format('c')
    );
    self::assertSame('deadbeef1234567890', $data->gitSha);
    self::assertCount(2, $data->hashes);
    self::assertSame($this->h('a'), $data->hashes['a.txt']);
    self::assertSame($this->h('b'), $data->hashes['b.txt']);
  }

  public function testManifestDataIsImmutable(): void
  {
    $data = new ManifestData(
      '1.0.0',
      new DateTimeImmutable('2026-01-01T00:00:00Z'),
      'sha',
      ['a.txt' => str_repeat('0', 64)]
    );
    self::assertSame('1.0.0', $data->version);

    $this->expectException(Error::class);
    /** @phpstan-ignore-next-line */
    $data->version = 'oops';
  }

  public function testHeaderOrderIsNotRequired(): void
  {
    // Spec says header contains these three fields; order shouldn't matter.
    $path = $this->writeManifest(
      "# git-sha: abc\n"
      . "# webcalendar-version: 2.0.0\n"
      . "# build-timestamp: 2026-05-01T00:00:00Z\n"
      . $this->h('x') . "  x.txt\n"
    );
    $data = ManifestParser::parse($path);
    self::assertSame('2.0.0', $data->version);
    self::assertSame('abc', $data->gitSha);
  }

  public function testIgnoresUnknownHeaderKeysForwardCompatibility(): void
  {
    // Future manifest might add fields; don't crash on forward unknowns.
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . "# future-field: something\n"
      . $this->h('x') . "  x.txt\n"
    );
    $data = ManifestParser::parse($path);
    self::assertSame('1.0.0', $data->version);
  }

  public function testHandlesNestedPaths(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "  includes/classes/Event.php\n"
      . $this->h('y') . "  pub/css/app.css\n"
    );
    $data = ManifestParser::parse($path);
    self::assertArrayHasKey('includes/classes/Event.php', $data->hashes);
    self::assertArrayHasKey('pub/css/app.css', $data->hashes);
  }

  public function testHandlesPathsContainingSpaces(): void
  {
    // sha256sum format uses "<hash>  <path>" — two spaces as separator.
    // Everything after those two spaces is the path, including any
    // subsequent spaces. Real WebCalendar paths don't have spaces, but
    // be correct anyway.
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "  path with spaces.txt\n"
    );
    $data = ManifestParser::parse($path);
    self::assertArrayHasKey('path with spaces.txt', $data->hashes);
  }

  // -- Malformed hash lines -------------------------------------------------

  public function testRejectsThreeSpacesBetweenHashAndPath(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "   x.txt\n"  // 3 spaces
    );
    try {
      ManifestParser::parse($path);
      self::fail('Expected RuntimeException');
    } catch (RuntimeException $e) {
      self::assertStringContainsString('line 4', $e->getMessage());
    }
  }

  public function testRejectsTabInsteadOfSpaces(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "\tx.txt\n"
    );
    $this->expectException(RuntimeException::class);
    ManifestParser::parse($path);
  }

  public function testRejectsUppercaseHexInHash(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . strtoupper(str_repeat('a', 64)) . "  x.txt\n"
    );
    $this->expectException(RuntimeException::class);
    ManifestParser::parse($path);
  }

  public function testRejectsShortHash(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . str_repeat('a', 63) . "  x.txt\n"
    );
    $this->expectException(RuntimeException::class);
    ManifestParser::parse($path);
  }

  public function testRejectsNonHexCharacterInHash(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . str_repeat('g', 64) . "  x.txt\n"
    );
    $this->expectException(RuntimeException::class);
    ManifestParser::parse($path);
  }

  public function testRejectsEmptyPath(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "  \n"
    );
    $this->expectException(RuntimeException::class);
    ManifestParser::parse($path);
  }

  public function testRejectsDuplicatePathWithLineNumber(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . $this->h('a') . "  dup.txt\n"
      . $this->h('b') . "  dup.txt\n"
    );
    try {
      ManifestParser::parse($path);
      self::fail('Expected RuntimeException');
    } catch (RuntimeException $e) {
      self::assertStringContainsString('dup.txt', $e->getMessage());
      self::assertStringContainsString('line 5', $e->getMessage());
    }
  }

  public function testRejectsBlankLinesInsideManifest(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . "\n"
      . $this->h('x') . "  x.txt\n"
    );
    $this->expectException(RuntimeException::class);
    ManifestParser::parse($path);
  }

  public function testAcceptsSingleTrailingLfArtifact(): void
  {
    // Splitting on \n produces an empty final element when the file
    // ends with LF; that's the expected canonical form.
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "  x.txt\n"
    );
    $data = ManifestParser::parse($path);
    self::assertCount(1, $data->hashes);
  }

  // -- Missing / malformed headers -----------------------------------------

  public function testRejectsMissingVersionHeader(): void
  {
    $path = $this->writeManifest(
      "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "  x.txt\n"
    );
    try {
      ManifestParser::parse($path);
      self::fail('Expected RuntimeException');
    } catch (RuntimeException $e) {
      self::assertStringContainsString('webcalendar-version', $e->getMessage());
    }
  }

  public function testRejectsMissingTimestampHeader(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "  x.txt\n"
    );
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/build-timestamp/');
    ManifestParser::parse($path);
  }

  public function testRejectsMissingGitShaHeader(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . $this->h('x') . "  x.txt\n"
    );
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/git-sha/');
    ManifestParser::parse($path);
  }

  public function testRejectsMalformedTimestamp(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: not-a-date\n"
      . "# git-sha: sha\n"
      . $this->h('x') . "  x.txt\n"
    );
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/timestamp/i');
    ManifestParser::parse($path);
  }

  // -- Empty manifest -------------------------------------------------------

  public function testRejectsManifestWithNoHashLines(): void
  {
    $path = $this->writeManifest(
      "# webcalendar-version: 1.0.0\n"
      . "# build-timestamp: 2026-04-23T12:00:00Z\n"
      . "# git-sha: sha\n"
    );
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/no hash|no entries|empty/i');
    ManifestParser::parse($path);
  }

  // -- File-level errors ---------------------------------------------------

  public function testRejectsMissingFile(): void
  {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/not exist|cannot read/i');
    ManifestParser::parse($this->tmpDir . '/does-not-exist');
  }

  // -- Round-trip with ManifestBuilder -------------------------------------

  public function testRoundTripsWithManifestBuilder(): void
  {
    // The parser must accept whatever the builder produces. Lock this
    // contract down so future changes to either side don't drift apart.
    require_once __DIR__ . '/../includes/classes/Security/ManifestBuilder.php';

    $fixtureRoot = $this->tmpDir . '/tree';
    mkdir($fixtureRoot, 0755, true);
    file_put_contents($fixtureRoot . '/alpha.txt', 'A');
    mkdir($fixtureRoot . '/sub', 0755, true);
    file_put_contents($fixtureRoot . '/sub/beta.txt', 'B');

    $manifestText = WebCalendar\Security\ManifestBuilder::build(
      $fixtureRoot,
      ['alpha.txt', 'sub/beta.txt'],
      '1.9.16',
      new DateTimeImmutable('2026-04-23T12:00:00Z'),
      'abc123'
    );

    $manifestPath = $this->tmpDir . '/round-trip.sha256';
    file_put_contents($manifestPath, $manifestText);

    $data = ManifestParser::parse($manifestPath);
    self::assertSame('1.9.16', $data->version);
    self::assertSame('abc123', $data->gitSha);
    self::assertCount(2, $data->hashes);
    self::assertSame(hash('sha256', 'A'), $data->hashes['alpha.txt']);
    self::assertSame(hash('sha256', 'B'), $data->hashes['sub/beta.txt']);
  }
}
