<?php

declare(strict_types=1);

namespace WebCalendar\Api;

use RuntimeException;

final class PrivatePathPolicy
{
  private array $publicRoots = [];

  public function __construct(string $installation, ?string $documentRoot = null)
  {
    clearstatcache(true);
    foreach ([$installation, $documentRoot] as $root) {
      if ($root === null) {
        continue;
      }
      $access = $this->normalize($root);
      $resolved = @realpath($root);
      if ($resolved === false || !@is_dir($resolved)) {
        throw new RuntimeException('Cannot resolve a supplied API public-directory boundary.');
      }
      $this->publicRoots[] = $access;
      $this->publicRoots[] = $this->normalize($resolved);
    }
    $this->publicRoots = array_unique($this->publicRoots);
  }

  public function resolve(string $path, bool $allowMissing = false): string
  {
    clearstatcache(true);
    $this->assertPrivate($this->normalize($path));
    $ancestor = $path;
    $suffix = [];
    $resolved = @realpath($ancestor);
    while ($resolved === false) {
      if (!$allowMissing || @is_link($ancestor) || @file_exists($ancestor)) {
        throw new RuntimeException('Cannot resolve the API configuration or storage path.');
      }
      $parent = dirname($ancestor);
      if ($parent === $ancestor) {
        throw new RuntimeException('Cannot resolve an existing parent for API storage.');
      }
      array_unshift($suffix, basename($ancestor));
      $ancestor = $parent;
      $resolved = @realpath($ancestor);
    }
    if ($suffix !== [] && !@is_dir($resolved)) {
      throw new RuntimeException('The existing parent of API storage is not a directory.');
    }
    $result = $this->normalize($resolved . ($suffix === [] ? '' : '/' . implode('/', $suffix)));
    $this->assertPrivate($result);

    // Intermediate aliases can enter the public tree and then point outward again.
    for ($prefix = $path; ; $prefix = dirname($prefix)) {
      $access = $this->normalize($prefix);
      $this->assertPrivate($access);
      $target = @realpath($prefix);
      if ($target !== false) {
        $this->assertPrivate($this->normalize($target));
      } elseif (@is_link($prefix)) {
        throw new RuntimeException('Cannot resolve an API path symlink.');
      }
      if (str_ends_with($access, '/') || dirname($prefix) === $prefix) {
        break;
      }
    }
    return $result;
  }

  private function assertPrivate(string $path): void
  {
    if (DIRECTORY_SEPARATOR === '\\') {
      $path = strtolower($path);
    }
    foreach ($this->publicRoots as $root) {
      $root = DIRECTORY_SEPARATOR === '\\' ? strtolower($root) : $root;
      if ($path === $root || str_starts_with($path, rtrim($root, '/') . '/')) {
        throw new RuntimeException('API configuration and storage must be outside public directories.');
      }
    }
  }

  private function normalize(string $path): string
  {
    if ($path === '' || str_contains($path, "\0")) {
      throw new RuntimeException('API paths must be absolute filesystem paths.');
    }
    if (DIRECTORY_SEPARATOR === '\\') {
      $path = str_replace('\\', '/', $path);
      if (preg_match('~^[A-Za-z]:/~', $path, $matches)
          || preg_match('~^//[^/]+/[^/]+(?:/|$)~', $path, $matches)) {
        $prefix = rtrim($matches[0], '/') . '/';
        $remainder = substr($path, strlen($matches[0]));
      } else {
        throw new RuntimeException('API paths must be absolute filesystem paths.');
      }
    } elseif (str_starts_with($path, '/')) {
      $prefix = '/';
      $remainder = substr($path, 1);
    } else {
      throw new RuntimeException('API paths must be absolute filesystem paths.');
    }
    $segments = [];
    foreach (explode('/', $remainder) as $segment) {
      if ($segment === '' || $segment === '.') {
        continue;
      }
      if ($segment === '..') {
        array_pop($segments);
      } else {
        $segments[] = $segment;
      }
    }
    return $prefix . implode('/', $segments);
  }
}
