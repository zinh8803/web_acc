<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = AdminUser::with('role')->where('email', $validated['email'])->first();

        if (!$admin || !Hash::check($validated['password'], $admin->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập quản trị viên không chính xác.'],
            ]);
        }

        if ($admin->status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản quản trị của bạn đã bị khóa.',
            ], 403);
        }

        $admin->update([
            'last_login_at' => now(),
        ]);

        $token = $admin->createToken('admin-api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'token' => $token,
                'access_token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'full_name' => $admin->full_name,
                    'email' => $admin->email,
                    'role' => $admin->role?->code,
                ],
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã đăng xuất tài khoản quản trị.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->user()->load('role');
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'full_name' => $admin->full_name,
                'email' => $admin->email,
                'role' => $admin->role?->code,
            ]
        ]);
    }
}
