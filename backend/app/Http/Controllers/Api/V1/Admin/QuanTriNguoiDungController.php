<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuanTriNguoiDungController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = NguoiDung::orderByDesc('id')->paginate(20);
        $users->getCollection()->transform(function ($u) {
            $arr = $u->toArray();
            $arr['full_name'] = $u->ho_ten;
            $arr['phone'] = $u->so_dien_thoai;
            $arr['role'] = $u->vai_tro;
            $arr['status'] = $u->trang_thai;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $users,
            'data' => $users
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = NguoiDung::with(['donHangs'])->findOrFail($id);
        $data = $user->toArray();
        $data['full_name'] = $user->ho_ten;
        $data['phone'] = $user->so_dien_thoai;
        $data['role'] = $user->vai_tro;
        $data['status'] = $user->trang_thai;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $data,
            'data' => $data
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = NguoiDung::findOrFail($id);
        $status = strtoupper($request->trang_thai ?? $request->status ?? 'ACTIVE');
        $user->update(['trang_thai' => $status]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Cập nhật trạng thái người dùng thành công.',
            'du_lieu' => $user,
            'data' => $user
        ]);
    }
}

