<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('nama_toko');
            $table->text('deskripsi_toko')->nullable();
            $table->text('alamat_usaha');
            $table->string('no_telepon');

            $table->enum('status_pengajuan', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->text('catatan_admin')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'status_pengajuan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_applications');
    }
};
