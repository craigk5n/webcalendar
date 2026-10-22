<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * The wizard workflow builds the web image once, with a layer cache, and
 * compose reuses it rather than building its own copy. That only works while
 * two things agree: the tag the workflow builds and the image: key in each
 * compose file.
 *
 * If they drift, nothing fails. Compose simply does not find the image it
 * wants, builds it again, and the caching is quietly gone -- 48 seconds back
 * on each of three jobs, with a green pipeline throughout. Hence a test.
 *
 * The Dockerfile has to match too. A prebuilt image from a different
 * Dockerfile than the one compose declares would mean CI tests something the
 * compose file does not describe.
 */
final class DockerImageTagTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';
  private const WORKFLOW = __DIR__ . '/../.github/workflows/test-web-wizard.yml';

  /**
   * @return array<int, array{0: string}>
   */
  public static function composeProvider(): array
  {
    return [
      ['docker/docker-compose-test-mysql.yml'],
      ['docker/docker-compose-test-postgresql.yml'],
      ['docker/docker-compose-test-sqlite.yml'],
    ];
  }

  private function read(string $rel): string
  {
    $s = file_get_contents(self::ROOT . '/' . $rel);
    self::assertIsString($s, "could not read $rel");

    return $s;
  }

  /**
   * The tag the workflow builds, taken from the workflow rather than written
   * down twice here.
   */
  private function workflowTag(): string
  {
    $src = file_get_contents(self::WORKFLOW);
    self::assertIsString($src);

    self::assertSame(3, preg_match_all('/^\s*tags:\s*(\S+)\s*$/m', $src, $m),
      'each of the three Selenium jobs must build the image');

    $tags = array_unique($m[1]);
    self::assertCount(1, $tags,
      'the three jobs must build the same tag, or they cannot share a cache');

    return $tags[0];
  }

  /**
   * @dataProvider composeProvider
   */
  public function testComposeUsesTheTagTheWorkflowBuilds(string $compose): void
  {
    $src = $this->read($compose);

    self::assertStringContainsString('image: ' . $this->workflowTag(), $src,
      basename($compose) . ' must name the image the workflow prebuilds, or '
      . 'compose builds its own and the layer cache does nothing');
  }

  /**
   * @dataProvider composeProvider
   */
  public function testComposeStillDeclaresHowToBuildIt(string $compose): void
  {
    $src = $this->read($compose);

    self::assertStringContainsString('dockerfile: docker/Dockerfile-dev', $src,
      basename($compose) . ' must keep its build: section, so the environment '
      . 'still works for a developer who has not prebuilt anything');
  }

  /**
   * The prebuilt image has to come from the Dockerfile compose names, or CI
   * is testing something the compose file does not describe.
   */
  public function testTheWorkflowBuildsTheDockerfileComposeDeclares(): void
  {
    $src = file_get_contents(self::WORKFLOW);
    self::assertIsString($src);

    self::assertSame(3,
      preg_match_all('#^\s*file:\s*docker/Dockerfile-dev\s*$#m', $src),
      'each cached build must use the same Dockerfile as the compose files');
  }

  /**
   * A cache that is only written is a cache that never helps.
   */
  public function testTheBuildBothReadsAndWritesTheCache(): void
  {
    $src = file_get_contents(self::WORKFLOW);
    self::assertIsString($src);

    self::assertSame(3, preg_match_all('/cache-from:\s*type=gha/', $src));
    self::assertSame(3, preg_match_all('/cache-to:\s*type=gha/', $src));
    self::assertSame(3, preg_match_all('/load:\s*true/', $src),
      'the image has to be loaded into the local daemon for compose to find '
      . 'it; a build that only populates the cache leaves nothing behind');
  }
}
