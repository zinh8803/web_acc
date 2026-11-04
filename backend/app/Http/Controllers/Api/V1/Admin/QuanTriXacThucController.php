<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuanTriVien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class QuanTriXacThucController extends Controller
{
    /**
     * Đăng nhập quản trị viên.
     */
    public function dangNhap(Request $request): JsonResponse
    {
        $email = $request->email;
        $matKhau = $request->mat_khau ?? $request->password;

        if (!$email || !$matKhau) {
            throw ValidationException::withMessages([
                'email' => ['Vui lòng nhập đầy đủ email và mật khẩu.'],
            ]);
        }

        $admin = QuanTriVien::where('email', $email)
            ->whereIn('vai_tro', ['SUPER_ADMIN', 'STAFF_SERVICE', 'STAFF_SUPPORT'])
            ->first();

        if (!$admin || !Hash::check($matKhau, $admin->mat_khau_ma_hoa)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập quản trị viên không chính xác.'],
            ]);
        }

        if ($admin->trang_thai !== 'ACTIVE') {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Tài khoản quản trị của bạn đã bị khóa.',
                'message' => 'Tài khoản quản trị của bạn đã bị khóa.',
            ], 403);
        }

        $admin->update(['dang_nhap_cuoi_luc' => now()]);
        $token = $admin->createToken('admin-api-token')->plainTextToken;

        $adminData = [
            'id' => $admin->id,
            'ho_ten' => $admin->ho_ten,
            'full_name' => $admin->ho_ten,
            'email' => $admin->email,
            'vai_tro' => $admin->vai_tro,
            'role' => $admin->vai_tro,
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đăng nhập thành công',
            'message' => 'Đăng nhập thành công',
            'du_lieu' => [
                'token' => $token,
                'access_token' => $token,
                'quan_tri_vien' => $adminData,
                'admin' => $adminData,
            ],
            'data' => [
                'token' => $token,
                'access_token' => $token,
                'admin' => $adminData,
            ]
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        return $this->dangNhap($request);
    }

    /**
     * Đăng xuất quản trị viên.
     */
    public function dangXuat(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã đăng xuất tài khoản quản trị.',
            'message' => 'Đã đăng xuất tài khoản quản trị.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->dangXuat($request);
    }

    /**
     * Thông tin quản trị viên đang đăng nhập.
     */
    public function thongTinCaNhan(Request $request): JsonResponse
    {
        $admin = $request->user();
        $adminData = [
            'id' => $admin->id,
            'ho_ten' => $admin->ho_ten,
            'full_name' => $admin->ho_ten,
            'email' => $admin->email,
            'vai_tro' => $admin->vai_tro,
            'role' => $admin->vai_tro,
            'trang_thai' => $admin->trang_thai,
            'status' => $admin->trang_thai,
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $adminData,
            'data' => $adminData,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->thongTinCaNhan($request);
    }
}

