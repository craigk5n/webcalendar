<?php

declare(strict_types=1);

namespace WebCalendar\Api\Controllers;

use WebCalendar\Api\Requests\SearchRequest;
use WebCalendar\Api\Resources\SearchPageResource;
use WebCalendar\Services\CalendarSearch;

final class SearchController
{
  public function index(SearchRequest $request, CalendarSearch $search): SearchPageResource
  {
    $user = $request->user();
    return new SearchPageResource($search->find($user->username, $request->route('calendar', $user->username),
      $request->validated(), $user->tokenCan('events:write')));
  }
}
