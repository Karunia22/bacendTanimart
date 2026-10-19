<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use App\Services\SellerApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSellerApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $query = SellerApplication::with(['user:id,name,email', 'reviewer:id,name'])
            ->latest();

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status_pengajuan', $status);
        }

        $applications = $query->paginate(15)->withQueryString();

        return view('admin.seller-applications.index', compact('applications', 'status'));
    }

    public function approve(
        Request $request,
        SellerApplication $sellerApplication,
        SellerApplicationService $service
    ): RedirectResponse {
        try {
            $service->approve($sellerApplication, $request->user()->id);

            return back()->with('snackbar_success', 'Pengajuan disetujui dan toko berhasil dibuat.');
        } catch (\RuntimeException $e) {
            return back()->with('snackbar_error', $e->getMessage());
        }
    }

    public function reject(
        Request $request,
        SellerApplication $sellerApplication,
        SellerApplicationService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $service->reject(
                $sellerApplication,
                $request->user()->id,
                $validated['catatan_admin']
            );

            return back()->with('snackbar_success', 'Pengajuan toko ditolak.');
        } catch (\RuntimeException $e) {
            return back()->with('snackbar_error', $e->getMessage());
        }
    }
}
