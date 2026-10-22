<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebCalendar\Security\ManifestVerifier;
use WebCalendar\Security\VerifyResult;

require_once __DIR__ . '/../includes/classes/Security/VerifyResult.php';
require_once __DIR__ . '/../includes/classes/Security/ManifestVerifier.php';
require_once __DIR__ . '/../includes/classes/Security/ReleaseKeyGenerator.php';

/**
 * Story 3.1 — ManifestVerifier class.
 *
 * Verifies that a (manifest, signature, pubkey-PEM) triple is
 * cryptographically consistent, returning an immutable VerifyResult.
 * Every test sets up a fresh temp directory with real fixture files
 * so the verifier's full file-IO path is exercised.
 */
final class ManifestVerifierTest extends TestCase
{
  private string $tmpDir;
  private string $manifestPath;
  private string $sigPath;
  private string $pubkeyPath;
  private string $secretKeyRaw;
  private string $manifestBytes;

  protected function setUp(): void
  {
    $this->tmpDir = sys_get_temp_dir() . '/wc_verifier_' . bin2hex(random_bytes(6));
    mkdir($this->tmpDir, 0755, true);

    $kp = sodium_crypto_sign_keypair();
    $this->secretKeyRaw = sodium_crypto_sign_secretkey($kp);
    $pubkeyRaw = sodium_crypto_sign_publickey($kp);

    $this->manifestBytes = "# webcalendar-version: TEST\n"
      . "# build-timestamp: 2026-04-23T00:00:00Z\n"
      . "# git-sha: deadbeef\n"
      . str_repeat('0', 64) . "  a.txt\n"
      . str_repeat('1', 64) . "  b.txt\n";

    $this->manifestPath = $this->tmpDir . '/MANIFEST.sha256';
    $this->sigPath = $this->tmpDir . '/MANIFEST.sha256.sig';
    $this->pubkeyPath = $this->tmpDir . '/release-signing-pubkey.pem';

    file_put_contents($this->manifestPath, $this->manifestBytes);

    $sigRaw = sodium_crypto_sign_detached($this->manifestBytes, $this->secretKeyRaw);
    file_put_contents($this->sigPath, base64_encode($sigRaw) . "\n");

    file_put_contents(
      $this->pubkeyPath,
      WebCalendar\Security\ReleaseKeyGenerator::formatPublicKeyPem($pubkeyRaw)
    );
  }

  protected function tearDown(): void
  {
    if (!is_dir($this->tmpDir)) {
      return;
    }
    foreach (glob($this->tmpDir . '/*') as $f) {
      @unlink($f);
    }
    rmdir($this->tmpDir);
  }

  // -- Happy path -----------------------------------------------------------

  public function testValidInputsReturnValid(): void
  {
    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );

