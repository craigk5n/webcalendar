<?php

declare(strict_types=1);

namespace WebCalendar\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use WebCalendar\Services\CalendarIdentity;

final class UserController
{
  public function show(Request $request, CalendarIdentity $identity): JsonResource
  {
    $profile = $request->attributes->get('calendar_profile');
    $profile['week_start'] = $identity->weekStart($request->user()->username);
    return new JsonResource($profile);
  }
}
