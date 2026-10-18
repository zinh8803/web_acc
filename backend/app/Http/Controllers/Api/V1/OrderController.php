<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Get list of user's orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product', 'payments'])
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get single order detail with delivered account credentials (if paid)
     */
    public function show(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->with(['items.product', 'items.gameAccount', 'payments.paymentMethod', 'coupon'])
            ->firstOrFail();

        $items = $order->items->map(function ($item) use ($order) {
            $data = $item->toArray();
            // Only reveal decrypted credentials if order is completed / paid
            if (in_array($order->status, ['completed', 'processing']) && $item->gameAccount) {
                $creds = $item->gameAccount->decryptCredentials();
                $data['credentials'] = [
                    'username' => $creds['username'],
                    'password' => $creds['password'],
                    'sec_email' => $creds['sec_email'],
                    'sec_phone' => $creds['sec_phone'],
                    'additional_info' => $creds['additional_info'],
                ];
            }
            return $data;
        });

        $orderData = $order->toArray();
        $orderData['items'] = $items;

        return response()->json([
            'success' => true,
            'data' => $orderData,
        ]);
    }

    /**
     * Checkout: convert cart or direct buy into order
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method_code' => 'required|string',
            'coupon_code' => 'nullable|string',
            'customer_notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($validated, $user) {
            $subtotal = 0;
            $itemsToCreate = [];

            // 1. Validate stock & lock accounts
            foreach ($validated['items'] as $reqItem) {
                $product = Product::lockForUpdate()->findOrFail($reqItem['product_id']);

                if ($product->status !== 'active') {
                    return response()->json([
                        'success' => false,
                        'message' => "Sản phẩm '{$product->name}' hiện không khả dụng."
                    ], 422);
                }

                $unitPrice = $product->sale_price ?? $product->price;
                $lineTotal = $unitPrice * $reqItem['quantity'];
                $subtotal += $lineTotal;

                // For account type product, reserve available game accounts
                $assignedAccounts = [];
                if ($product->product_type === 'account') {
                    $availableAccounts = GameAccount::where('product_id', $product->id)
                        ->where('status', 'available')
                        ->lockForUpdate()
                        ->limit($reqItem['quantity'])
                        ->get();

                    if ($availableAccounts->count() < $reqItem['quantity']) {
                        return response()->json([
                            'success' => false,
                            'message' => "Sản phẩm '{$product->name}' không đủ số lượng trong kho."
                        ], 422);
                    }

                    $assignedAccounts = $availableAccounts;
                }

                $itemsToCreate[] = [
                    'product' => $product,
                    'quantity' => $reqItem['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'accounts' => $assignedAccounts,
                ];
            }

            // 2. Validate coupon if provided
            $discountAmount = 0;
            $coupon = null;
            if (!empty($validated['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper($validated['coupon_code']))->lockForUpdate()->first();
                if ($coupon) {
                    $validation = $coupon->isValidFor($subtotal, $user->id);
                    if ($validation['valid']) {
                        $discountAmount = $coupon->calculateDiscount($subtotal);
                    }
                }
            }

            $totalAmount = max(0, $subtotal - $discountAmount);

            // 3. Create Order
            $orderCode = 'ORD-' . strtoupper(Str::random(4)) . '-' . time();
            $order = Order::create([
                'order_code' => $orderCode,
                'user_id' => $user->id,
                'status' => 'pending_payment',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'coupon_id' => $coupon?->id,
                'total_amount' => $totalAmount,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'expires_at' => now()->addMinutes(30),
            ]);

            // 4. Create OrderItems & mark GameAccounts as reserved
            foreach ($itemsToCreate as $itemData) {
                if (!empty($itemData['accounts'])) {
                    foreach ($itemData['accounts'] as $acc) {
                        $acc->update([
                            'status' => 'reserved',
                            'reserved_until' => now()->addMinutes(30),
                        ]);

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $itemData['product']->id,
                            'game_account_id' => $acc->id,
                            'item_type' => $itemData['product']->product_type,
                            'product_name' => $itemData['product']->name,
                            'quantity' => 1,
                            'unit_price' => $itemData['unit_price'],
                            'total_price' => $itemData['unit_price'],
                            'warranty_until' => now()->addDays(7),
                        ]);
                    }
                } else {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product']->id,
                        'game_account_id' => null,
                        'item_type' => $itemData['product']->product_type,
                        'product_name' => $itemData['product']->name,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total_price' => $itemData['total_price'],
                    ]);
                }
            }

            // 5. Create Payment record
            $paymentMethod = PaymentMethod::where('code', $validated['payment_method_code'])->first();
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod?->id,
                'transaction_code' => 'TXN-' . strtoupper(Str::random(10)),
                'method_code' => $validated['payment_method_code'],
                'amount' => $totalAmount,
                'fee_amount' => 0,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(30),
            ]);

            // Clear user's active cart items
            $cart = Cart::where('user_id', $user->id)->first();
            if ($cart) {
                $cart->items()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công. Vui lòng thanh toán.',
                'data' => [
                    'order' => $order->load('items'),
                    'payment' => $payment,
                ]
            ], 201);
        });
    }

    /**
     * Cancel an unpaid order
     */
    public function cancel(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending_payment')
            ->firstOrFail();

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            // Free reserved accounts
            foreach ($order->items as $item) {
                if ($item->game_account_id) {
                    GameAccount::where('id', $item->game_account_id)
                        ->where('status', 'reserved')
                        ->update(['status' => 'available', 'reserved_until' => null]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã huỷ đơn hàng thành công.'
        ]);
    }
}
