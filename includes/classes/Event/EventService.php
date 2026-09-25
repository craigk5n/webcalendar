<?php

declare(strict_types=1);

namespace WebCalendar\Event;

/**
 * Writes to webcal_entry and its satellite tables.
 *
 * mcp.php forked this from edit_entry_handler.php, then forked it again
 * internally between add_event() and add_recurring_event(), so the
 * id-allocation loop existed three times with the same comment above each
 * copy. tests/McpAddEventRaceConditionTest.php exists because one of those
 * copies was wrong. A third caller -- a CLI that writes events -- would have
 * made a fourth copy.
 *
 * Method names follow webcalendar-core's EventService where that costs
 * nothing, so moving to it later is a rename rather than a redesign.
 *
 * These are static and talk to dbi4php directly, matching how the rest of the
 * legacy tree works. The functions are required by their callers rather than
 * autoloaded, as with includes/classes/Security/.
 */
final class EventService
{
  /**
   * Collisions are resolved by retrying, so this only needs to be larger than
   * the number of writers realistically racing for the same id.
   */
  public const MAX_ID_ATTEMPTS = 5;

  /**
   * Inserts an event and the creator's participation row.
   *
   * webcal_entry.cal_id is a plain INT PRIMARY KEY with no auto-increment or
   * sequence on any supported backend, so the application assigns it as
   * MAX(cal_id) + 1. Two concurrent callers can compute the same id, so the
   * INSERT is made non-fatal and quiet and retried on collision: a duplicate
   * primary key makes it return false, and the id is recomputed. That is
   * portable across SQLite, MySQL and PostgreSQL with no dialect-specific
   * locking, which INSERT ... RETURNING or a sequence would need.
   *
   * @return int|null the new cal_id, or null when it could not be created
   */
  public static function createEvent(NewEvent $event): ?int
  {
    $sql = 'INSERT INTO webcal_entry ( cal_id, cal_name, cal_date, cal_time,'
      . ' cal_duration, cal_description, cal_location, cal_create_by,'
      . ' cal_mod_date, cal_mod_time, cal_type )'
      . ' VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )';

    $modDate = date('Ymd');
    $modTime = date('His');

    for ($attempt = 0; $attempt < self::MAX_ID_ATTEMPTS; $attempt++) {
      $candidate = self::nextEventId();
      if ($candidate === null) {
        return null;
      }

      // Non-fatal and quiet: a duplicate-key collision has to return false so
      // it can be retried rather than aborting the whole request.
      $ok = dbi_execute($sql, [
        $candidate,
        $event->name,
        $event->date,
        $event->time,
        $event->durationMinutes,
        $event->description,
        $event->location,
        $event->createdBy,
        $modDate,
        $modTime,
        $event->type,
      ], false, false);

      if ($ok) {
        self::addParticipant($candidate, $event->createdBy);
        return $candidate;
      }
    }

    return null;
  }

  /**
   * Removes an event and everything that hangs off it.
   *
   * Also the rollback path when a recurrence rule cannot be stored, so a
   * failed repeating insert does not leave a one-off event behind.
   */
  public static function deleteEvent(int $eventId): void
  {
    foreach ([
      'webcal_entry_repeats_not',
      'webcal_entry_repeats',
      'webcal_entry_user',
      'webcal_entry',
    ] as $table) {
      dbi_execute('DELETE FROM ' . $table . ' WHERE cal_id = ?', [$eventId]);
    }
  }

  public static function addParticipant(
    int $eventId,
    string $login,
    string $status = 'A'
  ): bool {
    return (bool) dbi_execute(
      'INSERT INTO webcal_entry_user ( cal_id, cal_login, cal_status )'
      . ' VALUES ( ?, ?, ? )',
      [$eventId, $login, $status]
    );
  }

  /**
   * @param array<string, mixed> $columns cal_id is added here
   */
  public static function storeRecurrence(int $eventId, array $columns): bool
  {
    $columns = array_merge(['cal_id' => $eventId], $columns);
    $names = array_keys($columns);
    $placeholders = implode(', ', array_fill(0, count($names), '?'));

    return (bool) dbi_execute(
      'INSERT INTO webcal_entry_repeats ( ' . implode(', ', $names)
      . ' ) VALUES ( ' . $placeholders . ' )',
      array_values($columns)
    );
  }

  private static function nextEventId(): ?int
  {
    $res = dbi_execute('SELECT MAX(cal_id) FROM webcal_entry');
    if (!$res) {
      return null;
    }
    $row = dbi_fetch_row($res);
    dbi_free_result($res);

    return (int) ($row[0] ?? 0) + 1;
  }
}
