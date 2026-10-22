<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ReleaseKeyGenerator;

require_once __DIR__ . '/../includes/classes/Security/ReleaseKeyGenerator.php';

/**
 * Story 1.1 — Generate the initial keypair.
 *
 * Covers the release-signing-key generator used by
 * tools/generate-release-key.php and (later) by the verifier.
 */
final class ReleaseKeyGeneratorTest extends TestCase
{
  public function testEnsureSodiumAvailablePassesWhenPresent(): void
  {
    // Production path: leaves $sodiumAvailable null, so it probes the engine.
    // Test environment always has libsodium via PHP 7.2+.
    self::assertTrue(function_exists('sodium_crypto_sign_keypair'));
    ReleaseKeyGenerator::ensureSodiumAvailable();
    $this->addToAssertionCount(1);
  }

  public function testEnsureSodiumAvailableThrowsWhenMissing(): void
  {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessageMatches('/libsodium/i');
    ReleaseKeyGenerator::ensureSodiumAvailable(false);
  }

  public function testGenerateProducesCorrectByteLengths(): void
  {
    $kp = ReleaseKeyGenerator::generate();

    self::assertArrayHasKey('publicKey', $kp);
    self::assertArrayHasKey('secretKey', $kp);
    self::assertSame(
      SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES,
      strlen($kp['publicKey']),
      'Ed25519 public key must be 32 bytes'
    );
    self::assertSame(
      SODIUM_CRYPTO_SIGN_SECRETKEYBYTES,
      strlen($kp['secretKey']),
      'Ed25519 secret key must be 64 bytes'
    );
  }

  public function testTwoGenerationsProduceDifferentKeys(): void
  {
    $a = ReleaseKeyGenerator::generate();
    $b = ReleaseKeyGenerator::generate();

    self::assertNotSame($a['publicKey'], $b['publicKey']);
    self::assertNotSame($a['secretKey'], $b['secretKey']);
  }

  public function testGeneratedKeypairRoundTripsAsExpected(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $message = 'MANIFEST ROUND-TRIP TEST ' . random_bytes(32);

    $signature = sodium_crypto_sign_detached($message, $kp['secretKey']);

    self::assertSame(SODIUM_CRYPTO_SIGN_BYTES, strlen($signature));
    self::assertTrue(
      sodium_crypto_sign_verify_detached($signature, $message, $kp['publicKey']),
      'Fresh keypair must produce a verifiable signature'
    );
  }

  public function testGeneratedKeypairRejectsTamperedMessage(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $message = 'original';
    $signature = sodium_crypto_sign_detached($message, $kp['secretKey']);

    self::assertFalse(
      sodium_crypto_sign_verify_detached($signature, 'tampered', $kp['publicKey'])
    );
  }

  public function testFormatPublicKeyPemWrapsInExpectedMarkers(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $pem = ReleaseKeyGenerator::formatPublicKeyPem($kp['publicKey']);

    self::assertStringContainsString(
      '-----BEGIN WEBCALENDAR RELEASE PUBLIC KEY-----',
      $pem
    );
    self::assertStringContainsString(
      '-----END WEBCALENDAR RELEASE PUBLIC KEY-----',
      $pem
    );
    self::assertStringEndsWith("\n", $pem);
    // LF-only line endings, no CRLF
    self::assertStringNotContainsString("\r", $pem);
  }

  public function testFormatPublicKeyPemRoundTrips(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $pem = ReleaseKeyGenerator::formatPublicKeyPem($kp['publicKey']);

    self::assertSame($kp['publicKey'], ReleaseKeyGenerator::parsePublicKeyPem($pem));
  }

  public function testFormatPublicKeyPemRejectsWrongLength(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/32 bytes/');
    ReleaseKeyGenerator::formatPublicKeyPem(str_repeat("\x00", 31));
  }

  public function testParsePublicKeyPemRejectsMalformedBlock(): void
  {
    $this->expectException(InvalidArgumentException::class);
    ReleaseKeyGenerator::parsePublicKeyPem('not a pem block');
  }

  public function testParsePublicKeyPemRejectsInvalidBase64(): void
  {
    $pem = "-----BEGIN WEBCALENDAR RELEASE PUBLIC KEY-----\n"
         . "!!!not-valid-base64!!!\n"
         . "-----END WEBCALENDAR RELEASE PUBLIC KEY-----\n";

    $this->expectException(InvalidArgumentException::class);
    ReleaseKeyGenerator::parsePublicKeyPem($pem);
  }

