<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * docs/CODEMAPS/functions.md indexes the large procedural includes so a
 * function can be found without grepping 6600 lines. It is generated, and a
 * generated file that silently goes stale is worse than none -- a reader
 * trusts it.
 *
 * This asserts the set of function names matches the source. Line numbers
 * shift constantly and are not worth failing a build over; a function that
 * appeared or disappeared is.
 */
final class CodemapTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';
  private const MAP = 'docs/CODEMAPS/functions.md';

  public function testCodemapExists(): void
  {
    $this->assertFileExists(self::ROOT . '/' . self::MAP,
      'run: php tools/build-codemap.php');
  }

  /**
   * @return array<int, array{0: string}>
   */
  public static function sourceProvider(): array
  {
    return [['includes/functions.php'], ['includes/dbi4php.php']];
  }

  /**
   * @dataProvider sourceProvider
   */
  public function testEveryTopLevelFunctionIsIndexed(string $source): void
  {
    $src = file_get_contents(self::ROOT . '/' . $source);
    $this->assertIsString($src);

    // Top-level declarations only, matching what the generator indexes.
    preg_match_all('/^function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/m', $src, $m);
    $declared = array_unique($m[1]);
    $this->assertNotEmpty($declared, "no functions found in $source");

    $map = file_get_contents(self::ROOT . '/' . self::MAP);
    $this->assertIsString($map, 'run: php tools/build-codemap.php');

    $missing = [];
    foreach ($declared as $name) {
      if (!str_contains($map, '| `' . $name . '()` |')) {
        $missing[] = $name;
      }
    }

    sort($missing);
    $this->assertSame([], $missing, count($missing) . ' function(s) in '
      . $source . ' are absent from ' . self::MAP
      . '. Run: php tools/build-codemap.php. Missing: '
      . implode(', ', array_slice($missing, 0, 15)));
  }

  /**
   * Control case. A generator that silently found almost nothing would still
   * satisfy a "no missing entries" check if the extraction and the assertion
   * shared the same blind spot, so pin the order of magnitude too.
   */
  public function testCodemapIndexesTheWholeFileNotAFraction(): void
  {
    $map = file_get_contents(self::ROOT . '/' . self::MAP);
    $this->assertIsString($map);

    $rows = substr_count($map, "\n| `");
    $this->assertGreaterThan(150, $rows,
      'the codemap looks truncated; the generator once found 16 of 161 '
      . 'because string-interpolation braces desynchronised its depth count');
  }

  /**
   * The generator's own --check mode regenerates and compares byte for byte,
   * so this covers every column at once -- including any added later -- with
   * no second implementation to drift from the first. The tests above stay
   * because they say what specifically must hold if this ever has to be
   * skipped.
   */
  public function testRegeneratingReproducesTheCommittedFile(): void
  {
    $root = realpath(self::ROOT);
    self::assertIsString($root);

    $output = [];
    $status = 0;
    @exec('php ' . escapeshellarg($root . '/tools/build-codemap.php')
      . ' --check 2>&1', $output, $status);

    self::assertSame(0, $status,
      "docs/CODEMAPS/functions.md is out of date. Run:\n"
      . "  php tools/build-codemap.php\n" . implode("\n", $output));
  }

  /**
   * These files pass data through globals, so the column is the part of the
   * contract a signature does not state. A silently empty one would leave the
   * map looking complete while saying nothing.
   */
  public function testTheGlobalsColumnIsPopulated(): void
  {
    $map = file_get_contents(self::ROOT . '/' . self::MAP);
    self::assertIsString($map);

    self::assertStringContainsString('| Globals |', $map,
      'the table must carry a Globals column');

    // export_get_event_entry() is the reason this column exists: two
    // parameters, and everything else arrives through globals.
    self::assertMatchesRegularExpression(
      '/\| `export_get_event_entry\(\)`.*\$DISPLAY_UNAPPROVED.*\$login/',
      $map,
      'export_get_event_entry() must list the globals it reads');

    $rows = preg_match_all('/^\| `\w+\(\)`/m', $map);
    $withGlobals = preg_match_all('/^\| `\w+\(\).*`\$\w+/m', $map);

    self::assertGreaterThan($rows / 4, $withGlobals,
      'far fewer functions declare globals than expected, which suggests the '
      . 'extraction has stopped working rather than that the code changed');
  }
}
