<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuanTriVien;
use App\Models\YeuCauDichVu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuanTriYeuCauDichVuController extends Controller
{
    /**
     * Danh sách yêu cầu dịch vụ.
     */
    public function index(Request $request): JsonResponse
    {
        $query = YeuCauDichVu::with(['nguoiDung', 'chiTietDonHang.donHang', 'chiTietDonHang.sanPham', 'nhanVienPhuTrach'])
            ->orderByDesc('id');

        $status = $request->query('trang_thai') ?? $request->query('status');
        if ($status) {
            $query->where('trang_thai', strtoupper($status));
        }

        $services = $query->paginate($request->integer('per_page', 15));

        $services->getCollection()->transform(function ($item) {
            $arr = $item->toArray();
            $arr['request_code'] = $item->ma_yeu_cau;
            $arr['status'] = $item->trang_thai;
            $arr['form_data'] = $item->du_lieu_bieu_mau;
            $arr['customer_note'] = $item->ghi_chu_khach_hang;
            $arr['order_item'] = $item->chiTietDonHang;
            $arr['user'] = $item->nguoiDung;
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

    /**
     * Chi tiết yêu cầu dịch vụ.
     */
    public function show(int $id): JsonResponse
    {
        $service = YeuCauDichVu::with(['nguoiDung', 'chiTietDonHang.donHang', 'chiTietDonHang.sanPham', 'nhanVienPhuTrach'])->findOrFail($id);
        $data = $service->toArray();
        $thongTin = $service->giaiMaThongTinDangNhap();

        $data['request_code'] = $service->ma_yeu_cau;
        $data['status'] = $service->trang_thai;
        $data['form_data'] = $service->du_lieu_bieu_mau;
        $data['customer_note'] = $service->ghi_chu_khach_hang;
        $data['order_item'] = $service->chiTietDonHang;
        $data['user'] = $service->nguoiDung;
        $data['staff'] = $service->nhanVienPhuTrach;
        $data['credentials'] = $thongTin;
        $data['thong_tin_dang_nhap'] = $thongTin;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $data,
            'data' => $data,
        ]);
    }

    public function credentials(int $id): JsonResponse
    {
        $service = YeuCauDichVu::findOrFail($id);
        $thongTin = $service->giaiMaThongTinDangNhap();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $thongTin,
            'data' => $thongTin,
        ]);
    }

    /**
     * Phân công nhân viên xử lý.
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $service = YeuCauDichVu::findOrFail($id);
        $assignedTo = $request->nhan_vien_phu_trach_id ?? $request->assigned_to;
        $dueAt = $request->han_hoan_thanh ?? $request->due_at;

        $updateData = [];
        if ($assignedTo !== null) {
            $updateData['nhan_vien_phu_trach_id'] = $assignedTo;
            if ($service->trang_thai === 'PENDING') {
                $updateData['trang_thai'] = 'IN_PROGRESS';
                $updateData['bat_dau_luc'] = now();
            }
        }
        if ($dueAt !== null) {
            $updateData['han_hoan_thanh'] = $dueAt;
        }

        $service->update($updateData);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã phân công nhân viên xử lý.',
            'du_lieu' => $service->fresh(['nguoiDung', 'chiTietDonHang.sanPham', 'nhanVienPhuTrach']),
            'data' => $service->fresh(['nguoiDung', 'chiTietDonHang.sanPham', 'nhanVienPhuTrach']),
        ]);
    }

    /**
     * Cập nhật trạng thái yêu cầu dịch vụ.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $service = YeuCauDichVu::findOrFail($id);
        $status = strtoupper($request->trang_thai ?? $request->status ?? '');

        if (!$status) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Vui lòng chọn trạng thái.'], 422);
        }

        $updateData = ['trang_thai' => $status];

        if ($request->has('do_uu_tien') || $request->has('priority')) {
            $updateData['do_uu_tien'] = (int) ($request->do_uu_tien ?? $request->priority);
        }
        if ($request->has('han_hoan_thanh') || $request->has('due_at')) {
            $updateData['han_hoan_thanh'] = $request->han_hoan_thanh ?? $request->due_at;
        }
        if ($request->has('ly_do_huy') || $request->has('cancel_reason')) {
            $updateData['ly_do_huy'] = $request->ly_do_huy ?? $request->cancel_reason;
        }
        if ($request->has('nhan_vien_phu_trach_id') || $request->has('assigned_to')) {
            $updateData['nhan_vien_phu_trach_id'] = $request->nhan_vien_phu_trach_id ?? $request->assigned_to;
        }

        if ($status === 'IN_PROGRESS' && !$service->bat_dau_luc) {
            $updateData['bat_dau_luc'] = now();
        }
        if ($status === 'DONE') {
            if (!$service->hoan_thanh_luc) {
                $updateData['hoan_thanh_luc'] = now();
            }
            $updateData['tu_dong_xac_nhan_luc'] = now()->addDays(3);
            $service->chiTietDonHang()->update(['trang_thai_giao_hang' => 'COMPLETED']);
        }
        if ($status === 'CANCELLED') {
            $service->chiTietDonHang()->update(['trang_thai_giao_hang' => 'CANCELLED']);
        }

        $service->update($updateData);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã cập nhật trạng thái yêu cầu dịch vụ.',
            'du_lieu' => $service->fresh(['nguoiDung', 'chiTietDonHang.sanPham', 'nhanVienPhuTrach']),
            'data' => $service->fresh(['nguoiDung', 'chiTietDonHang.sanPham', 'nhanVienPhuTrach']),
        ]);
    }
}

