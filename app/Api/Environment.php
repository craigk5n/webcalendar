<?php

declare(strict_types=1);

namespace WebCalendar\Api;

use Dotenv\Dotenv;
use Dotenv\Exception\ExceptionInterface;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\Adapter\ServerConstAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use RuntimeException;

final class Environment
{
  private bool $fileLoaded = false;
  private PrivatePathPolicy $paths;

  private function __construct(
    private string $installation,
    private string $privateDirectory,
    ?string $documentRoot,
    private RepositoryInterface $repository
  ) {
    $this->paths = new PrivatePathPolicy($installation, $documentRoot);
  }

  public static function directory(string $installation): string
  {
    $root = @realpath($installation);
    if ($root === false) {
      throw new RuntimeException('Cannot locate the WebCalendar installation.');
    }
    return dirname($root) . '/private/' . basename($root);
  }

  public static function load(string $installation, ?string $documentRoot = null): self
  {
    $repository = RepositoryBuilder::createWithNoAdapters()
      ->addReader(PutenvAdapter::class)
      ->addReader(ServerConstAdapter::class)
      ->addReader(EnvConstAdapter::class)
      ->addAdapter(ArrayAdapter::class)
      ->immutable()->make();
    $directory = self::directory($installation);
    $environment = new self($installation, $directory, $documentRoot, $repository);
    $file = $directory . '/.env';
    if (@is_dir($directory) && !is_readable($directory)) {
      throw new RuntimeException('Cannot read the private API configuration directory.');
    }
    if (@file_exists($file) || @is_link($file)) {
      $resolved = $environment->resolvePrivatePath($file);
      if (!@is_file($resolved) || !@is_readable($resolved)) {
        throw new RuntimeException('Cannot read the private API environment file.');
      }
      try {
        Dotenv::create($repository, dirname($resolved), basename($resolved))->load();
      } catch (ExceptionInterface $exception) {
        throw new RuntimeException('Cannot load the private API environment file. Check its syntax and permissions.');
      }
      $environment->fileLoaded = true;
    }
    return $environment;
  }

  public function get(string $name, ?string $default = null): ?string
  {
    return $this->repository->get($name) ?? $default;
  }

  public function enabled(): bool
  {
    return filter_var($this->get('WEBCALENDAR_API_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
  }

  public function storagePath(): string
  {
    $configured = $this->get('WEBCALENDAR_API_STORAGE');
    if ($configured !== null && $configured !== '') {
      return $configured;
    }
    return $this->fileLoaded ? $this->privateDirectory . '/storage'
      : sys_get_temp_dir() . '/webcalendar-api-' . substr(hash('sha256', $this->installation), 0, 16);
  }

  public function assertOutsideDocumentRoot(string $path): void
  {
    $this->paths->resolve($path);
  }

  public function resolvePrivatePath(string $path, bool $allowMissing = false): string
  {
    return $this->paths->resolve($path, $allowMissing);
  }
}
