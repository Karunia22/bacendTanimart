<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles:id,name')
            ->withCount(['orders', 'shop'])
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }
}
