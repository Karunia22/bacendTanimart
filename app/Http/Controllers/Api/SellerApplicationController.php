<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SellerApplication;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SellerApplicationController extends Controller
{
    /**
     * Pengguna mengajukan pembuatan toko.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nama_toko' => ['required', 'string', 'max:255'],
            'deskripsi_toko' => ['nullable', 'string'],
            'alamat_usaha' => ['required', 'string'],
            'no_telepon' => ['required', 'string', 'max:255'],
        ]);

        // Akun admin tidak boleh mengajukan toko.
        if ($user->hasRole('admin')) {
            return response()->json([
                'message' => 'Akun admin tidak dapat mengajukan toko.',
            ], 403);
        }

        // Pastikan pengguna belum memiliki toko.
        if ($user->shop()->exists()) {
            return response()->json([
                'message' => 'Akun ini sudah memiliki toko.',
            ], 422);
        }

        // Cegah pengajuan baru jika masih ada yang menunggu.
        $pendingExists = $user->sellerApplications()
            ->where('status_pengajuan', 'pending')
            ->exists();

        if ($pendingExists) {
            return response()->json([
                'message' => 'Pengajuan toko Anda masih menunggu peninjauan.',
            ], 422);
        }

        $application = $user->sellerApplications()->create([
            ...$validated,
            'status_pengajuan' => 'pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan toko berhasil dikirim.',
            'data' => $application,
        ], 201);
    }

    /**
     * Admin melihat seluruh pengajuan toko.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $query = SellerApplication::with([
            'user:id,name,email',
            'reviewer:id,name',
        ])->latest();

        if ($request->filled('status_pengajuan')) {
            $validated = $request->validate([
                'status_pengajuan' => [
                    'sometimes',
                    Rule::in(['pending', 'approved', 'rejected']),
                ],
            ]);

            $query->where(
                'status_pengajuan',
                $validated['status_pengajuan']
            );
        }

        return response()->json([
            'data' => $query->paginate(15),
        ]);
    }

    /**
     * Admin menyetujui pengajuan dan membuat toko.
     */
    public function approve(
        Request $request,
        SellerApplication $sellerApplication
    ): JsonResponse {
        if (! $request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $shop = DB::transaction(function () use (
            $request,
            $sellerApplication
        ) {
            $application = SellerApplication::query()
                ->lockForUpdate()
                ->findOrFail($sellerApplication->id);

            if ($application->status_pengajuan !== 'pending') {
                abort(422, 'Pengajuan ini sudah ditinjau.');
            }

            $user = $application->user;

            if ($user->shop()->exists()) {
                abort(422, 'Pengguna ini sudah memiliki toko.');
            }

            // Membuat slug toko yang unik.
            $baseSlug = Str::slug($application->nama_toko);

            if ($baseSlug === '') {
                $baseSlug = 'toko';
            }

            $slug = $baseSlug;
            $suffix = 2;

            while (Shop::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            // Membuat toko.
            $shop = Shop::create([
                'user_id' => $user->id,
                'nama_toko' => $application->nama_toko,
                'slug' => $slug,
                'deskripsi' => $application->deskripsi_toko,
                'alamat_usaha' => $application->alamat_usaha,
                'no_telepon' => $application->no_telepon,
                'status' => 'aktif',
            ]);

            // Memperbarui status pengajuan.
            $application->update([
                'status_pengajuan' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'catatan_admin' => null,
            ]);

            // Menambahkan role seller tanpa menghapus role customer.
            $sellerRole = Role::where('name', 'seller')
                ->firstOrFail();

            if (! $user->hasRole('seller')) {
                $user->roles()->attach($sellerRole->id);
            }

            return $shop;
        });

        return response()->json([
            'message' => 'Pengajuan disetujui dan toko berhasil dibuat.',
            'data' => $shop,
        ]);
    }

    /**
     * Admin menolak pengajuan toko.
     */
    public function reject(
        Request $request,
        SellerApplication $sellerApplication
    ): JsonResponse {
        if (! $request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $validated = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:2000'],
        ]);

        $application = DB::transaction(function () use (
            $request,
            $sellerApplication,
            $validated
        ) {
            $application = SellerApplication::query()
                ->lockForUpdate()
                ->findOrFail($sellerApplication->id);

            if ($application->status_pengajuan !== 'pending') {
                abort(422, 'Pengajuan ini sudah ditinjau.');
            }

            $application->update([
                'status_pengajuan' => 'rejected',
                'catatan_admin' => $validated['catatan_admin'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            return $application;
        });

        return response()->json([
            'message' => 'Pengajuan toko ditolak.',
            'data' => $application,
        ]);
    }

    /**
     * Pengguna melihat riwayat pengajuan tokonya.
     */
    public function mine(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()
                ->sellerApplications()
                ->latest()
                ->get(),
        ]);
    }
}
