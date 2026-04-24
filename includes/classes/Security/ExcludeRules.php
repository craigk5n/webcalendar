<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Exclusion-pattern matcher used by `InstallationScanner` (signed-
 * manifest feature, GitHub issue #233).
 *
 * Story 3.3 shipped the minimal constructor + `matches()`. Story 4.1
 * adds the `DEFAULT_PATTERNS` constant (encoding design decision D9)
 * and the `withDefaults()` factory that unions the defaults with
 * user-supplied extras from the `SECURITY_AUDIT_EXTRA_EXCLUDES` admin
 * setting.
 *
 * Pattern syntax:
 *   - Trailing-slash prefix (`tests/`, `.git/`) matches any path that
 *     starts with that prefix. Used to exclude directory subtrees.
 *   - Anything else is passed to `fnmatch()`, which handles `*`, `?`,
 *     and `[abc]` bracket expressions.
 */
final class ExcludeRules
{
  /**
   * The default exclusion set (D9). Keep this narrow — Story 3.5's UI
   * default is "flag everything"; admins add extras via the
   * `SECURITY_AUDIT_EXTRA_EXCLUDES` setting when they have site
   * customizations that would otherwise drown the report in noise.
   *
   * @var list<string>
   */
  public const DEFAULT_PATTERNS = [
    // Site-specific config and extension hooks — not shipped in the
    // release, so they must not be flagged as EXTRA.
    'includes/settings.php',
    'includes/site_extras.php',

    // The manifest artifacts themselves ship inside their own zip but
    // aren't listed in themselves, so they'd show as EXTRA without
    // this rule.
    'MANIFEST.sha256',
    'MANIFEST.sha256.sig',

    // Developer directories that ship in the release (see
    // release-files) but whose contents aren't load-bearing for
    // production audits.
    'tools/',
    'tests/',
    'docs/',

    // Never-shipped directories that may still be present in a
    // checkout-based install.
    'vendor/',
    '.git/',
    '.github/',
  ];

  /** @param list<string> $patterns */
  public function __construct(
    private readonly array $patterns
  ) {}

  /**
   * Build an `ExcludeRules` that combines `DEFAULT_PATTERNS` with any
   * admin-supplied extras from the `SECURITY_AUDIT_EXTRA_EXCLUDES`
   * setting (newline-separated globs).
   *
   * Parsing rules for the extras string:
   *   - Lines split on any of LF, CRLF, or bare CR (admin may paste
   *     from a Windows editor).
   *   - Each line is trimmed; empty lines are skipped.
   *   - Lines whose first non-whitespace character is `#` are treated
   *     as comments and skipped.
   *
   * `null` or an empty/whitespace-only string yields defaults only.
   */
  public static function withDefaults(?string $extraConfig = null): self
  {
    $extras = [];
    if ($extraConfig !== null && trim($extraConfig) !== '') {
      $lines = preg_split('/\r\n|\r|\n/', $extraConfig) ?: [];
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
          continue;
        }
        $extras[] = $line;
      }
    }

    return new self(array_values(
      array_merge(self::DEFAULT_PATTERNS, $extras)
    ));
  }

  public function matches(string $relPath): bool
  {
    // Reject empty input up-front: a "pattern" of '' (from a stray
    // blank line that somehow got through) must not match anything.
    if ($relPath === '') {
      return false;
    }

    foreach ($this->patterns as $pattern) {
      if ($pattern === '') {
        continue;
      }
      if (substr($pattern, -1) === '/') {
        if (str_starts_with($relPath, $pattern)) {
          return true;
        }
        continue;
      }
      if (fnmatch($pattern, $relPath)) {
        return true;
      }
    }
    return false;
  }
}
