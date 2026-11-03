<?php

namespace App;

enum PhotoCategory: string
{
    public const MAX_PHOTOS = 4;

    case Outfit = 'outfit';
    case Nail = 'nail';
    case Uchiwa = 'uchiwa';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Outfit => '服装',
            self::Nail => 'ネイル',
            self::Uchiwa => 'うちわ',
            self::Other => 'その他',
        };
    }
}
