<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerApplication;
use App\Models\Shop;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $stats = [
            'users' => User::count(),
            'shops' => Shop::count(),
            'products' => Product::count(),
            'orders' => Order::count(),
            'pending_applications' => SellerApplication::where('status_pengajuan', 'pending')->count(),
            'revenue' => Order::where('status', '!=', 'dibatalkan')->sum('total_harga'),
        ];

        $recentOrders = Order::with('user:id,name')->latest()->take(5)->get();
        $pendingApplications = SellerApplication::with('user:id,name,email')
            ->where('status_pengajuan', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard.index', compact(
            'stats',
            'recentOrders',
            'pendingApplications'
        ));
    }
}
