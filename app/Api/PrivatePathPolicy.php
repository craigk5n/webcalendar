<?php

declare(strict_types=1);

namespace WebCalendar\Api;

use RuntimeException;

final class PrivatePathPolicy
{
  private const FILE_TYPE_MASK = 0170000;
  private const DIRECTORY_TYPE = 0040000;
  private const SYMLINK_TYPE = 0120000;
  private const MAX_SYMLINK_EXPANSIONS = 40;

  private array $publicRoots = [];
  private array $canonicalRoots = [];
  private array $boundaries;

  public function __construct(string $installation, ?string $documentRoot = null)
  {
    $this->boundaries = [$installation, $documentRoot];
    $this->refreshRoots();
  }

  private function refreshRoots(): void
  {
    clearstatcache(true);
    $this->publicRoots = $this->canonicalRoots = [];
    foreach ($this->boundaries as $root) {
      if ($root === null) {
        continue;
      }
      $access = $this->normalize($root);
      $resolved = @realpath($root);
      if ($resolved === false || !@is_dir($resolved)) {
        throw new RuntimeException('Cannot resolve a supplied API public-directory boundary.');
      }
      $this->publicRoots[] = $access;
      $this->publicRoots[] = $this->canonicalRoots[] = $this->normalize($resolved);
    }
    $this->publicRoots = array_unique($this->publicRoots);
  }

  public function resolve(string $path, bool $allowMissing = false): string
  {
    $this->refreshRoots();
    $this->assertPrivate($this->normalize($path));
    [$current, $pending] = $this->splitAbsolute($path);
    $expansions = $missingDepth = 0;
    $targetDepth = 0;
    $projected = false;
    $visited = [];
    while ($pending !== []) {
      $component = array_shift($pending);
      if ($component === null) {
        $targetDepth--;
        continue;
      }
      if ($component === '' || $component === '.') {
        continue;
      }
      if ($component === '..') {
        $current = $this->normalize($current . '/..');
        $missingDepth = max(0, $missingDepth - 1);
        $this->assertPrivate($current);
        continue;
      }
      $next = $this->normalize($current . '/' . $component);
      $this->assertPrivate($next);
      if ($missingDepth > 0) {
        $current = $next;
        $missingDepth++;
        continue;
      }
      $metadata = $this->inspectComponent($next);
      if ($metadata === false) {
        if (!$allowMissing || $targetDepth !== 0 || !$this->isAbsentChild($current, $component)) {
          throw new RuntimeException('Cannot inspect an API configuration or storage path component.');
        }
        $current = $next;
        $missingDepth = 1;
        $projected = true;
        continue;
      }
      $type = $metadata['mode'] & self::FILE_TYPE_MASK;
      if ($type === self::SYMLINK_TYPE) {
        $target = @readlink($next);
        if ($target === false || $target === '' || str_contains($target, "\0")) {
          throw new RuntimeException('Cannot inspect an API path symlink destination.');
        }
        $destination = $this->linkDestination($current, $target);
        $this->assertPrivate($this->normalize($destination));
        [$current, $targetComponents] = $this->splitAbsolute($destination);
        // The marker ends an existing link target before optional missing children.
        $pending = array_merge($targetComponents, [null], $pending);
        $targetDepth++;
        $remainingPath = array_values(array_filter($pending, static fn ($part): bool => $part !== null));
        $state = $this->comparison(serialize([$current, $remainingPath]));
        if (isset($visited[$state]) || ++$expansions > self::MAX_SYMLINK_EXPANSIONS) {
          throw new RuntimeException('API path symlink expansion contains a loop or exceeds the traversal limit.');
        }
        $visited[$state] = true;
        continue;
      }
      $remainingPath = array_filter($pending, static fn ($part): bool => $part !== null);
      if ($remainingPath !== [] && $type !== self::DIRECTORY_TYPE) {
        throw new RuntimeException('An API path component is not a directory.');
      }
      $current = $next;
    }
    $this->assertPrivate($current);
    if (!$projected) {
      $resolved = @realpath($path);
      if ($resolved === false || $this->comparison($this->normalize($resolved)) !== $this->comparison($current)) {
        throw new RuntimeException('The API path could not be verified after symlink expansion.');
      }
      return $this->normalize($resolved);
    }
    return $current;
  }

  private function inspectComponent(string $path): array|false
  {
    $metadata = @lstat($path);
    if ($metadata !== false) {
      return $metadata;
    }
    if ($this->isCanonicalAncestor($path)) {
      // A canonical boundary proves its own ancestors are non-symlink directories.
      return ['mode' => self::DIRECTORY_TYPE];
    }
    return false;
  }

  private function isCanonicalAncestor(string $path): bool
  {
    $path = $this->comparison($path);
    foreach ($this->canonicalRoots as $root) {
      $root = $this->comparison($root);
      if ($root === $path || str_starts_with($root, rtrim($path, '/') . '/')) {
        return true;
      }
    }
    return false;
  }

  private function isAbsentChild(string $parent, string $name): bool
  {
    $entries = @scandir($parent);
    if ($entries === false) {
      return false;
    }
    foreach ($entries as $entry) {
      if ($this->comparison($entry) === $this->comparison($name)) {
        return false;
      }
    }
    return true;
  }

  private function linkDestination(string $parent, string $target): string
  {
    if (DIRECTORY_SEPARATOR === '\\') {
      $target = str_replace('\\', '/', $target);
      if (preg_match('~^[A-Za-z]:/~', $target) || str_starts_with($target, '//')) {
        return $target;
      }
      if (str_starts_with($target, '/') || preg_match('~^[A-Za-z]:~', $target)) {
        throw new RuntimeException('An API symlink target must be relative or fully qualified.');
      }
    } elseif (str_starts_with($target, '/')) {
      return $target;
    }
    return rtrim($parent, '/') . '/' . $target;
  }

  private function comparison(string $path): string
  {
    return DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path;
  }

  private function assertPrivate(string $path): void
  {
    $path = $this->comparison($path);
    foreach ($this->publicRoots as $root) {
      $root = $this->comparison($root);
      if ($path === $root || str_starts_with($path, rtrim($root, '/') . '/')) {
        throw new RuntimeException('API configuration and storage must be outside public directories.');
      }
    }
  }

  private function normalize(string $path): string
  {
    [$prefix, $components] = $this->splitAbsolute($path);
    $segments = [];
    foreach ($components as $segment) {
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

  private function splitAbsolute(string $path): array
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
    return [$prefix, explode('/', $remainder)];
  }
}
