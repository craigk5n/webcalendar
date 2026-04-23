#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * tools/build-manifest.php — emit MANIFEST.sha256 on stdout for the
 * signed-manifest feature (GitHub issue #233).
 *
 * Usage:
 *   php tools/build-manifest.php [--tree=DIR] [--list=FILE] [--version=X]
 *                                [--git-sha=Y] [--timestamp=ISO8601]
 *
 * Defaults:
 *   --tree       = .                       (directory containing the files)
 *   --list       = release-files           (one path per line, relative to --tree)
 *   --version    = composer.json's "version"
 *   --git-sha    = $GITHUB_SHA, else `git rev-parse HEAD`, else "unknown"
 *   --timestamp  = $SOURCE_DATE_EPOCH (for reproducible builds), else now UTC
 *
 * Reproducibility: if SOURCE_DATE_EPOCH is set to the same value across
 * runs, the output is byte-identical. The release workflow can either
 * pin SOURCE_DATE_EPOCH to the tag timestamp or accept per-run second
 * drift — the manifest is still verifiable either way; reproducibility
 * matters only for audit-trail recomputation.
 *
 * Exit codes:
 *   0 — manifest emitted successfully.
 *   1 — any listed file is missing / unreadable, or a flag is malformed.
 */

require_once __DIR__ . '/../includes/classes/Security/ManifestBuilder.php';

use WebCalendar\Security\ManifestBuilder;

/** @return array<string, string> */
function parse_flags(array $argv): array
{
  $flags = [];
  foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)=(.*)$/', $arg, $m)) {
      $flags[$m[1]] = $m[2];
    } elseif ($arg === '--help' || $arg === '-h') {
      $flags['help'] = '1';
    } else {
      fwrite(STDERR, "Unknown argument: $arg\n");
      exit(1);
    }
  }
  return $flags;
}

function read_version_from_composer(string $composerPath): string
{
  $json = @file_get_contents($composerPath);
  if ($json === false) {
    fwrite(STDERR, "ERROR: cannot read $composerPath\n");
    exit(1);
  }
  $data = json_decode($json, true);
  if (!is_array($data) || !isset($data['version']) || !is_string($data['version'])) {
    fwrite(STDERR, "ERROR: $composerPath has no 'version' key.\n");
    exit(1);
  }
  return $data['version'];
}

function resolve_git_sha(string $cliOverride = ''): string
{
  if ($cliOverride !== '') {
    return $cliOverride;
  }
  $env = getenv('GITHUB_SHA');
  if ($env !== false && $env !== '') {
    return $env;
  }
  $output = [];
  $status = 0;
  // @ to silence exec output on Windows / non-git environments.
  @exec('git rev-parse HEAD 2>/dev/null', $output, $status);
  if ($status === 0 && isset($output[0]) && preg_match('/^[0-9a-f]{7,40}$/', $output[0])) {
    return $output[0];
  }
  return 'unknown';
}

function resolve_timestamp(string $cliOverride = ''): DateTimeImmutable
{
  if ($cliOverride !== '') {
    try {
      return new DateTimeImmutable($cliOverride, new DateTimeZone('UTC'));
    } catch (Throwable $e) {
      fwrite(STDERR, "ERROR: --timestamp is not parseable: $cliOverride\n");
      exit(1);
    }
  }
  $sde = getenv('SOURCE_DATE_EPOCH');
  if ($sde !== false && $sde !== '' && ctype_digit($sde)) {
    return (new DateTimeImmutable('@' . $sde))->setTimezone(new DateTimeZone('UTC'));
  }
  return new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

function read_path_list(string $listPath): array
{
  $contents = @file_get_contents($listPath);
  if ($contents === false) {
    fwrite(STDERR, "ERROR: cannot read list file '$listPath'\n");
    exit(1);
  }
  $paths = [];
  foreach (explode("\n", $contents) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
      continue;
    }
    $paths[] = $line;
  }
  return $paths;
}

// -- main --------------------------------------------------------------------

$flags = parse_flags($argv);

if (isset($flags['help'])) {
  echo "Usage: php tools/build-manifest.php [--tree=DIR] [--list=FILE] "
    . "[--version=X] [--git-sha=Y] [--timestamp=ISO8601]\n";
  exit(0);
}

$treeRoot = $flags['tree'] ?? '.';
$listPath = $flags['list'] ?? 'release-files';

$version = $flags['version'] ?? read_version_from_composer(__DIR__ . '/../composer.json');
$gitSha = resolve_git_sha($flags['git-sha'] ?? '');
$timestamp = resolve_timestamp($flags['timestamp'] ?? '');

$paths = read_path_list($listPath);

if ($paths === []) {
  fwrite(STDERR, "ERROR: '$listPath' yielded zero entries.\n");
  exit(1);
}

try {
  $manifest = ManifestBuilder::build($treeRoot, $paths, $version, $timestamp, $gitSha);
} catch (Throwable $e) {
  fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
  exit(1);
}

echo $manifest;
