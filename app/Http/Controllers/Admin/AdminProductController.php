<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with(['seller:id,name', 'images:id,product_id'])
            ->latest()
            ->paginate(20);

        return view('admin.products.index', compact('products'));
    }
}
