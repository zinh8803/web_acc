<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GioHang;
use App\Models\ChiTietGioHang;
use App\Models\MaGiamGia;
use App\Models\LichSuDungMaGiamGia;
use App\Models\TaiKhoanGame;
use App\Models\BienTheSanPham;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Get user's cart.
     */
    public function show(Request $request): JsonResponse
    {
        $cart = GioHang::firstOrCreate(['nguoi_dung_id' => $request->user()->id]);
        $cart->load(['items.variant.product.primaryImage', 'items.gameAccount']);

        return $this->success($cart);
    }

    /**
     * Add item to cart. Only for ACCOUNT type products.
     */
    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:bien_the_san_pham,id',
            'game_account_id' => 'nullable|integer|exists:tai_khoan_game,id',
            'quantity' => 'sometimes|integer|min:1|max:10',
        ]);

        $variant = BienTheSanPham::with('product')->findOrFail($request->variant_id);

        if ($variant->product->status !== 'ACTIVE' || $variant->status !== 'ACTIVE') {
            return $this->error('Sản phẩm hoặc gói dịch vụ này hiện không khả dụng.', 422, 'UNAVAILABLE');
        }

        if ($variant->product->type === 'ACCOUNT') {
            $availableStock = TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count();
            if ($availableStock <= 0) {
                return $this->error('Sản phẩm tài khoản này đã hết hàng.', 422, 'OUT_OF_STOCK');
            }
        }

        $account = null;
        if (!empty($validated['game_account_id'])) {
            $account = TaiKhoanGame::whereKey($validated['game_account_id'])
                ->where('san_pham_id', $variant->san_pham_id)
                ->where('bien_the_id', $variant->id)
                ->where('trang_thai', 'AVAILABLE')
                ->first();
            if (!$account) {
                return $this->error('Tài khoản này vừa được người khác chọn hoặc đã hết hàng.', 409, 'ACCOUNT_UNAVAILABLE');
            }
        }

        $cart = GioHang::firstOrCreate(['nguoi_dung_id' => $request->user()->id]);

        $item = $account
            ? ChiTietGioHang::firstOrCreate(
                ['gio_hang_id' => $cart->id, 'tai_khoan_game_id' => $account->id],
                ['bien_the_id' => $variant->id, 'so_luong' => 1]
            )
            : ChiTietGioHang::firstOrCreate(
                ['gio_hang_id' => $cart->id, 'bien_the_id' => $variant->id, 'tai_khoan_game_id' => null],
                ['so_luong' => $validated['quantity'] ?? 1]
            );

        if (!$account && !$item->wasRecentlyCreated) {
            $item->increment('so_luong', $validated['quantity'] ?? 1);
        }

        $item->load(['variant.product.primaryImage', 'gameAccount']);

        return $this->success($item, 201);
    }

    /** Update an item quantity in the authenticated user's cart. */
    public function updateItem(Request $request, ChiTietGioHang $item): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $cart = GioHang::where('nguoi_dung_id', $request->user()->id)->firstOrFail();
        if ($item->gio_hang_id !== $cart->id) {
            return $this->error('Không tìm thấy sản phẩm trong giỏ hàng.', 404);
        }

        if ($item->tai_khoan_game_id && $validated['quantity'] !== 1) {
            return $this->error('Mỗi tài khoản game là một sản phẩm riêng, số lượng luôn là 1.', 422);
        }

        $item->update(['so_luong' => $validated['quantity']]);
        $item->load('variant.product.primaryImage');

        return $this->success($item);
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Request $request, ChiTietGioHang $item): JsonResponse
    {
        $cart = GioHang::where('nguoi_dung_id', $request->user()->id)->firstOrFail();

        if ($item->gio_hang_id !== $cart->id) {
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
        $coupon = MaGiamGia::where('ma_khuyen_mai', strtoupper($request->code))
            ->where('trang_thai', 'ACTIVE')
            ->first();

        if (!$coupon) {
            return $this->error('Mã giảm giá không hợp lệ hoặc đã hết hạn.', 422, 'COUPON_INVALID');
        }

        // Check time validity
        if ($coupon->bat_dau_luc && now()->lt($coupon->bat_dau_luc)) {
            return $this->error('Mã giảm giá chưa đến ngày áp dụng.', 422, 'COUPON_NOT_STARTED');
        }

        if ($coupon->ket_thuc_luc && now()->gt($coupon->ket_thuc_luc)) {
            return $this->error('Mã giảm giá đã hết hạn sử dụng.', 422, 'COUPON_EXPIRED');
        }

        // Check usage limit
        if ($coupon->gioi_han_tong_luot_dung && $coupon->so_luot_da_dung >= $coupon->gioi_han_tong_luot_dung) {
            return $this->error('Mã giảm giá đã hết lượt sử dụng.', 422, 'COUPON_USED_UP');
        }

        // Check per-user limit strictly (both coupon_usages and paid orders)
        $userUsageCount = LichSuDungMaGiamGia::where('ma_giam_gia_id', $coupon->id)
            ->where('nguoi_dung_id', $user->id)
            ->where('trang_thai', 'USED')
            ->count();

        $orderUsageCount = DonHang::where('ma_giam_gia_id', $coupon->id)
            ->where('nguoi_dung_id', $user->id)
            ->whereIn('trang_thai', ['PAID', 'COMPLETED', 'PROCESSING', 'DELIVERED'])
            ->count();

        $actualUsedByUser = max($userUsageCount, $orderUsageCount);

        if ($actualUsedByUser >= $coupon->gioi_han_moi_nguoi) {
            return $this->error('Bạn đã sử dụng mã giảm giá này rồi. Mỗi khách hàng chỉ được sử dụng ' . $coupon->gioi_han_moi_nguoi . ' lần.', 422, 'COUPON_LIMIT_REACHED');
        }

        // Check minimum order amount
        $orderTotal = (float) ($request->order_total ?? $request->subtotal ?? 0);
        if ($orderTotal < $coupon->gia_tri_don_toi_thieu) {
            return $this->error(
                "Đơn hàng tối thiểu " . number_format($coupon->gia_tri_don_toi_thieu) . "đ để dùng mã này.",
                422,
                'COUPON_MIN_ORDER'
            );
        }

        // Calculate discount
        $discountAmount = 0;
        if ($coupon->loai_giam_gia === 'PERCENT') {
            $discountAmount = $orderTotal * ($coupon->gia_tri_giam / 100);
            if ($coupon->giam_toi_da) {
                $discountAmount = min($discountAmount, $coupon->giam_toi_da);
            }
        } else {
            $discountAmount = min($coupon->gia_tri_giam, $orderTotal);
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
