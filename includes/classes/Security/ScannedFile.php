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
 * $lineEndingsOnly is set on a MODIFIED entry whose content matches the
 * manifest once line endings are normalised — the file was converted between
 * CRLF and LF somewhere after the release was signed, and is otherwise
 * byte-identical. It stays a finding: the file really does differ from what was
 * signed. It is annotated because the admin's next step is different, and
 * because an unexplained "modified file" on an untouched installation teaches
 * people to ignore the page (issue #788).
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
    public readonly ?string $actualHash = null,
    public readonly bool $lineEndingsOnly = false
  ) {}
}
