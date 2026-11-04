<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TinyMceBundleTest extends TestCase
{
  public function testShippedEditorAndPluginsMatchTheAuditedDependency(): void
  {
    $root = dirname(__DIR__);
    foreach (['tinymce.min.js', 'models/dom/model.min.js', 'themes/silver/theme.min.js',
      'plugins/lists/plugin.min.js', 'plugins/link/plugin.min.js', 'plugins/table/plugin.min.js',
      'plugins/code/plugin.min.js', 'plugins/autolink/plugin.min.js'] as $path) {
      self::assertSame(hash_file('sha256', $root . '/vendor/tinymce/tinymce/' . $path),
        hash_file('sha256', $root . '/pub/tinymce/' . $path), 'Rebuild the shipped TinyMCE assets: ' . $path);
    }
  }
}
