<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\YeuCauBaoHanh;
use App\Models\ChinhSachBaoHanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarrantyClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $claims = YeuCauBaoHanh::where('nguoi_dung_id', $request->user()->id)
            ->with(['orderItem.order', 'orderItem.product', 'policy', 'assignedStaff'])
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $claims,
        ]);
    }

    public function show(Request $request, string $claimCode): JsonResponse
    {
        $claim = YeuCauBaoHanh::where(function ($q) use ($claimCode) {
                $q->where('ma_yeu_cau_bao_hanh', $claimCode)->orWhere('id', $claimCode);
            })
            ->where('nguoi_dung_id', $request->user()->id)
            ->with(['orderItem.order', 'orderItem.product', 'orderItem.gameAccount', 'policy', 'assignedStaff'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $claim,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_item_id' => 'required|exists:chi_tiet_don_hang,id',
            'claim_type' => 'nullable|string',
            'reason' => 'nullable|string',
            'description' => 'required|string|max:2000',
        ]);

        $item = ChiTietDonHang::with(['order', 'product.warrantyPolicy', 'warrantyPolicy'])->findOrFail($validated['order_item_id']);

        if ($item->order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền khiếu nại sản phẩm này.'], 403);
        }

        if (!$item->order->isPaid()) {
            return response()->json(['success' => false, 'message' => 'Đơn hàng chưa thanh toán thành công.'], 422);
        }

        // Kiểm tra xem đã có khiếu nại đang xử lý cho sản phẩm này chưa
        $existingPendingClaim = YeuCauBaoHanh::where('chi_tiet_don_hang_id', $item->id)
            ->whereIn('trang_thai', ['SUBMITTED', 'IN_REVIEW', 'NEED_INFO'])
            ->first();
        if ($existingPendingClaim) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm này đang có một yêu cầu bảo hành đang được xử lý (Mã: ' . ($existingPendingClaim->ma_yeu_cau_bao_hanh ?: $existingPendingClaim->claim_code) . ').'
            ], 422);
        }

        // Kiểm tra thời hạn bảo hành
        if ($item->het_han_bao_hanh_luc && now()->gt($item->het_han_bao_hanh_luc)) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm đã hết thời hạn bảo hành.'], 422);
        }

        // Lấy Policy
        $policy = $item->warrantyPolicy ?: $item->product?->warrantyPolicy;
        if (!$policy) {
            $defaultCode = $item->loai_muc === 'SERVICE' ? 'WAR-SRV-COMPLETION' : 'WAR-ACC-LIFETIME';
            $policy = ChinhSachBaoHanh::where('ma_chinh_sach', $defaultCode)->first();
            if (!$policy) {
                $policy = ChinhSachBaoHanh::first();
            }
        }

        // Chuẩn hoá claim_type enum
        $rawType = $validated['claim_type'] ?? $validated['reason'] ?? 'OTHER';
        $validEnums = ['LOSS_ACCESS', 'RECOVERED_BY_OLD_OWNER', 'WRONG_CREDENTIALS', 'SERVICE_NOT_DONE', 'OTHER'];

        if (in_array($rawType, $validEnums, true)) {
            $claimType = $rawType;
        } elseif (str_contains(mb_strtolower($rawType), 'sai mật khẩu') || str_contains(mb_strtolower($rawType), 'sai pass')) {
            $claimType = 'WRONG_CREDENTIALS';
        } elseif (str_contains(mb_strtolower($rawType), 'back') || str_contains(mb_strtolower($rawType), 'đổi thông tin')) {
            $claimType = 'RECOVERED_BY_OLD_OWNER';
        } elseif (str_contains(mb_strtolower($rawType), 'mất') || str_contains(mb_strtolower($rawType), 'truy cập')) {
            $claimType = 'LOSS_ACCESS';
        } elseif (str_contains(mb_strtolower($rawType), 'cày') || str_contains(mb_strtolower($rawType), 'dịch vụ')) {
            $claimType = 'SERVICE_NOT_DONE';
        } else {
            $claimType = 'OTHER';
        }

        $slaHours = $policy?->thoi_gian_xu_ly_gio ?? $policy?->claim_sla_hours ?? 24;

        $claim = YeuCauBaoHanh::create([
            'ma_yeu_cau_bao_hanh' => 'WAR-' . strtoupper(Str::random(8)),
            'chi_tiet_don_hang_id' => $item->id,
            'nguoi_dung_id' => $request->user()->id,
            'tai_khoan_game_id' => $item->tai_khoan_game_id,
            'chinh_sach_bao_hanh_id' => $policy?->id,
            'loai_yeu_cau' => $claimType,
            'mo_ta_su_co' => $validated['description'],
            'trang_thai' => 'SUBMITTED',
            'han_xu_ly' => now()->addHours($slaHours),
            'ngay_gui_yeu_cau' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu bảo hành thành công! Kỹ thuật viên sẽ xử lý trong vòng ' . $slaHours . 'h.',
            'data' => $claim->load(['orderItem.order', 'orderItem.product', 'policy']),
        ], 201);
    }
}
