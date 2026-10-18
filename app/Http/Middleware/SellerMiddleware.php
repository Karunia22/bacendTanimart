<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('seller')) {
            return response()->json([
                'message' => 'Akses hanya untuk seller.',
            ], 403);
        }

        $shop = $user->shop;

        if (! $shop || $shop->status !== 'aktif') {
            return response()->json([
                'message' => 'Anda belum memiliki toko yang aktif.',
            ], 403);
        }

        return $next($request);
    }
}
