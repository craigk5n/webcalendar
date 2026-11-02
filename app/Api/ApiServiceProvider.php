<?php

declare(strict_types=1);

namespace WebCalendar\Api;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use WebCalendar\Api\Models\ApiToken;

final class ApiServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    Sanctum::usePersonalAccessTokenModel(ApiToken::class);
    $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    RateLimiter::for('api', fn (Request $request) => Limit::perMinute(config('webcalendar.requests_per_minute'))->by($request->ip()));
    RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(config('webcalendar.login_attempts_per_minute'))->by($request->ip()));
  }
}
