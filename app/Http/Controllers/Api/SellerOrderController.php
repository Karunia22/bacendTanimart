<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerOrderController extends Controller
{
    public function updateStatus(
        Request $request,
        int $orderId
    ): JsonResponse {
        $seller = $request->user();

        // Hanya seller yang boleh mengubah status.
        if (! $seller || $seller->role !== 'seller') {
            return response()->json([
                'message' => 'Hanya seller yang dapat mengubah status pesanan.',
            ], 403);
        }

        // Cari pesanan yang memiliki produk milik seller.
        $order = Order::whereHas(
            'items.product',
            function ($query) use ($seller) {
                $query->where('seller_id', $seller->id);
            }
        )->findOrFail($orderId);

        // Hanya boleh mengubah diproses menjadi dikirim.
        if ($order->status !== 'diproses') {
            return response()->json([
                'message' => 'Hanya pesanan berstatus diproses yang dapat dikirim.',
            ], 422);
        }

        $order->status = 'dikirim';
        $order->save();

        return response()->json([
            'message' => 'Status pesanan berhasil diubah menjadi dikirim.',
            'order' => $order->fresh(),
        ]);
    }
}
