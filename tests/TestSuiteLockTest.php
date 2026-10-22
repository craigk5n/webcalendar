<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * The suite cannot run concurrently with itself. McpIntegrationTest installs
 * into a fixed SQLite file in the temp directory and serves it on port 8099,
 * and the other MCP tests use 8100-8104, so two runs delete each other's
 * database and fight over the ports. That surfaces as a scatter of unrelated
 * errors -- 25 in the incident that prompted the lock -- which points nowhere
 * near the cause.
 *
 * tests/bootstrap.php takes an exclusive lock for the run. These tests
 * exercise it for real: this process is itself holding that lock, so a child
 * that tries to take it must be refused, and a child told the lock is already
 * held must proceed.
 *
 * The second case is not hypothetical. Without it, every test that PHPUnit
 * runs in a separate process became an error, because the child re-runs the
 * bootstrap and blocked on its own parent -- 64 errors, introduced and caught
 * while writing this.
 *
 * Note the deliberate avoidance of writing that annotation's name with its
 * leading "at" sign anywhere in these docblocks. PHPUnit parses annotations
 * out of docblock prose, so merely mentioning it turns this class into a
 * separate-process run, which then serialises \$GLOBALS -- and by this point in
 * the suite that holds an SQLite3 connection, so the whole run dies with
 * "Serialization of 'SQLite3' is not allowed". That cost an hour.
 */
final class TestSuiteLockTest extends TestCase
{
  private const BOOTSTRAP = __DIR__ . '/bootstrap.php';

  protected function setUp(): void
  {
    parent::setUp();

    if (getenv('WEBCAL_TEST_ALLOW_PARALLEL') === '1') {
      $this->markTestSkipped('the lock is disabled for this run');
    }
  }

  /**
   * @return array{0: int, 1: string} exit code and stderr
   */
  private function runBootstrap(array $env): array
  {
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(self::BOOTSTRAP);

    // Inherit this process's environment, then apply the overrides, so the
    // child sees the same world a PHPUnit subprocess would.
    $childEnv = getenv();
    foreach ($env as $k => $v) {
      $childEnv[$k] = $v;
    }

    $proc = proc_open($cmd, $descriptors, $pipes, null, $childEnv);
    self::assertIsResource($proc);

    stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    foreach ($pipes as $pipe) {
      fclose($pipe);
    }

    return [proc_close($proc), $stderr];
  }

  /**
   * This process holds the lock, so a child taking it fresh must be refused.
   */
  public function testASecondRunIsRefusedWhileThisOneHoldsTheLock(): void
  {
    [$code, $stderr] = $this->runBootstrap(['WEBCAL_TEST_LOCK_HELD' => '']);

    $this->assertSame(1, $code, 'a concurrent run must exit non-zero');
    $this->assertStringContainsString('already in progress', $stderr);
    $this->assertStringContainsString('WEBCAL_TEST_ALLOW_PARALLEL', $stderr,
      'the refusal must say how to override it');
  }

  /**
   * PHPUnit spawns a child for each test it runs in a separate process, and
   * that child re-runs the bootstrap. It must not block on its own parent.
   */
  public function testASeparateProcessChildProceeds(): void
  {
    [$code, $stderr] = $this->runBootstrap(['WEBCAL_TEST_LOCK_HELD' => '1']);

    $this->assertSame(0, $code,
      'a separate-process child must not block on the parent that spawned it; '
      . 'stderr was: ' . $stderr);
  }

  public function testTheEscapeHatchSkipsTheLockEntirely(): void
  {
    [$code] = $this->runBootstrap([
      'WEBCAL_TEST_LOCK_HELD' => '',
      'WEBCAL_TEST_ALLOW_PARALLEL' => '1',
    ]);

    $this->assertSame(0, $code);
  }

  /**
   * The lock only does anything if PHPUnit is told to load the bootstrap.
   */
  public function testPhpunitConfigLoadsTheBootstrap(): void
  {
    $config = file_get_contents(__DIR__ . '/phpunit.xml');
    $this->assertIsString($config);
    $this->assertStringContainsString('bootstrap="bootstrap.php"', $config,
      'tests/phpunit.xml must load bootstrap.php or the lock is inert');
  }
}
