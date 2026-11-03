<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaGiamGia;
use App\Models\LichSuDungMaGiamGia;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index(): JsonResponse
    {
        $coupons = MaGiamGia::orderByDesc('id')->get()->map(function ($coupon) {
            return [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
                'min_order_amount' => (float) ($coupon->min_order_amount ?? 0),
                'max_discount_amount' => $coupon->max_discount_amount ? (float) $coupon->max_discount_amount : null,
                'usage_limit' => $coupon->usage_limit,
                'usage_per_user' => $coupon->usage_per_user ?? 1,
                'used_count' => $coupon->used_count ?? 0,
                'starts_at' => $coupon->starts_at ? $coupon->starts_at->toIso8601String() : null,
                'ends_at' => $coupon->ends_at ? $coupon->ends_at->toIso8601String() : null,
                'status' => $coupon->status,
                'created_at' => $coupon->created_at ? $coupon->created_at->toIso8601String() : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $coupons]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:40|unique:ma_giam_gia,ma_khuyen_mai',
            'name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:PERCENT,FIXED',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'nullable|in:ACTIVE,DISABLED',
        ]);

        $code = strtoupper(trim($validated['code']));
        $name = !empty($validated['name']) ? $validated['name'] : $code;
        $usagePerUser = $validated['usage_per_user'] ?? 1;
        $status = $validated['status'] ?? 'ACTIVE';

        $coupon = MaGiamGia::create([
            'ma_khuyen_mai' => $code,
            'ten_ma_giam_gia' => $name,
            'mo_ta' => $validated['description'] ?? null,
            'loai_giam_gia' => $validated['discount_type'],
            'gia_tri_giam' => $validated['discount_value'],
            'don_hang_toi_thieu' => $validated['min_order_amount'] ?? 0,
            'giam_toi_da' => $validated['max_discount_amount'] ?? null,
            'gioi_han_su_dung' => $validated['usage_limit'] ?? null,
            'gioi_han_moi_nguoi_dung' => $usagePerUser,
            'ngay_bat_dau' => $validated['starts_at'] ?? null,
            'ngay_ket_thuc' => $validated['ends_at'] ?? null,
            'trang_thai' => $status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã giảm giá thành công.',
            'data' => $coupon,
        ], 201);
    }

    public function show(MaGiamGia $coupon): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $coupon]);
    }

    public function update(Request $request, MaGiamGia $coupon): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:40|unique:ma_giam_gia,ma_khuyen_mai,' . $coupon->id,
            'name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:PERCENT,FIXED',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'status' => 'nullable|in:ACTIVE,DISABLED',
        ]);

        $code = strtoupper(trim($validated['code']));
        $name = !empty($validated['name']) ? $validated['name'] : $code;

        $coupon->update([
            'ma_khuyen_mai' => $code,
            'ten_ma_giam_gia' => $name,
            'mo_ta' => $validated['description'] ?? $coupon->mo_ta,
            'loai_giam_gia' => $validated['discount_type'] ?? $coupon->loai_giam_gia,
            'gia_tri_giam' => $validated['discount_value'] ?? $coupon->gia_tri_giam,
            'don_hang_toi_thieu' => array_key_exists('min_order_amount', $validated) ? $validated['min_order_amount'] : $coupon->don_hang_toi_thieu,
            'giam_toi_da' => array_key_exists('max_discount_amount', $validated) ? $validated['max_discount_amount'] : $coupon->giam_toi_da,
            'gioi_han_su_dung' => array_key_exists('usage_limit', $validated) ? $validated['usage_limit'] : $coupon->gioi_han_su_dung,
            'gioi_han_moi_nguoi_dung' => $validated['usage_per_user'] ?? $coupon->gioi_han_moi_nguoi_dung,
            'ngay_bat_dau' => array_key_exists('starts_at', $validated) ? $validated['starts_at'] : $coupon->ngay_bat_dau,
            'ngay_ket_thuc' => array_key_exists('ends_at', $validated) ? $validated['ends_at'] : $coupon->ngay_ket_thuc,
            'trang_thai' => $validated['status'] ?? $coupon->trang_thai,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật mã giảm giá thành công.',
            'data' => $coupon,
        ]);
    }

    public function destroy(MaGiamGia $coupon): JsonResponse
    {
        // Kiểm tra xem đã có đơn hàng sử dụng mã này chưa
        $usedInOrders = DonHang::where('ma_giam_gia_id', $coupon->id)->exists();
        $usedInCouponUsages = LichSuDungMaGiamGia::where('ma_giam_gia_id', $coupon->id)->exists();

        if ($usedInOrders || $usedInCouponUsages) {
            // Không xóa vật lý để giữ toàn vẹn lịch sử đơn hàng, chuyển trạng thái DISABLED
            $coupon->update(['trang_thai' => 'DISABLED']);
            return response()->json([
                'success' => true,
                'message' => 'Mã giảm giá đã có đơn hàng sử dụng nên đã được chuyển sang trạng thái Vô hiệu hóa (DISABLED).',
                'data' => $coupon,
            ]);
        }

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa mã giảm giá thành công.',
        ]);
    }
}
