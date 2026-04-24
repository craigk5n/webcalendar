<?php

declare(strict_types=1);

namespace WebCalendar\Security;

/**
 * Classification of a scanned file entry (signed-manifest feature,
 * GitHub issue #233).
 *
 * MATCH entries are counted but not returned as `ScannedFile`s (see
 * `ScanReport::$matchedCount`), so the enum only lists the three
 * failure modes.
 */
enum ScanEntryKind: string
{
  case MODIFIED = 'modified';
  case MISSING = 'missing';
  case EXTRA = 'extra';
}
