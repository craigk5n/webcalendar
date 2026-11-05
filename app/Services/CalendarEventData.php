<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use DateTimeImmutable;
use DateTimeZone;
use WebCalendar\Api\Requests\EventWriteRequest;

final class CalendarEventData
{
  public static function fromRow(array $row, DateTimeZone $timezone): array
  {
    $untimed = (int) $row['cal_time'] < 0;
    $allDay = !$untimed && (int) $row['cal_time'] === 0 && (int) $row['cal_duration'] === EventWriteRequest::MINUTES_PER_DAY;
    $date = DateTimeImmutable::createFromFormat('!Ymd', sprintf('%08d', $row['cal_date']), new DateTimeZone('UTC'));
    $start = null;
    if (!$untimed && !$allDay) {
      $start = DateTimeImmutable::createFromFormat('!YmdHis', sprintf('%08d%06d', $row['cal_date'], $row['cal_time']), new DateTimeZone('UTC'))->setTimezone($timezone);
      $date = $start;
    }
    return ['id' => (int) $row['cal_id'], 'date' => $date->format('Y-m-d'), 'title' => $row['cal_name'],
      'description' => $row['cal_description'] ?? '', 'location' => $row['cal_location'] ?? null,
      'starts_at' => $start?->format(DATE_ATOM), 'duration_minutes' => (int) $row['cal_duration'],
      'all_day' => $allDay, 'untimed' => $untimed, 'status' => $row['cal_status'] ?? 'A',
      'access' => $row['cal_access'], 'timezone' => $timezone->getName()];
  }
}
