<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\GameAccount;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Get user's cart.
     */
    public function show(Request $request): JsonResponse
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $cart->load(['items.variant.product.primaryImage', 'items.variant.product.game']);

        return $this->success($cart);
    }

    /**
     * Add item to cart. Only for ACCOUNT type products.
     */
    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($request->variant_id);

        if ($variant->product->type !== 'ACCOUNT') {
            return $this->error('Sản phẩm dịch vụ không thể thêm vào giỏ hàng. Vui lòng mua ngay.', 422, 'SERVICE_NO_CART');
        }

        if ($variant->stock_quantity <= 0) {
            return $this->error('Sản phẩm này đã hết hàng.', 422, 'OUT_OF_STOCK');
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $item = CartItem::firstOrCreate(
            ['cart_id' => $cart->id, 'variant_id' => $request->variant_id],
            ['quantity' => 1]
        );

        $item->load('variant.product.primaryImage');

        return $this->success($item, 201);
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Request $request, CartItem $item): JsonResponse
    {
        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        if ($item->cart_id !== $cart->id) {
            return $this->error('Không tìm thấy sản phẩm trong giỏ hàng.', 404);
        }

        $item->delete();

        return $this->success(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng.']);
    }

    /**
     * Validate coupon code.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:40',
            'order_total' => 'required|numeric|min:0',
        ]);

        $user = $request->user();
        $coupon = Coupon::where('code', strtoupper($request->code))
            ->where('status', 'ACTIVE')
            ->first();

        if (!$coupon) {
            return $this->error('Mã giảm giá không hợp lệ hoặc đã hết hạn.', 422, 'COUPON_INVALID');
        }

        // Check time validity
        if ($coupon->starts_at && $coupon->starts_at > now()) {
            return $this->error('Mã giảm giá chưa có hiệu lực.', 422, 'COUPON_NOT_STARTED');
        }

        if ($coupon->ends_at && $coupon->ends_at < now()) {
            return $this->error('Mã giảm giá đã hết hạn.', 422, 'COUPON_EXPIRED');
        }

        // Check usage limit
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return $this->error('Mã giảm giá đã hết lượt sử dụng.', 422, 'COUPON_USED_UP');
        }

        // Check per-user limit
        $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->where('status', 'USED')
            ->count();

        if ($userUsageCount >= $coupon->usage_per_user) {
            return $this->error('Bạn đã sử dụng hết lượt dùng của mã này.', 422, 'COUPON_LIMIT_REACHED');
        }

        // Check minimum order amount
        $orderTotal = (float) ($request->order_total ?? $request->subtotal ?? 0);
        if ($orderTotal < $coupon->min_order_amount) {
            return $this->error(
                "Đơn hàng tối thiểu " . number_format($coupon->min_order_amount) . "đ để dùng mã này.",
                422,
                'COUPON_MIN_ORDER'
            );
        }

        // Calculate discount
        $discountAmount = 0;
        if ($coupon->discount_type === 'PERCENT') {
            $discountAmount = $orderTotal * ($coupon->discount_value / 100);
            if ($coupon->max_discount_amount) {
                $discountAmount = min($discountAmount, $coupon->max_discount_amount);
            }
        } else {
            $discountAmount = min($coupon->discount_value, $orderTotal);
        }

        return $this->success([
            'discount_amount' => (int) $discountAmount,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'max_discount_amount' => $coupon->max_discount_amount,
            ],
        ]);
    }

    private function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    private function error(string $message, int $status = 400, string $code = 'ERROR'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }
}
