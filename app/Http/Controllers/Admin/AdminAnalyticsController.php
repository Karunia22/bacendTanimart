<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminAnalyticsController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_shops' => Shop::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', '!=', 'dibatalkan')->sum('total_harga'),
        ];

        // Pendapatan 6 bulan terakhir (hanya pesanan yang tidak dibatalkan).
        $months = collect(range(5, 0))->map(function (int $i) {
            $date = Carbon::now()->subMonthsNoOverflow($i);

            return [
                'label' => $date->isoFormat('MMM YY'),
                'value' => (int) Order::where('status', '!=', 'dibatalkan')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_harga'),
            ];
        });

        $ordersByStatus = Order::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $topProducts = OrderItem::selectRaw('nama_produk, SUM(jumlah) as terjual, SUM(subtotal) as pendapatan')
            ->groupBy('nama_produk')
            ->orderByDesc('terjual')
            ->take(5)
            ->get();

        $recentUsers = User::latest()->take(8)->get();
        $recentOrders = Order::with('user:id,name')->latest()->take(8)->get();

        return view('admin.analytics.index', compact(
            'stats',
            'months',
            'ordersByStatus',
            'topProducts',
            'recentUsers',
            'recentOrders'
        ));
    }
}
