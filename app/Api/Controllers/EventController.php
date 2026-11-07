<?php

declare(strict_types=1);

namespace WebCalendar\Api\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use WebCalendar\Api\Requests\EventRequest;
use WebCalendar\Api\Requests\StoreEventRequest;
use WebCalendar\Api\Requests\UpdateEventRequest;
use WebCalendar\Api\Resources\EventResource;
use WebCalendar\Services\CalendarEventAccess;
use WebCalendar\Services\CalendarEventWriter;
use WebCalendar\Services\CalendarEvents;
use WebCalendar\Services\CalendarSelection;

final class EventController
{
  public function index(EventRequest $request, CalendarEvents $events, CalendarEventAccess $access): AnonymousResourceCollection
  {
    $data = $request->validated();
    $actor = $request->user()->username;
    $calendar = $request->route('calendar', $actor);
    $selected = (new CalendarSelection())->activate($actor, $calendar);
    $occurrences = $events->between($calendar, $data['start_date'], $data['end_date'], true, $actor);
    $editable = $request->user()->tokenCan('events:write')
      ? $access->editableIds($calendar, array_column($occurrences, 'id'), $actor !== $calendar) : [];
    foreach ($occurrences as &$occurrence) {
      $occurrence['can_edit'] = empty($occurrence['is_continuation']) && isset($editable[$occurrence['id']]);
      $occurrence['calendar_id'] = $calendar;
      $occurrence['calendar_name'] = $selected['name'];
    }
    unset($occurrence);
    return EventResource::collection($occurrences);
  }

  public function store(StoreEventRequest $request, CalendarEventWriter $writer): JsonResponse
  {
    return (new EventResource($writer->create($request->user()->username, $request->validated(), $request->route('calendar'))))
      ->response()->setStatusCode(Response::HTTP_CREATED);
  }

  public function update(UpdateEventRequest $request, CalendarEventWriter $writer): EventResource
  {
    return new EventResource($writer->update($request->user()->username, (int) $request->route('event'), $request->validated(), $request->route('calendar')));
  }
}
