<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\YeuCauBaoHanh;
use App\Models\ChinhSachBaoHanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuanTriBaoHanhController extends Controller
{
    /**
     * Danh sách chính sách bảo hành.
     */
    public function index(): JsonResponse
    {
        $policies = ChinhSachBaoHanh::orderByDesc('id')->get()->map(function ($p) {
            $arr = $p->toArray();
            $arr['name'] = $p->ten_chinh_sach;
            $arr['product_type'] = $p->loai_san_pham;
            $arr['duration_days'] = $p->so_ngay_bao_hanh;
            $arr['is_lifetime'] = $p->bao_hanh_tron_doi;
            $arr['claim_sla_hours'] = $p->thoi_gian_xu_ly_gio;
            $arr['status'] = $p->trang_thai;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $policies,
            'data' => $policies,
        ]);
    }

    /**
     * Tạo chính sách bảo hành.
     */
    public function store(Request $request): JsonResponse
    {
        $ten = $request->ten_chinh_sach ?? $request->name ?? 'Chính sách mới';
        $loai = $request->loai_san_pham ?? $request->product_type ?? 'ACCOUNT';
        $soNgay = $request->so_ngay_bao_hanh ?? $request->duration_days;
        $tronDoi = (bool) ($request->bao_hanh_tron_doi ?? $request->is_lifetime ?? false);
        $sla = (int) ($request->thoi_gian_xu_ly_gio ?? $request->claim_sla_hours ?? 24);

        $policy = ChinhSachBaoHanh::create([
            'ma_chinh_sach' => 'WAR-' . strtoupper(Str::random(6)),
            'ten_chinh_sach' => $ten,
            'loai_san_pham' => $loai,
            'so_ngay_bao_hanh' => $soNgay,
            'bao_hanh_tron_doi' => $tronDoi,
            'pham_vi_bao_hanh' => $request->pham_vi_bao_hanh ?? $request->coverage,
            'dieu_khoan_loai_tru' => $request->dieu_khoan_loai_tru ?? $request->exclusions,
            'thoi_gian_xu_ly_gio' => $sla,
            'mo_ta' => $request->mo_ta ?? $request->description,
            'trang_thai' => $request->trang_thai ?? $request->status ?? 'ACTIVE',
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Tạo chính sách bảo hành thành công.',
            'du_lieu' => $policy,
            'data' => $policy,
        ], 201);
    }

    /**
     * Cập nhật chính sách bảo hành.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $policy = ChinhSachBaoHanh::findOrFail($id);
        $updateData = [];

        if ($request->has('ten_chinh_sach') || $request->has('name')) {
            $updateData['ten_chinh_sach'] = $request->ten_chinh_sach ?? $request->name;
        }
        if ($request->has('loai_san_pham') || $request->has('product_type')) {
            $updateData['loai_san_pham'] = $request->loai_san_pham ?? $request->product_type;
        }
        if ($request->has('so_ngay_bao_hanh') || $request->has('duration_days')) {
            $updateData['so_ngay_bao_hanh'] = $request->so_ngay_bao_hanh ?? $request->duration_days;
        }
        if ($request->has('bao_hanh_tron_doi') || $request->has('is_lifetime')) {
            $updateData['bao_hanh_tron_doi'] = (bool) ($request->bao_hanh_tron_doi ?? $request->is_lifetime);
        }
        if ($request->has('thoi_gian_xu_ly_gio') || $request->has('claim_sla_hours')) {
            $updateData['thoi_gian_xu_ly_gio'] = (int) ($request->thoi_gian_xu_ly_gio ?? $request->claim_sla_hours);
        }
        if ($request->has('mo_ta') || $request->has('description')) {
            $updateData['mo_ta'] = $request->mo_ta ?? $request->description;
        }
        if ($request->has('trang_thai') || $request->has('status')) {
            $updateData['trang_thai'] = $request->trang_thai ?? $request->status;
        }

        $policy->update($updateData);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Cập nhật chính sách thành công.',
            'du_lieu' => $policy,
            'data' => $policy,
        ]);
    }

    /**
     * Xóa chính sách.
     */
    public function destroy(int $id): JsonResponse
    {
        $policy = ChinhSachBaoHanh::findOrFail($id);
        $policy->delete();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã xóa chính sách bảo hành.',
            'message' => 'Đã xoá chính sách bảo hành.',
        ]);
    }

    /**
     * Quản lý khiếu nại bảo hành.
     */
    public function claims(Request $request): JsonResponse
    {
        $query = YeuCauBaoHanh::with([
            'nguoiDung',
            'chiTietDonHang.donHang',
            'chiTietDonHang.sanPham',
            'chinhSach',
            'nhanVienPhuTrach',
            'nguoiDuyet'
        ])->orderByDesc('id');

        $status = $request->trang_thai ?? $request->status;
        if ($status) {
            $query->where('trang_thai', strtoupper($status));
        }

        $search = $request->tu_khoa ?? $request->search;
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ma_yeu_cau_bao_hanh', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('nguoiDung', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%")
                         ->orWhere('ho_ten', 'like', "%{$search}%");
                  });
            });
        }

        $claims = $query->paginate($request->integer('per_page', 15));

        $claims->getCollection()->transform(function ($item) {
            $arr = $item->toArray();
            $arr['claim_code'] = $item->ma_yeu_cau_bao_hanh;
            $arr['status'] = $item->trang_thai;
            $arr['description'] = $item->mo_ta_su_co;
            $arr['order_item'] = $item->chiTietDonHang;
            $arr['policy'] = $item->chinhSach;
            $arr['user'] = $item->nguoiDung;
            $arr['assigned_staff'] = $item->nhanVienPhuTrach;
            $arr['reviewed_by'] = $item->nguoiDuyet;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $claims,
            'data' => $claims,
        ]);
    }

    /**
     * Chi tiết khiếu nại bảo hành.
     */
    public function showClaim(string $claimId): JsonResponse
    {
        $claim = YeuCauBaoHanh::where('id', $claimId)
            ->orWhere('ma_yeu_cau_bao_hanh', $claimId)
            ->with([
                'nguoiDung',
                'chiTietDonHang.donHang',
                'chiTietDonHang.sanPham',
                'chiTietDonHang.taiKhoanGame',
                'chinhSach',
                'nhanVienPhuTrach',
                'nguoiDuyet'
            ])
            ->firstOrFail();

        $arr = $claim->toArray();
        $arr['claim_code'] = $claim->ma_yeu_cau_bao_hanh;
        $arr['status'] = $claim->trang_thai;
        $arr['description'] = $claim->mo_ta_su_co;
        $arr['order_item'] = $claim->chiTietDonHang;
        $arr['policy'] = $claim->chinhSach;
        $arr['user'] = $claim->nguoiDung;
        $arr['assigned_staff'] = $claim->nhanVienPhuTrach;
        $arr['reviewed_by'] = $claim->nguoiDuyet;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $arr,
            'data' => $arr,
        ]);
    }

    /**
     * Quyết định xử lý khiếu nại bảo hành.
     */
    public function decide(Request $request, string $claimId): JsonResponse
    {
        $claim = YeuCauBaoHanh::where('id', $claimId)
            ->orWhere('ma_yeu_cau_bao_hanh', $claimId)
            ->firstOrFail();

        $status = strtoupper($request->trang_thai ?? $request->status ?? '');
        if (!$status) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Vui lòng chọn trạng thái.'], 422);
        }

        $updateData = [
            'trang_thai' => $status,
            'hinh_thuc_giai_quyet' => $request->hinh_thuc_giai_quyet ?? $request->resolution_type ?? $claim->hinh_thuc_giai_quyet,
            'ghi_chu_giai_quyet' => $request->ghi_chu_giai_quyet ?? $request->resolution_note ?? $claim->ghi_chu_giai_quyet,
            'ly_do_tu_choi' => $request->ly_do_tu_choi ?? $request->reject_reason ?? $claim->ly_do_tu_choi,
            'nguoi_duyet_id' => $request->user()?->id,
        ];

        $assignedTo = $request->nhan_vien_phu_trach_id ?? $request->assigned_to;
        if ($assignedTo !== null) {
            $updateData['nhan_vien_phu_trach_id'] = $assignedTo;
        }

        if (in_array($status, ['APPROVED', 'RESOLVED', 'REJECTED', 'CANCELLED'], true)) {
            $updateData['ngay_giai_quyet'] = now();
        }

        $claim->update($updateData);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã cập nhật xử lý khiếu nại bảo hành thành công!',
            'message' => 'Đã cập nhật xử lý khiếu nại bảo hành thành công!',
            'du_lieu' => $claim->fresh(['nguoiDung', 'chiTietDonHang.donHang', 'chiTietDonHang.sanPham', 'chinhSach', 'nhanVienPhuTrach', 'nguoiDuyet']),
            'data' => $claim,
        ]);
    }
}

