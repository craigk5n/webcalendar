<?php

declare(strict_types=1);

namespace WebCalendar\Api\Models;

use Laravel\Sanctum\PersonalAccessToken;

final class ApiToken extends PersonalAccessToken
{
  protected $table = 'webcal_api_tokens';
}
