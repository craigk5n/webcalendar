<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Minimal exclusion-pattern matcher used by `InstallationScanner`
 * (signed-manifest feature, issue #233). Story 3.3 needs only the
 * `matches()` method; Story 4.1 will extend this class with a
 * config-sourced default set and admin-supplied extras.
 *
 * Pattern syntax:
 *   - Trailing-slash prefix (`tests/`, `.git/`) matches any path
 *     that starts with that prefix. Used for excluding directory
 *     subtrees.
 *   - Anything else is passed to `fnmatch()`, which handles `*`,
 *     `?`, and `[abc]` bracket expressions.
 *
 * Exclusion is a boolean — either a path is excluded (skipped by the
 * scanner for both EXTRA and MISSING classification) or it isn't.
 */
final class ExcludeRules
{
  /** @param list<string> $patterns */
  public function __construct(
    private readonly array $patterns
  ) {}

  public function matches(string $relPath): bool
  {
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
