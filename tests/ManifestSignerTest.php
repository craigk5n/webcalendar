<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ManifestSigner;

require_once __DIR__ . '/../includes/classes/Security/ManifestSigner.php';

/**
 * Story 2.2 — tools/sign-manifest.php.
 *
 * Covers the pure logic (ManifestSigner::sign). The CLI wrapper is a
 * thin I/O shim.
 */
final class ManifestSignerTest extends TestCase
{
  private string $secretKeyRaw;
  private string $publicKeyRaw;
  private string $secretKeyBase64;

  protected function setUp(): void
  {
    $kp = sodium_crypto_sign_keypair();
    $this->publicKeyRaw = sodium_crypto_sign_publickey($kp);
    $this->secretKeyRaw = sodium_crypto_sign_secretkey($kp);
    $this->secretKeyBase64 = base64_encode($this->secretKeyRaw);
  }

  // -- Happy path ----------------------------------------------------------

  public function testSignProducesVerifiableSignature(): void
  {
    $manifest = "# webcalendar-version: 1.0.0\nhashhashhash  a.txt\n";
    $result = ManifestSigner::sign($manifest, $this->secretKeyBase64);

    self::assertTrue($result['ok'], $result['reason']);
    self::assertNotNull($result['signature']);

    $rawSig = base64_decode($result['signature'], true);
    self::assertNotFalse($rawSig);
    self::assertSame(SODIUM_CRYPTO_SIGN_BYTES, strlen($rawSig));
    self::assertTrue(
      sodium_crypto_sign_verify_detached($rawSig, $manifest, $this->publicKeyRaw),
      'Signature must verify against the paired public key.'
    );
  }

  public function testSignatureIsSingleLineBase64(): void
  {
    $manifest = "content\n";
    $result = ManifestSigner::sign($manifest, $this->secretKeyBase64);
    self::assertTrue($result['ok']);

    $sig = $result['signature'];
    self::assertStringNotContainsString("\n", $sig);
    self::assertStringNotContainsString("\r", $sig);
    // base64 alphabet only
    self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+=*$/', $sig);
  }

  public function testSignIsDeterministicForSameInput(): void
  {
    // Ed25519 is deterministic by design (RFC 8032 §5.1.6). Lock this in
    // so reproducible-builds workflows can hash the sig alongside the
    // manifest.
    $manifest = "some bytes\n";
    $a = ManifestSigner::sign($manifest, $this->secretKeyBase64);
    $b = ManifestSigner::sign($manifest, $this->secretKeyBase64);

    self::assertTrue($a['ok']);
    self::assertTrue($b['ok']);
    self::assertSame($a['signature'], $b['signature']);
  }

  public function testDifferentMessagesProduceDifferentSignatures(): void
  {
    $a = ManifestSigner::sign("content A\n", $this->secretKeyBase64);
    $b = ManifestSigner::sign("content B\n", $this->secretKeyBase64);
    self::assertNotSame($a['signature'], $b['signature']);
  }

  // -- Tamper detection -----------------------------------------------------

  public function testTamperedManifestFailsVerification(): void
  {
    $manifest = "original\n";
    $result = ManifestSigner::sign($manifest, $this->secretKeyBase64);
    $rawSig = base64_decode($result['signature'], true);

    self::assertFalse(
      sodium_crypto_sign_verify_detached($rawSig, "modified\n", $this->publicKeyRaw)
    );
  }

  public function testOneByteFlipInManifestFailsVerification(): void
  {
    $manifest = str_repeat('x', 100);
    $result = ManifestSigner::sign($manifest, $this->secretKeyBase64);
    $rawSig = base64_decode($result['signature'], true);

    // Flip one bit somewhere in the middle.
    $tampered = $manifest;
    $tampered[50] = chr(ord($tampered[50]) ^ 0x01);

    self::assertFalse(
      sodium_crypto_sign_verify_detached($rawSig, $tampered, $this->publicKeyRaw)
    );
  }

  // -- Secret validation ---------------------------------------------------

  public function testRejectsEmptyStringSecret(): void
  {
    $result = ManifestSigner::sign("content\n", '');
    self::assertFalse($result['ok']);
    self::assertNull($result['signature']);
    self::assertMatchesRegularExpression('/empty|unset/i', $result['reason']);
  }

  public function testRejectsNullSecret(): void
  {
    $result = ManifestSigner::sign("content\n", null);
    self::assertFalse($result['ok']);
    self::assertMatchesRegularExpression('/empty|unset/i', $result['reason']);
  }

  public function testRejectsInvalidBase64Secret(): void
  {
    $result = ManifestSigner::sign("content\n", '!!!not-base64!!!');
    self::assertFalse($result['ok']);
    self::assertMatchesRegularExpression('/base64/i', $result['reason']);
  }

  public function testRejectsWrongLengthSecret(): void
  {
    $tooShort = base64_encode(str_repeat("\x00", 32));
    $result = ManifestSigner::sign("content\n", $tooShort);
    self::assertFalse($result['ok']);
    self::assertMatchesRegularExpression('/64 bytes/', $result['reason']);
  }

  // -- Log safety -----------------------------------------------------------

  public function testReasonDoesNotLeakSecret(): void
  {
    // Use a wrong-length secret so we get a failure, then assert the
    // secret does not appear in the reason string. This is the CI
    // log-safety invariant from the AC.
    $secret = base64_encode(str_repeat("\xAA", 32));
    $result = ManifestSigner::sign("content\n", $secret);
    self::assertFalse($result['ok']);
    self::assertStringNotContainsString($secret, $result['reason']);
    self::assertStringNotContainsString(substr($secret, 0, 20), $result['reason']);
  }

  public function testReasonDoesNotLeakSecretOnInvalidBase64Failure(): void
  {
    // Feed a "secret" that's a recognizable string so it would stand
    // out in logs if echoed.
    $tag = 'LEAKCANARY' . str_repeat('!', 50);
    $result = ManifestSigner::sign("content\n", $tag);
    self::assertFalse($result['ok']);
    self::assertStringNotContainsString('LEAKCANARY', $result['reason']);
  }
}
