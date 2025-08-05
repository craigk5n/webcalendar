<?php

namespace App\Helpers;

use function preg_replace;

class DateDisplay
{
    /**
     * Remove :00 from times based on DISPLAY_MINUTES config value.
     *
     * @param string $timeString  time value to shorten
     *
     */
    public static function getShortTime(string $timeString): string
    {
        return (ConfigService::get('DISPLAY_MINUTES')) ? $timeString
            : preg_replace('/(:00)/', '', $timeString);
    }
}