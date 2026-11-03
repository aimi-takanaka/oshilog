<?php

namespace App;

enum SpotStatus: string
{
    case Visited = 'visited';
    case Wishlist = 'wishlist';

    public function label(): string
    {
        return match ($this) {
            self::Visited => '行った',
            self::Wishlist => '行きたい',
        };
    }
}
