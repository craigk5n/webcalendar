<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test files must require the helpers they use, rather than relying on
 * tests/bootstrap.php having loaded them.
 *
 * Not every invocation loads that bootstrap. .github/workflows/test-mcp.yml
 * runs `vendor/bin/phpunit tests/McpTest.php` with no -c, so phpunit.xml and
 * its bootstrap are never read, and tests/run_unit_tests.sh runs several
 * files the same way. Loading a shared helper only from the bootstrap passed
 * locally and produced 57 "Class not found" errors in CI.
 */
final class TestFixtureRequiresTest extends TestCase
{
  private const DIR = __DIR__;

  /**
   * Helpers that live in tests/ and are not test classes, so nothing loads
   * them automatically.
   *
   * @return array<int, array{0: string}>
   */
  public static function helperProvider(): array
  {
    return [['McpServerFixture'], ['McpTestHelper'], ['CrossDatabaseTestHelper']];
  }

  /**
   * @dataProvider helperProvider
   */
  public function testEveryUserOfAHelperRequiresIt(string $helper): void
  {
    $offenders = [];

    foreach (glob(self::DIR . '/*.php') ?: [] as $file) {
      $name = basename($file);
      if ($name === $helper . '.php' || $name === 'bootstrap.php') {
        continue;
      }

      $src = file_get_contents($file);
      if ($src === false || !str_contains($src, $helper)) {
        continue;
      }

      // The reference may be only a mention in a comment, which needs no
      // require; look for it used as a class.
      if (!preg_match('/\b' . preg_quote($helper, '/') . '\s*(::|\()/', $src)
        && !preg_match('/new\s+' . preg_quote($helper, '/') . '\b/', $src)) {
        continue;
      }

      if (!preg_match('#require(_once)?\s.*[\'"][^\'"]*' . preg_quote($helper, '#') . '\.php[\'"]#', $src)) {
        $offenders[] = $name;
      }
    }

    sort($offenders);
    $this->assertSame([], $offenders, $helper
      . ' is used without being required by: ' . implode(', ', $offenders)
      . '. Not every phpunit invocation loads tests/bootstrap.php.');
  }
}
