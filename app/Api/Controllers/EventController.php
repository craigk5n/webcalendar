<?php

declare(strict_types=1);

namespace WebCalendar\Api\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use WebCalendar\Api\Requests\EventRequest;
use WebCalendar\Services\CalendarEvents;

final class EventController
{
  public function index(EventRequest $request, CalendarEvents $events): AnonymousResourceCollection
  {
    $data = $request->validated();
    return JsonResource::collection($events->between($request->user()->username, $data['start_date'], $data['end_date']));
  }
}
