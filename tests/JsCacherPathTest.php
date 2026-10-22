<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";

/**
 * Regression tests for the js_cacher.php path-containment fix.
 *
 * js_cacher.php used the readdir() listing of includes/js (which includes
 * '.' and '..') as its allowlist, so inc=js/../<path> passed the check and
 * was include_once'd -- an unauthenticated local file include / PHP
 * execution. The fix moves the decision into js_cacher_safe_path(), which
 * must confine the resolved file to includes/js (or includes/htmlarea) while
 * still accepting the legitimate 'true'/'false' trailing marker.
 *
 * @covers ::js_cacher_safe_path
 */
final class JsCacherPathTest extends TestCase
{
  /** includes/ directory, as js_cacher.php passes it. */
  private function base(): string
  {
    return __DIR__ . '/../includes';
  }

  public function test_helper_exists(): void
  {
    $this->assertTrue(
      function_exists('js_cacher_safe_path'),
      'js_cacher_safe_path() must exist (the containment decision point).'
    );
  }

  public function test_accepts_plain_js_asset(): void
  {
    $got = js_cacher_safe_path('js/popups.js', $this->base());
    $this->assertNotFalse($got);
    $this->assertSame(realpath($this->base() . '/js/popups.js'), $got);
  }

  public function test_accepts_php_asset_with_false_marker(): void
  {
    // Real callers pass e.g. js/availability.php/false/ -- the /false/ is a
    // parameter marker consumed by the included script, not part of the path.
    foreach (['js/availability.php/false/', 'js/catsel.php/false/'] as $inc) {
      $file = explode('/', $inc)[1];
      $got = js_cacher_safe_path($inc, $this->base());
      $this->assertNotFalse($got, "legit include rejected: $inc");
      $this->assertSame(realpath($this->base() . '/js/' . $file), $got);
    }
  }

  public function test_returned_path_is_contained_in_js_dir(): void
  {
    $root = realpath($this->base() . '/js');
    $got = js_cacher_safe_path('js/popups.js', $this->base());
    $this->assertNotFalse($got);
    $this->assertStringStartsWith($root . DIRECTORY_SEPARATOR, $got);
  }

  /**
   * @dataProvider traversalPayloads
   */
  public function test_rejects_traversal_and_special_segments(string $inc): void
  {
    $this->assertFalse(
      js_cacher_safe_path($inc, $this->base()),
      "unsafe inc was not rejected: " . addslashes($inc)
    );
  }

  public static function traversalPayloads(): array
  {
    return [
      'parent-escape-to-etc'   => ['js/../../../etc/passwd'],
      'dotdot-into-includes'   => ['js/../settings.php'],
      'bare-dotdot'            => ['js/..'],
      'single-dot'            => ['js/./popups.js'],
      'null-byte-segment'      => ["js/\0etc/passwd"],
      'wrong-root-includes'    => ['includes/settings.php'],
      'wrong-root-arbitrary'   => ['foo/bar.js'],
      'directory-only'         => ['js'],
      'directory-trailing'     => ['js/'],
      'nonexistent-file'       => ['js/this-file-does-not-exist.js'],
      'empty'                  => [''],
    ];
  }
}
