<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrderController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => [
                'required',
                'integer',
                'exists:addresses,id',
            ],
            'cart_item_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'cart_item_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:cart_items,id',
            ],
        ]);

        $user = $request->user();

        $address = $user->addresses()
            ->where('id', $validated['address_id'])
            ->first();

        if (! $address) {
            return response()->json([
                'message' => 'Alamat tidak ditemukan.',
            ], 404);
        }

        $cart = $user->cart;

        if (! $cart) {
            return response()->json([
                'message' => 'Keranjang masih kosong.',
            ], 422);
        }
        $selectedCartItemIds = $validated['cart_item_ids'];
        $order = DB::transaction(function () use (
            $user,
            $address,
            $cart,
            $selectedCartItemIds
        ) {

            $lockedCart = $cart->newQuery()
                ->whereKey($cart->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedCart) {
                abort(422, 'Keranjang tidak ditemukan.');
            }

            // Kunci baris item keranjang selama checkout berlangsung.
            $cartItems = $lockedCart->items()
                ->whereIn('id', $selectedCartItemIds)
                ->lockForUpdate()
                ->get();
            if ($cartItems->isEmpty()) {
                abort(422, 'Keranjang masih kosong.');
            }

            $totalHarga = 0;
            $orderItems = [];

            // Kunci dan validasi setiap produk menggunakan stok terbaru.
            foreach ($cartItems as $item) {
                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product || $product->status !== 'aktif') {
                    abort(422, 'Salah satu produk tidak tersedia.');
                }

                if ($item->jumlah < 1) {
                    abort(422, 'Jumlah produk harus minimal 1.');
                }

                if ($item->jumlah > $product->stok) {
                    abort(
                        422,
                        "Stok {$product->nama_produk} tidak mencukupi."
                    );
                }

                $harga = (float) $product->harga;
                $subtotal = $harga * $item->jumlah;

                $totalHarga += $subtotal;

                $orderItems[] = [
                    'product' => $product,
                    'jumlah' => $item->jumlah,
                    'harga' => $harga,
                    'subtotal' => $subtotal,
                ];
            }

            $order = $user->orders()->create([
                'address_id' => $address->id,
                'nomor_order' => 'ORD-' . now()->format('YmdHis')
                    . '-' . random_int(1000, 9999),
                'total_harga' => $totalHarga,
                'status' => 'menunggu_pembayaran',
            ]);

            foreach ($orderItems as $orderItem) {
                $product = $orderItem['product'];
                $jumlah = $orderItem['jumlah'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'nama_produk' => $product->nama_produk,
                    'harga' => $orderItem['harga'],
                    'jumlah' => $jumlah,
                    'subtotal' => $orderItem['subtotal'],
                ]);

                // Kurangi stok dengan operasi database atomik.
                $product->decrement('stok', $jumlah);
            }

            // Hapus item keranjang hanya jika seluruh proses berhasil.
            $lockedCart->items()
                ->whereIn('id', $selectedCartItemIds)
                ->delete();

            return $order;
        }, 3);

        $order->load([
            'address',
            'items.product',
        ]);

        return response()->json([
            'message' => 'Checkout berhasil.',
            'order' => $order,
        ], 201);
    }

    public function buyNow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'address_id' => [
                'required',
                'integer',
                'exists:addresses,id',
            ],
        ]);

        $user = $request->user();

        $address = $user->addresses()
            ->where('id', $validated['address_id'])
            ->first();

        if (! $address) {
            return response()->json([
                'message' => 'Alamat tidak ditemukan.',
            ], 404);
        }

        $order = DB::transaction(function () use (
            $user,
            $address,
            $validated
        ) {
            // Kunci produk agar stok tidak berubah selama transaksi.
            $product = Product::query()
                ->whereKey($validated['product_id'])
                ->lockForUpdate()
                ->first();

            if (! $product || $product->status !== 'aktif') {
                abort(422, 'Produk tidak tersedia.');
            }

            $quantity = (int) $validated['quantity'];

            if ($quantity > $product->stok) {
                abort(
                    422,
                    "Stok {$product->nama_produk} tidak mencukupi."
                );
            }

            $harga = (float) $product->harga;
            $subtotal = $harga * $quantity;

            $order = $user->orders()->create([
                'address_id' => $address->id,
                'nomor_order' => 'ORD-' . now()->format('YmdHis')
                    . '-' . random_int(1000, 9999),
                'total_harga' => $subtotal,
                'status' => 'menunggu_pembayaran',
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'nama_produk' => $product->nama_produk,
                'harga' => $harga,
                'jumlah' => $quantity,
                'subtotal' => $subtotal,
            ]);

            // Pengurangan stok ikut dibatalkan jika transaksi gagal.
            $product->decrement('stok', $quantity);

            return $order;
        }, 3);

        $order->load([
            'address',
            'items.product',
        ]);

        return response()->json([
            'message' => 'Pesanan berhasil dibuat.',
            'order' => $order,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with([
                'address',
                'items.product',
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar pesanan',
            'orders' => $orders,
        ]);
    }

    public function show(
        Request $request,
        Order $order
    ): JsonResponse {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke pesanan ini.',
            ], 403);
        }

        $order->load([
            'address',
            'items.product',
        ]);

        return response()->json([
            'message' => 'Detail pesanan',
            'order' => $order,
        ]);
    }

    public function cancel(
        Request $request,
        Order $order
    ): JsonResponse {
        $result = DB::transaction(function () use ($request, $order) {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->user_id !== $request->user()->id) {
                return [
                    'message' => 'Anda tidak memiliki akses ke pesanan ini.',
                    'status' => 403,
                ];
            }

            if ($lockedOrder->status !== 'menunggu_pembayaran') {
                return [
                    'message' => 'Pesanan tidak dapat dibatalkan pada status ini.',
                    'status' => 422,
                ];
            }

            // Kunci pembayaran jika sudah dibuat.
            $payment = $lockedOrder->payment()
                ->lockForUpdate()
                ->first();

            if ($payment && $payment->status === 'berhasil') {
                return [
                    'message' => 'Pesanan yang sudah dibayar tidak dapat dibatalkan melalui fitur ini.',
                    'status' => 422,
                ];
            }

            // Kunci semua produk agar stok konsisten.
            $items = $lockedOrder->items()->get();

            foreach ($items as $item) {
                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($product) {
                    $product->increment('stok', $item->jumlah);
                }
            }

            $lockedOrder->update([
                'status' => 'dibatalkan',
            ]);

            if ($payment && $payment->status === 'menunggu') {
                $payment->update([
                    'status' => 'dibatalkan',
                ]);
            }

            return [
                'message' => 'Pesanan berhasil dibatalkan.',
                'status' => 200,
            ];
        }, 3);

        return response()->json([
            'message' => $result['message'],
        ], $result['status']);
    }

    /**
     * Seller melihat pesanan yang berisi produknya sendiri.
     */
    public function sellerOrders(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;

        $orders = Order::query()
            ->whereHas('items.product', function ($query) use ($sellerId) {
                $query->where('seller_id', $sellerId);
            })
            ->with([
                'user:id,name,email',
                'address',
                'items' => function ($query) use ($sellerId) {
                    $query->whereHas('product', function ($productQuery) use ($sellerId) {
                        $productQuery->where('seller_id', $sellerId);
                    });
                },
                'items.product',
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar pesanan toko berhasil diambil.',
            'orders' => $orders,
        ]);
    }
}
