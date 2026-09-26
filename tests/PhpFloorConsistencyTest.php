<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * The supported PHP floor is stated in six places. Before 2026-09-24 they
 * disagreed four ways: composer.json required ^8.2, README.md said 8.2+,
 * CONTRIBUTING.md said 8.0+, docs/installation.md said "8.0 minimum", the
 * wizard gated at 8.0, and two workflows still tested 8.1 -- which had
 * reached end of life. An agent reading any one of them drew a different
 * conclusion about what it could use.
 *
 * These lock them together. Raising the floor means changing FLOOR here and
 * then making every assertion pass again.
 */
final class PhpFloorConsistencyTest extends TestCase
{
  private const FLOOR = '8.2';
  private const ROOT = __DIR__ . '/..';

  private function read(string $rel): string
  {
    $s = file_get_contents(self::ROOT . '/' . $rel);
    self::assertIsString($s, "could not read $rel");
    return $s;
  }

  public function testComposerRequiresTheFloor(): void
  {
    $json = json_decode($this->read('composer.json'), true);
    $this->assertSame('^' . self::FLOOR, $json['require']['php'] ?? null,
      'composer.json require.php must match the floor');
    $this->assertSame(self::FLOOR . '.0', $json['config']['platform']['php'] ?? null,
      'composer.json config.platform.php must match the floor');
  }

  /**
   * The wizard is the only thing that tells a user their PHP is too old, so
   * its gate has to be the floor rather than something older.
   */
  public function testWizardGatesOnTheFloor(): void
  {
    $src = $this->read('wizard/WizardValidator.php');
    $this->assertStringContainsString(
      "version_compare(\$phpVersion, '" . self::FLOOR . "', '>=')", $src,
      'WizardValidator must reject anything below the floor');
    $this->assertStringContainsString("'required' => '" . self::FLOOR . "+'", $src,
      'the version row the wizard displays must name the floor');
  }

  /**
   * @return array<int, array{0: string}>
   */
  public static function workflowProvider(): array
  {
    return [
      ['.github/workflows/php-syntax-check.yml'],
      ['.github/workflows/test-install.yml'],
      ['.github/workflows/ci.yml'],
      ['.github/workflows/test-mcp.yml'],
    ];
  }

  /**
   * @dataProvider workflowProvider
   */
  public function testNoWorkflowTestsBelowTheFloor(string $workflow): void
  {
    $src = $this->read($workflow);
    if (!preg_match_all("/php-version:\s*\[([^\]]+)\]/", $src, $m)) {
      $this->addToAssertionCount(1);
      return; // no matrix in this workflow
    }

    $tooOld = [];
    foreach ($m[1] as $list) {
      foreach (explode(',', $list) as $v) {
        $v = trim($v, " '\"");
        if ($v !== '' && version_compare($v, self::FLOOR, '<')) {
          $tooOld[] = $v;
        }
      }
    }

    $this->assertSame([], $tooOld, basename($workflow)
      . ' tests PHP below the floor: ' . implode(', ', $tooOld));
  }

  /**
   * @return array<int, array{0: string}>
   */
  public static function docProvider(): array
  {
    return [['README.md'], ['CONTRIBUTING.md'], ['docs/installation.md']];
  }

  /**
   * Catches a doc that still advertises an older minimum, which is how
   * CONTRIBUTING.md came to claim 8.0+ long after composer.json said 8.2.
   *
   * @dataProvider docProvider
   */
  public function testDocsDoNotAdvertiseAnOlderMinimum(string $doc): void
  {
    $src = $this->read($doc);
    $stale = [];

    // "PHP 8.0+", "PHP 8.1 minimum", "PHP 8.0 or later" and similar.
    if (preg_match_all('/PHP[^\n]{0,18}?\b(\d+\.\d+)\s*(\+|minimum|or later|or newer)/i',
        $src, $m, PREG_SET_ORDER)) {
      foreach ($m as $hit) {
        if (version_compare($hit[1], self::FLOOR, '<')) {
          $stale[] = trim($hit[0]);
        }
      }
    }

    $this->assertSame([], $stale, basename($doc)
      . ' advertises a PHP minimum below the floor: ' . implode('; ', $stale));
  }
}
