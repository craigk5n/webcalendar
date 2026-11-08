<?php

declare(strict_types=1);

namespace WebCalendar\Api\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use WebCalendar\Services\CalendarIdentity;
use WebCalendar\Services\ApiCredentialGuard;
use WebCalendar\Api\Models\ApiUser;

final class AuthenticateIdentity
{
  public function handle(Request $request, Closure $next): Response
  {
    $token = $request->user()->currentAccessToken();
    $user = ApiUser::find($request->user()->username);
    $generation = $token->credential_generation;
    if (!$user || !$generation || $generation !== $user->credential_generation) { throw new AuthenticationException(); }
    $abilities = $token->abilities ?? [];
    if (in_array('session:refresh', $abilities, true) || in_array('session:consumed', $abilities, true)) {
      abort(Response::HTTP_FORBIDDEN);
    }
    $identity = new CalendarIdentity();
    $profile = $identity->find($user->username);
    if (!$profile || $user->auth_backend !== $GLOBALS['user_inc']
        || !$user->subject_id || !hash_equals($user->subject_id, $profile['subject_id'])) {
      ApiCredentialGuard::locked(function () use ($user, $generation, $identity): void {
        $current = ApiUser::find($user->username);
        if (!$current || $current->credential_generation !== $generation) { return; }
        $profile = $identity->find($user->username);
        if (!$profile || $current->auth_backend !== $GLOBALS['user_inc'] || !$current->subject_id
            || !hash_equals($current->subject_id, $profile['subject_id'])) {
          ApiCredentialGuard::invalidate($current);
        }
      });
      throw new AuthenticationException();
    }
    unset($profile['subject_id']);
    $request->attributes->set('calendar_profile', $profile);
    return $next($request);
  }
}
