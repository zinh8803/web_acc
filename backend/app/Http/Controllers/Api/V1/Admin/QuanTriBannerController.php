<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannerQuangCao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class QuanTriBannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = BannerQuangCao::orderBy('thu_tu_hien_thi', 'asc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($b) {
                $arr = $b->toArray();
                $arr['title'] = $b->tieu_de;
                $arr['image_url'] = $b->duong_dan_anh;
                $arr['link_url'] = $b->duong_dan_lien_ket;
                $arr['sort_order'] = $b->thu_tu_hien_thi;
                $arr['status'] = $b->trang_thai;
                return $arr;
            });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $banners,
            'data' => $banners,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $banner = BannerQuangCao::findOrFail($id);
        $arr = $banner->toArray();
        $arr['title'] = $banner->tieu_de;
        $arr['image_url'] = $banner->duong_dan_anh;
        $arr['link_url'] = $banner->duong_dan_lien_ket;
        $arr['sort_order'] = $banner->thu_tu_hien_thi;
        $arr['status'] = $banner->trang_thai;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $arr,
            'data' => $arr,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->tieu_de ?? $request->title;
        $imageUrl = $request->duong_dan_anh ?? $request->image_url;
        $linkUrl = $request->duong_dan_lien_ket ?? $request->link_url;
        $sortOrder = (int) ($request->thu_tu_hien_thi ?? $request->sort_order ?? 0);
        $status = $request->trang_thai ?? $request->status ?? 'ACTIVE';

        if ($request->hasFile('image_file') || $request->hasFile('anh_file')) {
            $file = $request->file('image_file') ?? $request->file('anh_file');
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
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Vui lòng cung cấp link hình ảnh hoặc tải ảnh lên.',
            ], 422);
        }

        $banner = BannerQuangCao::create([
            'tieu_de' => $title,
            'duong_dan_anh' => $imageUrl,
            'duong_dan_lien_ket' => $linkUrl,
            'thu_tu_hien_thi' => $sortOrder,
            'trang_thai' => $status,
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Tạo banner thành công.',
            'message' => 'Tạo banner thành công.',
            'du_lieu' => $banner,
            'data' => $banner,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $banner = BannerQuangCao::findOrFail($id);

        $imageUrl = $request->duong_dan_anh ?? $request->image_url ?? $banner->duong_dan_anh;

        if ($request->hasFile('image_file') || $request->hasFile('anh_file')) {
            $file = $request->file('image_file') ?? $request->file('anh_file');
            $uploadPath = public_path('uploads/banners');
            if (!File::isDirectory($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true, true);
            }
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $imageUrl = url('/uploads/banners/' . $fileName);
        }

        $banner->update([
            'tieu_de' => $request->tieu_de ?? $request->title ?? $banner->tieu_de,
            'duong_dan_anh' => $imageUrl,
            'duong_dan_lien_ket' => $request->duong_dan_lien_ket ?? $request->link_url ?? $banner->duong_dan_lien_ket,
            'thu_tu_hien_thi' => $request->has('thu_tu_hien_thi') || $request->has('sort_order') ? (int) ($request->thu_tu_hien_thi ?? $request->sort_order) : $banner->thu_tu_hien_thi,
            'trang_thai' => $request->trang_thai ?? $request->status ?? $banner->trang_thai,
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Cập nhật banner thành công.',
            'message' => 'Cập nhật banner thành công.',
            'du_lieu' => $banner,
            'data' => $banner,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $banner = BannerQuangCao::findOrFail($id);
        $banner->delete();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Xóa banner thành công.',
            'message' => 'Xóa banner thành công.',
        ]);
    }
}

