<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'customer',
            'status' => 'aktif',
        ]);

        // Tautkan role customer pada pivot user_roles.
        $customerRole = Role::where('name', 'customer')->first();

        if ($customerRole && ! $user->hasRole('customer')) {
            $user->roles()->attach($customerRole->id);
        }

        $token = $user->createToken('android')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => $this->userWithRoles($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! password_verify($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if ($user->status !== 'aktif') {
            return response()->json([
                'message' => 'Akun tidak aktif.',
            ], 403);
        }

        $token = $user->createToken('android')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $this->userWithRoles($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Data user beserta array nama role.
     */
    private function userWithRoles(User $user): array
    {
        return array_merge($user->toArray(), [
            'roles' => $user->roleNames(),
        ]);
    }
}
