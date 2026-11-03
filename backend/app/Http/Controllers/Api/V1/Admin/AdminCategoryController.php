<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class AdminCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $sortCol = Schema::hasColumn('danh_muc', 'thu_tu_hien_thi') ? 'thu_tu_hien_thi' : 'sort_order';
        return response()->json([
            'success' => true,
            'data' => DanhMuc::orderBy($sortCol)->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $table = Schema::hasTable('danh_muc') ? 'danh_muc' : 'categories';
        $validated = $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'ten_danh_muc' => 'sometimes|nullable|string|max:255',
            'parent_id' => "nullable|exists:{$table},id",
            'danh_muc_cha_id' => "nullable|exists:{$table},id",
            'type' => 'nullable|in:ACCOUNT,SERVICE,TAI_KHOAN,DICH_VU',
            'loai_danh_muc' => 'nullable|in:ACCOUNT,SERVICE,TAI_KHOAN,DICH_VU',
            'description' => 'nullable|string',
            'mo_ta' => 'nullable|string',
            'image_file' => 'nullable|image|max:5120',
            'sort_order' => 'nullable|integer',
            'thu_tu_hien_thi' => 'nullable|integer',
        ]);

        $name = $validated['ten_danh_muc'] ?? $validated['name'] ?? 'Danh mục mới';
        $slug = Str::slug($name) . '-' . Str::random(4);
        $type = $validated['loai_danh_muc'] ?? $validated['type'] ?? 'ACCOUNT';
        if ($type === 'TAI_KHOAN') $type = 'ACCOUNT';
        if ($type === 'DICH_VU') $type = 'SERVICE';

        $data = [
            'ten_danh_muc' => $name,
            'duong_dan_tinh' => $slug,
            'loai_danh_muc' => $type,
            'danh_muc_cha_id' => $validated['danh_muc_cha_id'] ?? $validated['parent_id'] ?? null,
            'mo_ta' => $validated['mo_ta'] ?? $validated['description'] ?? null,
            'thu_tu_hien_thi' => $validated['thu_tu_hien_thi'] ?? $validated['sort_order'] ?? 0,
            'trang_thai' => 'ACTIVE',
        ];

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('categories', 'public');
            $data['anh_danh_muc'] = Storage::disk('public')->url($path);
        }

        $category = DanhMuc::create($data);

        return response()->json(['success' => true, 'data' => $category], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = DanhMuc::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'ten_danh_muc' => 'sometimes|string|max:150',
            'type' => 'sometimes|in:ACCOUNT,SERVICE,TAI_KHOAN,DICH_VU',
            'loai_danh_muc' => 'sometimes|in:ACCOUNT,SERVICE,TAI_KHOAN,DICH_VU',
            'description' => 'nullable|string',
            'mo_ta' => 'nullable|string',
            'image_file' => 'nullable|image|max:5120',
            'sort_order' => 'sometimes|integer',
            'thu_tu_hien_thi' => 'sometimes|integer',
        ]);

        $updateData = [];
        $name = $validated['ten_danh_muc'] ?? $validated['name'] ?? null;
        if ($name && $name !== ($category->ten_danh_muc ?? $category->name)) {
            $updateData['ten_danh_muc'] = $name;
            $updateData['duong_dan_tinh'] = Str::slug($name) . '-' . Str::lower(Str::random(4));
        }

        if (isset($validated['loai_danh_muc']) || isset($validated['type'])) {
            $t = $validated['loai_danh_muc'] ?? $validated['type'];
            if ($t === 'TAI_KHOAN') $t = 'ACCOUNT';
            if ($t === 'DICH_VU') $t = 'SERVICE';
            $updateData['loai_danh_muc'] = $t;
        }

        if (array_key_exists('mo_ta', $validated) || array_key_exists('description', $validated)) {
            $updateData['mo_ta'] = $validated['mo_ta'] ?? $validated['description'];
        }

        if (isset($validated['thu_tu_hien_thi']) || isset($validated['sort_order'])) {
            $updateData['thu_tu_hien_thi'] = $validated['thu_tu_hien_thi'] ?? $validated['sort_order'];
        }

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('categories', 'public');
            $updateData['anh_danh_muc'] = Storage::disk('public')->url($path);
        }

        $category->update($updateData);
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = DanhMuc::findOrFail($id);
        if ($category->sanPhams()->exists() || $category->danhMucCon()->exists()) {
            return response()->json(['success' => false, 'message' => 'Không thể xóa danh mục đang có sản phẩm hoặc danh mục con.'], 409);
        }
        $category->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa danh mục thành công.']);
    }
}
