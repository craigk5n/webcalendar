<?php

declare(strict_types=1);

namespace WebCalendar\Api\Models;

use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;

final class ApiUser extends User
{
  use HasApiTokens;

  protected $table = 'webcal_api_users';
  protected $primaryKey = 'username';
  protected $keyType = 'string';
  public $incrementing = false;
  public $timestamps = false;
  protected $fillable = ['username', 'auth_backend'];
}
