<?php

declare(strict_types=1);

namespace WebCalendar\Api\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use WebCalendar\Services\CalendarIdentity;

final class AuthenticateIdentity
{
  public function handle(Request $request, Closure $next): Response
  {
    $user = $request->user();
    $abilities = $user->currentAccessToken()->abilities ?? [];
    if (in_array('session:refresh', $abilities, true) || in_array('session:consumed', $abilities, true)) {
      abort(Response::HTTP_FORBIDDEN);
    }
    $identity = new CalendarIdentity();
    $profile = $identity->find($user->username);
    if (!$profile || $user->auth_backend !== $GLOBALS['user_inc']
        || !$user->subject_id || !hash_equals($user->subject_id, $profile['subject_id'])) {
      $user->tokens()->delete();
      throw new AuthenticationException();
    }
    unset($profile['subject_id']);
    $request->attributes->set('calendar_profile', $profile);
    return $next($request);
  }
}
