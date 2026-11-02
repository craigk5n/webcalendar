<?php

declare(strict_types=1);

return [
  'default' => 'file',
  'stores' => ['file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')]],
  'prefix' => 'webcalendar_api',
];
