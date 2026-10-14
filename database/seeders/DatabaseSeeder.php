<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrNew([
            'email' => env('ADMIN_EMAIL', 'admin@pro1.com'),
        ]);

        $admin->name = env('ADMIN_NAME', 'Administrator');
        $admin->type = UserType::Admin;

        if (! $admin->exists) {
            $admin->password = Hash::make(env('ADMIN_PASSWORD', '11111111'));
        }

        $admin->save();
    }
}
