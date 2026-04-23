#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * tools/verify-release-signing-key.php — dry-run validator for
 * RELEASE_SIGNING_KEY.
 *
 * Reads the env var RELEASE_SIGNING_KEY (base64 of a 64-byte Ed25519
 * secret key) and confirms that the public key derived from it matches
 * the committed release-signing-pubkey.pem. This proves that whichever
 * value you pasted into the GitHub Actions secret actually corresponds
 * to the public key that ships with releases.
 *
 * The secret is never echoed or logged. On failure, only a generic
 * reason string is printed to stderr.
 *
 * Exit codes:
 *   0 — secret is set, well-formed, and matches the committed pubkey.
 *   1 — secret is unset, malformed, or belongs to a different keypair.
 *
 * Intended for use from .github/workflows/verify-release-signing.yml
 * (manual workflow_dispatch run, scoped to the `release` environment).
 * Can also be run locally by the maintainer:
 *
 *   RELEASE_SIGNING_KEY='...' php tools/verify-release-signing-key.php
 */

require_once __DIR__ . '/../includes/classes/Security/ReleaseKeyGenerator.php';

use WebCalendar\Security\ReleaseKeyGenerator;

$pubkeyPath = __DIR__ . '/../release-signing-pubkey.pem';
$pem = @file_get_contents($pubkeyPath);
if ($pem === false) {
  fwrite(STDERR, "ERROR: Cannot read $pubkeyPath\n");
  exit(1);
}

try {
  $expectedPub = ReleaseKeyGenerator::parsePublicKeyPem($pem);
} catch (Throwable $e) {
  fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
  exit(1);
}

// getenv() returns false when unset; normalise to null so the verifier
// can treat unset and empty identically.
$rawEnv = getenv('RELEASE_SIGNING_KEY');
$envValue = $rawEnv === false ? null : $rawEnv;

$result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(
  $envValue,
  $expectedPub
);

if ($result['valid']) {
  echo 'OK: ' . $result['reason'] . "\n";
  exit(0);
}

fwrite(STDERR, 'FAIL: ' . $result['reason'] . "\n");
exit(1);
