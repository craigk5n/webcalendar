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

final class EventController
{
  public function index(EventRequest $request, CalendarEvents $events, CalendarEventAccess $access): AnonymousResourceCollection
  {
    $data = $request->validated();
    $occurrences = $events->between($request->user()->username, $data['start_date'], $data['end_date']);
    $editable = $request->user()->tokenCan('events:write')
      ? $access->editableIds($request->user()->username, array_column($occurrences, 'id')) : [];
    foreach ($occurrences as &$occurrence) {
      $occurrence['can_edit'] = empty($occurrence['is_continuation']) && isset($editable[$occurrence['id']]);
    }
    unset($occurrence);
    return EventResource::collection($occurrences);
  }

  public function store(StoreEventRequest $request, CalendarEventWriter $writer): JsonResponse
  {
    return (new EventResource($writer->create($request->user()->username, $request->validated())))
      ->response()->setStatusCode(Response::HTTP_CREATED);
  }

  public function update(UpdateEventRequest $request, CalendarEventWriter $writer, string $event): EventResource
  {
    return new EventResource($writer->update($request->user()->username, (int) $event, $request->validated()));
  }
}
