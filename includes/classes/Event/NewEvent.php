<?php

declare(strict_types=1);

namespace WebCalendar\Event;

/**
 * The fields needed to create a row in webcal_entry.
 *
 * Named and shaped to sit close to webcalendar-core's Event entity so a later
 * port is mechanical, without pulling in that package: this repository ships
 * no vendor/ and loads classes through require_once, so a Composer dependency
 * is not available here.
 *
 * Dates are YYYYMMDD and times HHMMSS as integers, matching the columns.
 * A time of -1 means untimed, which is the convention the rest of the
 * application already uses.
 */
final class NewEvent
{
  public const UNTIMED = -1;

  public function __construct(
    public readonly string $name,
    public readonly int $date,
    public readonly int $time,
    public readonly int $durationMinutes,
    public readonly string $description,
    public readonly string $location,
    public readonly string $createdBy,
    public readonly string $type = 'E'
  ) {}
}
