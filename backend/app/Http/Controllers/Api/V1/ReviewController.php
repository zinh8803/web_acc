<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use App\Models\DanhGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(SanPham $product): JsonResponse
    {
        $reviews = DanhGia::where('san_pham_id', $product->id)
            ->where('trang_thai', 'APPROVED')
            ->with('user:id,ho_ten,anh_dai_dien')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    public function store(Request $request, SanPham $product): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|min:5|max:1000',
        ]);

        $review = DanhGia::create([
            'nguoi_dung_id' => $request->user()->id,
            'san_pham_id' => $product->id,
            'so_sao' => $validated['rating'],
            'noi_dung_danh_gia' => $validated['content'],
            'trang_thai' => 'APPROVED',
            'ngay_tao' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá đã được gửi.',
            'data' => $review,
        ], 201);
    }
}
