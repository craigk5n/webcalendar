<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use WebCalendar\Api\Controllers\EventController;
use WebCalendar\Api\Controllers\CalendarController;
use WebCalendar\Api\Controllers\TokenController;
use WebCalendar\Api\Controllers\UserController;

Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:api-login');
Route::post('/token-renewals', [\WebCalendar\Api\Controllers\RenewalController::class, 'store']);
Route::delete('/sessions/current', [\WebCalendar\Api\Controllers\RenewalController::class, 'destroy']);
Route::middleware(['auth:sanctum', 'api.identity'])->group(function (): void {
  Route::get('/users/current', [UserController::class, 'show']);
  Route::delete('/tokens/current', [TokenController::class, 'destroy']);
  Route::get('/events', [EventController::class, 'index'])->middleware('abilities:events:read');
  Route::get('/search-results', [\WebCalendar\Api\Controllers\SearchController::class, 'index'])->middleware('abilities:events:read');
  Route::get('/calendars/{calendar}/search-results', [\WebCalendar\Api\Controllers\SearchController::class, 'index'])->middleware('abilities:events:read');
  Route::get('/calendars', [CalendarController::class, 'index'])->middleware('abilities:events:read');
  Route::get('/calendars/{calendar}/events', [EventController::class, 'index'])->middleware('abilities:events:read');
  Route::post('/calendars/{calendar}/events', [EventController::class, 'store'])->middleware('abilities:events:write');
  Route::patch('/calendars/{calendar}/events/{event}', [EventController::class, 'update'])->whereNumber('event')->middleware('abilities:events:write');
  Route::post('/events', [EventController::class, 'store'])->middleware('abilities:events:write');
  Route::patch('/events/{event}', [EventController::class, 'update'])->whereNumber('event')->middleware('abilities:events:write');
});
