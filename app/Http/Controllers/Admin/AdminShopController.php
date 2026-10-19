<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\View\View;

class AdminShopController extends Controller
{
    public function index(): View
    {
        $shops = Shop::with('user:id,name,email')
            ->withCount('products')
            ->latest()
            ->paginate(20);

        return view('admin.shops.index', compact('shops'));
    }
}
