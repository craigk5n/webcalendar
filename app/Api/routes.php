<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use WebCalendar\Api\Controllers\EventController;
use WebCalendar\Api\Controllers\TokenController;
use WebCalendar\Api\Controllers\UserController;

Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:api-login');
Route::middleware(['auth:sanctum', 'api.identity'])->group(function (): void {
  Route::get('/users/current', [UserController::class, 'show']);
  Route::delete('/tokens/current', [TokenController::class, 'destroy']);
  Route::get('/events', [EventController::class, 'index'])->middleware('abilities:events:read');
});
