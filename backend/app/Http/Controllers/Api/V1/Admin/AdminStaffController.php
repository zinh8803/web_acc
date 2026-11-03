<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuanTriVien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminStaffController extends Controller
{
    public function index(): JsonResponse
    {
        $roleCol = Schema::hasColumn('nguoi_dung', 'vai_tro') ? 'vai_tro' : 'role';
        return response()->json([
            'success' => true,
            'data' => QuanTriVien::whereIn($roleCol, ['SUPER_ADMIN', 'STAFF_SERVICE', 'STAFF_SUPPORT'])->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $table = Schema::hasTable('nguoi_dung') ? 'nguoi_dung' : 'users';
        $validated = $request->validate([
            'email' => "required|email|unique:{$table},email",
            'password' => 'required|string|min:8',
            'full_name' => 'sometimes|nullable|string',
            'ho_ten' => 'sometimes|nullable|string',
            'role' => 'nullable|string|in:SUPER_ADMIN,STAFF_SERVICE,STAFF_SUPPORT',
            'vai_tro' => 'nullable|string|in:SUPER_ADMIN,STAFF_SERVICE,STAFF_SUPPORT',
        ]);

        $hoTen = $validated['ho_ten'] ?? $validated['full_name'] ?? 'Nhân viên';
        $vaiTro = $validated['vai_tro'] ?? $validated['role'] ?? 'STAFF_SUPPORT';

        $admin = QuanTriVien::create([
            'email' => $validated['email'],
            'mat_khau_ma_hoa' => Hash::make($validated['password']),
            'ho_ten' => $hoTen,
            'vai_tro' => $vaiTro,
            'trang_thai' => 'ACTIVE',
        ]);

        return response()->json(['success' => true, 'data' => $admin], 201);
    }
}
