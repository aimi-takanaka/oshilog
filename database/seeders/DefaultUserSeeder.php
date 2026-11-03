<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultUserSeeder extends Seeder
{
    /**
     * Ensure the phase-1 default user exists without demo event data.
     */
    public function run(): void
    {
        $userId = config('oshilog.default_user_id');

        User::query()->updateOrCreate(
            ['id' => $userId],
            [
                'name' => '推し活ユーザー',
                'email' => 'oshi@example.com',
                'email_verified_at' => now(),
                'password' => Hash::make(Str::password(32)),
            ],
        );
    }
}
