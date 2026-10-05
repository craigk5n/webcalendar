<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * js_cacher.php?inc= must only ever include a file directly inside
 * includes/js/.
 *
 * The check used to look at the first path segment and nothing else, then
 * append every segment, ".." included, to build the path it included. The
 * one later gate compared the second segment against readdir() of
 * includes/js, and readdir() lists "." and "..", so
 * inc=js/../../../../../etc/passwd was included for an anonymous visitor.
 *
 * Each case runs js_cacher.php in a child process. A rejected value must
 * return before includes/functions.php is loaded, so do_config() is still
 * undefined at shutdown; an accepted one gets as far as defining it (and
 * then fails for want of a database, which does not matter here).
 */
final class JsCacherPathTraversalTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  private function reachesConfig(string $inc): bool
  {
    $script = 'register_shutdown_function(function () {'
      . ' fwrite(STDERR, function_exists("do_config")'
      . ' ? "@@REACHED@@" : "@@REJECTED@@"); });'
      . ' $_GET["inc"] = ' . var_export($inc, true) . ';'
      . ' $_REQUEST = $_GET;'
      . ' include "js_cacher.php";';
    $cmd = escapeshellarg(PHP_BINARY) . ' -d display_errors=0 -r '
      . escapeshellarg($script);
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
      $pipes, realpath(self::ROOT), ['WEBCALENDAR_USE_ENV' => 'false']);
    $this->assertIsResource($proc);
    stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    proc_close($proc);

    $this->assertMatchesRegularExpression('/@@(REACHED|REJECTED)@@/',
      $stderr, "no verdict for inc=$inc");
    return str_contains($stderr, '@@REACHED@@');
  }

  public static function rejected(): array
  {
    return [
      'etc/passwd' => ['js/../../../../../etc/passwd'],
      'parent of js' => ['js/../functions.php'],
      'two levels up, the old print link' => ['js/../../year.php'],
      'dot segment' => ['js/./visible.php'],
      'missing file' => ['js/nonexistent.php'],
      'no file' => ['js'],
      'empty file' => ['js/'],
      'other directory' => ['../functions.php'],
      'htmlarea no longer exists' => ['htmlarea/../../year.php'],
      'absolute' => ['/etc/passwd'],
    ];
  }

  /** @dataProvider rejected */
  public function testRejectsAnythingOutsideJsDirectory(string $inc): void
  {
    $this->assertFalse($this->reachesConfig($inc));
  }

  public static function accepted(): array
  {
    return [
      'plain' => ['js/visible.php'],
      'with false flag' => ['js/edit_entry.php/false/'],
      'with arguments' => ['js/availability.php/false/x/10/5/2026/form'],
      // Used to be appended to the path; now only arguments, so this
      // includes js/visible.php and nothing else.
      'dots after the file name' => ['js/visible.php/../../config.php'],
    ];
  }

  /** @dataProvider accepted */
  public function testAcceptsFilesTheApplicationRequests(string $inc): void
  {
    $this->assertTrue($this->reachesConfig($inc));
  }
}
