<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * The wizard's Selenium tests wait for conditions, not for the clock.
 *
 * A fixed sleep after a navigation is wrong in both directions: it is dead
 * time when the page is ready sooner, and a failure when a loaded runner takes
 * longer than the guess. Replacing the unconditional ones took about 25
 * seconds off each of the three suites, measured locally against an isolated
 * copy of the tree.
 *
 * Sleeps inside a bounded polling loop are a different thing and are left
 * alone: they are the interval of a proper wait, and they stop as soon as the
 * condition holds.
 */
final class SeleniumWaitsTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  /**
   * @return array<int, array{0: string}>
   */
  public static function suiteProvider(): array
  {
    return [
      ['tests/web-install-mysql.py'],
      ['tests/web-install-postgresql.py'],
      ['tests/web-install-sqlite.py'],
    ];
  }

  /**
   * @return list<string>
   */
  private function lines(string $rel): array
  {
    $src = file_get_contents(self::ROOT . '/' . $rel);
    self::assertIsString($src, "could not read $rel");

    return explode("\n", $src);
  }

  /**
   * The file with its docstrings and comments removed.
   *
   * The helper below explains in prose why element staleness is not used to
   * detect a navigation, and a search of the whole file found that
   * explanation and counted it as a use.
   */
  private function pythonCode(string $rel): string
  {
    $src = file_get_contents(self::ROOT . '/' . $rel);
    self::assertIsString($src, "could not read $rel");

    // Triple-quoted blocks are docstrings in these files, never data.
    $src = (string) preg_replace('/"""[\s\S]*?"""/', '', $src);

    $out = [];
    foreach (explode("\n", $src) as $line) {
      $out[] = preg_replace('/(^|\s)#.*$/', '', $line);
    }

    return implode("\n", $out);
  }

  /**
   * The rule, stated as the thing it forbids: nothing may navigate and then
   * sleep. What comes after a navigation is a wait for what the next line
   * actually needs.
   *
   * @dataProvider suiteProvider
   */
  public function testNoSleepDirectlyFollowsANavigation(string $suite): void
  {
    $lines = $this->lines($suite);
    $offenders = [];

    foreach ($lines as $i => $line) {
      $isNavigation = str_contains($line, 'driver.get(')
        || str_contains($line, '.click()');
      if (!$isNavigation) {
        continue;
      }
      // The next non-blank line.
      for ($j = $i + 1; $j < count($lines); $j++) {
        if (trim($lines[$j]) === '') {
          continue;
        }
        if (preg_match('/^\s*time\.sleep\(/', $lines[$j])) {
          $offenders[] = basename($suite) . ':' . ($j + 1) . ' sleeps after '
            . trim($line);
        }
        break;
      }
    }

    self::assertSame([], $offenders, count($offenders) . ' fixed sleep(s) '
      . "immediately after a navigation. Wait for the condition the next "
      . "lines depend on instead:\n  " . implode("\n  ", $offenders));
  }

  /**
   * @dataProvider suiteProvider
   */
  public function testTheWaitHelpersExistAndAreUsed(string $suite): void
  {
    $src = implode("\n", $this->lines($suite));

    foreach (['wait_for_page', 'click_and_wait'] as $helper) {
      self::assertStringContainsString("def $helper(", $src,
        basename($suite) . " must define $helper()");
      self::assertGreaterThan(1, substr_count($src, $helper),
        basename($suite) . " defines $helper() but never calls it");
    }
  }

  /**
   * EC.staleness_of() looks like the right way to wait out a navigation and
   * is not safe here: it treats only StaleElementReferenceException as "gone",
   * while Chrome raises WebDriverException("Node with given id does not belong
   * to the document") during the navigation, which propagates. It turned a
   * passing test_new_installation into a failure when this change was first
   * written, found by running the suites rather than by reading them.
   *
   * @dataProvider suiteProvider
   */
  public function testNavigationIsNotDetectedByElementStaleness(string $suite): void
  {
    $code = $this->pythonCode($suite);

    self::assertStringNotContainsString('staleness_of', $code,
      basename($suite) . ' must detect a navigation with the window marker, '
      . 'not by touching an element reference that the navigation invalidates');
  }
}
