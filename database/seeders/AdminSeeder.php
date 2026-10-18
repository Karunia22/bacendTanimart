<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@tanimart.test'],
            [
                'name' => 'Admin TaniMart',
                'password' => Hash::make('AdminTaniMart123!'),
                'role' => 'admin',
                'status' => 'aktif',
            ]
        );
    }
}
