<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * A reusable workflow may not build its concurrency group from
 * ${{ github.workflow }}.
 *
 * Inside a workflow called with `uses: ./.github/workflows/x.yml`, that
 * expression is the name of the CALLER, not of the file it appears in. So
 * ci.yml, phpstan.yml and test-web-wizard.yml -- all called by release.yml --
 * each evaluated it to "Create Release" and landed in one concurrency group
 * keyed "Create Release-refs/heads/<ref>".
 *
 * GitHub permits one pending run per group and cancels the older pending one
 * when a newer arrives, so release.yml cancelled one of its own test jobs.
 * Cutting v1.9.24 on 2026-09-26 failed that way: PHPStan was cancelled one
 * second in, having run no steps, and `build` needs it, so nothing was
 * published. Which of the three loses is a matter of scheduling order, which
 * is why it had not happened before.
 *
 * The concurrency blocks were added earlier in the same release to stop
 * superseded pull request runs from occupying runners, and that behaviour is
 * unchanged: on a pull request there is no caller, so the group was already
 * the workflow's own name, and a literal prefix per file is the same thing
 * said in a way that does not change meaning when the workflow is called.
 */
final class ReusableWorkflowConcurrencyTest extends TestCase
{
  private const DIR = __DIR__ . '/../.github/workflows';

  /**
   * The group expression a workflow declares, or null when it declares none.
   *
   * Read with comments stripped. The fix carries a comment explaining the
   * trap, and that comment names ${{ github.workflow }} -- so a test that
   * searched the raw text would be satisfied by the very prose describing the
   * bug. Seven guards in this suite failed exactly that way.
   */
  private function concurrencyGroup(string $file): ?string
  {
    $yaml = SourceText::yaml((string) file_get_contents($file));

    if (preg_match('/^concurrency:\s*$\s*^\s+group:\s*(.+)$/m', $yaml, $m)
      !== 1) {
      return null;
    }

    return trim($m[1]);
  }

  /**
   * @return list<string>
   */
  private function workflows(): array
  {
    $files = glob(self::DIR . '/*.yml');
    self::assertIsArray($files);
    self::assertNotEmpty($files, 'no workflow files found');

    return array_values($files);
  }

  /**
   * caller file => list of the workflow files it calls.
   *
   * @return array<string, list<string>>
   */
  private function callGraph(): array
  {
    $graph = [];

    foreach ($this->workflows() as $file) {
      $yaml = SourceText::yaml((string) file_get_contents($file));
      if (preg_match_all('#^\s*uses:\s*\./\.github/workflows/([\w.-]+\.yml)#m',
        $yaml, $m) === 0) {
        continue;
      }
      $graph[basename($file)] = array_values(array_unique($m[1]));
    }

    return $graph;
  }

  public function testReleaseStillCallsOtherWorkflows(): void
  {
    $graph = $this->callGraph();

    self::assertArrayHasKey('release.yml', $graph,
      'release.yml must call the test workflows; if that changed, the rest of '
      . 'this test is checking nothing');
    self::assertGreaterThan(1, count($graph['release.yml']),
      'the collision needs two or more callees to be possible');
  }

  public function testNoCalledWorkflowKeysItsGroupOnTheCallersName(): void
  {
    $offenders = [];

    foreach ($this->callGraph() as $caller => $callees) {
      foreach ($callees as $callee) {
        $path = self::DIR . '/' . $callee;
        if (!is_file($path)) {
          continue;
        }
        $group = $this->concurrencyGroup($path);
        if ($group !== null && str_contains($group, 'github.workflow')) {
          $offenders[] = "$callee (called by $caller) keys its concurrency "
            . "group on github.workflow, which resolves to \"$caller\": $group";
        }
      }
    }

    self::assertSame([], $offenders, "A called workflow must not use "
      . "github.workflow in its concurrency group -- inside a reusable "
      . "workflow that is the caller's name:\n  "
      . implode("\n  ", $offenders));
  }

  public function testNoCallerHasTwoCalleesInOneConcurrencyGroup(): void
  {
    $offenders = [];

    foreach ($this->callGraph() as $caller => $callees) {
      $seen = [];
      foreach ($callees as $callee) {
        $path = self::DIR . '/' . $callee;
        if (!is_file($path)) {
          continue;
        }
        $group = $this->concurrencyGroup($path);
        if ($group === null) {
          continue;
        }
        // github.workflow is identical for every callee of one caller, so
        // compare what the group actually evaluates to for them.
        $resolved = str_replace('${{ github.workflow }}', $caller, $group);
        if (isset($seen[$resolved])) {
          $offenders[] = "$caller calls both {$seen[$resolved]} and $callee, "
            . "and both resolve to the concurrency group: $resolved";
        }
        $seen[$resolved] = $callee;
      }
    }

    self::assertSame([], $offenders, "Two workflows called by the same caller "
      . "share a concurrency group. GitHub keeps one pending run per group and "
      . "cancels the older, so a release cancels its own test jobs:\n  "
      . implode("\n  ", $offenders));
  }
}
