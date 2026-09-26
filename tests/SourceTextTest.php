<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * SourceText is what stops the structural guards from matching prose, so its
 * own behaviour is tested here rather than assumed in eleven places.
 *
 * Each case below is a real failure that happened: a guard searching for the
 * mechanism it forbids and finding the comment explaining why the mechanism
 * is not used.
 */
final class SourceTextTest extends TestCase
{
  public function testPhpCommentsGoAndCodeStays(): void
  {
    $src = <<<'PHP'
      <?php
      // staleness_of() is not used here, because Chrome raises a different
      // exception during a navigation.
      $marker = 'window.__wcNav';
      /** @var string keep this identifier: staleness_of */
      $other = 1;
      PHP;

    $code = SourceText::php($src);

    self::assertStringNotContainsString('staleness_of', $code,
      'a mention in a comment must not survive');
    self::assertStringContainsString("\$marker = 'window.__wcNav';", $code);
    self::assertStringContainsString('$other = 1;', $code);
  }

  /**
   * Ordering assertions compare offsets inside the stripped source, so the
   * whitespace between tokens has to survive.
   */
  public function testPhpKeepsTheShapeOfWhatIsLeft(): void
  {
    $code = SourceText::php("<?php\n\$a = 1;\n// gone\n\$b = 2;\n");

    self::assertStringContainsString("\$a = 1;\n", $code);
    self::assertStringContainsString("\$b = 2;", $code);
    self::assertLessThan(strpos($code, '$b'), strpos($code, '$a'));
  }

  /**
   * A '#' inside a string is data. Dropping the line would silently remove
   * code, which is worse than leaving a comment in.
   */
  public function testAStringContainingAHashIsNotMistakenForAComment(): void
  {
    $src = "echo \"count: #1\"\nexport X=1\n";

    self::assertStringContainsString('count: #1', SourceText::shell($src));
    self::assertStringContainsString('export X=1', SourceText::shell($src));
  }

  public function testShellCommentLinesGo(): void
  {
    $src = "# WEBCAL_TEST_ALLOW_DESTRUCTIVE is honoured below\nif false; then\n";
    $code = SourceText::shell($src);

    self::assertStringNotContainsString('WEBCAL_TEST_ALLOW_DESTRUCTIVE', $code);
    self::assertStringContainsString('if false; then', $code);
  }

  /**
   * Line positions still have to line up after stripping, because
   * DestructiveTestGuardTest measures whether the refusal precedes the first
   * docker command.
   */
  public function testShellKeepsLineCountSoOrderingStillHolds(): void
  {
    $src = "one\n# two\nthree\n";

    self::assertSame(substr_count($src, "\n"),
      substr_count(SourceText::shell($src), "\n"));
  }

  public function testYamlCommentLinesGo(): void
  {
    $src = "# WEBCALENDAR_USE_ENV=true means settings.php is never read\n"
      . "    environment:\n      - WEBCALENDAR_USE_ENV=false\n";
    $code = SourceText::yaml($src);

    self::assertStringNotContainsString('USE_ENV=true', $code,
      'the comment explaining the setting must not satisfy a search for it');
    self::assertStringContainsString('USE_ENV=false', $code);
  }

  public function testPythonDocstringsAndCommentsGo(): void
  {
    $src = <<<'PY'
      def click_and_wait(driver, element):
          """EC.staleness_of() is not used: Chrome raises WebDriverException."""
          # also not using staleness_of here
          driver.execute_script("window.__wcNavMarker = 1;")
      PY;

    $code = SourceText::python($src);

    self::assertStringNotContainsString('staleness_of', $code);
    self::assertStringContainsString('__wcNavMarker', $code);
  }

  public function testPhpFunctionReturnsOnlyThatFunction(): void
  {
    $src = <<<'PHP'
      <?php
      function before() { return 'no'; }
      function wanted($x) {
        if ($x) {
          return 'yes';
        }
        return 'no';
      }
      function after() { return 'no'; }
      PHP;

    $body = SourceText::phpFunction($src, 'wanted');

    self::assertIsString($body);
    self::assertStringContainsString("return 'yes';", $body);
    self::assertStringNotContainsString('function before', $body);
    self::assertStringNotContainsString('function after', $body);
  }

  /**
   * Counting brace characters instead of brace tokens walks off the end here:
   * "{$var}" opens with T_CURLY_OPEN and closes with a plain '}'. This is the
   * bug that made tools/build-codemap.php find 16 of 161 functions.
   */
  public function testPhpFunctionSurvivesCurlyInterpolation(): void
  {
    $src = <<<'PHP'
      <?php
      function wanted($var) {
        $s = "value: {$var} here";
        return $s;
      }
      function after() { return 'no'; }
      PHP;

    $body = SourceText::phpFunction($src, 'wanted');

    self::assertIsString($body);
    self::assertStringContainsString('return $s;', $body);
    self::assertStringNotContainsString('function after', $body);
  }

  public function testPhpFunctionStripsCommentsToo(): void
  {
    $src = "<?php\nfunction wanted() {\n  // dbi_get_cached_rows is not used\n"
      . "  return dbi_execute('SELECT 1');\n}\n";

    $body = (string) SourceText::phpFunction($src, 'wanted');

    self::assertStringNotContainsString('dbi_get_cached_rows', $body);
    self::assertStringContainsString('dbi_execute', $body);
  }

  public function testPhpFunctionIsNullWhenAbsent(): void
  {
    self::assertNull(
      SourceText::phpFunction("<?php\nfunction other() {}\n", 'wanted'));
  }

  /**
   * A name that merely begins the same way is a different function.
   */
  public function testPhpFunctionDoesNotMatchALongerName(): void
  {
    $src = "<?php\nfunction wanted_more() { return 1; }\n";

    self::assertNull(SourceText::phpFunction($src, 'wanted'));
  }

  /**
   * The signature has to be excluded, or an assertion about what the body
   * touches cannot be stated: wc_read_or_generate_password() takes $argv, and
   * the rule is that nothing inside it reads $argv except the --stdin flag.
   */
  public function testPhpFunctionBodyLeavesOutTheSignature(): void
  {
    $src = "<?php\nfunction wanted(array \$argv): string {\n"
      . "  return 'x';\n}\n";

    $body = (string) SourceText::phpFunctionBody($src, 'wanted');

    self::assertStringNotContainsString('$argv', $body);
    self::assertStringStartsWith('{', $body);
    self::assertStringContainsString("return 'x';", $body);
  }

  public function testPhpFunctionBodyStripsCommentsToo(): void
  {
    $src = "<?php\nfunction wanted() {\n  // chmod is not used here\n"
      . "  return 1;\n}\n";

    $body = (string) SourceText::phpFunctionBody($src, 'wanted');

    self::assertStringNotContainsString('chmod', $body);
    self::assertStringContainsString('return 1;', $body);
  }
}
