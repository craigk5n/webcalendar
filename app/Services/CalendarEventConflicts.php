<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use WebCalendar\Api\Exceptions\EventWriteException;
use WebCalendar\Api\Requests\EventWriteRequest;

final class CalendarEventConflicts
{
  public function check(string $username, array $fields, DateTimeZone $timezone, int $excludedId = 0, ?string $actor = null): void
  {
    if (($GLOBALS['ALLOW_CONFLICTS'] ?? 'N') === 'Y' || $fields['untimed']) { return; }
    $start = $fields['all_day'] ? new DateTimeImmutable($fields['date'], $timezone) : new DateTimeImmutable($fields['starts_at']);
    $end = $fields['all_day'] ? $start->modify('+1 day')->setTime(0, 0) : $start->modify('+' . $fields['duration_minutes'] . ' minutes');
    $firstDay = $start->setTimezone($timezone)->setTime(0, 0);
    $lastDay = $end->modify('-1 second')->setTimezone($timezone)->setTime(0, 0);
    $counts = [];
    $consider = function (int $id, DateTimeImmutable $otherStart, DateTimeImmutable $otherEnd) use ($start, $end, $firstDay, $lastDay, &$counts): void {
      if ($otherStart < $end && $otherEnd > $start) { $this->conflict(); }
      if (($GLOBALS['LIMIT_APPTS'] ?? 'N') === 'Y') {
        for ($day = $firstDay; $day <= $lastDay; $day = $day->modify('+1 day')->setTime(0, 0)) {
          if ($otherStart < $day->modify('+1 day')->setTime(0, 0) && $otherEnd > $day) {
            $counts[$day->format('Y-m-d')][$id . ':' . $otherStart->getTimestamp()] = true;
          }
        }
      }
    };
    $utc = new DateTimeZone('UTC');
    $lowerDate = min($firstDay->modify('-1 day')->format('Ymd'), $start->setTimezone($utc)->modify('-1 day')->format('Ymd'));
    $upperDate = max($lastDay->format('Ymd'), $lastDay->modify('+1 day')->setTime(0, 0)->setTimezone($utc)->format('Ymd'));
    // Include earlier long-running entries; a start-date-only lookup misses overnight overlaps.
    $ordinary = DB::table('webcal_entry as e')->join('webcal_entry_user as u', 'u.cal_id', '=', 'e.cal_id')
      ->where('u.cal_login', $username)->whereIn('u.cal_status', ['A', 'W'])->where('e.cal_type', 'E')
      ->where('e.cal_time', '>=', 0)->where('e.cal_id', '<>', $excludedId)->where('e.cal_date', '<=', (int) $upperDate)
      ->where(fn (Builder $q) => $q->where('e.cal_date', '>=', (int) $lowerDate)
        ->orWhere('e.cal_duration', '>', EventWriteRequest::MINUTES_PER_DAY)
        ->orWhere(fn (Builder $q) => $q->where('e.cal_duration', EventWriteRequest::MINUTES_PER_DAY)->where('e.cal_time', '<>', 0)))
      ->get(['e.cal_id', 'e.cal_date', 'e.cal_time', 'e.cal_duration']);
    foreach ($ordinary as $row) {
      $allDay = (int) $row->cal_time === 0 && (int) $row->cal_duration === EventWriteRequest::MINUTES_PER_DAY;
      $otherStart = $allDay
        ? DateTimeImmutable::createFromFormat('!Ymd', sprintf('%08d', $row->cal_date), $timezone)
        : DateTimeImmutable::createFromFormat('!YmdHis', sprintf('%08d%06d', $row->cal_date, $row->cal_time), $utc);
      $otherEnd = $allDay ? $otherStart->modify('+1 day')->setTime(0, 0) : $otherStart->modify('+' . $row->cal_duration . ' minutes');
      $consider((int) $row->cal_id, $otherStart, $otherEnd);
    }

    $recurring = DB::table('webcal_entry as e')->join('webcal_entry_user as u', 'u.cal_id', '=', 'e.cal_id')
      ->where('u.cal_login', $username)->whereIn('u.cal_status', ['A', 'W'])->where('e.cal_type', 'M')
      ->where('e.cal_time', '>=', 0)->pluck('e.cal_duration', 'e.cal_id')->all();
    if ($recurring) {
      $maximumDuration = max($recurring);
      if ($maximumDuration > config('webcalendar.max_event_duration_minutes')) {
        throw new EventWriteException(Response::HTTP_CONFLICT, 'unsupported_schedule',
          'Conflict checking requires recurring durations within the API duration limit.');
      }
      $lookback = (int) ceil($maximumDuration / EventWriteRequest::MINUTES_PER_DAY) + 1;
      $cursor = $firstDay->modify('-' . $lookback . ' days')->setTime(0, 0);
      $windowDays = (int) config('webcalendar.max_event_range_days');
      $events = new CalendarEvents();
      while ($cursor <= $lastDay) {
        $windowEnd = min($cursor->modify('+' . ($windowDays - 1) . ' days')->setTime(0, 0), $lastDay);
        foreach ($events->between($username, $cursor->format('Y-m-d'), $windowEnd->format('Y-m-d'), false, $actor) as $event) {
          if (!isset($recurring[$event['id']]) || $event['id'] === $excludedId || $event['untimed'] || !empty($event['is_continuation'])) { continue; }
          $otherStart = $event['all_day'] ? new DateTimeImmutable($event['date'], $timezone) : new DateTimeImmutable($event['starts_at']);
          $otherEnd = $event['all_day'] ? $otherStart->modify('+1 day')->setTime(0, 0) : $otherStart->modify('+' . $event['duration_minutes'] . ' minutes');
          $consider($event['id'], $otherStart, $otherEnd);
        }
        $cursor = $windowEnd->modify('+1 day')->setTime(0, 0);
      }
    }
    $limit = (int) ($GLOBALS['LIMIT_APPTS_NUMBER'] ?? 0);
    if (($GLOBALS['LIMIT_APPTS'] ?? 'N') === 'Y' && $limit > 0) {
      foreach ($counts as $appointments) {
        if (count($appointments) >= $limit) { $this->conflict(); }
      }
    }
  }

  private function conflict(): never
  {
    throw new EventWriteException(Response::HTTP_CONFLICT, 'schedule_conflict', 'The event conflicts with the calendar scheduling rules.');
  }
}
