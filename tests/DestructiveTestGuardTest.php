<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * tests/run-*-install-tests.sh drive the web installation wizard against a
 * container that bind mounts the working copy, and tests/web-install-*.py
 * deletes includes/settings.php as part of its setup. On a CI checkout that
 * costs nothing. On a working copy that is also a live install it removes the
 * live configuration and takes the site down, which is exactly what happened
 * on 2026-09-24.
 *
 * Each runner now refuses unless settings.php is absent or
 * WEBCAL_TEST_ALLOW_DESTRUCTIVE=1 is set, and CI sets it. These tests keep
 * that true: the guard has to be present, it has to come before any docker
 * command, and the workflow has to keep supplying the variable or the wizard
 * jobs would refuse and fail.
 */
final class DestructiveTestGuardTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';
  private const ENV_VAR = 'WEBCAL_TEST_ALLOW_DESTRUCTIVE';

  /**
   * @return array<int, array{0: string}>
   */
  public static function runnerProvider(): array
  {
    return [
      ['tests/run-mysql-install-tests.sh'],
      ['tests/run-postgresql-install-tests.sh'],
      ['tests/run-sqlite-install-tests.sh'],
    ];
  }

  private function read(string $rel): string
  {
    $s = file_get_contents(self::ROOT . '/' . $rel);
    self::assertIsString($s, "could not read $rel");
    return $s;
  }

  /**
   * The runner with its comment lines removed.
   *
   * Each runner explains the guard in a comment block that names
   * includes/settings.php and the variable, so searching the whole file for
   * those strings passed even with the condition replaced by `if false`.
   * Found by mutating the runners on 2026-09-25.
   */
  private function shellCode(string $rel): string
  {
    return SourceText::shell($this->read($rel));
  }

  /**
   * Where the refusal itself is, not merely where the strings appear.
   *
   * The two echo lines inside the block name both of them too, so presence
   * alone stays true when the condition is disabled. What makes it a guard is
   * one condition that tests for the file and honours the override together.
   *
   * @return int|false
   */
  private function guardOffset(string $code)
  {
    $pattern = '/\bif\b[^\n]*-f[^\n]*includes\/settings\.php[^\n]*'
      . preg_quote(self::ENV_VAR, '/') . '/';

    if (preg_match($pattern, $code, $m, PREG_OFFSET_CAPTURE)) {
      return $m[0][1];
    }

    return false;
  }

  /**
   * @dataProvider runnerProvider
   */
  public function testRunnerRefusesWhenALiveSettingsFileIsPresent(string $runner): void
  {
    $code = $this->shellCode($runner);
    $offset = $this->guardOffset($code);

    $this->assertNotFalse($offset, basename($runner) . ' must refuse in a '
      . 'single condition that tests for includes/settings.php and honours '
      . self::ENV_VAR . '. Naming them in a comment or an echo is not a '
      . 'guard.');

    // Only the refusal's own block counts. The runners end with another
    // `exit 1`, so searching everything after the condition matched that one
    // even with the refusal's exit removed.
    $block = substr($code, $offset);
    $end = strpos($block, "\nfi");
    if ($end !== false) {
      $block = substr($block, 0, $end);
    }

    $this->assertMatchesRegularExpression('/\bexit 1\b/', $block,
      basename($runner) . ' must exit non-zero inside the refusal itself');
  }

  /**
   * A guard that runs after `docker compose up` has already started the
   * container is too late, because the pytest service deletes the file.
   *
   * @dataProvider runnerProvider
   */
  public function testGuardPrecedesAnyDockerCommand(string $runner): void
  {
    $code = $this->shellCode($runner);

    $guard = $this->guardOffset($code);
    $this->assertNotFalse($guard, basename($runner) . ' has no guard');

    if (preg_match('/^\s*docker\s/m', $code, $m, PREG_OFFSET_CAPTURE)) {
      $this->assertLessThan($m[0][1], $guard,
        basename($runner) . ' runs docker before the guard');
    } else {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Without the variable the wizard jobs would refuse, so this also catches
   * someone adding the guard and forgetting CI.
   */
  public function testWizardWorkflowSuppliesTheOverride(): void
  {
    $src = $this->read('.github/workflows/test-web-wizard.yml');

    // Assignments, not occurrences. substr_count() also counted a longer
    // name that merely begins with this one, so renaming the variable left
    // the total at three while no job set it any more.
    $assignments = preg_match_all(
      '/\b' . preg_quote(self::ENV_VAR, '/') . '\b\s*[:=]/', $src);

    $this->assertSame(3, $assignments,
      'each of the three wizard jobs must set ' . self::ENV_VAR);
  }
}
