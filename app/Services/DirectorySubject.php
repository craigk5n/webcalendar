<?php

declare(strict_types=1);

namespace WebCalendar\Services;

final class DirectorySubject
{
  public static function fromEntry(array $entry, string $authority): ?string
  {
    foreach (['entryuuid', 'objectguid'] as $attribute) {
      $values = $entry[$attribute] ?? [];
      if (($values['count'] ?? 0) === 1 && is_string($values[0] ?? null) && $values[0] !== '') {
        return hash('sha256', $authority . "\0" . $attribute . "\0" . $values[0]);
      }
    }
    return null;
  }
}
