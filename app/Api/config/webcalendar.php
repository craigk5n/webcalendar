<?php

declare(strict_types=1);

return [
  'token_lifetime_days' => 30,
  'access_lifetime_minutes' => 60,
  'refresh_inactivity_days' => 90,
  'rotation_recovery_seconds' => 120,
  'max_event_range_days' => 93,
  'max_event_duration_minutes' => 93 * 1440,
  'login_attempts_per_minute' => 10,
  'requests_per_minute' => 120,
];
