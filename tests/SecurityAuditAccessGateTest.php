<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Story 4.3 — Access control integration.
 *
 * The new "File integrity" section added by Story 3.5 must sit
 * BEHIND the pre-existing `ACCESS_SECURITY_AUDIT` UAC gate at the
 * top of `security_audit.php`. No new UAC constant is introduced
 * — the existing check already protects the whole page.
 *
 * This is a source-structure test rather than an HTTP test: it
 * reads `security_audit.php` and asserts structural invariants
 * that make the access gate effective. If the file is ever
 * reorganized in a way that exposes the file-integrity render
 * path without the gate, this test breaks the build.
 */
final class SecurityAuditAccessGateTest extends TestCase
{
  private const AUDIT_PHP = __DIR__ . '/../security_audit.php';
  private const ACCESS_PHP = __DIR__ . '/../includes/access.php';

  private string $source;

  /** @var list<string> */
  private array $lines;

  protected function setUp(): void
  {
    $src = file_get_contents(self::AUDIT_PHP);
    self::assertNotFalse($src, 'security_audit.php must exist');
    $this->source = $src;
    $this->lines = explode("\n", $src);
  }

  public function testAccessGateIsPresentAtTopOfFile(): void
  {
    // The gate must call die_miserable_death if the user is not an
    // admin or lacks ACCESS_SECURITY_AUDIT when UAC is enabled.
    $gateFound = false;
    foreach (array_slice($this->lines, 0, 30) as $line) {
      if (str_contains($line, 'ACCESS_SECURITY_AUDIT')) {
        $gateFound = true;
        break;
      }
    }
    self::assertTrue(
      $gateFound,
      'Expected ACCESS_SECURITY_AUDIT gate in first 30 lines of security_audit.php.'
    );
  }

  public function testFileIntegrityRenderIsCalledAfterAccessGate(): void
  {
    $gateLine = $this->lineOfFirstMatch('/access_can_access_function\s*\(\s*ACCESS_SECURITY_AUDIT\s*\)/');
    $dieLine = $this->lineOfFirstMatch('/die_miserable_death\s*\(\s*print_not_auth\s*\(\s*\)\s*\)/');
    $renderCallLine = $this->lineOfFirstCallSite('render_file_integrity_section');

    self::assertNotNull($gateLine, 'ACCESS_SECURITY_AUDIT gate must exist');
    self::assertNotNull($dieLine, 'die_miserable_death(print_not_auth()) call must exist');
    self::assertNotNull($renderCallLine, 'render_file_integrity_section() call must exist');

    self::assertLessThan(
      $renderCallLine,
      $gateLine,
      "ACCESS_SECURITY_AUDIT check (line $gateLine) must come BEFORE "
      . "render_file_integrity_section() call (line $renderCallLine) — otherwise "
      . "unauthenticated users could trigger the file-integrity section."
    );
    self::assertLessThan(
      $renderCallLine,
      $dieLine,
      "die_miserable_death exit (line $dieLine) must come BEFORE "
      . "render_file_integrity_section() call (line $renderCallLine)."
    );
  }

  public function testNoDuplicateFileIntegrityCallBeforeGate(): void
  {
    // Defensive: if some future refactor adds a CALL to
    // render_file_integrity_section() somewhere BEFORE the gate,
    // this test catches it.
    $gateLine = $this->lineOfFirstMatch('/access_can_access_function\s*\(\s*ACCESS_SECURITY_AUDIT\s*\)/');
    self::assertNotNull($gateLine);

    foreach ($this->lines as $idx => $line) {
      $lineNo = $idx + 1;
      if ($lineNo >= $gateLine) {
        break;
      }
      self::assertStringNotContainsString(
        'render_file_integrity_section(',
        $line,
        "render_file_integrity_section() call on line $lineNo is BEFORE "
        . "the access gate on line $gateLine."
      );
    }
  }

  public function testNoNewUacConstantIntroduced(): void
  {
    $accessSrc = file_get_contents(self::ACCESS_PHP);
    self::assertNotFalse($accessSrc);

    // Per AC: "No new UAC function added."
    $forbiddenPatterns = [
      'ACCESS_FILE_INTEGRITY',
      'ACCESS_MANIFEST',
      'ACCESS_RELEASE_SIGNING',
      'ACCESS_SIGNED_MANIFEST',
    ];
    foreach ($forbiddenPatterns as $needle) {
      self::assertStringNotContainsString(
        $needle,
        $accessSrc,
        "A new UAC constant '$needle' was introduced, violating Story 4.3's "
        . 'AC ("No new UAC function added"). Reuse ACCESS_SECURITY_AUDIT instead.'
      );
    }
  }

  public function testExistingSecurityAuditUacConstantStillDefined(): void
  {
    // Don't accidentally delete the one we DO depend on.
    $accessSrc = file_get_contents(self::ACCESS_PHP);
    self::assertNotFalse($accessSrc);
    self::assertStringContainsString(
      "define( 'ACCESS_SECURITY_AUDIT',",
      $accessSrc,
      'ACCESS_SECURITY_AUDIT constant must remain defined in includes/access.php — '
      . 'security_audit.php depends on it.'
    );
  }

  // -- Helpers --------------------------------------------------------------

  private function lineOfFirstMatch(string $pattern): ?int
  {
    foreach ($this->lines as $idx => $line) {
      if (preg_match($pattern, $line)) {
        return $idx + 1;
      }
    }
    return null;
  }

  private function lineOfFirstCallSite(string $functionName): ?int
  {
    $needle = $functionName . '(';
    $defPrefix = 'function ' . $functionName;

    foreach ($this->lines as $idx => $line) {
      $trim = ltrim($line);
      // Skip the definition itself — we want call sites only.
      if (str_starts_with($trim, $defPrefix)) {
        continue;
      }
      if (str_contains($line, $needle)) {
        return $idx + 1;
      }
    }
    return null;
  }
}
