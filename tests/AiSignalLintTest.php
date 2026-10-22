<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * tools/check-ai-signals.php flags comments that read as machine-written,
 * using the mechanical subset of webcalendar-core's AI-SIGNALS.md.
 *
 * A linter that finds nothing looks exactly like a linter that is broken, so
 * these feed it known-bad comments and require each rule to fire, then feed it
 * ordinary code and require silence. The fixtures live in a temporary
 * directory so the result does not depend on this repository's own comments.
 */
final class AiSignalLintTest extends TestCase
{
  private const TOOL = __DIR__ . '/../tools/check-ai-signals.php';

  private string $dir = '';

  protected function setUp(): void
  {
    parent::setUp();
    $this->dir = sys_get_temp_dir() . '/wc-ai-signal-' . bin2hex(random_bytes(4));
    if (!mkdir($this->dir) && !is_dir($this->dir)) {
      $this->fail('could not create a fixture directory');
    }
  }

  protected function tearDown(): void
  {
    foreach (glob($this->dir . '/*') ?: [] as $file) {
      unlink($file);
    }
    if (is_dir($this->dir)) {
      rmdir($this->dir);
    }
    parent::tearDown();
  }

  /**
   * @return array{output: string, code: int}
   */
  private function lint(string $comment, bool $strict = false): array
  {
    file_put_contents(
      $this->dir . '/probe.php',
      "<?php\n\n" . $comment . "\nfunction probe(): void\n{\n}\n"
    );

    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(self::TOOL)
      . ' --path=' . escapeshellarg($this->dir)
      . ($strict ? ' --strict' : '') . ' 2>&1';

    $output = '';
    $handle = popen($cmd, 'r');
    self::assertIsResource($handle);
    while (!feof($handle)) {
      $output .= (string) fread($handle, 8192);
    }

    return ['output' => $output, 'code' => pclose($handle)];
  }

  /**
   * @return array<string, array{0: string, 1: string}>
   */
  public static function signalProvider(): array
  {
    return [
      'second person' => ['// You can set this flag to enable the feature.', 'second-person'],
      'instructional' => ['// Make sure to call this before rendering.', 'instructional'],
      'buzzword' => ['// A robust solution for handling the input.', 'buzzword'],
      'bookend marker' => ['// --- End Event Processing Section ---', 'bookend-marker'],
    ];
  }

  /**
   * @dataProvider signalProvider
   */
  public function testEachRuleFires(string $comment, string $rule): void
  {
    $result = $this->lint($comment);

    $this->assertStringContainsString($rule, $result['output'],
      'the ' . $rule . ' rule did not fire on: ' . $comment);
  }

  /**
   * The counterpart. Comments a maintainer would actually write must pass, or
   * the linter is noise and gets switched off.
   */
  public function testOrdinaryCommentsAreNotFlagged(): void
  {
    $result = $this->lint(
      "// Callers hold the lock for the whole request, so this cannot block.\n"
      . "/**\n * Returns null when the manifest is absent, which is normal for\n"
      . " * an install from source.\n */"
    );

    $this->assertStringContainsString('No AI-writing signals', $result['output'],
      'a clean comment was flagged: ' . $result['output']);
  }

  public function testStrictModeExitsNonZeroOnAFinding(): void
  {
    $clean = $this->lint('// Nothing to see here.', true);
    $this->assertSame(0, $clean['code']);

    $dirty = $this->lint('// You should call this first.', true);
    $this->assertSame(1, $dirty['code'],
      '--strict must fail the build when a signal is present');
  }

  /**
   * Markdown is out of scope on purpose: documentation addressing its reader
   * as "you" is correct, and only PHP comment tokens are examined.
   */
  public function testProseOutsideCommentsIsIgnored(): void
  {
    file_put_contents($this->dir . '/readme.md',
      'You can run this. Make sure to be robust and comprehensive.');
    $result = $this->lint('// Nothing to see here.');

    $this->assertStringContainsString('No AI-writing signals', $result['output']);
  }

  /**
   * Code that discusses these phrases has to be able to quote one. This tool
   * flagged its own docblock the first time it ran in CI, which is how the
   * marker came to exist.
   */
  public function testTheAllowMarkerSuppressesAComment(): void
  {
    $flagged = $this->lint('// You should call this first.');
    $this->assertStringContainsString('second-person', $flagged['output']);

    // The marker covers the comment it is in, so both have to be in one
    // block. That is how it is used in practice, in a docblock explaining a
    // rule and quoting an example of it.
    $allowed = $this->lint(
      "/**\n * ai-signals:allow -- quoting the phrasing for illustration.\n"
      . " *\n * You should call this first.\n */"
    );
    $this->assertStringContainsString('No AI-writing signals', $allowed['output'],
      'the marker must suppress the finding in its own comment');
  }

  /**
   * The marker only covers the comment it appears in, so it cannot be dropped
   * at the top of a file to switch the whole check off.
   */
  public function testTheAllowMarkerDoesNotLeakToOtherComments(): void
  {
    $result = $this->lint(
      "// ai-signals:allow -- this one is deliberate: you should ignore it.\n"
      . "function a() {}\n"
      . '// You should not be able to hide this one.'
    );

    $this->assertStringContainsString('second-person', $result['output'],
      'a marker in one comment must not silence a different comment');
  }

  /**
   * A string that happens to contain a flagged phrase is not a comment.
   */
  public function testCodeAndStringLiteralsAreIgnored(): void
  {
    $result = $this->lint(
      '// Nothing to see here.' . "\n"
      . '$message = "You should make sure to use a robust password";'
    );

    $this->assertStringContainsString('No AI-writing signals', $result['output']);
  }
}
