<?php

declare(strict_types=1);

namespace WebCalendar\Security;

use InvalidArgumentException;
use RuntimeException;

/**
 * Generates the Ed25519 keypair used by the release workflow to sign
 * `MANIFEST.sha256`. Intended for one-off use during initial setup and
 * during key rotation; see `docs/release-signing.md`.
 *
 * The public key half is committed as `release-signing-pubkey.pem` and
 * ships inside each release zip. The secret key half is stored only as
 * the GitHub Actions secret `RELEASE_SIGNING_KEY` and must never be
 * committed.
 *
 * All methods are static because key generation is a one-off operation
 * with no state to carry between calls.
 */
final class ReleaseKeyGenerator
{
  // Typed class constants are PHP 8.3+; plain const keeps us parse-compatible
  // with the project's PHP 8.1 floor (see .github/workflows/php-syntax-check.yml).
  public const PEM_HEADER = '-----BEGIN WEBCALENDAR RELEASE PUBLIC KEY-----';
  public const PEM_FOOTER = '-----END WEBCALENDAR RELEASE PUBLIC KEY-----';

  /**
   * Throws if libsodium is not available.
   *
   * @param bool|null $sodiumAvailable Test override; leave null in production.
   */
  public static function ensureSodiumAvailable(?bool $sodiumAvailable = null): void
  {
    $sodiumAvailable ??= function_exists('sodium_crypto_sign_keypair');
    if (!$sodiumAvailable) {
      throw new RuntimeException(
        'libsodium extension is required for release key generation. '
        . 'Install ext-sodium (bundled with PHP 7.2+).'
      );
    }
  }

  /**
   * Generate a fresh Ed25519 signing keypair.
   *
   * @return array{publicKey: string, secretKey: string} raw bytes (32 / 64)
   */
  public static function generate(): array
  {
    self::ensureSodiumAvailable();
    $kp = sodium_crypto_sign_keypair();
    return [
      'publicKey' => sodium_crypto_sign_publickey($kp),
      'secretKey' => sodium_crypto_sign_secretkey($kp),
    ];
  }

  /**
   * Wrap a 32-byte raw Ed25519 public key in a PEM-style block suitable
   * for committing as `release-signing-pubkey.pem`.
   */
  public static function formatPublicKeyPem(string $rawPublicKey): string
  {
    if (strlen($rawPublicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
      throw new InvalidArgumentException(
        'Ed25519 public key must be exactly ' . SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
        . ' bytes; got ' . strlen($rawPublicKey)
      );
    }
    $b64 = base64_encode($rawPublicKey);
    return self::PEM_HEADER . "\n"
      . wordwrap($b64, 64, "\n", true) . "\n"
      . self::PEM_FOOTER . "\n";
  }

  /**
   * Extract the 32-byte raw Ed25519 public key from a PEM block.
   */
  public static function parsePublicKeyPem(string $pem): string
  {
    $pattern = '/' . preg_quote(self::PEM_HEADER, '/')
      . '\s+(.+?)\s+'
      . preg_quote(self::PEM_FOOTER, '/') . '/s';
    if (!preg_match($pattern, $pem, $m)) {
      throw new InvalidArgumentException(
        'Input does not contain a WebCalendar release public key PEM block.'
      );
    }
    $body = preg_replace('/\s+/', '', $m[1]);
    // strict=true: reject whitespace/invalid chars inside the body.
    $raw = base64_decode((string) $body, true);
    if ($raw === false) {
      throw new InvalidArgumentException('Public key body is not valid base64.');
    }
    if (strlen($raw) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
      throw new InvalidArgumentException(
        'Decoded public key must be ' . SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
        . ' bytes; got ' . strlen($raw)
      );
    }
    return $raw;
  }

  /**
   * Base64-encode a 64-byte raw Ed25519 secret key for pasting into the
   * GitHub Actions secret `RELEASE_SIGNING_KEY`.
   *
   * The #[\SensitiveParameter] attribute redacts the value from stack
   * traces on PHP 8.2+; on 8.1 it is a no-op but harmless.
   */
  public static function formatSecretKeyForGitHub(
    #[\SensitiveParameter] string $rawSecretKey
  ): string {
    if (strlen($rawSecretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
      throw new InvalidArgumentException(
        'Ed25519 secret key must be exactly ' . SODIUM_CRYPTO_SIGN_SECRETKEYBYTES
        . ' bytes; got ' . strlen($rawSecretKey)
      );
    }
    return base64_encode($rawSecretKey);
  }

  /**
   * Validate that a base64-encoded secret-key env var (the value stored
   * in the GitHub Actions secret RELEASE_SIGNING_KEY) is well-formed
   * and that the public key derived from it matches the given expected
   * raw public key (the committed `release-signing-pubkey.pem`).
   *
   * Returns a result envelope rather than throwing because the CI
   * verification workflow needs to surface the failure reason to the
   * operator, not crash with an uncaught exception.
   *
   * The reason string is carefully constructed to never contain the
   * secret-key input — log-safe even on failure.
   *
   * @return array{valid: bool, reason: string}
   */
  public static function verifySecretKeyEnvMatchesPublicKey(
    #[\SensitiveParameter] ?string $secretKeyBase64,
    string $expectedPublicKey32Bytes
  ): array {
    if ($secretKeyBase64 === null || $secretKeyBase64 === '') {
      return [
        'valid' => false,
        'reason' => 'RELEASE_SIGNING_KEY is empty or unset.',
      ];
    }

    $decoded = base64_decode($secretKeyBase64, true);
    if ($decoded === false) {
      return [
        'valid' => false,
        'reason' => 'RELEASE_SIGNING_KEY is not valid base64.',
      ];
    }

    if (strlen($decoded) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
      return [
        'valid' => false,
        'reason' => 'RELEASE_SIGNING_KEY must decode to '
          . SODIUM_CRYPTO_SIGN_SECRETKEYBYTES . ' bytes; got '
          . strlen($decoded) . '.',
      ];
    }

    if (strlen($expectedPublicKey32Bytes) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
      return [
        'valid' => false,
        'reason' => 'Expected public key must be '
          . SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES . ' bytes; got '
          . strlen($expectedPublicKey32Bytes) . '.',
      ];
    }

    $derived = sodium_crypto_sign_publickey_from_secretkey($decoded);
    if (!hash_equals($expectedPublicKey32Bytes, $derived)) {
      return [
        'valid' => false,
        'reason' => 'RELEASE_SIGNING_KEY does not match release-signing-pubkey.pem '
          . '(keypair mismatch — the secret does not correspond to the committed public key).',
      ];
    }

    return [
      'valid' => true,
      'reason' => 'RELEASE_SIGNING_KEY matches release-signing-pubkey.pem.',
    ];
  }
}
