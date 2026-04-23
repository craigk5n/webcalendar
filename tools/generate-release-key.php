#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * tools/generate-release-key.php — one-off Ed25519 signing keypair
 * generator for the WebCalendar release workflow.
 *
 * Usage:
 *   php tools/generate-release-key.php
 *
 * Outputs, on stdout:
 *   1. The 64-byte secret key, base64-encoded, for pasting into the
 *      GitHub Actions secret RELEASE_SIGNING_KEY.
 *   2. A PEM-wrapped 32-byte public key, for committing as
 *      release-signing-pubkey.pem at the repository root.
 *
 * The secret key is printed to stdout and never persisted by this
 * script. Capture it once, store it in the GitHub secret, and close the
 * terminal. If the value scrolls out of your terminal buffer without
 * being saved, re-run the script and choose a fresh keypair.
 *
 * See docs/release-signing.md for the full operational procedure.
 */

require_once __DIR__ . '/../includes/classes/Security/ReleaseKeyGenerator.php';

use WebCalendar\Security\ReleaseKeyGenerator;

try {
  ReleaseKeyGenerator::ensureSodiumAvailable();
} catch (Throwable $e) {
  fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
  exit(1);
}

$keypair = ReleaseKeyGenerator::generate();
$githubSecret = ReleaseKeyGenerator::formatSecretKeyForGitHub($keypair['secretKey']);
$pem = ReleaseKeyGenerator::formatPublicKeyPem($keypair['publicKey']);

echo str_repeat('=', 72) . "\n";
echo "WebCalendar release-signing keypair — generated "
  . gmdate('Y-m-d\TH:i:s\Z') . "\n";
echo str_repeat('=', 72) . "\n\n";

echo "1) GitHub Actions secret RELEASE_SIGNING_KEY — paste this value:\n";
echo "   (Settings -> Secrets and variables -> Actions -> New repository secret)\n\n";
echo $githubSecret . "\n\n";

echo str_repeat('-', 72) . "\n\n";

echo "2) release-signing-pubkey.pem — write this to the repo root:\n\n";
echo $pem . "\n";

echo str_repeat('-', 72) . "\n\n";

echo "Next steps:\n";
echo "  - Commit release-signing-pubkey.pem (public key only; never the secret).\n";
echo "  - Paste the secret above into the RELEASE_SIGNING_KEY GitHub secret.\n";
echo "  - Scope the secret to the 'release' environment.\n";
echo "  - Clear your terminal buffer once the secret is saved.\n";
