<?php

declare(strict_types=1);

namespace WebCalendar\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use WebCalendar\Api\Models\ApiToken;
use WebCalendar\Api\Models\ApiUser;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class RenewableSessionService
{
  private const REFRESH = ['session:refresh'];
  private const CONSUMED = ['session:consumed'];
  private const EVENTS = ['events:read', 'events:write'];

  public function issue(ApiUser $user, string $name): array
  {
    $this->assertTransactionalStorage();
    $secret = bin2hex(random_bytes(32));
    $refresh = $user->tokens()->create([
      'name' => $name, 'token' => hash('sha256', $secret), 'abilities' => array_merge(self::REFRESH, self::EVENTS),
      'expires_at' => now()->addDays(config('webcalendar.refresh_inactivity_days')),
    ]);
    $access = $user->createToken($name, self::EVENTS, now()->addMinutes(config('webcalendar.access_lifetime_minutes')));
    $access->accessToken->forceFill(['refresh_id' => $refresh->id])->save();
    return $this->representation($access->accessToken, $access->plainTextToken, $refresh, $secret);
  }

  public static function revoke(ApiToken $token): void
  {
    $id = $token->refresh_id ?? $token->id;
    DB::transaction(function () use ($id): void {
      ApiToken::whereKey($id)->lockForUpdate()->first();
      ApiToken::where('id', $id)->orWhere('refresh_id', $id)->delete();
    }, 5);
  }

  public function renew(string $credential, string $rotation, bool $revoke = false): array
  {
    $this->assertTransactionalStorage();
    [$id, $secret] = explode('|', $credential, 2);
    $result = DB::transaction(function () use ($id, $secret, $rotation, $revoke): ?array {
      $refresh = ApiToken::whereKey($id)->lockForUpdate()->first();
      if (!$refresh || !in_array('session:refresh', $refresh->abilities ?? [], true) || $refresh->refresh_id !== null) {
        return null;
      }
      $hash = hash('sha256', $secret);
      $current = hash_equals($refresh->token, $hash);
      $used = $current ? null : ApiToken::where('refresh_id', $id)->where('token', $hash)->first();
      if (!$current && (!$used || $used->abilities !== self::CONSUMED)) {
        return null;
      }
      $user = ApiUser::where('username', $refresh->tokenable_id)->first();
      $profile = $user ? (new CalendarIdentity())->find($user->username) : null;
      if (!$profile || $user->auth_backend !== $GLOBALS['user_inc'] || !$user->subject_id
          || !hash_equals($user->subject_id, $profile['subject_id'])) {
        if ($user) {
          $user->tokens()->delete();
        }
        return null;
      }
      if ($revoke || $refresh->expires_at->isPast()) {
        self::revoke($refresh);
        return $revoke ? [] : null;
      }
      $nextSecret = hash_hmac('sha256', 'refresh:' . $rotation, $secret);
      $accessSecret = hash_hmac('sha256', 'access:' . $rotation, $secret);
      if (!$current) {
        $access = ApiToken::where('refresh_id', $id)->where('token', hash('sha256', $accessSecret))->first();
        if (!$used->rotated_at || now()->diffInSeconds($used->rotated_at, true) > config('webcalendar.rotation_recovery_seconds')
            || !hash_equals($used->rotation_hash ?? '', hash('sha256', $rotation))
            || !hash_equals($refresh->token, hash('sha256', $nextSecret)) || !$access) {
          self::revoke($refresh);
          return null;
        }
        return $this->representation($access, $access->id . '|' . $accessSecret, $refresh, $nextSecret);
      }
      // In-flight readers may still be using the previous, unexpired access token.
      ApiToken::where('refresh_id', $id)->whereNull('rotation_hash')->where('expires_at', '<', now())->delete();
      $refresh->forceFill([
        'token' => hash('sha256', $nextSecret),
        'expires_at' => now()->addDays(config('webcalendar.refresh_inactivity_days')),
      ])->save();
      $user->tokens()->create([
        'name' => $refresh->name, 'token' => $hash, 'abilities' => self::CONSUMED,
        'refresh_id' => $id, 'rotation_hash' => hash('sha256', $rotation), 'rotated_at' => now(),
        'expires_at' => $refresh->expires_at,
      ]);
      $access = $user->tokens()->create([
        'name' => $refresh->name, 'token' => hash('sha256', $accessSecret), 'abilities' => array_values(array_intersect(self::EVENTS, $refresh->abilities)),
        'refresh_id' => $id, 'expires_at' => now()->addMinutes(config('webcalendar.access_lifetime_minutes')),
      ]);
      return $this->representation($access, $access->id . '|' . $accessSecret, $refresh, $nextSecret);
    }, 5);
    if ($result === null) {
      throw new AuthenticationException();
    }
    return $result;
  }

  private function representation(ApiToken $access, string $token, ApiToken $refresh, string $secret): array
  {
    return [
      'token' => $token, 'token_type' => 'Bearer', 'expires_at' => $access->expires_at->toIso8601String(),
      'refresh_token' => $refresh->id . '|' . $secret, 'refresh_expires_at' => $refresh->expires_at->toIso8601String(),
    ];
  }

  private function assertTransactionalStorage(): void
  {
    if (DB::connection()->getDriverName() !== 'mysql') {
      return;
    }
    $tables = DB::select("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('webcal_api_users', 'webcal_api_tokens')");
    if (count($tables) !== 2 || array_filter($tables, fn ($table) => strcasecmp($table->ENGINE ?? '', 'InnoDB') !== 0)) {
      throw new ServiceUnavailableHttpException(null, 'Renewable sessions require InnoDB API tables.');
    }
  }
}
