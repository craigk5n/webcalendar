<?php

declare(strict_types=1);

namespace WebCalendar\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CalendarResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return array_intersect_key($this->resource, array_flip(['id', 'name', 'timezone', 'week_start', 'can_write']));
  }
}
