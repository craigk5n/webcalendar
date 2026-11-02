<?php

declare(strict_types=1);

return [
  'defaults' => ['guard' => 'sanctum', 'passwords' => 'users'],
  'guards' => ['sanctum' => ['driver' => 'sanctum', 'provider' => 'users']],
  'providers' => ['users' => ['driver' => 'eloquent', 'model' => WebCalendar\Api\Models\ApiUser::class]],
];
