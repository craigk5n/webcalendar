<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Verifies that a WebCalendar release manifest is cryptographically
 * consistent with its detached signature and the committed public
 * key (signed-manifest feature, GitHub issue #233).
 *
 * Returns an immutable `VerifyResult`. Every failure mode yields a
 * human-readable `$reason` the admin UI can display. The signature
 * check itself uses `sodium_crypto_sign_verify_detached()`, which is
 * constant-time — no `hash_equals()` needed here.
 *
 * Pubkey parsing delegates to `ReleaseKeyGenerator::parsePublicKeyPem()`,
 * which already enforces the 32-byte invariant and rejects malformed
 * PEM blocks / invalid base64 with `InvalidArgumentException`.
 */
final class ManifestVerifier
{
  /**
   * Read all three files, decode, and verify.
   *
   * The signature file is read with leading/trailing whitespace
   * tolerance — pipe-chained tools occasionally append CRLF or extra
   * LFs. The base64 body itself cannot contain whitespace, so trim()
   * is safe and matches the signer's single-line + LF output.
   */
  public static function verify(
    string $manifestPath,
    string $signaturePath,
    string $publicKeyPemPath
  ): VerifyResult {
    $manifest = self::readFile($manifestPath, 'MANIFEST.sha256');
    if ($manifest instanceof VerifyResult) {
      return $manifest;
    }

    $sigRaw = self::readSignature($signaturePath);
    if ($sigRaw instanceof VerifyResult) {
      return $sigRaw;
    }

    $pubkey = self::readPublicKey($publicKeyPemPath);
    if ($pubkey instanceof VerifyResult) {
      return $pubkey;
    }

    $ok = sodium_crypto_sign_verify_detached($sigRaw, $manifest, $pubkey);
    if (!$ok) {
      return new VerifyResult(
        false,
        'Manifest signature does not verify — the manifest or the signature has been tampered with, '
        . 'or the public key does not match the key that signed this release.'
      );
    }

    return new VerifyResult(
      true,
      'Manifest signature is valid.'
    );
  }

  /** @return string|VerifyResult  file bytes on success, VerifyResult on failure */
  private static function readFile(string $path, string $displayName)
  {
    if (!is_file($path)) {
      return new VerifyResult(false, "$displayName not found at $path (file does not exist).");
    }
    $bytes = @file_get_contents($path);
    if ($bytes === false) {
      return new VerifyResult(false, "$displayName at $path cannot be read.");
    }
    return $bytes;
  }

  /** @return string|VerifyResult  raw 64-byte signature, or VerifyResult on failure */
  private static function readSignature(string $path)
  {
    $raw = self::readFile($path, 'MANIFEST.sha256.sig');
    if ($raw instanceof VerifyResult) {
      return $raw;
    }

    $trimmed = trim($raw);
    if ($trimmed === '') {
      return new VerifyResult(
        false,
        'MANIFEST.sha256.sig is empty.'
      );
    }

    $decoded = base64_decode($trimmed, true);
    if ($decoded === false) {
      return new VerifyResult(
        false,
        'MANIFEST.sha256.sig signature is not valid base64.'
      );
    }

    if (strlen($decoded) !== SODIUM_CRYPTO_SIGN_BYTES) {
      return new VerifyResult(
        false,
        'MANIFEST.sha256.sig signature must decode to '
        . SODIUM_CRYPTO_SIGN_BYTES . ' bytes; got '
        . strlen($decoded) . '.'
      );
    }

    return $decoded;
  }

  /** @return string|VerifyResult  32-byte raw public key, or VerifyResult on failure */
  private static function readPublicKey(string $path)
  {
    $pem = self::readFile($path, 'release-signing-pubkey.pem');
    if ($pem instanceof VerifyResult) {
      return $pem;
    }

    try {
      return ReleaseKeyGenerator::parsePublicKeyPem($pem);
    } catch (\InvalidArgumentException $e) {
      return new VerifyResult(
        false,
        'release-signing-pubkey.pem is malformed: ' . $e->getMessage()
      );
    }
  }
}
