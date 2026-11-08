<?php

declare(strict_types=1);

namespace WebCalendar\Api\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use WebCalendar\Api\Requests\RenewalRequest;
use WebCalendar\Services\RenewableSessionService;

final class RenewalController
{
  public function store(RenewalRequest $request, RenewableSessionService $sessions): JsonResource
  {
    $data = $request->validated();
    return new JsonResource($sessions->renew($data['refresh_token'], $data['rotation_secret']));
  }

  public function destroy(RenewalRequest $request, RenewableSessionService $sessions): JsonResource
  {
    $data = $request->validated();
    $sessions->renew($data['refresh_token'], $data['rotation_secret'], true);
    return new JsonResource(['revoked' => true]);
  }
}
