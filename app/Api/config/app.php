<?php

declare(strict_types=1);

return [
  'name' => 'WebCalendar API',
  'env' => 'production',
  'debug' => false,
  'timezone' => 'UTC',
  'key' => app(WebCalendar\Api\Environment::class)->get('WEBCALENDAR_API_KEY'),
];
