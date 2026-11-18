<?php

declare(strict_types=1);

namespace WebCalendar\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EventDeletionResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return ['id' => $this->resource['id'], 'deleted' => $this->resource['deleted']];
  }
}
