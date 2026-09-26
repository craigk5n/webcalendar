<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Every `composer install` in a workflow has to ask for dist archives.
 *
 * Without --prefer-dist Composer installs packages from source, which means
 * cloning a git repository for each one. Measured on the same commit and the
 * same lock file:
 *
 *   PHPStan job, `composer install --no-interaction --prefer-dist`     6s
 *   CI job,      `composer install`                                  301s
 *
 * The CI job's own tests took 33 seconds of its 346. Three of the slowest
 * jobs in the pipeline were spending nine tenths of their time on package
 * installation, and nothing would have noticed the flag going missing again.
 */
final class WorkflowComposerInstallTest extends TestCase
{
  private const WORKFLOWS = __DIR__ . '/../.github/workflows';

  /**
   * @return array<int, array{0: string}>
   */
  public static function workflowProvider(): array
  {
    $files = glob(self::WORKFLOWS . '/*.yml') ?: [];

    return array_map(static fn ($f) => [basename($f)], $files);
  }

  /**
   * @dataProvider workflowProvider
   */
  public function testComposerInstallAsksForDistArchives(string $workflow): void
  {
    $src = file_get_contents(self::WORKFLOWS . '/' . $workflow);
    self::assertIsString($src);

    // Each invocation, with whatever follows it on the same line.
    if (!preg_match_all('/composer install[^\n]*/', $src, $matches)) {
      $this->addToAssertionCount(1);
      return;
    }

    foreach ($matches[0] as $invocation) {
      $this->assertStringContainsString('--prefer-dist', $invocation,
        "$workflow runs `$invocation`. Without --prefer-dist Composer clones "
        . 'each package from source, which took 301 seconds where the dist '
        . 'install takes 6.');
    }
  }
}
