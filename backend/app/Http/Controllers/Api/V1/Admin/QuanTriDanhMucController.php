<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuanTriDanhMucController extends Controller
{
    /**
     * Danh sách tất cả danh mục.
     */
    public function index(): JsonResponse
    {
        $danhMuc = DanhMuc::orderBy('thu_tu_hien_thi')->get()->map(function ($item) {
            $arr = $item->toArray();
            $arr['name'] = $item->ten_danh_muc;
            $arr['slug'] = $item->duong_dan_tinh;
            $arr['type'] = $item->loai_danh_muc;
            $arr['description'] = $item->mo_ta;
            $arr['sort_order'] = $item->thu_tu_hien_thi;
            $arr['status'] = $item->trang_thai;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $danhMuc,
            'data' => $danhMuc,
        ]);
    }

    /**
     * Tạo danh mục mới.
     */
    public function store(Request $request): JsonResponse
    {
        $ten = $request->ten_danh_muc ?? $request->name ?? 'Danh mục mới';
        $danhMucChaId = $request->danh_muc_cha_id ?? $request->parent_id ?? null;
        $loai = $request->loai_danh_muc ?? $request->type ?? 'ACCOUNT';
        if ($loai === 'TAI_KHOAN') $loai = 'ACCOUNT';
        if ($loai === 'DICH_VU') $loai = 'SERVICE';

        $moTa = $request->mo_ta ?? $request->description ?? null;
        $thuTu = (int) ($request->thu_tu_hien_thi ?? $request->sort_order ?? 0);
        $slug = Str::slug($ten) . '-' . Str::random(4);

        $category = DanhMuc::create([
            'ten_danh_muc' => $ten,
            'duong_dan_tinh' => $slug,
            'loai_danh_muc' => $loai,
            'danh_muc_cha_id' => $danhMucChaId,
            'mo_ta' => $moTa,
            'thu_tu_hien_thi' => $thuTu,
            'trang_thai' => 'ACTIVE',
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Tạo danh mục thành công.',
            'du_lieu' => $category,
            'data' => $category
        ], 201);
    }

    /**
     * Cập nhật danh mục.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = DanhMuc::findOrFail($id);
        $updateData = [];

        $ten = $request->ten_danh_muc ?? $request->name;
        if ($ten && $ten !== $category->ten_danh_muc) {
            $updateData['ten_danh_muc'] = $ten;
            $updateData['duong_dan_tinh'] = Str::slug($ten) . '-' . Str::lower(Str::random(4));
        }

        $loai = $request->loai_danh_muc ?? $request->type;
        if ($loai) {
            if ($loai === 'TAI_KHOAN') $loai = 'ACCOUNT';
            if ($loai === 'DICH_VU') $loai = 'SERVICE';
            $updateData['loai_danh_muc'] = $loai;
        }

        if ($request->has('mo_ta') || $request->has('description')) {
            $updateData['mo_ta'] = $request->mo_ta ?? $request->description;
        }

        if ($request->has('thu_tu_hien_thi') || $request->has('sort_order')) {
            $updateData['thu_tu_hien_thi'] = (int) ($request->thu_tu_hien_thi ?? $request->sort_order);
        }

        if ($request->has('trang_thai') || $request->has('status')) {
            $updateData['trang_thai'] = $request->trang_thai ?? $request->status;
        }

        $category->update($updateData);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Cập nhật danh mục thành công.',
            'du_lieu' => $category,
            'data' => $category
        ]);
    }

    /**
     * Xóa danh mục.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = DanhMuc::findOrFail($id);
        $category->delete();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã xóa danh mục.',
            'message' => 'Đã xóa danh mục.'
        ]);
    }
}

