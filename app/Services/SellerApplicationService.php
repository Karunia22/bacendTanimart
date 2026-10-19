<?php

namespace App\Services;

use App\Models\Role;
use App\Models\SellerApplication;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Logika persetujuan/penolakan pengajuan penjual.
 *
 * Dipakai BERSAMA oleh:
 *  - API  : App\Http\Controllers\Api\SellerApplicationController (token Sanctum)
 *  - Web  : App\Http\Controllers\Admin\AdminSellerApplicationController (sesi admin)
 *
 * Jangan menaruh logika ini di controller mana pun — ubah di sini saja.
 * Lihat README.md bagian 6.
 */
class SellerApplicationService
{
    /**
     * Menyetujui pengajuan: buat toko + attach role seller.
     * Berjalan dalam transaksi + lockForUpdate. Melempar RuntimeException
     * bila pengajuan sudah ditinjau atau pengguna sudah punya toko.
     *
     * @return Shop toko yang dibuat
     */
    public function approve(SellerApplication $application, int $adminId): Shop
    {
        return DB::transaction(function () use ($application, $adminId) {
            $fresh = SellerApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($fresh->status_pengajuan !== 'pending') {
                throw new RuntimeException('Pengajuan ini sudah ditinjau.');
            }

            $user = $fresh->user;

            if ($user->shop()->exists()) {
                throw new RuntimeException('Pengguna ini sudah memiliki toko.');
            }

            $shop = Shop::create([
                'user_id' => $user->id,
                'nama_toko' => $fresh->nama_toko,
                'slug' => $this->uniqueSlug($fresh->nama_toko),
                'deskripsi' => $fresh->deskripsi_toko,
                'alamat_usaha' => $fresh->alamat_usaha,
                'no_telepon' => $fresh->no_telepon,
                'status' => 'aktif',
            ]);

            $fresh->update([
                'status_pengajuan' => 'approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'catatan_admin' => null,
            ]);

            $sellerRole = Role::where('name', 'seller')->first();

            if ($sellerRole && ! $user->hasRole('seller')) {
                $user->roles()->attach($sellerRole->id);
            }

            return $shop;
        });
    }

    /**
     * Menolak pengajuan beserta catatan admin (wajib diisi pemanggil).
     */
    public function reject(SellerApplication $application, int $adminId, string $catatan): void
    {
        DB::transaction(function () use ($application, $adminId, $catatan) {
            $fresh = SellerApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($fresh->status_pengajuan !== 'pending') {
                throw new RuntimeException('Pengajuan ini sudah ditinjau.');
            }

            $fresh->update([
                'status_pengajuan' => 'rejected',
                'catatan_admin' => $catatan,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);
        });
    }

    private function uniqueSlug(string $namaToko): string
    {
        $base = Str::slug($namaToko);
        if ($base === '') {
            $base = 'toko';
        }

        $slug = $base;
        $suffix = 2;

        while (Shop::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
