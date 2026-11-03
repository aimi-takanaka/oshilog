<?php

namespace App;

enum EventTimeOfDay: string
{
    case Day = 'day';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Day => '昼',
            self::Night => '夜',
        };
    }
}
