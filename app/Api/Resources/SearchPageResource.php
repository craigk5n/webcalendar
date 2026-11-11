<?php

declare(strict_types=1);

namespace WebCalendar\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SearchPageResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'results' => array_map(fn (array $result) => [
        'id' => (string) $result['event']['id'],
        'is_recurring' => $result['is_recurring'],
        'event' => (new EventResource($result['event']))->toArray($request),
      ], $this->resource['results']),
      'next_cursor' => $this->resource['next_cursor'],
    ];
  }
}
