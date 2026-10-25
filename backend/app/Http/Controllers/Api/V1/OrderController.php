<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items.product')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 10));

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->with(['items.product', 'items.gameAccount', 'coupon'])
            ->firstOrFail();

        $data = $order->toArray();
        $isPaid = $order->isPaid();
        $data['items'] = $order->items->map(function (OrderItem $item) use ($isPaid) {
            $itemData = $item->toArray();
            if ($isPaid && $item->gameAccount) {
                $credentials = $item->gameAccount->decryptCredentials();
                $itemData['credentials'] = [
                    'username' => $credentials['username'],
                    'password' => $credentials['password'],
                    'extra_info' => $credentials['extra'],
                ];
            }
            return $itemData;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.game_account_id' => 'nullable|integer|exists:game_accounts,id',
            'items.*.quantity' => 'required|integer|min:1|max:10',
            'payment_method_code' => 'nullable|string|max:30',
            'coupon_code' => 'nullable|string|max:40',
            'customer_notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $order = DB::transaction(function () use ($validated, $user, $request) {
            $preparedItems = [];
            $subtotal = 0;

            foreach ($validated['items'] as $requestedItem) {
                $variant = ProductVariant::with('product')->lockForUpdate()->findOrFail($requestedItem['variant_id']);
                $product = $variant->product;
                if (!$product || $product->status !== 'ACTIVE' || $variant->status !== 'ACTIVE') {
                    throw ValidationException::withMessages(['items' => ['Một sản phẩm trong giỏ không còn khả dụng.']]);
                }

                $quantity = $requestedItem['quantity'];
                $unitPrice = $variant->sale_price ?? $variant->price;
                $lineTotal = (int) $unitPrice * $quantity;
                $accounts = collect();

                if ($product->type === 'ACCOUNT') {
                    if (!empty($requestedItem['game_account_id'])) {
                        if ($quantity !== 1) {
                            throw ValidationException::withMessages(['items' => ['Mỗi tài khoản được bán riêng lẻ, số lượng phải bằng 1.']]);
                        }
                        $accounts = GameAccount::whereKey($requestedItem['game_account_id'])
                            ->where('variant_id', $variant->id)
                            ->where('product_id', $product->id)
                            ->where('status', 'AVAILABLE')
                            ->lockForUpdate()
                            ->get();
                    } else {
                        $accounts = GameAccount::where('variant_id', $variant->id)
                            ->where('status', 'AVAILABLE')
                            ->lockForUpdate()
                            ->limit($quantity)
                            ->get();
                    }
                    if ($accounts->count() < $quantity) {
                        throw ValidationException::withMessages(['items' => ["Sản phẩm '{$product->name}' không đủ hàng trong kho."]]);
                    }
                }

                $preparedItems[] = compact('variant', 'product', 'quantity', 'unitPrice', 'lineTotal', 'accounts');
                $subtotal += $lineTotal;
            }

            $coupon = null;
            $discount = 0;
            if (!empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper($validated['coupon_code']))
                    ->where('status', 'ACTIVE')
                    ->lockForUpdate()
                    ->first();
                if (!$coupon || ($coupon->starts_at && $coupon->starts_at->isFuture()) || ($coupon->ends_at && $coupon->ends_at->isPast())) {
                    throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá không hợp lệ hoặc đã hết hạn.']]);
                }
                if (($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit)
                    || CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $user->id)->where('status', 'USED')->count() >= $coupon->usage_per_user
                    || $subtotal < $coupon->min_order_amount) {
                    throw ValidationException::withMessages(['coupon_code' => ['Đơn hàng không đủ điều kiện sử dụng mã giảm giá này.']]);
                }
                $discount = $coupon->discount_type === 'PERCENT'
                    ? (int) round(min($subtotal * $coupon->discount_value / 100, $coupon->max_discount_amount ?: $subtotal))
                    : (int) min($coupon->discount_value, $subtotal);
            }

            $order = Order::create([
                'order_code' => 'ORD-' . strtoupper(Str::random(12)),
                'user_id' => $user->id,
                'status' => 'PENDING_PAYMENT',
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'total' => max(0, $subtotal - $discount),
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'payment_method_code' => $validated['payment_method_code'] ?? 'vietqr',
                'customer_note' => $validated['customer_notes'] ?? null,
                'expires_at' => now()->addMinutes(30),
                'ip_address' => $request->ip(),
            ]);

            foreach ($preparedItems as $prepared) {
                $product = $prepared['product'];
                $variant = $prepared['variant'];
                if ($product->type === 'ACCOUNT') {
                    foreach ($prepared['accounts'] as $account) {
                        $item = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'variant_id' => $variant->id,
                            'game_account_id' => $account->id,
                            'item_type' => 'ACCOUNT',
                            'product_name' => $product->name,
                            'variant_name' => $variant->name,
                            'thumbnail_url' => $account->image_url ?: $product->thumbnail_url,
                            'unit_price' => $prepared['unitPrice'],
                            'quantity' => 1,
                            'line_total' => $prepared['unitPrice'],
                            'warranty_policy_id' => $product->warranty_policy_id,
                            'warranty_expires_at' => $product->warrantyPolicy?->duration_days
                                ? now()->addDays($product->warrantyPolicy->duration_days)
                                : null,
                        ]);
                        $account->update([
                            'status' => 'RESERVED',
                            'reserved_by_order_id' => $order->id,
                            'reserved_until' => $order->expires_at,
                        ]);
                    }
                    $variant->update([
                        'stock_quantity' => GameAccount::where('variant_id', $variant->id)->where('status', 'AVAILABLE')->count(),
                    ]);
                } else {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'item_type' => 'SERVICE',
                        'product_name' => $product->name,
                        'variant_name' => $variant->name,
                        'thumbnail_url' => $product->thumbnail_url,
                        'unit_price' => $prepared['unitPrice'],
                        'quantity' => $prepared['quantity'],
                        'line_total' => $prepared['lineTotal'],
                        'warranty_policy_id' => $product->warranty_policy_id,
                    ]);
                }
            }

            if ($coupon) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discount,
                ]);
                $coupon->increment('used_count');
            }

            Cart::where('user_id', $user->id)->first()?->items()->delete();
            return $order->load('items');
        });

        return response()->json([
            'success' => true,
            'message' => 'Đặt hàng thành công. Vui lòng thanh toán.',
            'data' => [
                'order' => $order,
                'payment' => ['amount' => $order->total, 'method_code' => $order->payment_method_code],
            ],
        ], 201);
    }

    public function cancel(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->where('status', 'PENDING_PAYMENT')
            ->with('items')
            ->firstOrFail();

        DB::transaction(function () use ($order, $request) {
            $order->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancel_reason' => $request->input('reason'),
            ]);
            $reservedItems = $order->items->whereNotNull('game_account_id');
            GameAccount::whereIn('id', $reservedItems->pluck('game_account_id'))
                ->where('status', 'RESERVED')
                ->update(['status' => 'AVAILABLE', 'reserved_by_order_id' => null, 'reserved_until' => null]);
            foreach ($reservedItems->groupBy('variant_id') as $variantId => $items) {
                $availableCount = GameAccount::where('variant_id', $variantId)->where('status', 'AVAILABLE')->count();
                \App\Models\ProductVariant::whereKey($variantId)->update(['stock_quantity' => $availableCount]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Đã huỷ đơn hàng thành công.']);
    }

    public function viewCredentials(Request $request, string $orderCode, OrderItem $item): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password_hash)) {
            return response()->json(['success' => false, 'error' => ['message' => 'Mật khẩu không chính xác.']], 422);
        }

        $order = Order::where('order_code', $orderCode)->where('user_id', $user->id)->firstOrFail();
        abort_unless($order->isPaid() && $item->order_id === $order->id && $item->game_account_id, 404);
        $credentials = GameAccount::findOrFail($item->game_account_id)->decryptCredentials();

        return response()->json(['success' => true, 'data' => [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'extra_info' => $credentials['extra'],
        ]]);
    }
}
