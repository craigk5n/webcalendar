<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use DateTimeImmutable;
use DateTimeZone;
use Event;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use WebCalendar\Api\Requests\EventWriteRequest;

final class CalendarEvents
{
  private const SECONDS_PER_MINUTE = 60;

  public function between(string $username, string $startDate, string $endDate, bool $includeContinuations = true, ?string $actor = null): array
  {
    if ($actor !== null) { (new CalendarSelection())->activate($actor, $username); }
    else { (new CalendarIdentity())->activate($username); }
    if ($includeContinuations && !(new CalendarEventAccess())->canViewDetails($username)) {
      throw new AuthorizationException();
    }
    if (!access_can_access_function(ACCESS_EVENT_VIEW)) {
      throw new AuthorizationException();
    }
    $timezone = new DateTimeZone($GLOBALS['TIMEZONE']);
    $start = new DateTimeImmutable($startDate, $timezone);
    $end = new DateTimeImmutable($endDate . ' 23:59:59', $timezone);
    $GLOBALS['layers'] = [];
    $GLOBALS['PUBLIC_ACCESS_DEFAULT_VISIBLE'] = 'N';
    $GLOBALS['thismonth'] = (int) $start->format('m');
    $GLOBALS['thisyear'] = (int) $start->format('Y');
    $GLOBALS['jumpdate'] = $start->getTimestamp();
    $GLOBALS['max_until'] = $end->getTimestamp();
    $GLOBALS['events'] = [];
    $GLOBALS['repeated_events'] = query_events($username, true, 'AND (wer.cal_end >= ' . $start->format('Ymd') . ' OR wer.cal_end IS NULL)');
    $repeatIds = array_unique(array_map(fn (Event $event) => $event->getID(), $GLOBALS['repeated_events']));
    $durations = $repeatIds ? DB::table('webcal_entry')->whereIntegerInRaw('cal_id', $repeatIds)->pluck('cal_duration', 'cal_id')->all() : [];
    $result = $this->ordinaryBetween($username, $start, $end, $timezone, $includeContinuations);
    for ($day = $start; $day <= $end; $day = $day->modify('+1 day')->setTime(0, 0)) {
      $date = $day->format('Ymd');
      foreach (get_repeating_entries($username, $date) as $event) {
        if ($event->getLogin() !== $username || !in_array($event->getStatus(), ['A', 'W'], true)) {
          continue;
        }
        if (!$includeContinuations && $event->getClone()) { continue; }
        $startsAt = $this->occurrenceStart($event, $day, $timezone);
        $result[$event->getID() . ':' . $date] = [
          'id' => $event->getID(),
          'date' => $day->format('Y-m-d'),
          'title' => $event->getName(),
          'description' => $event->getDescription(),
          'location' => $event->getLocation(),
          'starts_at' => $startsAt ? $startsAt->format(DATE_ATOM) : null,
          'duration_minutes' => $event->getClone() ? $event->getDuration() : (int) ($durations[$event->getID()] ?? $event->getDuration()),
          'all_day' => $event->isAllDay(),
          'untimed' => $event->isUntimed(),
          'status' => $event->getStatus(),
          'access' => $event->getAccess(),
          'timezone' => $timezone->getName(),
          'is_continuation' => (bool) $event->getClone(),
        ];
      }
    }
    // Internal schedule checks need all busy intervals, even for hidden occurrences.
    if ($includeContinuations && $actor !== null && $actor !== $username) {
      $access = new CalendarEventAccess();
      $result = array_filter($result, fn (array $event) => $access->calendarPermission('view', $username, 'E', $event['access']));
    }
    usort($result, fn ($left, $right) => [$left['date'], $left['starts_at'] ?? '', $left['id']] <=> [$right['date'], $right['starts_at'] ?? '', $right['id']]);
    return array_values($result);
  }

  private function ordinaryBetween(string $username, DateTimeImmutable $start, DateTimeImmutable $end, DateTimeZone $timezone, bool $includeContinuations): array
  {
    $utc = new DateTimeZone('UTC');
    $firstDate = min($start->modify('-1 day')->format('Ymd'), $start->setTimezone($utc)->modify('-1 day')->format('Ymd'));
    $lastDate = max($end->format('Ymd'), $end->setTimezone($utc)->format('Ymd'));
    $rows = DB::table('webcal_entry as e')->join('webcal_entry_user as u', 'u.cal_id', '=', 'e.cal_id')
      ->where('u.cal_login', $username)->whereIn('u.cal_status', ['A', 'W'])->where('e.cal_type', 'E')
      ->where('e.cal_date', '<=', (int) $lastDate)
      ->where(fn (Builder $q) => $q->where('e.cal_date', '>=', (int) $firstDate)->orWhere('e.cal_duration', '>', EventWriteRequest::MINUTES_PER_DAY))
      ->select('e.*', 'u.cal_status')->cursor();
    $result = [];
    foreach ($rows as $row) {
      $event = CalendarEventData::fromRow((array) $row, $timezone);
      if ($event['all_day'] || $event['untimed']) {
        if ($event['date'] >= $start->format('Y-m-d') && $event['date'] <= $end->format('Y-m-d')) {
          $result[$event['id'] . ':' . str_replace('-', '', $event['date'])] = $event;
        }
        continue;
      }
      $eventStart = new DateTimeImmutable($event['starts_at']);
      $eventEnd = $eventStart->modify('+' . max(0, $event['duration_minutes']) . ' minutes');
      $firstDay = max($start, $eventStart->setTimezone($timezone)->setTime(0, 0));
      $lastDay = min($end->setTime(0, 0), ($event['duration_minutes'] > 0 ? $eventEnd->modify('-1 second') : $eventStart)->setTimezone($timezone)->setTime(0, 0));
      // Midnight can jump to 01:00; do not carry that clock time into later days.
      for ($day = $firstDay; $day <= $lastDay; $day = $day->modify('+1 day')->setTime(0, 0)) {
        $occurrence = $event;
        if ($day->format('Y-m-d') !== $event['date']) {
          if (!$includeContinuations) { continue; }
          // Display fragments are read-only; the starting day keeps the full schedule.
          $fragmentEnd = min($eventEnd, $day->modify('+1 day')->setTime(0, 0));
          $occurrence['date'] = $day->format('Y-m-d');
          $occurrence['starts_at'] = $day->format(DATE_ATOM);
          $occurrence['duration_minutes'] = (int) (($fragmentEnd->getTimestamp() - $day->getTimestamp()) / self::SECONDS_PER_MINUTE);
          $occurrence['title'] .= ' (' . translate('cont.') . ')';
          $occurrence['is_continuation'] = true;
        }
        $result[$event['id'] . ':' . $day->format('Ymd')] = $occurrence;
      }
    }
    return $result;
  }

  private function occurrenceStart(Event $event, DateTimeImmutable $day, DateTimeZone $timezone): ?DateTimeImmutable
  {
    if (!$event->isTimed()) {
      return null;
    }
    $utc = new DateTimeImmutable('@' . $event->getDateTimeTS());
    $original = $utc->setTimezone($timezone);
    if ($event->getCalType() !== 'M') {
      return $original;
    }
    // Recurrence dates are local, but event clock times are stored in UTC.
    $dateShift = strcmp($utc->format('Y-m-d'), $original->format('Y-m-d'));
    return $utc->setDate((int) $day->format('Y'), (int) $day->format('m'), (int) $day->format('d'))
      ->modify($dateShift . ' days')->setTimezone($timezone);
  }
}
