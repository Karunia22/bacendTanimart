<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();
        if (! $user->hasRole('admin') && $user->role !== 'admin') {
            Auth::logout();
            return redirect()->route('admin.login')->withErrors(['email' => 'Akses hanya untuk admin.']);
        }

        return $next($request);
    }
}
