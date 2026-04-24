#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * tools/sign-manifest.php — produce MANIFEST.sha256.sig from a
 * MANIFEST.sha256 using the Ed25519 secret key in the env var
 * RELEASE_SIGNING_KEY (signed-manifest feature, GitHub issue #233).
 *
 * Usage:
 *   RELEASE_SIGNING_KEY='<base64-64-byte-secret>' \
 *     php tools/sign-manifest.php /path/to/MANIFEST.sha256
 *
 * Output:
 *   Writes a sibling file `<input>.sig` containing the base64 Ed25519
 *   signature (single line + trailing LF).
 *
 * The secret is never echoed or written to disk. On failure, only a
 * generic reason string goes to stderr (see ManifestSigner's log-safety
 * tests).
 *
 * Exit codes:
 *   0 — signature written.
 *   1 — secret is missing / malformed, input unreadable, or the
 *       output file could not be written.
 */

require_once __DIR__ . '/../includes/classes/Security/ManifestSigner.php';

use WebCalendar\Security\ManifestSigner;

if ($argc < 2) {
  fwrite(STDERR, "Usage: php tools/sign-manifest.php <path/to/MANIFEST.sha256>\n");
  exit(1);
}

$manifestPath = $argv[1];
$sigPath = $manifestPath . '.sig';

$manifestBytes = @file_get_contents($manifestPath);
if ($manifestBytes === false) {
  fwrite(STDERR, "ERROR: cannot read manifest at '$manifestPath'\n");
  exit(1);
}

$rawEnv = getenv('RELEASE_SIGNING_KEY');
$envValue = $rawEnv === false ? null : $rawEnv;

$result = ManifestSigner::sign($manifestBytes, $envValue);

if (!$result['ok']) {
  fwrite(STDERR, 'ERROR: ' . $result['reason'] . "\n");
  exit(1);
}

$sig = $result['signature'] . "\n";
$written = @file_put_contents($sigPath, $sig);
if ($written === false) {
  fwrite(STDERR, "ERROR: cannot write signature to '$sigPath'\n");
  exit(1);
}

echo "Wrote $sigPath (" . strlen($sig) . " bytes)\n";
