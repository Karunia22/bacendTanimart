<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse
    {
        if ($product->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke produk ini.',
            ], 403);
        }

        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $path = $request->file('image')->store('products', 'public');

        $isPrimary = ! $product->images()->exists();

        $productImage = ProductImage::create([
            'product_id' => $product->id,
            'image' => $path,
            'is_primary' => $isPrimary,
        ]);

        return response()->json([
            'message' => 'Gambar produk berhasil ditambahkan.',
            'image' => $productImage,
        ], 201);
    }

    public function destroy(
        Request $request,
        Product $product,
        ProductImage $image
    ): JsonResponse {
        if ($product->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke produk ini.',
            ], 403);
        }

        if ($image->product_id !== $product->id) {
            return response()->json([
                'message' => 'Gambar tidak ditemukan pada produk ini.',
            ], 404);
        }

        DB::transaction(function () use ($product, $image) {
            $wasPrimary = $image->is_primary;

            $image->delete();

            if ($wasPrimary) {
                $newPrimary = $product->images()
                    ->oldest('id')
                    ->first();

                if ($newPrimary) {
                    $newPrimary->update([
                        'is_primary' => true,
                    ]);
                }
            }
        });

        Storage::disk('public')->delete($image->image);

        return response()->json([
            'message' => 'Gambar produk berhasil dihapus.',
        ]);
    }

    public function setPrimary(
        Request $request,
        Product $product,
        ProductImage $image
    ): JsonResponse {
        if ($product->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke produk ini.',
            ], 403);
        }

        if ($image->product_id !== $product->id) {
            return response()->json([
                'message' => 'Gambar tidak ditemukan pada produk ini.',
            ], 404);
        }

        DB::transaction(function () use ($product, $image) {
            $product->images()->update([
                'is_primary' => false,
            ]);

            $image->update([
                'is_primary' => true,
            ]);
        });

        return response()->json([
            'message' => 'Gambar utama berhasil diubah.',
            'image' => $image->fresh(),
        ]);
    }
}