    self::assertInstanceOf(VerifyResult::class, $result);
    self::assertTrue($result->valid, 'reason was: ' . $result->reason);
    self::assertNotEmpty($result->reason);
  }

  public function testVerifyResultIsImmutable(): void
  {
    $r = new VerifyResult(true, 'ok');
    self::assertTrue($r->valid);
    self::assertSame('ok', $r->reason);

    $this->expectException(Error::class);
    // Writing to a readonly prop after construction is a fatal Error.
    /** @phpstan-ignore-next-line */
    $r->valid = false;
  }

  // -- Signature mismatch ---------------------------------------------------

  public function testModifiedManifestFailsWithMismatchReason(): void
  {
    // Append a byte to the manifest after it was signed.
    file_put_contents($this->manifestPath, "extra\n", FILE_APPEND);

    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );

    self::assertFalse($result->valid);
    self::assertMatchesRegularExpression(
      '/signature.*(mismatch|does not verify|failed)/i',
      $result->reason
    );
  }

  public function testOneByteFlipInManifestFailsVerification(): void
  {
    $bytes = $this->manifestBytes;
    $bytes[30] = chr(ord($bytes[30]) ^ 0x01);
    file_put_contents($this->manifestPath, $bytes);

    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );

    self::assertFalse($result->valid);
  }

  public function testSwappedPubkeyFails(): void
  {
    // Generate a DIFFERENT keypair and overwrite the pubkey file.
    $otherKp = sodium_crypto_sign_keypair();
    file_put_contents(
      $this->pubkeyPath,
      WebCalendar\Security\ReleaseKeyGenerator::formatPublicKeyPem(
        sodium_crypto_sign_publickey($otherKp)
      )
    );

    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );

    self::assertFalse($result->valid);
  }

  // -- Missing files --------------------------------------------------------

  public function testMissingManifestReturnsNotFoundReason(): void
  {
    unlink($this->manifestPath);
    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertFalse($result->valid);
    self::assertMatchesRegularExpression('/not found|does not exist|cannot read/i', $result->reason);
    self::assertStringContainsString('MANIFEST.sha256', $result->reason);
  }

  public function testMissingSignatureReturnsNotFoundReason(): void
  {
    unlink($this->sigPath);
    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertFalse($result->valid);
    self::assertMatchesRegularExpression('/not found|does not exist|cannot read/i', $result->reason);
  }

  public function testMissingPubkeyReturnsNotFoundReason(): void
  {
    unlink($this->pubkeyPath);
    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertFalse($result->valid);
    self::assertMatchesRegularExpression('/not found|does not exist|cannot read/i', $result->reason);
  }

  // -- Malformed inputs -----------------------------------------------------

  public function testMalformedPubkeyPemReturnsKeyFormatReason(): void
  {
    file_put_contents($this->pubkeyPath, "not a pem block at all\n");
    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertFalse($result->valid);
    self::assertMatchesRegularExpression('/pem|public key/i', $result->reason);
  }

  public function testTruncatedPubkeyReturnsKeyFormatReason(): void
  {
    // Build a PEM block whose base64 body decodes to fewer than 32 bytes.
    $shortPem = "-----BEGIN WEBCALENDAR RELEASE PUBLIC KEY-----\n"
      . base64_encode('too short') . "\n"
      . "-----END WEBCALENDAR RELEASE PUBLIC KEY-----\n";
    file_put_contents($this->pubkeyPath, $shortPem);

    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertFalse($result->valid);
    self::assertMatchesRegularExpression('/32 bytes|public key/i', $result->reason);
  }

  public function testInvalidBase64SignatureReturnsReason(): void
  {
    file_put_contents($this->sigPath, "!!!not-base64!!!\n");
    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertFalse($result->valid);
    self::assertMatchesRegularExpression('/signature|base64/i', $result->reason);
  }

  public function testWrongLengthSignatureReturnsReason(): void
  {
    // Valid base64 but decodes to fewer than 64 bytes.
    file_put_contents($this->sigPath, base64_encode('too short') . "\n");
    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertFalse($result->valid);
    self::assertMatchesRegularExpression('/64 bytes|signature/i', $result->reason);
  }

  public function testEmptySignatureFileReturnsReason(): void
  {
    file_put_contents($this->sigPath, '');
    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertFalse($result->valid);
  }

  // -- Signature file with trailing whitespace is tolerated -----------------

  public function testSignatureWithTrailingWhitespaceStillVerifies(): void
  {
    // Pipe tools might add CRLF or extra LFs. trim() tolerance is
    // expected — the base64 body itself contains no whitespace.
    $current = file_get_contents($this->sigPath);
    file_put_contents($this->sigPath, trim($current) . "\n\n\n");

    $result = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertTrue($result->valid, $result->reason);
  }

  // -- Reason always present ------------------------------------------------

  public function testReasonIsAlwaysNonEmptyRegardlessOfOutcome(): void
  {
    // Pass case
    $pass = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertNotEmpty($pass->reason);

    // Fail case
    unlink($this->manifestPath);
    $fail = ManifestVerifier::verify(
      $this->manifestPath,
      $this->sigPath,
      $this->pubkeyPath
    );
    self::assertNotEmpty($fail->reason);
  }
}
