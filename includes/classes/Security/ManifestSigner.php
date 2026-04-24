<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Signs `MANIFEST.sha256` bytes with an Ed25519 secret key, producing
 * the base64 detached signature that ships as `MANIFEST.sha256.sig`
 * (GitHub issue #233).
 *
 * Ed25519 is deterministic by construction (RFC 8032 §5.1.6): the same
 * message signed with the same key always yields the same signature.
 * That lets reproducible-builds workflows pin `SOURCE_DATE_EPOCH` and
 * get byte-identical (manifest, signature) pairs across runs.
 *
 * Error handling is envelope-style — all failures return
 * {ok: false, signature: null, reason: string} rather than throwing,
 * so the CI workflow can print a clean operator message and the
 * reason string stays in our control. No code path echoes the secret
 * key into the reason; this is covered by the log-safety unit tests.
 */
final class ManifestSigner
{
  /**
   * Sign `$manifestBytes` with the base64-encoded Ed25519 secret key.
   *
   * @return array{ok: bool, signature: string|null, reason: string}
   */
  public static function sign(
    string $manifestBytes,
    #[\SensitiveParameter] ?string $secretKeyBase64
  ): array {
    if ($secretKeyBase64 === null || $secretKeyBase64 === '') {
      return [
        'ok' => false,
        'signature' => null,
        'reason' => 'RELEASE_SIGNING_KEY is empty or unset.',
      ];
    }

    $decoded = base64_decode($secretKeyBase64, true);
    if ($decoded === false) {
      return [
        'ok' => false,
        'signature' => null,
        'reason' => 'RELEASE_SIGNING_KEY is not valid base64.',
      ];
    }

    if (strlen($decoded) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
      return [
        'ok' => false,
        'signature' => null,
        'reason' => 'RELEASE_SIGNING_KEY must decode to '
          . SODIUM_CRYPTO_SIGN_SECRETKEYBYTES . ' bytes; got '
          . strlen($decoded) . '.',
      ];
    }

    $rawSignature = sodium_crypto_sign_detached($manifestBytes, $decoded);

    // Defensive scrub of the local decoded secret. sodium_memzero()
    // guarantees the buffer is wiped even if the optimizer would skip
    // a plain null-out.
    sodium_memzero($decoded);

    return [
      'ok' => true,
      'signature' => base64_encode($rawSignature),
      'reason' => 'Signed successfully.',
    ];
  }
}
