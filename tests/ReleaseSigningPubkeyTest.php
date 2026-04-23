<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ReleaseKeyGenerator;

require_once __DIR__ . '/../includes/classes/Security/ReleaseKeyGenerator.php';

/**
 * Story 1.2 — Commit the public key file.
 *
 * Regression tests that the repo-root `release-signing-pubkey.pem` is
 * present, well-formed, and actually shipped in release builds.
 */
final class ReleaseSigningPubkeyTest extends TestCase
{
  private const PUBKEY_PATH = __DIR__ . '/../release-signing-pubkey.pem';

  public function testPublicKeyFileIsCommittedAtRepoRoot(): void
  {
    self::assertFileExists(
      self::PUBKEY_PATH,
      'release-signing-pubkey.pem must be committed at the repository root '
      . 'so it ships inside the release zip.'
    );
  }

  public function testPublicKeyFileParsesToExactlyThirtyTwoBytes(): void
  {
    $pem = file_get_contents(self::PUBKEY_PATH);
    self::assertNotFalse($pem, 'Failed to read release-signing-pubkey.pem');

    $raw = ReleaseKeyGenerator::parsePublicKeyPem($pem);
    self::assertSame(
      SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES,
      strlen($raw),
      'Committed public key must decode to a 32-byte Ed25519 public key.'
    );
  }

  public function testPublicKeyFileIsListedInReleaseFiles(): void
  {
    $listing = file_get_contents(__DIR__ . '/../release-files');
    self::assertNotFalse($listing, 'Failed to read release-files manifest list');

    $paths = array_filter(
      array_map('trim', explode("\n", $listing)),
      static fn(string $line): bool => $line !== ''
    );

    self::assertContains(
      'release-signing-pubkey.pem',
      $paths,
      'release-signing-pubkey.pem must be listed in release-files so the '
      . 'release workflow includes it in the zip.'
    );
  }

  public function testPublicKeyFileUsesLfLineEndings(): void
  {
    $pem = file_get_contents(self::PUBKEY_PATH);
    self::assertNotFalse($pem);
    self::assertStringNotContainsString(
      "\r",
      $pem,
      'PEM file must use LF line endings only (matches project .editorconfig).'
    );
  }

  public function testPrivateKeyIsNotAccidentallyCommitted(): void
  {
    // Sanity check: make sure no file matching the privkey pattern
    // sneaks into the tracked tree. The .gitignore rule provides the
    // upstream defense; this test locks it in.
    $matches = glob(__DIR__ . '/../release-signing-privkey*');
    self::assertSame(
      [],
      $matches ?: [],
      'A file matching release-signing-privkey* exists in the repo. '
      . 'Remove it immediately and rotate the keypair — the private key '
      . 'half must NEVER be committed.'
    );
  }
}
