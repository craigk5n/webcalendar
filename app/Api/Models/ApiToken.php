<?php

declare(strict_types=1);

namespace WebCalendar\Api\Models;

use Laravel\Sanctum\PersonalAccessToken;

final class ApiToken extends PersonalAccessToken
{
  protected $table = 'webcal_api_tokens';
  protected $fillable = ['name', 'token', 'abilities', 'expires_at', 'refresh_id', 'rotation_hash', 'rotated_at', 'credential_generation'];

  protected $casts = ['abilities' => 'json', 'last_used_at' => 'datetime', 'expires_at' => 'datetime', 'rotated_at' => 'datetime'];
}
