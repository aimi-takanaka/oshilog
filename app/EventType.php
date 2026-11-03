<?php

namespace App;

enum EventType: string
{
    case Live = 'live';
    case Stage = 'stage';
    case Musical = 'musical';
    case Event = 'event';
    case FilmingLocation = 'filming_location';

    public function label(): string
    {
        return match ($this) {
            self::Live => 'ライブ',
            self::Stage => '舞台',
            self::Musical => 'ミュージカル',
            self::Event => 'イベント',
            self::FilmingLocation => 'ロケ地',
        };
    }
}
