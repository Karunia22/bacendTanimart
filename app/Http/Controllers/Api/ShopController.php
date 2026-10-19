<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ShopController extends Controller
{
    /**
     * Menampilkan data toko milik penjual yang login.
     */
    public function show(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return response()->json([
                'message' => 'Toko tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Data toko berhasil diambil.',
            'shop' => $shop,
        ]);
    }

    /**
     * Memperbarui data toko milik penjual yang login.
     */
    public function update(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        if (! $shop) {
            return response()->json([
                'message' => 'Toko tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'nama_toko' => ['sometimes', 'string', 'max:255'],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'alamat_usaha' => ['sometimes', 'string'],
            'no_telepon' => ['sometimes', 'string', 'max:255'],
        ]);

        $shop->update($validated);

        return response()->json([
            'message' => 'Data toko berhasil diperbarui.',
            'shop' => $shop->fresh(),
        ]);
    }
}
