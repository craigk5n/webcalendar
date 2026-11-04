<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PhpLintTest extends TestCase
{
  public function testVersionSpecificUpstreamFileRetainsApplicableLintCoverage(): void
  {
    [$status, $output] = $this->lint(__DIR__ . '/../includes/classes/mcp-sdk/symfony/polyfill-uuid/bootstrap85.php');
    self::assertSame(0, $status, $output);
    self::assertStringContainsString(PHP_VERSION_ID < 80500 ? 'requires PHP 8.5' : 'No syntax errors', $output);
  }

  public function testUnrelatedInvalidPhpIsNeverSkippedByItsFilename(): void
  {
    $directory = sys_get_temp_dir() . '/php-lint-' . bin2hex(random_bytes(5));
    mkdir($directory);
    $path = $directory . '/bootstrap85.php';
    file_put_contents($path, '<?php function {');
    try {
      [$status, $output] = $this->lint($path);
      self::assertSame(1, $status, $output);
      self::assertStringContainsString('Errors parsing', $output);
    } finally {
      unlink($path);
      rmdir($directory);
    }
  }

  private function lint(string $path): array
  {
    $process = proc_open([PHP_BINARY, __DIR__ . '/../tools/lint-php.php', $path],
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $output];
  }
}
