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
    $identity = new CalendarIdentity();
    $profile = $identity->find($user->username);
    if (!$profile || $user->auth_backend !== $GLOBALS['user_inc']) {
      $user->tokens()->delete();
      throw new AuthenticationException();
    }
    $request->attributes->set('calendar_profile', $profile);
    return $next($request);
  }
}
