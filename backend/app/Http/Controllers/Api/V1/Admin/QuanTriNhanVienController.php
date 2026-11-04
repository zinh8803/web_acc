<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuanTriVien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class QuanTriNhanVienController extends Controller
{
    public function index(): JsonResponse
    {
        $staff = QuanTriVien::whereIn('vai_tro', ['SUPER_ADMIN', 'STAFF_SERVICE', 'STAFF_SUPPORT'])->get()->map(function ($s) {
            $arr = $s->toArray();
            $arr['full_name'] = $s->ho_ten;
            $arr['role'] = $s->vai_tro;
            $arr['status'] = $s->trang_thai;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $staff,
            'data' => $staff
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $email = $request->email;
        $matKhau = $request->mat_khau ?? $request->password;
        $hoTen = $request->ho_ten ?? $request->full_name ?? 'Nhân viên';
        $vaiTro = $request->vai_tro ?? $request->role ?? 'STAFF_SUPPORT';

        if (!$email || !$matKhau) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Vui lòng nhập email và mật khẩu.'], 422);
        }

        $admin = QuanTriVien::create([
            'email' => $email,
            'mat_khau_ma_hoa' => Hash::make($matKhau),
            'ho_ten' => $hoTen,
            'vai_tro' => $vaiTro,
            'trang_thai' => 'ACTIVE',
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Tạo nhân viên thành công.',
            'du_lieu' => $admin,
            'data' => $admin
        ], 201);
    }
}

