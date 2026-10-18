<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        $now = now();

        DB::table('roles')->insert([
            ['name' => 'admin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'customer', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'seller', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $roles = DB::table('roles')->pluck('id', 'name');

        foreach (DB::table('users')->select('id', 'role')->get() as $user) {
            $roleNames = match ($user->role) {
                'admin' => ['admin'],
                'seller' => ['customer', 'seller'],
                default => ['customer'],
            };

            foreach ($roleNames as $roleName) {
                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roles[$roleName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
    }
};
