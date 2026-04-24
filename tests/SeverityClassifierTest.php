<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ScanEntryKind;
use WebCalendar\Security\ScannedFile;
use WebCalendar\Security\Severity;
use WebCalendar\Security\SeverityClassifier;

require_once __DIR__ . '/../includes/classes/Security/ScanEntryKind.php';
require_once __DIR__ . '/../includes/classes/Security/ScannedFile.php';
require_once __DIR__ . '/../includes/classes/Security/Severity.php';
require_once __DIR__ . '/../includes/classes/Security/SeverityClassifier.php';

/**
 * Story 3.4 — Severity classifier.
 *
 * Tags each ScannedFile with CRITICAL / WARN / INFO so the admin UI
 * (Story 3.5) can prioritize display and the noise filter (Story 4.2)
 * can suppress low-severity findings.
 */
final class SeverityClassifierTest extends TestCase
{
  // -- EXTRA + executable extensions → CRITICAL ----------------------------

  public function testExtraPhpIsCritical(): void
  {
    self::assertSame(
      Severity::CRITICAL,
      SeverityClassifier::classify(
        new ScannedFile('shell.php', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraPhtmlIsCritical(): void
  {
    self::assertSame(
      Severity::CRITICAL,
      SeverityClassifier::classify(
        new ScannedFile('x.phtml', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraPharIsCritical(): void
  {
    self::assertSame(
      Severity::CRITICAL,
      SeverityClassifier::classify(
        new ScannedFile('evil.phar', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraIncIsCritical(): void
  {
    self::assertSame(
      Severity::CRITICAL,
      SeverityClassifier::classify(
        new ScannedFile('legacy.inc', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraPhpExtensionIsCaseInsensitive(): void
  {
    // Attacker might upload Shell.PHP to dodge naive pattern matching.
    self::assertSame(
      Severity::CRITICAL,
      SeverityClassifier::classify(
        new ScannedFile('Shell.PHP', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraPhpInNestedPathIsCritical(): void
  {
    self::assertSame(
      Severity::CRITICAL,
      SeverityClassifier::classify(
        new ScannedFile('pub/uploads/malware.php', ScanEntryKind::EXTRA)
      )
    );
  }

  // -- EXTRA + non-executable extensions → INFO ----------------------------

  public function testExtraCssIsInfo(): void
  {
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify(
        new ScannedFile('custom.css', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraHtmlIsInfo(): void
  {
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify(
        new ScannedFile('readme.html', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraTextIsInfo(): void
  {
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify(
        new ScannedFile('notes.txt', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraMarkdownIsInfo(): void
  {
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify(
        new ScannedFile('NOTES.md', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraSourceMapIsInfo(): void
  {
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify(
        new ScannedFile('app.js.map', ScanEntryKind::EXTRA)
      )
    );
  }

  /**
   * @dataProvider provideImageExtensions
   */
  public function testExtraImageIsInfo(string $path): void
  {
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify(
        new ScannedFile($path, ScanEntryKind::EXTRA)
      )
    );
  }

  public static function provideImageExtensions(): iterable
  {
    yield 'png' => ['logo.png'];
    yield 'jpg' => ['photo.jpg'];
    yield 'jpeg' => ['photo.jpeg'];
    yield 'gif' => ['anim.gif'];
    yield 'svg' => ['icon.svg'];
    yield 'ico' => ['favicon.ico'];
    yield 'webp' => ['img.webp'];
  }

  public function testExtraJsonIsInfo(): void
  {
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify(
        new ScannedFile('data.json', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraJsIsInfo(): void
  {
    // JS is executable client-side but NOT a webshell vector for PHP.
    self::assertSame(
      Severity::INFO,
      SeverityClassifier::classify(
        new ScannedFile('app.js', ScanEntryKind::EXTRA)
      )
    );
  }

  // -- EXTRA + unknown extension → WARN ------------------------------------

  public function testExtraUnknownExtensionIsWarn(): void
  {
    self::assertSame(
      Severity::WARN,
      SeverityClassifier::classify(
        new ScannedFile('foo.xyz', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraWithNoExtensionIsWarn(): void
  {
    self::assertSame(
      Severity::WARN,
      SeverityClassifier::classify(
        new ScannedFile('mysteryfile', ScanEntryKind::EXTRA)
      )
    );
  }

  public function testExtraDoubleExtensionLooksAtFinalExtension(): void
  {
    // shell.php.bak — final ext is .bak (unknown) → WARN, not CRITICAL.
    // Attackers DO use this trick, but Apache config determines whether
    // .bak runs as PHP. Being conservative with WARN is correct.
    self::assertSame(
      Severity::WARN,
      SeverityClassifier::classify(
        new ScannedFile('shell.php.bak', ScanEntryKind::EXTRA)
      )
    );
  }

  // -- MODIFIED → WARN regardless of extension -----------------------------

  public function testModifiedPhpIsWarn(): void
  {
    self::assertSame(
      Severity::WARN,
      SeverityClassifier::classify(
        new ScannedFile(
          'admin.php',
          ScanEntryKind::MODIFIED,
          str_repeat('0', 64),
          str_repeat('1', 64)
        )
      )
    );
  }

  public function testModifiedCssIsWarn(): void
  {
    self::assertSame(
      Severity::WARN,
      SeverityClassifier::classify(
        new ScannedFile(
          'pub/style.css',
          ScanEntryKind::MODIFIED,
          str_repeat('0', 64),
          str_repeat('1', 64)
        )
      )
    );
  }

  public function testModifiedUnknownExtensionIsWarn(): void
  {
    self::assertSame(
      Severity::WARN,
      SeverityClassifier::classify(
        new ScannedFile(
          'foo.xyz',
          ScanEntryKind::MODIFIED,
          str_repeat('0', 64),
          str_repeat('1', 64)
        )
      )
    );
  }

  // -- MISSING → WARN regardless of extension ------------------------------

  public function testMissingPhpIsWarn(): void
  {
    self::assertSame(
      Severity::WARN,
      SeverityClassifier::classify(
        new ScannedFile(
          'login.php',
          ScanEntryKind::MISSING,
          str_repeat('0', 64),
          null
        )
      )
    );
  }

  public function testMissingCssIsWarn(): void
  {
    self::assertSame(
      Severity::WARN,
      SeverityClassifier::classify(
        new ScannedFile(
          'pub/style.css',
          ScanEntryKind::MISSING,
          str_repeat('0', 64),
          null
        )
      )
    );
  }

  // -- Severity enum sanity -----------------------------------------------

  public function testSeverityEnumValues(): void
  {
    self::assertSame('critical', Severity::CRITICAL->value);
    self::assertSame('warn', Severity::WARN->value);
    self::assertSame('info', Severity::INFO->value);
  }

  public function testSeverityHasExactlyThreeCases(): void
  {
    self::assertCount(3, Severity::cases());
  }
}
