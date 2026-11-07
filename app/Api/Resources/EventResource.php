<?php

declare(strict_types=1);

namespace WebCalendar\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EventResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return array_intersect_key($this->resource, array_flip([
      'id', 'date', 'title', 'description', 'location', 'starts_at', 'duration_minutes',
      'all_day', 'untimed', 'status', 'access', 'timezone', 'can_edit', 'calendar_id', 'calendar_name',
    ]));
  }
}
