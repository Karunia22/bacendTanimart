<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SellerSalesController extends Controller
{
    /**
     * Ringkasan penjualan untuk penjual yang login.
     */
    public function summary(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;

        $itemQuery = OrderItem::query()
            ->whereHas('product', function ($query) use ($sellerId) {
                $query->where('seller_id', $sellerId);
            })
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'dibatalkan');
            });

        $totalPendapatan = (clone $itemQuery)->sum('subtotal');
        $totalProdukTerjual = (clone $itemQuery)->sum('jumlah');

        $orderQuery = Order::query()
            ->whereHas('items.product', function ($query) use ($sellerId) {
                $query->where('seller_id', $sellerId);
            });

        $totalPesanan = (clone $orderQuery)
            ->where('status', '!=', 'dibatalkan')
            ->count();

        $pesananPerStatus = (clone $orderQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return response()->json([
            'message' => 'Ringkasan penjualan toko berhasil diambil.',
            'summary' => [
                'total_pendapatan' => (float) $totalPendapatan,
                'total_pesanan' => $totalPesanan,
                'total_produk_terjual' => (int) $totalProdukTerjual,
                'pesanan_per_status' => $pesananPerStatus,
            ],
        ]);
    }

    /**
     * Data grafik penjualan (per hari/bulan).
     */
    public function chart(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;
        $period = $request->get('period', '30d');
        $to = Carbon::now();
        $from = match ($period) {
            '7d' => $to->copy()->subDays(7),
            '90d' => $to->copy()->subDays(90),
            '1y' => $to->copy()->subYear(),
            default => $to->copy()->subDays(30),
        };

        $rows = OrderItem::query()
            ->selectRaw('DATE(orders.created_at) as date, SUM(order_items.subtotal) as revenue, SUM(order_items.jumlah) as qty')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.status', '!=', 'dibatalkan')
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupByRaw('DATE(orders.created_at)')
            ->orderBy('date')
            ->get();

        $labels = [];
        $revenues = [];
        foreach ($rows as $r) {
            $labels[] = $r->date;
            $revenues[] = (float) $r->revenue;
        }

        $monthly = OrderItem::query()
            ->selectRaw('DATE_FORMAT(orders.created_at, "%Y-%m") as month, SUM(order_items.subtotal) as revenue, SUM(order_items.jumlah) as qty')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.status', '!=', 'dibatalkan')
            ->where('orders.created_at', '>=', $to->copy()->subMonths(12)->startOfMonth())
            ->groupByRaw('DATE_FORMAT(orders.created_at, "%Y-%m")')
            ->orderBy('month')
            ->get();

        $monthlyLabels = [];
        $monthlyRevenues = [];
        foreach ($monthly as $m) {
            $monthlyLabels[] = $m->month;
            $monthlyRevenues[] = (float) $m->revenue;
        }

        $yearly = OrderItem::query()
            ->selectRaw('YEAR(orders.created_at) as year, SUM(order_items.subtotal) as revenue, SUM(order_items.jumlah) as qty')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.status', '!=', 'dibatalkan')
            ->groupByRaw('YEAR(orders.created_at)')
            ->orderBy('year')
            ->get();

        $yearlyLabels = [];
        $yearlyRevenues = [];
        foreach ($yearly as $y) {
            $yearlyLabels[] = (string) $y->year;
            $yearlyRevenues[] = (float) $y->revenue;
        }

        return response()->json([
            'message' => 'Data grafik penjualan berhasil diambil.',
            'chart' => [
                'daily' => [
                    'labels' => $labels,
                    'revenues' => $revenues,
                ],
                'monthly' => [
                    'labels' => $monthlyLabels,
                    'revenues' => $monthlyRevenues,
                ],
                'yearly' => [
                    'labels' => $yearlyLabels,
                    'revenues' => $yearlyRevenues,
                ],
            ],
        ]);
    }

    public function report(Request $request)
    {
        $sellerId = $request->user()->id;
        $period = $request->get('period', 'monthly');
        $month = $request->get('month');
        $year = $request->get('year', date('Y'));

        $query = OrderItem::query()
            ->selectRaw('orders.nomor_order, orders.created_at, products.nama_produk, order_items.jumlah, order_items.harga, order_items.subtotal, orders.status')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.seller_id', $sellerId)
            ->where('orders.status', '!=', 'dibatalkan')
            ->orderBy('orders.created_at', 'desc');

        if ($period === 'monthly' && $month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereBetween('orders.created_at', [$date->startOfMonth(), $date->endOfMonth()]);
            $title = 'Laporan Penjualan Bulanan - ' . $date->format('F Y');
        } elseif ($period === 'yearly') {
            $query->whereYear('orders.created_at', $year);
            $title = 'Laporan Penjualan Tahunan - ' . $year;
        } else {
            $date = $month ? Carbon::createFromFormat('Y-m', $month) : Carbon::now();
            $query->whereBetween('orders.created_at', [$date->startOfMonth(), $date->endOfMonth()]);
            $title = 'Laporan Penjualan Bulanan - ' . $date->format('F Y');
        }

        $items = $query->get();
        $total = $items->sum('subtotal');

        $pdf = Pdf::loadView('reports.seller-sales', [
            'title' => $title,
            'items' => $items,
            'total' => $total,
            'generatedAt' => Carbon::now(),
        ]);

        return $pdf->download('laporan-penjualan-' . ($month ?? $year) . '.pdf');
    }
}
