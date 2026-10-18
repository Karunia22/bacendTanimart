<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'nama_toko',
    'slug',
    'deskripsi',
    'logo',
    'banner',
    'alamat_usaha',
    'no_telepon',
    'status',
])]
class Shop extends Model
{
    /**
     * Relasi ke pemilik toko.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke produk milik pemilik toko.
     *
     * products.seller_id mengacu ke users.id.
     */
    public function products(): HasMany
    {
        return $this->hasMany(
            Product::class,
            'seller_id',
            'user_id'
        );
    }
}
