<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\YeuCauBaoHanh;
use App\Models\ChinhSachBaoHanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class YeuCauBaoHanhController extends Controller
{
    /**
     * Danh sách khiếu nại bảo hành của người dùng.
     */
    public function danhSach(Request $request): JsonResponse
    {
        $claims = YeuCauBaoHanh::where('nguoi_dung_id', $request->user()->id)
            ->with(['chiTietDonHang.donHang', 'chiTietDonHang.sanPham', 'chinhSach', 'nhanVienPhuTrach'])
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        $claims->getCollection()->transform(function ($item) {
            $arr = $item->toArray();
            $arr['claim_code'] = $item->ma_yeu_cau_bao_hanh;
            $arr['status'] = $item->trang_thai;
            $arr['description'] = $item->mo_ta_su_co;
            $arr['order_item'] = $item->chiTietDonHang;
            $arr['policy'] = $item->chinhSach;
            $arr['assigned_staff'] = $item->nhanVienPhuTrach;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $claims,
            'data' => $claims,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->danhSach($request);
    }

    /**
     * Chi tiết yêu cầu bảo hành.
     */
    public function chiTiet(Request $request, string $claimCode): JsonResponse
    {
        $claim = YeuCauBaoHanh::where(function ($q) use ($claimCode) {
                $q->where('ma_yeu_cau_bao_hanh', $claimCode)->orWhere('id', $claimCode);
            })
            ->where('nguoi_dung_id', $request->user()->id)
            ->with(['chiTietDonHang.donHang', 'chiTietDonHang.sanPham', 'chiTietDonHang.taiKhoanGame', 'chinhSach', 'nhanVienPhuTrach'])
            ->firstOrFail();

        $arr = $claim->toArray();
        $arr['claim_code'] = $claim->ma_yeu_cau_bao_hanh;
        $arr['status'] = $claim->trang_thai;
        $arr['description'] = $claim->mo_ta_su_co;
        $arr['order_item'] = $claim->chiTietDonHang;
        $arr['policy'] = $claim->chinhSach;
        $arr['assigned_staff'] = $claim->nhanVienPhuTrach;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $arr,
            'data' => $arr,
        ]);
    }

    public function show(Request $request, string $claimCode): JsonResponse
    {
        return $this->chiTiet($request, $claimCode);
    }

    /**
     * Gửi yêu cầu bảo hành.
     */
    public function guiYeuCau(Request $request): JsonResponse
    {
        $chiTietDonHangId = $request->chi_tiet_don_hang_id ?? $request->order_item_id;
        $moTaSuCo = $request->mo_ta_su_co ?? $request->description;
        $loaiYeuCau = $request->loai_yeu_cau ?? $request->claim_type ?? $request->reason ?? 'OTHER';

        if (!$chiTietDonHangId) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Vui lòng chọn sản phẩm cần bảo hành.'], 422);
        }

        if (!$moTaSuCo || mb_strlen($moTaSuCo) < 5) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Vui lòng nhập chi tiết sự cố gặp phải.'], 422);
        }

        $item = ChiTietDonHang::with(['donHang', 'sanPham.chinhSachBaoHanh', 'chinhSachBaoHanh'])->findOrFail($chiTietDonHangId);

        if ($item->donHang->nguoi_dung_id !== $request->user()->id) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Bạn không có quyền khiếu nại sản phẩm này.'], 403);
        }

        if (!$item->donHang->isPaid()) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Đơn hàng chưa được thanh toán.'], 422);
        }

        $dangXuLy = YeuCauBaoHanh::where('chi_tiet_don_hang_id', $item->id)
            ->whereIn('trang_thai', ['SUBMITTED', 'IN_REVIEW', 'NEED_INFO'])
            ->first();

        if ($dangXuLy) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Sản phẩm này đang có yêu cầu bảo hành đang được xử lý (Mã: ' . $dangXuLy->ma_yeu_cau_bao_hanh . ').'
            ], 422);
        }

        if ($item->het_han_bao_hanh_luc && now()->gt($item->het_han_bao_hanh_luc)) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Sản phẩm đã hết thời hạn bảo hành.'], 422);
        }

        $policy = $item->chinhSachBaoHanh ?: $item->sanPham?->chinhSachBaoHanh;
        if (!$policy) {
            $defaultCode = $item->loai_muc === 'SERVICE' ? 'WAR-SRV-COMPLETION' : 'WAR-ACC-LIFETIME';
            $policy = ChinhSachBaoHanh::where('ma_chinh_sach', $defaultCode)->first() ?: ChinhSachBaoHanh::first();
        }

        $validEnums = ['LOSS_ACCESS', 'RECOVERED_BY_OLD_OWNER', 'WRONG_CREDENTIALS', 'SERVICE_NOT_DONE', 'OTHER'];
        if (in_array($loaiYeuCau, $validEnums, true)) {
            $claimType = $loaiYeuCau;
        } elseif (str_contains(mb_strtolower($loaiYeuCau), 'sai mật khẩu') || str_contains(mb_strtolower($loaiYeuCau), 'sai pass')) {
            $claimType = 'WRONG_CREDENTIALS';
        } elseif (str_contains(mb_strtolower($loaiYeuCau), 'back') || str_contains(mb_strtolower($loaiYeuCau), 'đổi thông tin')) {
            $claimType = 'RECOVERED_BY_OLD_OWNER';
        } elseif (str_contains(mb_strtolower($loaiYeuCau), 'mất') || str_contains(mb_strtolower($loaiYeuCau), 'truy cập')) {
            $claimType = 'LOSS_ACCESS';
        } elseif (str_contains(mb_strtolower($loaiYeuCau), 'cày') || str_contains(mb_strtolower($loaiYeuCau), 'dịch vụ')) {
            $claimType = 'SERVICE_NOT_DONE';
        } else {
            $claimType = 'OTHER';
        }

        $slaHours = $policy?->thoi_gian_xu_ly_gio ?? 24;

        $claim = YeuCauBaoHanh::create([
            'ma_yeu_cau_bao_hanh' => 'WAR-' . strtoupper(Str::random(8)),
            'chi_tiet_don_hang_id' => $item->id,
            'nguoi_dung_id' => $request->user()->id,
            'tai_khoan_game_id' => $item->tai_khoan_game_id,
            'chinh_sach_bao_hanh_id' => $policy?->id,
            'loai_yeu_cau' => $claimType,
            'mo_ta_su_co' => $moTaSuCo,
            'trang_thai' => 'SUBMITTED',
            'han_xu_ly' => now()->addHours($slaHours),
            'ngay_gui_yeu_cau' => now(),
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => "Đã gửi yêu cầu bảo hành thành công! Kỹ thuật viên sẽ hỗ trợ trong vòng {$slaHours}h.",
            'message' => 'Đã gửi yêu cầu bảo hành thành công!',
            'du_lieu' => $claim->load(['chiTietDonHang.donHang', 'chiTietDonHang.sanPham', 'chinhSach']),
            'data' => $claim,
        ], 201);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->guiYeuCau($request);
    }
}

