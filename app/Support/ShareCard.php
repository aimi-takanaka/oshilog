<?php

namespace App\Support;

class ShareCard
{
    public const STYLE_SCRAPBOOK = 'scrapbook';

    public const STYLE_TICKET = 'ticket';

    public const BACKGROUND_CREAM = 'cream';

    public const BACKGROUND_DOTS = 'dots';

    public const BACKGROUND_STRIPE = 'stripe';

    public const SCRAPBOOK_MAX_PHOTOS = 4;

    /**
     * @var array<string, int>
     */
    public const IMPRESSION_LIMITS = [
        self::STYLE_SCRAPBOOK => 80,
        self::STYLE_TICKET => 160,
    ];

    /**
     * @var array<string, string>
     */
    public const BACKGROUNDS = [
        self::BACKGROUND_CREAM => 'クリーム',
        self::BACKGROUND_DOTS => 'ドット',
        self::BACKGROUND_STRIPE => 'ストライプ',
    ];

    public static function isValidStyle(string $style): bool
    {
        return array_key_exists($style, self::IMPRESSION_LIMITS);
    }

    public static function isValidBackground(string $background): bool
    {
        return array_key_exists($background, self::BACKGROUNDS);
    }

    public static function impressionLimit(string $style): int
    {
        return self::IMPRESSION_LIMITS[$style] ?? self::IMPRESSION_LIMITS[self::STYLE_SCRAPBOOK];
    }
}
