<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../includes/functions.php";

/**
 * Regression tests for the category_handler.php icon path-traversal fix.
 *
 * category_handler.php read file_get_contents($icon_path . $urlname) with
 * $icon_path = 'wc-icons/' and $urlname an unfiltered POST field, so
 * urlname=../includes/settings.php read a file outside wc-icons/ and stored
 * its bytes in the category icon blob, disclosed via getIcon.php. The copy is
 * now confined by category_icon_safe_path().
 *
 * @covers ::category_icon_safe_path
 */
final class CategoryIconPathTest extends TestCase
{
  private function iconDir(): string
  {
    return __DIR__ . '/../wc-icons';
  }

  public function test_accepts_real_icon_in_directory(): void
  {
    $got = category_icon_safe_path('example.gif', $this->iconDir());
    $this->assertNotFalse($got);
    $this->assertSame(realpath($this->iconDir() . '/example.gif'), $got);
  }

  public function test_directory_components_are_stripped_to_basename(): void
  {
    // A leading path is reduced to its basename, so it can only ever name a
    // file directly inside wc-icons/.
    $got = category_icon_safe_path('anything/example.gif', $this->iconDir());
    $this->assertSame(realpath($this->iconDir() . '/example.gif'), $got);
  }

  public function test_returned_path_is_contained_in_icon_dir(): void
  {
    $base = realpath($this->iconDir());
    $got = category_icon_safe_path('example.gif', $this->iconDir());
    $this->assertNotFalse($got);
    $this->assertStringStartsWith($base . DIRECTORY_SEPARATOR, $got);
  }

  /**
   * @dataProvider unsafeNames
   */
  public function test_rejects_traversal_and_bad_extensions(string $urlname): void
  {
    $this->assertFalse(
      category_icon_safe_path($urlname, $this->iconDir()),
      'unsafe icon name not rejected: ' . addslashes($urlname)
    );
  }

  public static function unsafeNames(): array
  {
    return [
      'traversal-to-settings' => ['../includes/settings.php'],
      'deep-traversal'        => ['../../../etc/passwd'],
      'wrong-extension'       => ['example.txt'],
      'php-extension'         => ['shell.php'],
      'null-byte-smuggle'     => ["shell.php\0.gif"],
      'nonexistent-gif'       => ['no-such-icon.gif'],
      'empty'                 => [''],
    ];
  }
}
