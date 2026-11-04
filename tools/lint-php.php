<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit;
}

$root = dirname(__DIR__);
$minimumVersions = [
  // The upstream loader requires this constant-attribute file only on PHP 8.5+.
  'includes/classes/mcp-sdk/symfony/polyfill-uuid/bootstrap85.php' => 80500,
];
$files = array_slice($argv, 1);
if ($files === []) {
  $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    static fn (SplFileInfo $file): bool => !in_array($file->getPathname(), [$root . '/vendor', $root . '/.git'], true)
  ));
  foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
      $files[] = $file->getPathname();
    }
  }
}
sort($files);
$errors = $checked = $skipped = 0;
foreach ($files as $file) {
  $path = realpath($file);
  if ($path === false || !is_file($path)) {
    fwrite(STDERR, "Missing PHP file: $file\n");
    $errors++;
    continue;
  }
  $relative = str_starts_with($path, $root . DIRECTORY_SEPARATOR) ? substr($path, strlen($root) + 1) : $path;
  $minimum = $minimumVersions[$relative] ?? 80200;
  if (PHP_VERSION_ID < $minimum) {
    print "Skipped $relative: requires PHP " . intdiv($minimum, 10000) . '.' . intdiv($minimum % 10000, 100) . ".\n";
    $skipped++;
    continue;
  }
  $process = proc_open([PHP_BINARY, '-l', $path], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
  if (!is_resource($process)) {
    throw new RuntimeException('Cannot start the PHP syntax checker.');
  }
  fclose($pipes[0]);
  print stream_get_contents($pipes[1]);
  print stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  $errors += proc_close($process) === 0 ? 0 : 1;
  $checked++;
}
print "PHP " . PHP_VERSION . ": $checked checked, $skipped version-specific skips, $errors errors.\n";
exit($errors > 0 ? 1 : 0);
