<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Finding severity for the file-integrity audit (signed-manifest
 * feature, GitHub issue #233). Backed-string enum so the value can
 * be round-tripped through config (noise-filter setting) and the
 * translation catalog.
 *
 * CRITICAL — blocking-level signal (executable-extension file appeared
 *            that isn't in the signed manifest; high webshell risk).
 * WARN     — meaningful deviation that needs admin review (modified
 *            shipped file, missing shipped file, or extra file with
 *            an unrecognized extension).
 * INFO     — deviation with low executable-code risk (extra file with
 *            a static-asset or documentation extension).
 */
enum Severity: string
{
  case CRITICAL = 'critical';
  case WARN = 'warn';
  case INFO = 'info';
}
