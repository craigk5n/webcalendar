<?php

declare(strict_types=1);

namespace WebCalendar\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;
use WebCalendar\Api\Requests\TokenRequest;
use WebCalendar\Services\DeviceTokenService;

final class TokenController
{
  public function store(TokenRequest $request, DeviceTokenService $tokens): JsonResponse
  {
    $data = $request->validated();
    return (new JsonResource($tokens->issue($data['username'], $data['password'], $data['device_name'])))
      ->response()->setStatusCode(Response::HTTP_CREATED);
  }

  public function destroy(Request $request): JsonResource
  {
    $request->user()->currentAccessToken()->delete();
    return new JsonResource(['revoked' => true]);
  }
}
