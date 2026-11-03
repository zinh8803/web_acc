<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuanTriVien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $roleCol = Schema::hasColumn('nguoi_dung', 'vai_tro') ? 'vai_tro' : 'role';
        $admin = QuanTriVien::where('email', $validated['email'])
            ->whereIn($roleCol, ['SUPER_ADMIN', 'STAFF_SERVICE', 'STAFF_SUPPORT'])
            ->first();

        $pwd = $admin?->mat_khau_ma_hoa ?? $admin?->password_hash;
        if (!$admin || !Hash::check($validated['password'], $pwd)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập quản trị viên không chính xác.'],
            ]);
        }

        $status = $admin->trang_thai ?? $admin->status;
        if ($status !== 'ACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản quản trị của bạn đã bị khóa.',
            ], 403);
        }

        $lastLoginCol = Schema::hasColumn('nguoi_dung', 'dang_nhap_cuoi_luc') ? 'dang_nhap_cuoi_luc' : 'last_login_at';
        $admin->update([
            $lastLoginCol => now(),
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
                    'full_name' => $admin->ho_ten ?? $admin->full_name,
                    'ho_ten' => $admin->ho_ten ?? $admin->full_name,
                    'email' => $admin->email,
                    'role' => $admin->vai_tro ?? $admin->role,
                    'vai_tro' => $admin->vai_tro ?? $admin->role,
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
        $admin = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'full_name' => $admin->ho_ten ?? $admin->full_name,
                'ho_ten' => $admin->ho_ten ?? $admin->full_name,
                'email' => $admin->email,
                'role' => $admin->vai_tro ?? $admin->role,
                'vai_tro' => $admin->vai_tro ?? $admin->role,
            ]
        ]);
    }
}
