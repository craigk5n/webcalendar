<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CalendarEventAccess
{
  /** The caller activates the account before checking its current preferences. */
  public function canWrite(): bool
  {
    return ($GLOBALS['readonly'] ?? 'N') !== 'Y'
      && access_can_access_function(ACCESS_EVENT_EDIT)
      && access_can_access_function(ACCESS_EVENT_VIEW)
      && $this->canViewDetails($GLOBALS['user'] ?? $GLOBALS['login'])
      && $this->calendarPermission('edit', $GLOBALS['user'] ?? $GLOBALS['login']);
  }

  public function calendarPermission(string $action, string $calendar, string $type = '', string $access = ''): bool
  {
    if (!access_is_enabled() || $calendar === $GLOBALS['login']) { return true; }
    return (bool) access_user_calendar($action, $calendar, $GLOBALS['login'], $type, $access);
  }

  public function refreshPermissions(): void
  {
    // Load once per activation, including each write attempt, rather than per event.
    $GLOBALS['access_other_cals'] = [];
    if (access_is_enabled()) { access_load_user_permissions(false); }
  }

  public function canViewDetails(string $calendar): bool
  {
    if (!access_is_enabled() || $calendar === $GLOBALS['login']) { return true; }
    return access_user_calendar('time', $calendar, $GLOBALS['login']) === 'N';
  }

  public function canViewEvent(string $calendar, array $row): bool
  {
    return $this->canViewDetails($calendar)
      && $this->calendarPermission('view', $calendar, $row['cal_type'], $row['cal_access']);
  }

  public function canEditEvent(string $calendar, array $row): bool
  {
    return $this->canViewEvent($calendar, $row)
      && $this->calendarPermission('edit', $calendar, $row['cal_type'], $row['cal_access'])
      && $this->calendarPermission('edit', $row['cal_create_by'], $row['cal_type'], $row['cal_access']);
  }

  /** @return array<int, bool> */
  public function editableIds(string $username, array $ids, bool $delegated = false): array
  {
    if (!$ids || !$this->canWrite()) {
      return [];
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $linked = DB::table('webcal_entry')->whereIntegerInRaw('cal_group_id', $ids)
      ->whereIntegerInRaw('cal_ext_for_id', $ids, 'or')->get(['cal_group_id', 'cal_ext_for_id']);
    $parents = [];
    foreach ($linked as $child) {
      foreach ([$child->cal_group_id, $child->cal_ext_for_id] as $parent) {
        if ($parent !== null && (int) $parent > 0) { $parents[] = (int) $parent; }
      }
    }
    $query = DB::table('webcal_entry as e')
      ->join('webcal_entry_user as own', 'own.cal_id', '=', 'e.cal_id')
      ->whereIntegerInRaw('e.cal_id', $ids)
      ->when(!$delegated, fn (Builder $q) => $q->where('e.cal_create_by', $username))->where('e.cal_type', 'E')
      ->where('own.cal_login', $username)->where('own.cal_status', 'A');
    foreach (['cal_group_id', 'cal_ext_for_id'] as $column) {
      $query->where(fn (Builder $q) => $q->whereNull('e.' . $column)->orWhere('e.' . $column, 0));
    }
    $query->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('webcal_entry_user as other')
      ->whereColumn('other.cal_id', 'e.cal_id')->where('other.cal_login', '<>', $username));
    foreach (['webcal_entry_ext_user', 'webcal_entry_repeats', 'webcal_entry_repeats_not'] as $table) {
      $query->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from($table . ' as related')->whereColumn('related.cal_id', 'e.cal_id'));
    }
    if ($parents) { $query->whereIntegerNotInRaw('e.cal_id', array_unique($parents)); }
    $editable = [];
    foreach ($query->get(['e.cal_id', 'e.cal_create_by', 'e.cal_type', 'e.cal_access']) as $row) {
      if ($this->canEditEvent($username, (array) $row)) { $editable[(int) $row->cal_id] = true; }
    }
    return $editable;
  }
}
