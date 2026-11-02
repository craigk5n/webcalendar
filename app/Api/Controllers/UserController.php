<?php

declare(strict_types=1);

namespace WebCalendar\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserController
{
  public function show(Request $request): JsonResource
  {
    return new JsonResource($request->attributes->get('calendar_profile'));
  }
}
