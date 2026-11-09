<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuanTriDonHangController extends Controller
{
    /**
     * Danh sách đơn hàng trong admin.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DonHang::with(['nguoiDung:id,email,ho_ten,so_dien_thoai'])->orderByDesc('id');

        $search = $request->get('tu_khoa') ?? $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ma_don_hang', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        $status = $request->get('trang_thai') ?? $request->get('status');
        if ($status) {
            $query->where('trang_thai', strtoupper($status));
        }

        $paginator = $query->paginate($request->get('per_page', 20));

        $paginator->getCollection()->transform(function ($order) {
            $arr = $order->toArray();
            $arr['order_code'] = $order->ma_don_hang;
            $arr['status'] = $order->trang_thai;
            $arr['total'] = $order->tong_tien;
            $arr['created_at'] = $order->ngay_tao;
            $arr['user'] = $order->nguoiDung;
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
     * Chi tiết đơn hàng trong admin.
     */
    public function show(string $code): JsonResponse
    {
        $order = DonHang::with(['nguoiDung', 'chiTiets.sanPham', 'chiTiets.taiKhoanGame', 'chiTiets.yeuCauDichVu.nhanVienPhuTrach', 'maGiamGia'])
            ->where('ma_don_hang', $code)
            ->orWhere('id', $code)
            ->firstOrFail();

        $data = $order->toArray();
        $data['order_code'] = $order->ma_don_hang;
        $data['status'] = $order->trang_thai;
        $data['total'] = $order->tong_tien;
        $data['subtotal'] = $order->tam_tinh;
        $data['discount_total'] = $order->tong_giam_gia;
        $data['created_at'] = $order->ngay_tao;
        $data['user'] = $order->nguoiDung;
        $data['coupon'] = $order->maGiamGia;

        $data['items'] = collect($order->chiTiets)->map(function ($item) {
            $itemData = $item->toArray();
            $itemData['item_type'] = in_array($item->loai_muc, ['TAI_KHOAN', 'ACCOUNT']) ? 'ACCOUNT' : 'SERVICE';
            $itemData['loai_muc'] = $item->loai_muc;
            $itemData['product'] = $item->sanPham;
            $itemData['product_name'] = $item->ten_san_pham_luu_lai;
            $itemData['variant_name'] = $item->ten_bien_the_luu_lai;
            $itemData['unit_price'] = $item->don_gia;
            $itemData['line_total'] = $item->thanh_tien;
            $itemData['quantity'] = $item->so_luong;
            $itemData['thumbnail_url'] = $item->anh_san_pham_luu_lai;

            if ($item->yeuCauDichVu) {
                $ycdv = $item->yeuCauDichVu;
                $itemData['service_request'] = [
                    'id' => $ycdv->id,
                    'request_code' => $ycdv->ma_yeu_cau,
                    'status' => $ycdv->trang_thai,
                    'due_at' => $ycdv->han_hoan_thanh,
                    'started_at' => $ycdv->bat_dau_luc,
                    'completed_at' => $ycdv->hoan_thanh_luc,
                    'cancel_reason' => $ycdv->ly_do_huy,
                    'form_data' => $ycdv->du_lieu_bieu_mau,
                    'staff' => $ycdv->nhanVienPhuTrach ? [
                        'id' => $ycdv->nhanVienPhuTrach->id,
                        'full_name' => $ycdv->nhanVienPhuTrach->ho_ten,
                    ] : null,
                ];
            } else {
                $itemData['service_request'] = null;
            }

            if ($item->taiKhoanGame) {
                $thongTin = $item->taiKhoanGame->giaiMaThongTin();
                $itemData['credentials'] = [
                    'username' => $thongTin['ten_dang_nhap'],
                    'password' => $thongTin['mat_khau'],
                    'extra_info' => $thongTin['thong_tin_phu'] ?? null,
                    'ten_dang_nhap' => $thongTin['ten_dang_nhap'],
                    'mat_khau' => $thongTin['mat_khau'],
                    'thong_tin_phu' => $thongTin['thong_tin_phu'] ?? null,
                ];
            }
            return $itemData;
        });

        $data['chi_tiets'] = $data['items'];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $data,
            'data' => $data,
        ]);
    }

    /**
     * Cập nhật trạng thái đơn hàng.
     */
    public function updateStatus(Request $request, string $code): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $code)->orWhere('id', $code)->firstOrFail();

        $status = strtoupper($request->trang_thai ?? $request->status ?? '');
        if (!$status) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Vui lòng chọn trạng thái.'], 422);
        }

        $wasPaid = $order->isPaid();
        $updateData = ['trang_thai' => $status];

        if (!$order->thanh_toan_luc && in_array($status, ['PAID', 'PROCESSING', 'COMPLETED'], true)) {
            $updateData['thanh_toan_luc'] = now();
        }
        $order->update($updateData);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã cập nhật trạng thái đơn hàng.',
            'message' => 'Đã cập nhật trạng thái đơn hàng.',
            'du_lieu' => $order,
            'data' => $order,
        ]);
    }
}

