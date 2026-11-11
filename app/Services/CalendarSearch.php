<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use DateTimeZone;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CalendarSearch
{
  private const DEFAULT_PAGE_SIZE = 50;

  public function find(string $actor, string $calendar, array $input, bool $canWrite): array
  {
    $selected = (new CalendarSelection())->activate($actor, $calendar);
    $access = new CalendarEventAccess();
    if (!$access->canViewDetails($calendar)) { throw new AuthorizationException(); }
    $visibility = array_values(array_filter(['P', 'R', 'C'], fn ($value) => $access->calendarPermission('view', $calendar, 'E', $value)));
    $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $input['q']) . '%';
    $limit = (int) ($input['limit'] ?? self::DEFAULT_PAGE_SIZE);
    $rows = DB::table('webcal_entry as e')
      ->join('webcal_entry_user as u', 'u.cal_id', '=', 'e.cal_id')
      ->where('u.cal_login', $calendar)->whereIn('u.cal_status', ['A', 'W'])
      ->whereIn('e.cal_type', ['E', 'M'])->whereIn('e.cal_access', $visibility)
      ->where(function (Builder $query) use ($pattern): void {
        foreach (['cal_name', 'cal_description', 'cal_location'] as $column) {
          $query->orWhereRaw("LOWER(e.$column) LIKE LOWER(?) ESCAPE '!'", [$pattern]);
        }
      })
      ->when(isset($input['cursor']), fn (Builder $query) => $query->where('e.cal_id', '<', (int) $input['cursor']))
      ->orderByDesc('e.cal_id')->limit($limit + 1)->get(['e.*', 'u.cal_status']);
    $hasMore = $rows->count() > $limit;
    $rows = $rows->take($limit);
    $editable = $canWrite ? $access->editableIds($calendar, $rows->pluck('cal_id')->all(), $actor !== $calendar) : [];
    $repeatIds = $rows->isEmpty() ? [] : DB::table('webcal_entry_repeats')->whereIn('cal_id', $rows->pluck('cal_id')->all())->pluck('cal_id')->all();
    $results = [];
    foreach ($rows as $row) {
      $recurring = $row->cal_type === 'M' || in_array($row->cal_id, $repeatIds);
      $event = CalendarEventData::fromRow((array) $row, new DateTimeZone($selected['timezone']));
      $event['can_edit'] = !$recurring && isset($editable[$event['id']]);
      $event['calendar_id'] = $calendar;
      $event['calendar_name'] = $selected['name'];
      $results[] = ['event' => $event, 'is_recurring' => $recurring];
    }
    return ['results' => $results, 'next_cursor' => $hasMore ? (string) $rows->last()->cal_id : null];
  }
}
