<?php

namespace App;

class WeatherCode
{
    /**
     * Convert an Open-Meteo WMO weather code to a Japanese label.
     */
    public static function label(int $code): string
    {
        return match (true) {
            $code === 0 => '晴れ',
            in_array($code, [1, 2, 3], true) => 'くもり',
            in_array($code, [45, 48], true) => '霧',
            in_array($code, [51, 53, 55, 56, 57, 66, 67], true) => '霧雨',
            in_array($code, [61, 63, 65, 80, 81, 82], true) => '雨',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => '雪',
            in_array($code, [95, 96, 99], true) => '雷雨',
            default => '不明',
        };
    }
}
