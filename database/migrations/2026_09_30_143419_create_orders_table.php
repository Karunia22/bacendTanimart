<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('address_id')
                ->constrained('addresses')
                ->restrictOnDelete();

            $table->string('nomor_order')->unique();

            $table->decimal('total_harga', 12, 2);

            $table->enum('status', [
                'menunggu_pembayaran',
                'diproses',
                'dikirim',
                'selesai',
                'dibatalkan',
            ])->default('menunggu_pembayaran');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
