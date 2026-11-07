<?php

declare(strict_types=1);

namespace WebCalendar\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use WebCalendar\Api\Resources\CalendarResource;
use WebCalendar\Services\CalendarSelection;

final class CalendarController
{
  public function index(Request $request, CalendarSelection $calendars): AnonymousResourceCollection
  {
    $available = $calendars->available($request->user()->username);
    foreach ($available as &$calendar) { $calendar['can_write'] = $calendar['can_write'] && $request->user()->tokenCan('events:write'); }
    unset($calendar);
    return CalendarResource::collection($available);
  }
}
