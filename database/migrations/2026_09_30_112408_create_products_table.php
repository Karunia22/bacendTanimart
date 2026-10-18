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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seller_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('nama_produk');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 12, 2);
            $table->unsignedInteger('stok')->default(0);
            $table->unsignedInteger('berat');
            $table->enum('status', [
                'aktif',
                'nonaktif',
            ])->default('aktif');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
