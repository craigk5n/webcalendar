<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use WebCalendar\Api\PrivatePathPolicy;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/McpServerFixture.php';

final class PrivatePathPolicyTest extends TestCase
{
  private string $root;
  private PrivatePathPolicy $policy;

  protected function setUp(): void
  {
    $this->root = McpServerFixture::tempPath('private-path-policy', '');
    (new Filesystem())->mkdir([$this->root . '/public/calendar', $this->root . '/secure/target',
      $this->root . '/secure/unused', $this->root . '/publicity']);
    $this->policy = new PrivatePathPolicy($this->root . '/public/calendar', $this->root . '/public');
  }

  protected function tearDown(): void
  {
    if (isset($this->root)) {
      (new Filesystem())->remove($this->root);
    }
  }

  public function testPrivatePathsNormalizeDotSegmentsAndTrailingSeparators(): void
  {
    self::assertSame($this->root . '/secure/target', $this->policy->resolve($this->root . '/secure/./unused/../target//'));
    self::assertSame($this->root . '/publicity', $this->policy->resolve($this->root . '/publicity/'));
  }

  public function testMissingPrivateChildrenResolveWithoutCreatingDirectories(): void
  {
    $path = $this->root . '/secure/target/new/nested';
    self::assertSame($path, $this->policy->resolve($path, true));
    self::assertDirectoryDoesNotExist($path);
  }

  public function testPrivateAliasesToPrivateTargetsAreSupported(): void
  {
    (new Filesystem())->symlink($this->root . '/secure/target', $this->root . '/secure/alias');
    self::assertSame($this->root . '/secure/target/new', $this->policy->resolve($this->root . '/secure/alias/new', true));
  }

