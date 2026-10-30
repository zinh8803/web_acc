<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index(): JsonResponse
    {
        $coupons = Coupon::orderByDesc('id')->get()->map(function ($coupon) {
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
            'code' => 'required|string|max:40|unique:coupons,code',
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

        $validated['code'] = strtoupper(trim($validated['code']));
        if (empty($validated['name'])) {
            $validated['name'] = $validated['code'];
        }
        $validated['usage_per_user'] = $validated['usage_per_user'] ?? 1;
        $validated['status'] = $validated['status'] ?? 'ACTIVE';

        $coupon = Coupon::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mã giảm giá thành công.',
            'data' => $coupon,
        ], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $coupon]);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:40|unique:coupons,code,' . $coupon->id,
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

        $validated['code'] = strtoupper(trim($validated['code']));
        if (empty($validated['name'])) {
            $validated['name'] = $validated['code'];
        }

        $coupon->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật mã giảm giá thành công.',
            'data' => $coupon,
        ]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        // Kiểm tra xem đã có đơn hàng sử dụng mã này chưa
        $usedInOrders = Order::where('coupon_id', $coupon->id)->exists();
        $usedInCouponUsages = CouponUsage::where('coupon_id', $coupon->id)->exists();

        if ($usedInOrders || $usedInCouponUsages) {
            // Không xóa vật lý để giữ toàn vẹn lịch sử đơn hàng, chuyển trạng thái DISABLED
            $coupon->update(['status' => 'DISABLED']);
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
