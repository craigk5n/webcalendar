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
    $profile = (new CalendarIdentity())->authenticate($username, $password);
    if (!$profile) {
      throw new AuthenticationException('Invalid credentials.');
    }
    return DB::transaction(function () use ($username, $deviceName, $profile): array {
      $user = ApiUser::firstOrCreate(['username' => $username], ['auth_backend' => $GLOBALS['user_inc'], 'subject_id' => $profile['subject_id']]);
      if ($user->auth_backend !== $GLOBALS['user_inc'] || $user->subject_id !== $profile['subject_id']) {
        $user->tokens()->delete();
        $user->update(['auth_backend' => $GLOBALS['user_inc'], 'subject_id' => $profile['subject_id']]);
      }
      $expires = now()->addDays(config('webcalendar.token_lifetime_days'));
      $token = $user->createToken($deviceName, ['events:read'], $expires);
      return ['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $expires->toIso8601String()];
    });
  }
}