  public function testRetargetedAliasDoesNotReuseCachedPrivateTarget(): void
  {
    $alias = $this->root . '/secure/alias';
    (new Filesystem())->symlink($this->root . '/secure/target', $alias);
    self::assertSame($this->root . '/secure/target', $this->policy->resolve($alias));
    $process = proc_open([PHP_BINARY, '-r', 'unlink($argv[1]); exit(symlink($argv[2], $argv[1]) ? 0 : 1);',
      $alias, $this->root . '/public'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    self::assertSame(0, proc_close($process), $output);
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($alias);
  }

  public function testMissingChildrenOfPrivateAliasIntoPublicDirectoryAreRejected(): void
  {
    (new Filesystem())->symlink($this->root . '/public', $this->root . '/secure/public-link');
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($this->root . '/secure/public-link/new/nested', true);
  }

  public function testIntermediatePublicAliasCannotBeHiddenByAnotherOutwardLink(): void
  {
    $fs = new Filesystem();
    $fs->symlink($this->root . '/public', $this->root . '/secure/public-link');
    $fs->symlink($this->root . '/secure/target', $this->root . '/public/outward');
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($this->root . '/secure/public-link/outward');
  }

  public static function chainedDestinations(): array
  {
    return [
      'absolute existing' => [false, ''],
      'relative existing' => [true, ''],
      'absolute missing children' => [false, '/new/nested'],
      'relative missing children' => [true, '/new/nested'],
    ];
  }

  /** @dataProvider chainedDestinations */
  public function testTwoLinkChainCannotHideAPublicDestination(bool $relative, string $suffix): void
  {
    symlink($relative ? '../secure/target' : $this->root . '/secure/target', $this->root . '/public/outward');
    symlink($relative ? '../public/outward' : $this->root . '/public/outward', $this->root . '/secure/start');
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($this->root . '/secure/start' . $suffix, $suffix !== '');
  }

  public function testLeafFileChainCannotHideAPublicDestination(): void
  {
    file_put_contents($this->root . '/secure/target/settings', 'synthetic-private-settings');
    symlink($this->root . '/secure/target/settings', $this->root . '/public/outward-file');
    symlink($this->root . '/public/outward-file', $this->root . '/secure/start-file');
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($this->root . '/secure/start-file');
  }

  public function testWhollyPrivateChainsRemainSupported(): void
  {
    symlink('target', $this->root . '/secure/second');
    symlink('second', $this->root . '/secure/first');
    self::assertSame($this->root . '/secure/target', $this->policy->resolve($this->root . '/secure/first'));
  }

  public function testFiniteRepeatedLinkIsNotMistakenForACycle(): void
  {
    symlink('.', $this->root . '/secure/repeat');
    self::assertSame($this->root . '/secure/target', $this->policy->resolve($this->root . '/secure/repeat/repeat/target'));
  }

  public function testDotDotIsAppliedAfterThePrecedingLinkIsExpanded(): void
  {
    (new Filesystem())->mkdir([$this->root . '/other/nested', $this->root . '/other/target']);
    symlink($this->root . '/other/nested', $this->root . '/secure/jump');
    self::assertSame($this->root . '/other/target', $this->policy->resolve($this->root . '/secure/jump/../target'));
  }

  public function testDotDotInLinkTargetCannotEraseAPublicHop(): void
  {
    symlink($this->root . '/secure/unused', $this->root . '/public/outward');
    symlink($this->root . '/public/outward', $this->root . '/secure/middle');
    symlink('middle/../target', $this->root . '/secure/start');
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($this->root . '/secure/start');
  }

  public function testMissingProjectionResumesInspectionAfterDotDot(): void
  {
    symlink($this->root . '/secure/target', $this->root . '/public/outward');
    symlink($this->root . '/public/outward', $this->root . '/secure/start');
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($this->root . '/secure/missing/../start/new', true);
  }

  public static function cyclicDestinations(): array
  {
    return ['self' => ['self'], 'two nodes' => ['pair'], 'growing suffix' => ['growing']];
  }

  /** @dataProvider cyclicDestinations */
  public function testSymlinkCyclesFailCleanly(string $kind): void
  {
    $first = $this->root . '/secure/cycle';
    if ($kind === 'pair') {
      symlink('cycle-two', $first);
      symlink('cycle', $this->root . '/secure/cycle-two');
    } else {
      symlink($kind === 'self' ? 'cycle' : 'cycle/child', $first);
    }
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($first, true);
  }

  public static function inspectionModes(): array
  {
    return ['existing path' => [false], 'missing children permitted' => [true]];
  }

  /** @dataProvider inspectionModes */
  public function testUninspectableChainIsNotAcceptedUsingItsFinalTarget(bool $allowMissing): void
  {
    symlink($this->root . '/secure/target', $this->root . '/public/outward');
    symlink($this->root . '/public/outward', $this->root . '/secure/start');
    $allowed = $this->root . '/public' . PATH_SEPARATOR . $this->root . '/secure/target'
      . PATH_SEPARATOR . dirname(__DIR__);
    $script = <<<'PHP'
      require $argv[1] . '/vendor/autoload.php';
      $policy = new WebCalendar\Api\PrivatePathPolicy($argv[2] . '/public/calendar', $argv[2] . '/public');
      try {
        $policy->resolve($argv[2] . '/secure/start', $argv[3] === '1');
        echo json_encode(['rejected' => false]);
      } catch (RuntimeException $exception) {
        echo json_encode(['rejected' => true]);
      }
      PHP;
    $process = proc_open([PHP_BINARY, '-d', 'open_basedir=' . $allowed, '-d', 'display_errors=stderr',
      '-r', $script, dirname(__DIR__), $this->root, $allowMissing ? '1' : '0'],
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    self::assertSame(0, proc_close($process), $output);
    self::assertSame('{"rejected":true}', $output);
  }

  public function testBrokenSymlinkIsNotTreatedAsAnOrdinaryMissingDirectory(): void
  {
    (new Filesystem())->symlink($this->root . '/missing-target', $this->root . '/secure/broken');
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($this->root . '/secure/broken/new', true);
  }

  public static function invalidBoundaries(): array
  {
    return ['empty' => [''], 'relative' => ['public'], 'missing' => ['/nonexistent-webcalendar-public-root']];
  }

  /** @dataProvider invalidBoundaries */
  public function testSuppliedInvalidBoundaryIsNotTreatedAsAbsent(string $boundary): void
  {
    $this->expectException(RuntimeException::class);
    new PrivatePathPolicy($this->root . '/public/calendar', $boundary);
  }

  public function testAbsentBroaderBoundaryIsSupportedForCli(): void
  {
    $policy = new PrivatePathPolicy($this->root . '/public/calendar');
    self::assertSame($this->root . '/secure/target', $policy->resolve($this->root . '/secure/target'));
  }

  public static function invalidCandidates(): array
  {
    return ['empty' => [''], 'relative' => ['private/storage'], 'nul' => ["/private/\0storage"]];
  }

  /** @dataProvider invalidCandidates */
  public function testMalformedOrRelativeCandidateIsRejected(string $path): void
  {
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($path, true);
  }

  public function testPublicRootItselfIsNotPrivateStorage(): void
  {
    $this->expectException(RuntimeException::class);
    $this->policy->resolve($this->root . '/public/');
  }
}
