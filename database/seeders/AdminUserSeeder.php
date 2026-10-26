<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@luattuliem.vn'],
            [
                'name' => 'Quản trị viên',
                'password' => 'Tuliem@2026',
                'is_admin' => true,
            ],
        );
    }
}
