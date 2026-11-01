<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminBannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = Banner::orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

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

        $banner = Banner::create([
            'title' => $validated['title'] ?? null,
            'image_url' => $imageUrl,
            'link_url' => $validated['link_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo banner thành công.',
            'data' => $banner,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

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
            'title' => array_key_exists('title', $validated) ? $validated['title'] : $banner->title,
            'image_url' => $imageUrl,
            'link_url' => array_key_exists('link_url', $validated) ? $validated['link_url'] : $banner->link_url,
            'sort_order' => $validated['sort_order'] ?? $banner->sort_order,
            'status' => $validated['status'] ?? $banner->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật banner thành công.',
            'data' => $banner,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa banner thành công.',
        ]);
    }
}
