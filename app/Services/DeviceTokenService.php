<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use Illuminate\Auth\AuthenticationException;
use WebCalendar\Api\Models\ApiUser;

final class DeviceTokenService
{
  public function issue(string $username, string $password, string $deviceName, bool $renewable = false): array
  {
    $profile = (new CalendarIdentity())->authenticate($username, $password);
    if (!$profile) {
      throw new AuthenticationException('Invalid credentials.');
    }
    return ApiCredentialGuard::locked(function () use ($username, $deviceName, $profile, $renewable): array {
      $latest = (new CalendarIdentity())->find($username);
      if (!$latest || !hash_equals($profile['subject_id'], $latest['subject_id'])) { throw new AuthenticationException(); }
      $user = ApiUser::firstOrCreate(['username' => $username], [
        'auth_backend' => $GLOBALS['user_inc'], 'subject_id' => $profile['subject_id'],
        'credential_generation' => bin2hex(random_bytes(32)),
      ]);
      if ($user->auth_backend !== $GLOBALS['user_inc'] || $user->subject_id !== $profile['subject_id'] || !$user->credential_generation) {
        ApiCredentialGuard::invalidate($user);
        $user->update(['auth_backend' => $GLOBALS['user_inc'], 'subject_id' => $profile['subject_id']]);
      }
      if ($renewable) {
        return (new RenewableSessionService())->issue($user, $deviceName);
      }
      $expires = now()->addDays(config('webcalendar.token_lifetime_days'));
      $token = $user->createToken($deviceName, ['events:read', 'events:write'], $expires);
      $token->accessToken->forceFill(['credential_generation' => $user->credential_generation])->save();
      return ['token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $expires->toIso8601String()];
    });
  }
}
