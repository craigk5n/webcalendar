<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * Request values reach js_cacher.php as path segments after the file name:
 * catsel.php passes its form name, availability.php a date and a form name.
 * They were written unescaped twice over -- into the page's
 * <script src="js_cacher.php?inc=..."> attribute, where a quote ended the
 * attribute and the rest became markup, and into the JavaScript the
 * included file prints.
 */
final class JsCacherArgumentEscapingTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';
  private const PAYLOAD = 'x" onload=alert(1) <b>';

  public static function setUpBeforeClass(): void
  {
    if (function_exists('js_cacher_src')) {
      return;
    }
    $src = file_get_contents(self::ROOT . '/includes/init.php');
    self::assertNotFalse($src);
    $fn = SourceText::phpFunction($src, 'js_cacher_src');
    self::assertIsString($fn, 'includes/init.php must define js_cacher_src()');
    eval($fn);
  }

  public function testScriptSrcCannotLeaveTheAttribute(): void
  {
    $src = js_cacher_src('js/catsel.php/false/' . self::PAYLOAD);
    $this->assertDoesNotMatchRegularExpression('/["\'<> ]/', $src);
  }

  public function testScriptSrcRoundTripsToTheSameSegments(): void
  {
    $inc = 'js/availability.php/false/10/5/2026/' . self::PAYLOAD;
    parse_str((string) parse_url(js_cacher_src($inc), PHP_URL_QUERY), $q);
    $this->assertSame($inc, $q['inc']);
  }

  public function testScriptSrcLeavesOrdinaryValuesAlone(): void
  {
    $this->assertSame('js_cacher.php?inc=js/visible.php',
      js_cacher_src('js/visible.php'));
  }

  private function run_js(string $file, array $arinc): string
  {
    if (!defined('_ISVALID')) {
      define('_ISVALID', true);
    }
    ob_start();
    (static function (string $path, array $arinc): void {
      include $path;
    })(self::ROOT . '/includes/js/' . $file, $arinc);
    return (string) ob_get_clean();
  }

  public function testCatselKeepsAnIdentifier(): void
  {
    $out = $this->run_js('catsel.php', ['js', 'catsel.php', 'false',
      'editentryform']);
    $this->assertStringContainsString('var arinctri = editentryform;', $out);
  }

  public function testCatselRefusesScript(): void
  {
    $out = $this->run_js('catsel.php', ['js', 'catsel.php', 'false',
      '1;alert(document.cookie)']);
    $this->assertStringContainsString('var arinctri = null;', $out);
    $this->assertStringNotContainsString('alert', $out);
  }

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testAvailabilityWritesOnlyNumbersAndAnIdentifier(): void
  {
    // availability.php calls etranslate(); a stub is enough here, and this
    // test runs in its own process so the stub cannot collide with the real
    // one in other tests.
    eval('function etranslate($s, $d = false) { echo $s; }');
    $out = $this->run_js('availability.php', ['js', 'availability.php',
      'false', '10;alert(1)', '5', '2026', "f');alert(2);('"]);
    $this->assertStringContainsString('var month =10;', $out);
    $this->assertStringContainsString('var year =2026;', $out);
    $this->assertStringNotContainsString('alert(', $out);

    $out = $this->run_js('availability.php', ['js', 'availability.php',
      'false', '10', '5', '2026', 'editentryform']);
    $this->assertStringContainsString("'editentryform' == 'editentryform'",
      $out);
  }
}
