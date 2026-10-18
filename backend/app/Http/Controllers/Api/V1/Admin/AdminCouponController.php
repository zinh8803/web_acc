<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Coupon::orderByDesc('id')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code|max:50',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ]);
        $validated['code'] = strtoupper($validated['code']);
        $coupon = Coupon::create($validated);
        return response()->json(['success' => true, 'data' => $coupon], 201);
    }
}
