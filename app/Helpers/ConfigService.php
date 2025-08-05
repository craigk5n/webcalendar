<?php

namespace App\Helpers;

use function dbi_execute;
use function dbi_fetch_row;

class ConfigService
{
    private const DEFAULT_DISPLAY_MINUTES = false;
    private const YES = 'Y';

    public static function get(string $key): ?string
    {
        $res = dbi_execute( <<<SQL
SELECT  cal_value FROM webcal_config
WHERE   cal_setting = ?
SQL, [$key], false, false );
        if ($res) {
            $row = dbi_fetch_row($res);
            return $row[0] == self::YES;
        } else {
            return match($key) {
                'DISPLAY_MINUTES' => self::DEFAULT_DISPLAY_MINUTES,
                default => null,
            };
        }
    }
}