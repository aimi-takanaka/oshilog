<?php

namespace App;

enum SpotCategory: string
{
    case Cafe = 'cafe';
    case Shop = 'shop';
    case Sightseeing = 'sightseeing';

    public function label(): string
    {
        return match ($this) {
            self::Cafe => 'カフェ',
            self::Shop => 'お店',
            self::Sightseeing => '観光地',
        };
    }
}
