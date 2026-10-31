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
        $query = Order::where('order_code', $orderCode)
            ->with(['items.product', 'items.gameAccount', 'items.serviceRequest.staff', 'coupon']);

        $user = $request->user();
        if ($user && !in_array($user->role ?? '', ['SUPER_ADMIN', 'ADMIN', 'STAFF_SUPPORT', 'STAFF_SERVICE'], true)) {
            $query->where('user_id', $user->id);
        }

        $order = $query->firstOrFail();

        $data = $order->toArray();
        $isPaid = $order->isPaid();
        $data['items'] = $order->items->map(function (OrderItem $item) use ($isPaid) {
            $itemData = $item->toArray();
            if ($isPaid && $item->gameAccount) {
                $credentials = $item->gameAccount->decryptCredentials();
                $extra = is_array($credentials['extra'] ?? null) ? $credentials['extra'] : [];
                unset($extra['cost_price'], $extra['supplier'], $extra['supplier_id']);
                $itemData['credentials'] = [
                    'username' => $credentials['username'],
                    'password' => $credentials['password'],
                    'extra_info' => !empty($extra) ? $extra : null,
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
            'items.*.service_data' => 'nullable|array',
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

                $serviceData = $requestedItem['service_data'] ?? null;
                $preparedItems[] = compact('variant', 'product', 'quantity', 'unitPrice', 'lineTotal', 'accounts', 'serviceData');
                $subtotal += $lineTotal;
            }

            $coupon = null;
            $discount = 0;
            if (!empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper($validated['coupon_code']))
                    ->where('status', 'ACTIVE')
                    ->lockForUpdate()
                    ->first();
                if (!$coupon) {
                    throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá không tồn tại hoặc đã ngừng hoạt động.']]);
                }
                if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
                    throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá chưa đến ngày áp dụng (bắt đầu từ: ' . $coupon->starts_at->format('d/m/Y H:i') . ').']]);
                }
                if ($coupon->ends_at && now()->gt($coupon->ends_at)) {
                    throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá đã hết hạn sử dụng (hạn chót: ' . $coupon->ends_at->format('d/m/Y H:i') . ').']]);
                }
                if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                    throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá đã hết lượt sử dụng trên hệ thống.']]);
                }

                $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'USED')
                    ->count();
                $orderUsageCount = Order::where('coupon_id', $coupon->id)
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['PAID', 'COMPLETED', 'PROCESSING', 'DELIVERED'])
                    ->count();
                $actualUsed = max($userUsageCount, $orderUsageCount);

                if ($actualUsed >= $coupon->usage_per_user) {
                    throw ValidationException::withMessages(['coupon_code' => ['Bạn đã sử dụng mã giảm giá này rồi. Mỗi khách hàng chỉ được sử dụng ' . $coupon->usage_per_user . ' lần.']]);
                }

                if ($subtotal < $coupon->min_order_amount) {
                    throw ValidationException::withMessages(['coupon_code' => ['Đơn hàng tối thiểu ' . number_format($coupon->min_order_amount) . 'đ để sử dụng mã này.']]);
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
                    $orderItem = OrderItem::create([
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
                        'fulfillment_status' => 'PENDING',
                    ]);

                    $serviceData = $prepared['serviceData'] ?? null;
                    if ($serviceData) {
                        $loginInfo = [];
                        if (!empty($serviceData['game_username'])) $loginInfo['username'] = $serviceData['game_username'];
                        if (!empty($serviceData['game_password'])) $loginInfo['password'] = $serviceData['game_password'];
                        if (!empty($serviceData['two_factor'])) $loginInfo['two_factor'] = $serviceData['two_factor'];

                        \App\Models\ServiceRequest::create([
                            'request_code' => 'SRV-' . strtoupper(Str::random(8)),
                            'order_item_id' => $orderItem->id,
                            'user_id' => $user->id,
                            'form_data' => [
                                'server' => $serviceData['server'] ?? null,
                                'ingame_name' => $serviceData['ingame_name'] ?? null,
                                'current_rank' => $serviceData['current_rank'] ?? null,
                                'target_rank' => $serviceData['target_rank'] ?? null,
                                'target_hero' => $serviceData['target_hero'] ?? null,
                                'variant_name' => $variant->name,
                            ],
                            'game_login_enc' => !empty($loginInfo) ? \Illuminate\Support\Facades\Crypt::encryptString(json_encode($loginInfo)) : null,
                            'customer_note' => $serviceData['notes'] ?? $validated['customer_notes'] ?? null,
                            'status' => 'PENDING',
                        ]);
                    }
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

            if ($order->coupon_id) {
                CouponUsage::where('order_id', $order->id)->where('status', 'USED')->update(['status' => 'REVERTED']);
                Coupon::whereKey($order->coupon_id)->decrement('used_count');
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
