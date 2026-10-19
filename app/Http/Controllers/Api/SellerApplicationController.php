<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use App\Services\SellerApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        SellerApplication $sellerApplication,
        SellerApplicationService $service
    ): JsonResponse {
        if (! $request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        try {
            $shop = $service->approve($sellerApplication, $request->user()->id);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

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
        SellerApplication $sellerApplication,
        SellerApplicationService $service
    ): JsonResponse {
        if (! $request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Akses hanya untuk admin.',
            ], 403);
        }

        $validated = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $service->reject(
                $sellerApplication,
                $request->user()->id,
                $validated['catatan_admin']
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Pengajuan toko ditolak.',
            'data' => $sellerApplication->fresh(),
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
