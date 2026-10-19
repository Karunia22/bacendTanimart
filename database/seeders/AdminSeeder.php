<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@tanimart.test'],
            [
                'name' => 'Admin TaniMart',
                'password' => Hash::make('AdminTaniMart123!'),
                'role' => 'admin',
                'status' => 'aktif',
            ]
        );

        // Pastikan role admin tertaut di pivot user_roles.
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole && ! $admin->hasRole('admin')) {
            $admin->roles()->attach($adminRole->id);
        }
    }
}
