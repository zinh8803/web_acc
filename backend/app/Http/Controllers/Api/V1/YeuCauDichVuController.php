<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\YeuCauDichVu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class YeuCauDichVuController extends Controller
{
    /**
     * Danh sách yêu cầu dịch vụ của người dùng.
     */
    public function danhSach(Request $request): JsonResponse
    {
        $services = YeuCauDichVu::where('nguoi_dung_id', $request->user()->id)
            ->with(['chiTietDonHang.donHang', 'chiTietDonHang.sanPham', 'nhanVienPhuTrach'])
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        $services->getCollection()->transform(function ($item) {
            $arr = $item->toArray();
            $arr['request_code'] = $item->ma_yeu_cau;
            $arr['status'] = $item->trang_thai;
            $arr['form_data'] = $item->du_lieu_bieu_mau;
            $arr['customer_note'] = $item->ghi_chu_khach_hang;
            $arr['order_item'] = $item->chiTietDonHang;
            $arr['staff'] = $item->nhanVienPhuTrach;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $services,
            'data' => $services,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->danhSach($request);
    }

    /**
     * Chi tiết yêu cầu dịch vụ.
     */
    public function chiTiet(Request $request, string $requestCode): JsonResponse
    {
        $service = YeuCauDichVu::where('ma_yeu_cau', $requestCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->with(['chiTietDonHang.donHang', 'chiTietDonHang.sanPham', 'nhanVienPhuTrach'])
            ->firstOrFail();

        $arr = $service->toArray();
        $arr['request_code'] = $service->ma_yeu_cau;
        $arr['status'] = $service->trang_thai;
        $arr['form_data'] = $service->du_lieu_bieu_mau;
        $arr['customer_note'] = $service->ghi_chu_khach_hang;
        $arr['order_item'] = $service->chiTietDonHang;
        $arr['staff'] = $service->nhanVienPhuTrach;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $arr,
            'data' => $arr,
        ]);
    }

    public function show(Request $request, string $requestCode): JsonResponse
    {
        return $this->chiTiet($request, $requestCode);
    }

    /**
     * Tạo yêu cầu dịch vụ.
     */
    public function taoYeuCau(Request $request): JsonResponse
    {
        $chiTietDonHangId = $request->chi_tiet_don_hang_id ?? $request->order_item_id;
        $duLieuBieuMau = $request->du_lieu_bieu_mau ?? $request->form_data ?? [];
        $thongTinDangNhap = $request->thong_tin_dang_nhap ?? $request->game_login;
        $ghiChuKhachHang = $request->ghi_chu_khach_hang ?? $request->customer_note;

        if (!$chiTietDonHangId) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Vui lòng chọn dòng sản phẩm dịch vụ.'], 422);
        }

        $item = ChiTietDonHang::with(['donHang', 'sanPham'])->findOrFail($chiTietDonHangId);
        if ($item->donHang->nguoi_dung_id !== $request->user()->id) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Không có quyền thực hiện.'], 403);
        }

        if ($item->loai_muc !== 'SERVICE' || !in_array($item->donHang->trang_thai, ['PAID', 'PROCESSING', 'COMPLETED'], true)) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Yêu cầu dịch vụ chỉ tạo được cho đơn hàng dịch vụ đã thanh toán.'], 422);
        }

        if (YeuCauDichVu::where('chi_tiet_don_hang_id', $item->id)->exists()) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Dòng sản phẩm này đã được tạo yêu cầu dịch vụ.'], 409);
        }

        $service = YeuCauDichVu::create([
            'ma_yeu_cau' => 'SRV-' . strtoupper(Str::random(8)),
            'chi_tiet_don_hang_id' => $item->id,
            'nguoi_dung_id' => $request->user()->id,
            'du_lieu_bieu_mau' => $duLieuBieuMau,
            'thong_tin_dang_nhap_ma_hoa' => !empty($thongTinDangNhap) ? Crypt::encryptString(is_array($thongTinDangNhap) ? json_encode($thongTinDangNhap) : $thongTinDangNhap) : null,
            'ghi_chu_khach_hang' => $ghiChuKhachHang,
            'trang_thai' => 'PENDING',
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Yêu cầu dịch vụ đã được gửi.',
            'message' => 'Yêu cầu dịch vụ đã được gửi.',
            'du_lieu' => $service,
            'data' => $service,
        ], 201);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->taoYeuCau($request);
    }

    /**
     * Khách hàng xác nhận hoàn thành dịch vụ.
     */
    public function xacNhanHoanThanh(Request $request, string $requestCode): JsonResponse
    {
        $service = YeuCauDichVu::where('ma_yeu_cau', $requestCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('trang_thai', 'DONE')
            ->firstOrFail();

        $service->update(['khach_xac_nhan_luc' => now()]);
        $service->chiTietDonHang()->update(['trang_thai_giao_hang' => 'COMPLETED']);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $service->fresh(),
            'data' => $service->fresh(),
        ]);
    }

    public function confirm(Request $request, string $requestCode): JsonResponse
    {
        return $this->xacNhanHoanThanh($request, $requestCode);
    }
}

