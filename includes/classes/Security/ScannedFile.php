<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Immutable description of one non-matching file encountered by
 * `InstallationScanner::scan()` (signed-manifest feature, issue #233).
 *
 * Convention:
 *   MODIFIED — both $expectedHash and $actualHash populated.
 *   MISSING  — $expectedHash populated, $actualHash null.
 *   EXTRA    — both null (the file isn't in the manifest; there is
 *              no "expected" hash; $actualHash left null because
 *              the only consumers care about classification and
 *              severity, not the body hash of unknown content).
 *
 * `final class` + per-property `readonly` for PHP 8.1-compatibility
 * (same reason as `VerifyResult`, `ManifestData`).
 */
final class ScannedFile
{
  public function __construct(
    public readonly string $path,
    public readonly ScanEntryKind $kind,
    public readonly ?string $expectedHash = null,
    public readonly ?string $actualHash = null
  ) {}
}
