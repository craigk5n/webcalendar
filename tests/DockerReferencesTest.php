<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Every Dockerfile and compose file the build, the workflows or the install
 * test scripts name has to exist.
 *
 * Renaming docker/Dockerfile-php8-dev broke the three Selenium wizard jobs,
 * because the reference reaches it indirectly: test-web-wizard.yml runs
 * tests/run-*-install-tests.sh, those name a docker-compose-test-*.yml, and
 * each of those builds a `dockerfile:`. Nothing failed until CI pulled the
 * whole chain together, and a grep that assumed paths carry an extension
 * missed it twice.
 */
final class DockerReferencesTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  /**
   * Files that name a docker path: compose files, the publish workflows, the
   * install test scripts and the manual build script.
   *
   * @return array<int, string>
   */
  private function sources(): array
  {
    return array_merge(
      glob(self::ROOT . '/docker/*.yml') ?: [],
      glob(self::ROOT . '/docker/*.sh') ?: [],
      glob(self::ROOT . '/.github/workflows/*.yml') ?: [],
      glob(self::ROOT . '/tests/*.sh') ?: []
    );
  }

  /**
   * `dockerfile:` in a compose file resolves against that service's build
   * context, which is `..` everywhere here, so the value is repo-relative.
   */
  public function testEveryComposeBuildNamesAFileThatExists(): void
  {
    $missing = [];

    foreach (glob(self::ROOT . '/docker/*.yml') ?: [] as $file) {
      foreach (file($file, FILE_IGNORE_NEW_LINES) as $n => $line) {
        if (preg_match('/^\s*#/', $line)) {
          continue; // commented-out service
        }
        if (!preg_match('/^\s*dockerfile:\s*(\S+)/', $line, $m)) {
          continue;
        }
        if (!file_exists(self::ROOT . '/' . $m[1])) {
          $missing[] = basename($file) . ':' . ($n + 1) . ' -> ' . $m[1];
        }
      }
    }

    $this->assertSame([], $missing,
      'compose files naming a missing Dockerfile: ' . implode(', ', $missing));
  }

  /**
   * Catches `file: ./docker/Dockerfile-prod` in the publish workflows and
   * `-f docker/...` in build_and_push.sh, neither of which carries a
   * recognisable extension.
   */
  public function testEveryReferencedDockerPathExists(): void
  {
    $missing = [];

    foreach ($this->sources() as $file) {
      $src = file_get_contents($file);
      if ($src === false) {
        continue;
      }
      preg_match_all('#(?<![\w/-])\.?/?(docker/[A-Za-z0-9._-]+)#', $src, $m);
      foreach (array_unique($m[1]) as $path) {
        // docker/login-action and friends are GitHub Actions, not paths.
        if (preg_match('/-action$/', $path)) {
          continue;
        }
        if (!file_exists(self::ROOT . '/' . $path)) {
          $missing[] = basename($file) . ' -> ' . $path;
        }
      }
    }

    $this->assertSame([], $missing,
      'referenced docker paths that do not exist: ' . implode(', ', $missing));
  }
}
