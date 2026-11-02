<?php

declare(strict_types=1);

return [
  'name' => 'WebCalendar API',
  'env' => 'production',
  'debug' => false,
  'timezone' => 'UTC',
  'key' => getenv('WEBCALENDAR_API_KEY') ?: null,
];
