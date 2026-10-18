<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar alamat',
            'addresses' => $addresses,
        ]);
    }

    public function show(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke alamat ini.',
            ], 403);
        }

        return response()->json([
            'message' => 'Detail alamat',
            'address' => $address,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_penerima' => ['required', 'string', 'max:255'],
            'no_telepon' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kode_pos' => ['required', 'string', 'max:10'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        $isPrimary = $validated['is_primary'] ?? false;

        // Jika ini alamat pertama, otomatis menjadi alamat utama.
        if (! $user->addresses()->exists()) {
            $isPrimary = true;
        }

        DB::transaction(function () use ($user, $validated, $isPrimary, &$address) {
            if ($isPrimary) {
                $user->addresses()->update([
                    'is_primary' => false,
                ]);
            }

            $address = $user->addresses()->create([
                'nama_penerima' => $validated['nama_penerima'],
                'no_telepon' => $validated['no_telepon'],
                'alamat' => $validated['alamat'],
                'kota' => $validated['kota'],
                'provinsi' => $validated['provinsi'],
                'kode_pos' => $validated['kode_pos'],
                'is_primary' => $isPrimary,
            ]);
        });

        return response()->json([
            'message' => 'Alamat berhasil ditambahkan.',
            'address' => $address,
        ], 201);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke alamat ini.',
            ], 403);
        }

        $validated = $request->validate([
            'nama_penerima' => ['sometimes', 'string', 'max:255'],
            'no_telepon' => ['sometimes', 'string', 'max:20'],
            'alamat' => ['sometimes', 'string'],
            'kota' => ['sometimes', 'string', 'max:100'],
            'provinsi' => ['sometimes', 'string', 'max:100'],
            'kode_pos' => ['sometimes', 'string', 'max:10'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $address, $validated) {
            if (($validated['is_primary'] ?? false) === true) {
                $request->user()
                    ->addresses()
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            $address->update($validated);
        });

        return response()->json([
            'message' => 'Alamat berhasil diperbarui.',
            'address' => $address->fresh(),
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke alamat ini.',
            ], 403);
        }

        $wasPrimary = $address->is_primary;

        $address->delete();

        // Jika alamat utama dihapus, jadikan alamat terbaru sebagai utama.
        if ($wasPrimary) {
            $newPrimary = $request->user()
                ->addresses()
                ->latest('id')
                ->first();

            if ($newPrimary) {
                $newPrimary->update([
                    'is_primary' => true,
                ]);
            }
        }

        return response()->json([
            'message' => 'Alamat berhasil dihapus.',
        ]);
    }

    public function setPrimary(
        Request $request,
        Address $address
    ): JsonResponse {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke alamat ini.',
            ], 403);
        }

        DB::transaction(function () use ($request, $address) {
            $request->user()
                ->addresses()
                ->update([
                    'is_primary' => false,
                ]);

            $address->update([
                'is_primary' => true,
            ]);
        });

        return response()->json([
            'message' => 'Alamat utama berhasil diubah.',
            'address' => $address->fresh(),
        ]);
    }
}
