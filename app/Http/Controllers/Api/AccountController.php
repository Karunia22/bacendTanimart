<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    /**
     * Menampilkan informasi akun pengguna yang login.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $primaryAddress = $user->addresses()
            ->where('is_primary', true)
            ->first();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->role,
            ],
            'primary_address' => $primaryAddress ? [
                'id' => $primaryAddress->id,
                'nama_penerima' => $primaryAddress->nama_penerima,
                'no_telepon' => $primaryAddress->no_telepon,
                'alamat' => $primaryAddress->alamat,
                'kota' => $primaryAddress->kota,
                'provinsi' => $primaryAddress->provinsi,
                'kode_pos' => $primaryAddress->kode_pos,
                'is_primary' => $primaryAddress->is_primary,
            ] : null,
        ]);
    }

    /**
     * Mengubah nama pengguna.
     */
    public function updateName(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->name = $validated['name'];
        $user->save();

        return response()->json([
            'message' => 'Nama pengguna berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Mengubah kata sandi.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8),
            ],
        ]);

        $user = $request->user();
        $user->password = $validated['password'];
        $user->save();

        return response()->json([
            'message' => 'Kata sandi berhasil diperbarui.',
        ]);
    }
}
