<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannerQuangCao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminBannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = BannerQuangCao::orderBy('thu_tu_hien_thi', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $banner = BannerQuangCao::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $banner,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:1000',
            'image_file' => 'nullable|image|max:10240',
            'link_url' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:ACTIVE,HIDDEN',
        ]);

        $imageUrl = $validated['image_url'] ?? null;

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $uploadPath = public_path('uploads/banners');
            if (!File::isDirectory($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true, true);
            }
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $imageUrl = url('/uploads/banners/' . $fileName);
        }

        if (empty($imageUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cung cấp link hình ảnh hoặc tải ảnh lên.',
            ], 422);
        }

        $banner = BannerQuangCao::create([
            'tieu_de' => $validated['title'] ?? null,
            'duong_dan_anh' => $imageUrl,
            'duong_dan_lien_ket' => $validated['link_url'] ?? null,
            'thu_tu_hien_thi' => $validated['sort_order'] ?? 0,
            'trang_thai' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo banner thành công.',
            'data' => $banner,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $banner = BannerQuangCao::findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:1000',
            'image_file' => 'nullable|image|max:10240',
            'link_url' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|in:ACTIVE,HIDDEN',
        ]);

        $imageUrl = $validated['image_url'] ?? $banner->image_url;

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $uploadPath = public_path('uploads/banners');
            if (!File::isDirectory($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true, true);
            }
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $imageUrl = url('/uploads/banners/' . $fileName);
        }

        $banner->update([
            'tieu_de' => array_key_exists('title', $validated) ? $validated['title'] : $banner->tieu_de,
            'duong_dan_anh' => $imageUrl,
            'duong_dan_lien_ket' => array_key_exists('link_url', $validated) ? $validated['link_url'] : $banner->duong_dan_lien_ket,
            'thu_tu_hien_thi' => $validated['sort_order'] ?? $banner->thu_tu_hien_thi,
            'trang_thai' => $validated['status'] ?? $banner->trang_thai,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật banner thành công.',
            'data' => $banner,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $banner = BannerQuangCao::findOrFail($id);
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa banner thành công.',
        ]);
    }
}
