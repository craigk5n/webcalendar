<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class CalendarSelection
{
  public function available(string $actor): array
  {
    $ids = DB::table('webcal_asst')->where('cal_assistant', $actor)->orderBy('cal_boss')->pluck('cal_boss')->all();
    $result = [];
    foreach (array_unique([$actor, ...$ids]) as $id) {
      try { $result[] = $this->activate($actor, $id); }
      catch (AuthorizationException) { continue; }
    }
    (new CalendarIdentity())->activate($actor);
    return $result;
  }

  public function activate(string $actor, string $calendar): array
  {
    (new CalendarIdentity())->activate($actor);
    $access = new CalendarEventAccess();
    $access->refreshPermissions();
    if (!access_can_access_function(ACCESS_EVENT_VIEW)
        || !$access->calendarPermission('view', $calendar)
        || ($calendar !== $actor && !DB::table('webcal_asst')->where('cal_assistant', $actor)->where('cal_boss', $calendar)->exists())) {
      throw new AuthorizationException();
    }
    if (in_array($calendar, ['__public__', '__default__'], true)) { throw new AuthorizationException(); }
    $nonuser = DB::table('webcal_nonuser_cals')->where('cal_login', $calendar)->first();
    if ($nonuser) {
      $name = trim($nonuser->cal_firstname . ' ' . $nonuser->cal_lastname);
    } else {
      unset($GLOBALS['cached_user_var']);
      $GLOBALS['selected_enabled'] = 'Y';
      if (!user_load_variables($calendar, 'selected_') || $GLOBALS['selected_enabled'] !== 'Y') {
        throw new AuthorizationException();
      }
      $name = $GLOBALS['selected_fullname'] ?? $calendar;
    }
    $settings = DB::table('webcal_config')->whereIn('cal_setting', ['TIMEZONE', 'WEEK_START'])->pluck('cal_value', 'cal_setting')->all();
    $preferences = DB::table('webcal_user_pref')->where('cal_login', $calendar)
      ->whereIn('cal_setting', ['TIMEZONE', 'WEEK_START'])->pluck('cal_value', 'cal_setting')->all();
    $settings = array_replace($settings, $preferences);
    $GLOBALS['TIMEZONE'] = !empty($settings['TIMEZONE']) ? $settings['TIMEZONE'] : $GLOBALS['api_installation_timezone'];
    date_default_timezone_set($GLOBALS['TIMEZONE']);
    set_env('TZ', $GLOBALS['TIMEZONE']);
    $GLOBALS['WEEK_START'] = (int) ($settings['WEEK_START'] ?? 0);
    $GLOBALS['user'] = $calendar;
    $GLOBALS['is_assistant'] = $actor !== $calendar;
    return [
      'id' => $calendar, 'name' => $name ?: $calendar, 'timezone' => $GLOBALS['TIMEZONE'],
      'week_start' => $GLOBALS['WEEK_START'], 'can_write' => (new CalendarEventAccess())->canWrite(),
    ];
  }
}
