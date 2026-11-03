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
