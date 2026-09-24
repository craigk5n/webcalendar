<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

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
   * @dataProvider runnerProvider
   */
  public function testRunnerRefusesWhenALiveSettingsFileIsPresent(string $runner): void
  {
    $src = $this->read($runner);

    $this->assertStringContainsString('includes/settings.php', $src,
      basename($runner) . ' must test for a live settings.php');
    $this->assertStringContainsString(self::ENV_VAR, $src,
      basename($runner) . ' must honour ' . self::ENV_VAR);
    $this->assertMatchesRegularExpression('/\bexit 1\b/', $src,
      basename($runner) . ' must exit non-zero when it refuses');
  }

  /**
   * A guard that runs after `docker compose up` has already started the
   * container is too late, because the pytest service deletes the file.
   *
   * @dataProvider runnerProvider
   */
  public function testGuardPrecedesAnyDockerCommand(string $runner): void
  {
    $src = $this->read($runner);

    $guard = strpos($src, self::ENV_VAR);
    $this->assertNotFalse($guard, basename($runner) . ' has no guard');

    if (preg_match('/^\s*docker\s/m', $src, $m, PREG_OFFSET_CAPTURE)) {
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

    $this->assertSame(3, substr_count($src, self::ENV_VAR),
      'each of the three wizard jobs must set ' . self::ENV_VAR);
  }
}
