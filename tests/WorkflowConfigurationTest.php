<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WorkflowConfigurationTest extends TestCase
{
  private const WORKFLOW_DIRECTORY = __DIR__ . '/../.github/workflows';

  public static function publishingJobs(): array
  {
    return [
      'development' => ['docker-dev.yml', 'push_dev_to_registry'],
      'release' => ['docker.yml', 'push_to_registry'],
    ];
  }

  /**
   * @dataProvider publishingJobs
   */
  public function testPublishingRequiresJobLevelOptIn(string $filename, string $job): void
  {
    $source = file_get_contents(self::WORKFLOW_DIRECTORY . '/' . $filename);
    self::assertIsString($source);
    self::assertSame(1, preg_match(
      '/^  ' . preg_quote($job, '/') . ':[ \t]*\r?\n(.*?)(?=^  [\w-]+:|\z)/ms',
      $source,
      $matches
    ), "$filename must declare publishing job $job");

    self::assertMatchesRegularExpression(
      "/^    if:[ \t]+vars\\.PUBLISH_DOCKER_IMAGES[ \t]*==[ \t]*'true'[ \t]*\\r?$/m",
      $matches[1],
      "$filename must gate the entire publishing job before any steps run"
    );
  }

  public static function workflowFiles(): array
  {
    $files = array_merge(
      glob(self::WORKFLOW_DIRECTORY . '/*.yml') ?: [],
      glob(self::WORKFLOW_DIRECTORY . '/*.yaml') ?: []
    );
    sort($files);

    $cases = [];
    foreach ($files as $file) {
      $cases[basename($file)] = [$file];
    }
    return $cases;
  }

  /**
   * @dataProvider workflowFiles
   */
  public function testWorkflowRunnersRemainPinned(string $file): void
  {
    $source = file_get_contents($file);
    self::assertIsString($source);
    preg_match_all('/^[ \t]*runs-on:[ \t]*([^\r\n]*)/m', $source, $matches);

    foreach ($matches[1] as $runner) {
      self::assertSame('ubuntu-24.04', trim($runner, " \t\"'"),
        basename($file) . ' must retain the pinned Ubuntu runner');
    }
  }
}
