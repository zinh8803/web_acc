<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhGia;
use App\Models\SanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuanTriDanhGiaController extends Controller
{
    /**
     * Danh sách đánh giá quản trị.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DanhGia::with([
            'nguoiDung:id,ho_ten,email,so_dien_thoai',
            'sanPham:id,ten_san_pham,ma_san_pham,anh_dai_dien,duong_dan_tinh',
            'chiTietDonHang:id,don_hang_id,ten_san_pham_luu_lai',
            'chiTietDonHang.donHang:id,ma_don_hang',
        ])->orderByDesc('id');

        $search = $request->get('tu_khoa') ?? $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('noi_dung_danh_gia', 'like', "%{$search}%")
                  ->orWhereHas('nguoiDung', function ($u) use ($search) {
                      $u->where('ho_ten', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('sanPham', function ($p) use ($search) {
                      $p->where('ten_san_pham', 'like', "%{$search}%")
                        ->orWhere('ma_san_pham', 'like', "%{$search}%");
                  });
            });
        }

        $status = $request->get('trang_thai') ?? $request->get('status');
        if ($status) {
            $query->where('trang_thai', $status);
        }

        $rating = $request->get('so_sao') ?? $request->get('rating');
        if ($rating) {
            $query->where('so_sao', (int) $rating);
        }

        $productId = $request->get('san_pham_id') ?? $request->get('product_id');
        if ($productId) {
            $query->where('san_pham_id', (int) $productId);
        }

        $paginator = $query->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

        $paginator->getCollection()->transform(function ($item) {
            $arr = $item->toArray();
            $arr['user'] = $item->nguoiDung ? [
                'id' => $item->nguoiDung->id,
                'name' => $item->nguoiDung->ho_ten,
                'email' => $item->nguoiDung->email,
            ] : null;
            $arr['product'] = $item->sanPham ? [
                'id' => $item->sanPham->id,
                'name' => $item->sanPham->ten_san_pham,
                'sku' => $item->sanPham->ma_san_pham,
                'thumbnail_url' => $item->sanPham->anh_dai_dien,
            ] : null;
            $arr['order'] = $item->chiTietDonHang?->donHang ? [
                'id' => $item->chiTietDonHang->donHang->id,
                'order_code' => $item->chiTietDonHang->donHang->ma_don_hang,
            ] : null;
            $arr['rating'] = $item->so_sao;
            $arr['content'] = $item->noi_dung_danh_gia;
            $arr['status'] = $item->trang_thai;
            $arr['created_at'] = $item->ngay_tao;
            $arr['ngay_tao'] = $item->ngay_tao;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $paginator,
            'data' => $paginator,
        ]);
    }

    /**
     * Cập nhật trạng thái đánh giá (Ẩn / Duyệt).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $review = DanhGia::findOrFail($id);

        $status = $request->trang_thai ?? $request->status;
        if ($status) {
            $review->update(['trang_thai' => $status]);
        }

        // Cập nhật lại điểm đánh giá của sản phẩm
        $this->capNhatDiemSanPham($review->san_pham_id);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Cập nhật trạng thái đánh giá thành công.',
            'message' => 'Cập nhật trạng thái đánh giá thành công.',
            'du_lieu' => $review,
            'data' => $review,
        ]);
    }

    /**
     * Xóa đánh giá.
     */
    public function destroy(int $id): JsonResponse
    {
        $review = DanhGia::findOrFail($id);
        $productId = $review->san_pham_id;
        $review->delete();

        // Cập nhật lại điểm đánh giá của sản phẩm
        $this->capNhatDiemSanPham($productId);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã xóa đánh giá thành công.',
            'message' => 'Đã xoá đánh giá thành công.',
        ]);
    }

    private function capNhatDiemSanPham(?int $productId): void
    {
        if (!$productId) return;
        $product = SanPham::find($productId);
        if (!$product) return;

        $avg = DanhGia::where('san_pham_id', $productId)->where('trang_thai', 'APPROVED')->avg('so_sao');
        $count = DanhGia::where('san_pham_id', $productId)->where('trang_thai', 'APPROVED')->count();

        $product->update([
            'diem_danh_gia_tb' => $avg ? round($avg, 1) : 5.0,
            'so_luot_danh_gia' => $count,
        ]);
    }
}