  public function testParsePublicKeyPemRejectsWrongDecodedLength(): void
  {
    // Valid base64 but not 32 bytes decoded.
    $pem = "-----BEGIN WEBCALENDAR RELEASE PUBLIC KEY-----\n"
         . base64_encode('too short') . "\n"
         . "-----END WEBCALENDAR RELEASE PUBLIC KEY-----\n";

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/32 bytes/');
    ReleaseKeyGenerator::parsePublicKeyPem($pem);
  }

  public function testFormatSecretKeyForGitHubRoundTrips(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $b64 = ReleaseKeyGenerator::formatSecretKeyForGitHub($kp['secretKey']);

    // Single line, no whitespace — ready to paste into GitHub secret UI.
    self::assertSame(trim($b64), $b64);
    self::assertStringNotContainsString("\n", $b64);

    $decoded = base64_decode($b64, true);
    self::assertNotFalse($decoded);
    self::assertSame($kp['secretKey'], $decoded);
  }

  public function testFormatSecretKeyForGitHubRejectsWrongLength(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/64 bytes/');
    ReleaseKeyGenerator::formatSecretKeyForGitHub(str_repeat("\x00", 32));
  }

  // -- verifySecretKeyEnvMatchesPublicKey (Story 1.3 support) --------------

  public function testVerifySecretKeyEnvRejectsEmptyInput(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey('', $kp['publicKey']);
    self::assertFalse($result['valid']);
    self::assertMatchesRegularExpression('/empty|unset/i', $result['reason']);
  }

  public function testVerifySecretKeyEnvRejectsNullInput(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(null, $kp['publicKey']);
    self::assertFalse($result['valid']);
    self::assertMatchesRegularExpression('/empty|unset/i', $result['reason']);
  }

  public function testVerifySecretKeyEnvRejectsInvalidBase64(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(
      '!!!not-base64!!!',
      $kp['publicKey']
    );
    self::assertFalse($result['valid']);
    self::assertMatchesRegularExpression('/base64/i', $result['reason']);
  }

  public function testVerifySecretKeyEnvRejectsWrongSecretLength(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $wrongLength = base64_encode(str_repeat("\x00", 32)); // 32 bytes instead of 64
    $result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(
      $wrongLength,
      $kp['publicKey']
    );
    self::assertFalse($result['valid']);
    self::assertMatchesRegularExpression('/64 bytes/', $result['reason']);
  }

  public function testVerifySecretKeyEnvRejectsWrongExpectedPublicKeyLength(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(
      base64_encode($kp['secretKey']),
      str_repeat("\x00", 31) // malformed expected pubkey
    );
    self::assertFalse($result['valid']);
    self::assertMatchesRegularExpression('/public key/i', $result['reason']);
  }

  public function testVerifySecretKeyEnvRejectsMismatchedKeypair(): void
  {
    $kpA = ReleaseKeyGenerator::generate();
    $kpB = ReleaseKeyGenerator::generate();

    // Secret key from A, expected public key from B — keypair mismatch.
    $result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(
      base64_encode($kpA['secretKey']),
      $kpB['publicKey']
    );
    self::assertFalse($result['valid']);
    self::assertMatchesRegularExpression('/mismatch|does not match/i', $result['reason']);
  }

  public function testVerifySecretKeyEnvAcceptsMatchingKeypair(): void
  {
    $kp = ReleaseKeyGenerator::generate();
    $result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(
      base64_encode($kp['secretKey']),
      $kp['publicKey']
    );
    self::assertTrue($result['valid'], $result['reason']);
    self::assertMatchesRegularExpression('/match/i', $result['reason']);
  }

  public function testVerifySecretKeyEnvReasonDoesNotLeakSecret(): void
  {
    // Even on failure, the reason string must never contain the base64
    // input (a protective invariant for CI log safety).
    $kpA = ReleaseKeyGenerator::generate();
    $kpB = ReleaseKeyGenerator::generate();
    $secretB64 = base64_encode($kpA['secretKey']);

    $result = ReleaseKeyGenerator::verifySecretKeyEnvMatchesPublicKey(
      $secretB64,
      $kpB['publicKey']
    );
    self::assertFalse($result['valid']);
    self::assertStringNotContainsString($secretB64, $result['reason']);
    // Also check shorter substrings of the secret don't appear in the reason.
    self::assertStringNotContainsString(substr($secretB64, 0, 32), $result['reason']);
  }
}
