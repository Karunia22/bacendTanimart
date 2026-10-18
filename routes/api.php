<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\SellerApplicationController;
use App\Http\Controllers\Api\SellerOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('seller')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
        Route::delete(
            '/products/{product}/images/{image}',
            [ProductImageController::class, 'destroy']
        );
        Route::patch(
            '/products/{product}/images/{image}/primary',
            [ProductImageController::class, 'setPrimary']
        );
        Route::get('/seller/products', [ProductController::class, 'myProducts']);
        Route::post('/seller/products', [ProductController::class, 'store']);
        Route::get('/seller/orders', [
            OrderController::class,
            'sellerOrders',
        ]);
        Route::patch(
            '/seller/orders/{orderId}/status',
            [SellerOrderController::class, 'updateStatus']
        );
    });

    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses/{address}', [AddressController::class, 'show']);
    Route::patch('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{address}/primary', [AddressController::class, 'setPrimary']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroyItem']);
    Route::delete('/cart', [CartController::class, 'clear']);

    Route::post('/orders/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/buy-now', [OrderController::class, 'buyNow']);

    Route::post('/orders/{order}/payment', [PaymentController::class, 'store']);
    Route::get('/orders/{order}/payment', [PaymentController::class, 'show']);
    Route::post('/orders/{order}/payment/simulate-success', [PaymentController::class, 'simulateSuccess']);

    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::get('/account', [AccountController::class, 'show']);
    Route::patch('/account/name', [AccountController::class, 'updateName']);
    Route::patch('/account/password', [AccountController::class, 'updatePassword']);

    // Pengajuan dan status milik pengguna yang login.
    Route::post('/seller-applications', [SellerApplicationController::class, 'store']);

    Route::get('/seller-applications/mine', [SellerApplicationController::class, 'mine']);

    // Pengelolaan pengajuan oleh admin.
    Route::get('/admin/seller-applications', [SellerApplicationController::class, 'index']);

    Route::post('/admin/seller-applications/{sellerApplication}/approve', [SellerApplicationController::class, 'approve']);

    Route::post('/admin/seller-applications/{sellerApplication}/reject', [SellerApplicationController::class, 'reject']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
