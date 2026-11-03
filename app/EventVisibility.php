<?php

namespace App;

enum EventVisibility: string
{
    case Private = 'private';
    case Shared = 'shared';

    public function label(): string
    {
        return match ($this) {
            self::Private => '非公開',
            self::Shared => '共有',
        };
    }
}
