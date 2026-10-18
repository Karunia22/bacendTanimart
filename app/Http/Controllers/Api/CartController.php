<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cart = $request->user()->cart;

        if (! $cart) {
            return response()->json([
                'message' => 'Keranjang masih kosong.',
                'cart' => null,
            ]);
        }

        $cart->load([
            'items.product.images',
        ]);

        return response()->json([
            'message' => 'Keranjang belanja',
            'cart' => $cart,
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::find($validated['product_id']);

        if ($product->status !== 'aktif') {
            return response()->json([
                'message' => 'Produk tidak tersedia.',
            ], 404);
        }

        if ($product->stok < $validated['jumlah']) {
            return response()->json([
                'message' => 'Jumlah produk melebihi stok yang tersedia.',
                'stok_tersedia' => $product->stok,
            ], 422);
        }

        $user = $request->user();

        $cart = $user->cart;

        if (! $cart) {
            $cart = $user->cart()->create();
        }

        $cartItem = $cart->items()
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            $jumlahBaru = $cartItem->jumlah + $validated['jumlah'];

            if ($jumlahBaru > $product->stok) {
                return response()->json([
                    'message' => 'Jumlah produk di keranjang melebihi stok yang tersedia.',
                    'stok_tersedia' => $product->stok,
                    'jumlah_di_keranjang' => $cartItem->jumlah,
                ], 422);
            }

            $cartItem->update([
                'jumlah' => $jumlahBaru,
            ]);
        } else {
            $cartItem = $cart->items()->create([
                'product_id' => $product->id,
                'jumlah' => $validated['jumlah'],
            ]);
        }

        $cartItem->load('product.images');

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke keranjang.',
            'item' => $cartItem,
        ], 201);
    }

    public function updateItem(
        Request $request,
        CartItem $cartItem
    ): JsonResponse {
        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke item ini.',
            ], 403);
        }

        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $product = $cartItem->product;

        if ($validated['jumlah'] > $product->stok) {
            return response()->json([
                'message' => 'Jumlah melebihi stok yang tersedia.',
                'stok_tersedia' => $product->stok,
            ], 422);
        }

        $cartItem->update([
            'jumlah' => $validated['jumlah'],
        ]);

        return response()->json([
            'message' => 'Jumlah produk berhasil diperbarui.',
            'item' => $cartItem->fresh()->load('product.images'),
        ]);
    }

    public function destroyItem(
        Request $request,
        CartItem $cartItem
    ): JsonResponse {
        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke item ini.',
            ], 403);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus dari keranjang.',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $request->user()->cart;

        if (! $cart) {
            return response()->json([
                'message' => 'Keranjang sudah kosong.',
            ]);
        }

        $cart->items()->delete();

        return response()->json([
            'message' => 'Keranjang berhasil dikosongkan.',
        ]);
    }
}
