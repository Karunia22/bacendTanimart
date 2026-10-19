<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::with(['user:id,name', 'items:id,order_id,product_id'])
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }
}
