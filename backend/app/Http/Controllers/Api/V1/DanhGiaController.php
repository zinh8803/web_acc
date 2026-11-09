<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use App\Models\DanhGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DanhGiaController extends Controller
{
    /**
     * Danh sách đánh giá của sản phẩm.
     */
    public function danhSach(SanPham $product): JsonResponse
    {
        $reviews = DanhGia::where('san_pham_id', $product->id)
            ->where('trang_thai', 'APPROVED')
            ->with('nguoiDung:id,ho_ten')
            ->orderByDesc('id')
            ->paginate(10);

        $reviews->getCollection()->transform(function ($item) {
            $arr = $item->toArray();
            $arr['rating'] = $item->so_sao;
            $arr['content'] = $item->noi_dung_danh_gia;
            $arr['created_at'] = $item->ngay_tao;
            $arr['ngay_tao'] = $item->ngay_tao;
            $arr['user'] = $item->nguoiDung ? [
                'id' => $item->nguoiDung->id,
                'full_name' => $item->nguoiDung->ho_ten,
                'ho_ten' => $item->nguoiDung->ho_ten,
            ] : null;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $reviews,
            'data' => $reviews,
        ]);
    }

    public function index(SanPham $product): JsonResponse
    {
        return $this->danhSach($product);
    }

    /**
     * Gửi đánh giá mới.
     */
    public function guiDanhGia(Request $request, ?SanPham $product = null): JsonResponse
    {
        if (!$product || !$product->exists) {
            $productId = $request->san_pham_id ?? $request->product_id;
            if (!$productId) {
                return response()->json([
                    'thanh_cong' => false,
                    'success' => false,
                    'thong_bao' => 'Vui lòng cung cấp ID sản phẩm cần đánh giá.',
                    'error' => ['message' => 'Vui lòng cung cấp sản phẩm.']
                ], 422);
            }
            $product = SanPham::findOrFail($productId);
        }

        $soSao = $request->so_sao ?? $request->rating;
        $noiDung = $request->noi_dung_danh_gia ?? $request->content;

        if (!$soSao || $soSao < 1 || $soSao > 5) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Vui lòng chọn số sao từ 1 đến 5.',
                'error' => ['message' => 'Số sao phải từ 1 đến 5.']
            ], 422);
        }

        if (!$noiDung || mb_strlen($noiDung) < 5) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Nội dung đánh giá tối thiểu 5 ký tự.',
                'error' => ['message' => 'Nội dung tối thiểu 5 ký tự.']
            ], 422);
        }

        $chiTietDonHangId = $request->chi_tiet_don_hang_id ?? $request->order_item_id;

        $review = DanhGia::create([
            'nguoi_dung_id' => $request->user()->id,
            'san_pham_id' => $product->id,
            'chi_tiet_don_hang_id' => $chiTietDonHangId,
            'so_sao' => (int) $soSao,
            'noi_dung_danh_gia' => $noiDung,
            'trang_thai' => 'APPROVED',
            'ngay_tao' => now(),
        ]);

        // Cập nhật điểm đánh giá trung bình và số lượt đánh giá của sản phẩm
        $avg = DanhGia::where('san_pham_id', $product->id)->where('trang_thai', 'APPROVED')->avg('so_sao');
        $count = DanhGia::where('san_pham_id', $product->id)->where('trang_thai', 'APPROVED')->count();
        $product->update([
            'diem_danh_gia_tb' => $avg ? round($avg, 1) : 5.0,
            'so_luot_danh_gia' => $count,
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đánh giá đã được gửi thành công.',
            'message' => 'Đánh giá đã được gửi.',
            'du_lieu' => $review,
            'data' => $review,
        ], 201);
    }

    public function store(Request $request, SanPham $product): JsonResponse
    {
        return $this->guiDanhGia($request, $product);
    }
}

