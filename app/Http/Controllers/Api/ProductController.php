<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::where('status', 'aktif')
            ->with([
                'seller:id,name',
                'images',
            ])
            ->inRandomOrder()
            ->take(10)
            ->get();

        return response()->json([
            'message' => 'Daftar produk',
            'products' => $products,
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        if ($product->status !== 'aktif') {
            return response()->json([
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $product->load([
            'seller:id,name',
            'images',
        ]);

        return response()->json([
            'message' => 'Detail produk',
            'product' => $product,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_produk' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'harga' => ['required', 'numeric', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'berat' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],

            'images' => ['required', 'array', 'min:1'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $storedPaths = [];

        try {
            $product = DB::transaction(function () use (
                $request,
                $validated,
                &$storedPaths
            ) {
                $product = Product::create([
                    'seller_id' => $request->user()->id,
                    'nama_produk' => $validated['nama_produk'],
                    'deskripsi' => $validated['deskripsi'] ?? null,
                    'harga' => $validated['harga'],
                    'stok' => $validated['stok'],
                    'berat' => $validated['berat'],
                    'status' => $validated['status'] ?? 'aktif',
                ]);

                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store(
                        'products/' . $product->id,
                        'public'
                    );

                    if ($path === false) {
                        throw new \RuntimeException(
                            'Gagal menyimpan file gambar.'
                        );
                    }

                    $storedPaths[] = $path;

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }

                return $product;
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            report($exception);

            return response()->json([
                'message' => 'Gagal menyimpan produk dan gambarnya.',
            ], 500);
        }

        $product->load([
            'seller:id,name',
            'images',
        ]);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan.',
            'product' => $product,
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        if ($product->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke produk ini.',
            ], 403);
        }

        $validated = $request->validate([
            'nama_produk' => ['sometimes', 'string', 'max:255'],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'harga' => ['sometimes', 'numeric', 'min:0'],
            'stok' => ['sometimes', 'integer', 'min:0'],
            'berat' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Produk berhasil diperbarui.',
            'product' => $product->fresh(),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        if ($product->seller_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke produk ini.',
            ], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    public function myProducts(Request $request): JsonResponse
    {
        $products = Product::where('seller_id', $request->user()->id)
            ->with([
                'seller:id,name',
                'images',
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar produk saya',
            'products' => $products,
        ]);
    }
}
