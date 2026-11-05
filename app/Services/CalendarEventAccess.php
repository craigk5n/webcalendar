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
      && access_can_access_function(ACCESS_EVENT_VIEW);
  }

  /** @return array<int, bool> */
  public function editableIds(string $username, array $ids): array
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
      ->where('e.cal_create_by', $username)->where('e.cal_type', 'E')
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
    return array_fill_keys($query->pluck('e.cal_id')->all(), true);
  }
}
