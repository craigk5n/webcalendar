<?php

declare(strict_types=1);

namespace WebCalendar\Dev;

/**
 * Decides whether the destructive development commands may run.
 *
 * `seed` and `reset` rewrite the calendar. They ship in releases because
 * bin/webcal.php does, so the protection has to be a check rather than
 * absence.
 *
 * Deliberately NOT gated on `mode: dev`. That looks like the right marker
 * and is not one: the maintainer's own live installation carries
 * `mode: dev` in settings.php, because the setting controls error display
 * rather than disposability. A gate that a live install already satisfies
 * is worse than no gate, because it reads as protection.
 *
 * What is required instead:
 *
 *   SQLite      — a mistake then costs one file, which can be deleted. Live
 *                 installs are overwhelmingly MySQL or PostgreSQL, so this
 *                 alone stops most accidents.
 *   Opt-in      — `--force`, or WEBCAL_ALLOW_SEED=1. Nothing sets either by
 *                 accident, which is the property `mode: dev` lacks.
 *
 * Both, every time. The caller also prints the database file it is about to
 * modify, so the operator sees the target before it happens.
 */
final class DevGuard
{
  public const ENV_OPT_IN = 'WEBCAL_ALLOW_SEED';

  /**
   * @param string $dbType   configured database type
   * @param string $dbFile   configured database, echoed back so the operator
   *                         can see which one is at risk
   * @param bool   $forced   --force was passed
   * @param string $envOptIn value of WEBCAL_ALLOW_SEED
   * @return string|null null when the command may proceed, otherwise the
   *                     reason to print and exit on
   */
  public static function refuseReason(
    string $dbType,
    string $dbFile,
    bool $forced,
    string $envOptIn = ''
  ): ?string {
    if (!str_contains($dbType, 'sqlite')) {
      return 'seed and reset only support SQLite, so a mistake costs one file'
        . ' that can be deleted. This install uses: '
        . ($dbType === '' ? '(unset)' : $dbType);
    }

    if (!$forced && $envOptIn !== '1') {
      return 'refusing to rewrite ' . ($dbFile === '' ? 'the database' : $dbFile)
        . ' without an explicit opt-in. Pass --force, or set '
        . self::ENV_OPT_IN . '=1, once you are certain this calendar is'
        . ' disposable. Note that "mode: dev" is not treated as consent:'
        . ' live installations set it too.';
    }

    return null;
  }
}
