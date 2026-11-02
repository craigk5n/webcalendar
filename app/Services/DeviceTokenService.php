<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use WebCalendar\Api\Models\ApiUser;

final class DeviceTokenService
{
  public function issue(string $username, string $password, string $deviceName): array
  {
    if (!(new CalendarIdentity())->authenticate($username, $password)) {
      throw new AuthenticationException('Invalid credentials.');
    }
    return DB::transaction(function () use ($username, $deviceName): array {
      $user = ApiUser::firstOrCreate(['username' => $username], ['auth_backend' => $GLOBALS['user_inc']]);
      if ($user->auth_backend !== $GLOBALS['user_inc']) {
        $user->tokens()->delete();
        $user->update(['auth_backend' => $GLOBALS['user_inc']]);
      }
      $expires = now()->addDays(config('webcalendar.token_lifetime_days'));
      $token = $user->createToken($deviceName, ['events:read'], $expires);
      return ['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $expires->toIso8601String()];
    });
  }
}
