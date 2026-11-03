<?php

namespace App\Support;

use App\Models\Venue;

class PresetVenues
{
    /**
     * Well-known Japanese venues with coordinates for the map.
     *
     * @return list<array{name: string, address: string, latitude: float, longitude: float, nearest_station: string, aliases?: list<string>}>
     */
    public static function all(): array
    {
        return [
            [
                'name' => '東京ドーム',
                'address' => '東京都文京区後楽1-3-61',
                'latitude' => 35.7056396,
                'longitude' => 139.7518903,
                'nearest_station' => '水道橋駅',
            ],
            [
                'name' => 'Kanadevia Hall',
                'aliases' => ['カナデビアホール'],
                'address' => '東京都文京区後楽1-3-61 ミーツポート1F',
                'latitude' => 35.7044000,
                'longitude' => 139.7536000,
                'nearest_station' => '水道橋駅',
            ],
            [
                'name' => '日本武道館',
                'address' => '東京都千代田区北の丸公園2-3',
                'latitude' => 35.6938397,
                'longitude' => 139.7499097,
                'nearest_station' => '九段下駅',
            ],
            [
                'name' => '横浜アリーナ',
                'address' => '神奈川県横浜市港北区新横浜3-10',
                'latitude' => 35.5122725,
                'longitude' => 139.6201921,
                'nearest_station' => '新横浜駅',
            ],
            [
                'name' => 'さいたまスーパーアリーナ',
                'address' => '埼玉県さいたま市中央区新都心8',
                'latitude' => 35.8949700,
                'longitude' => 139.6308300,
                'nearest_station' => 'さいたま新都心駅',
            ],
            [
                'name' => '有明アリーナ',
                'address' => '東京都江東区有明1-2-35',
                'latitude' => 35.6436100,
                'longitude' => 139.7938900,
                'nearest_station' => '有明駅',
            ],
            [
                'name' => '国立代々木競技場第一体育館',
                'address' => '東京都渋谷区神南2-1-1',
                'latitude' => 35.6675000,
                'longitude' => 139.7000000,
                'nearest_station' => '原宿駅',
            ],
            [
                'name' => '東京ガーデンシアター',
                'address' => '東京都江東区有明2-1-7',
                'latitude' => 35.6356000,
                'longitude' => 139.7872000,
                'nearest_station' => '有明駅',
            ],
            [
                'name' => '大阪城ホール',
                'address' => '大阪府大阪市中央区大阪城3-1',
                'latitude' => 34.6873000,
                'longitude' => 135.5270000,
                'nearest_station' => '大阪城公園駅',
            ],
            [
                'name' => '京セラドーム大阪',
                'address' => '大阪府大阪市西区千代崎3-中2-1',
                'latitude' => 34.6693000,
                'longitude' => 135.4761000,
                'nearest_station' => 'ドーム前駅',
            ],
            [
                'name' => '帝国劇場',
                'address' => '東京都千代田区丸の内3-1-1',
                'latitude' => 35.6764000,
                'longitude' => 139.7608000,
                'nearest_station' => '日比谷駅',
            ],
            [
                'name' => '明治座',
                'address' => '東京都中央区日本橋浜町2-31-1',
                'latitude' => 35.6880100,
                'longitude' => 139.7869870,
                'nearest_station' => '浜町駅',
            ],
        ];
    }

    /**
     * @param  array{name: string, aliases?: list<string>}  $preset
     */
    public static function displayName(array $preset): string
    {
        $aliases = $preset['aliases'] ?? [];

        if ($aliases === []) {
            return $preset['name'];
        }

        return $preset['name'].'（'.implode(' / ', $aliases).'）';
    }

    /**
     * @return array{name: string, address: string, latitude: float, longitude: float, nearest_station: string, aliases?: list<string>}|null
     */
    public static function findByName(string $name): ?array
    {
        $needle = self::normalize($name);

        if ($needle === '') {
            return null;
        }

        foreach (self::all() as $venue) {
            if (self::matches($venue, $name)) {
                return $venue;
            }
        }

        return null;
    }

    /**
     * @param  array{name: string, aliases?: list<string>}  $preset
     */
    public static function matches(array $preset, string $name): bool
    {
        $needle = self::normalize($name);

        if ($needle === '') {
            return false;
        }

        foreach (self::namesOf($preset) as $candidate) {
            if (self::normalize($candidate) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Presets the user has not added to their venue list yet.
     *
     * @return list<array{name: string, address: string, latitude: float, longitude: float, nearest_station: string, aliases?: list<string>}>
     */
    public static function availableForUser(int $userId): array
    {
        $existing = Venue::query()
            ->where('user_id', $userId)
            ->pluck('name')
            ->map(fn (string $name): string => self::normalize($name))
            ->all();

        return array_values(array_filter(
            self::all(),
            function (array $preset) use ($existing): bool {
                foreach (self::namesOf($preset) as $name) {
                    if (in_array(self::normalize($name), $existing, true)) {
                        return false;
                    }
                }

                return true;
            },
        ));
    }

    public static function addForUser(int $userId, string $name): bool
    {
        $preset = self::findByName($name);

        if ($preset === null) {
            return false;
        }

        $alreadyAdded = Venue::query()
            ->where('user_id', $userId)
            ->get()
            ->contains(fn (Venue $venue): bool => self::matches($preset, $venue->name));

        if ($alreadyAdded) {
            return false;
        }

        Venue::query()->create([
            'user_id' => $userId,
            'name' => $preset['name'],
            'address' => $preset['address'],
            'latitude' => $preset['latitude'],
            'longitude' => $preset['longitude'],
            'nearest_station' => $preset['nearest_station'],
        ]);

        return true;
    }

    /**
     * @param  list<string>  $names
     */
    public static function addManyForUser(int $userId, array $names): int
    {
        $created = 0;

        foreach (array_unique($names) as $name) {
            if (self::addForUser($userId, $name)) {
                $created++;
            }
        }

        return $created;
    }

    public static function seedForUser(int $userId): int
    {
        return self::addManyForUser(
            $userId,
            array_column(self::all(), 'name'),
        );
    }

    /**
     * @param  array{name: string, aliases?: list<string>}  $preset
     * @return list<string>
     */
    private static function namesOf(array $preset): array
    {
        return array_values(array_filter([
            $preset['name'],
            ...($preset['aliases'] ?? []),
        ]));
    }

    private static function normalize(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '', trim($value)) ?? '');
    }
}
