<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use DateTimeImmutable;
use DateTimeZone;
use Event;
use Illuminate\Auth\Access\AuthorizationException;

final class CalendarEvents
{
  public function between(string $username, string $startDate, string $endDate): array
  {
    (new CalendarIdentity())->activate($username);
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
    $GLOBALS['events'] = read_events($username, $start->getTimestamp(), $end->getTimestamp());
    $GLOBALS['repeated_events'] = query_events($username, true, 'AND (wer.cal_end >= ' . $start->format('Ymd') . ' OR wer.cal_end IS NULL)');
    $result = [];
    for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
      $date = $day->format('Ymd');
      $ordinary = array_filter(get_entries($date), fn ($event) => $event->getCalType() === 'E');
      foreach (array_merge($ordinary, get_repeating_entries($username, $date)) as $event) {
        if ($event->getLogin() !== $username || !in_array($event->getStatus(), ['A', 'W'], true)) {
          continue;
        }
        $startsAt = $this->occurrenceStart($event, $day, $timezone);
        $result[$event->getID() . ':' . $date] = [
          'id' => $event->getID(),
          'date' => $day->format('Y-m-d'),
          'title' => $event->getName(),
          'description' => $event->getDescription(),
          'location' => $event->getLocation(),
          'starts_at' => $startsAt ? $startsAt->format(DATE_ATOM) : null,
          'duration_minutes' => $event->getDuration(),
          'all_day' => $event->isAllDay(),
          'untimed' => $event->isUntimed(),
          'status' => $event->getStatus(),
          'access' => $event->getAccess(),
          'timezone' => $timezone->getName(),
        ];
      }
    }
    usort($result, fn ($left, $right) => [$left['date'], $left['starts_at'] ?? '', $left['id']] <=> [$right['date'], $right['starts_at'] ?? '', $right['id']]);
    return array_values($result);
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
