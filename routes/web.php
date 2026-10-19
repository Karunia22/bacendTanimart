<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminSellerApplicationController;
use App\Http\Controllers\Admin\AdminShopController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/seller-applications', [AdminSellerApplicationController::class, 'index'])->name('seller-applications.index');

        Route::post('/seller-applications/{sellerApplication}/approve', [AdminSellerApplicationController::class, 'approve'])->name('seller-applications.approve');

        Route::post('/seller-applications/{sellerApplication}/reject', [AdminSellerApplicationController::class, 'reject'])->name('seller-applications.reject');
        Route::get('/shops', [AdminShopController::class, 'index'])->name('shops.index');
        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
    });
});
