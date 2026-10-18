<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * List enabled payment methods
     */
    public function methods(): JsonResponse
    {
        $methods = PaymentMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Generate payment instruction (VietQR / MoMo / VNPay)
     */
    public function initiate(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->with(['payments', 'items'])
            ->firstOrFail();

        if ($order->status !== 'pending_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này không ở trạng thái chờ thanh toán.',
            ], 400);
        }

        $payment = $order->payments()->latest()->first();
        $methodCode = $payment?->method_code ?? 'vietqr';

        $bankInfo = [
            'bank_code' => 'MB',
            'bank_name' => 'Ngân hàng Quân Đội (MB Bank)',
            'account_number' => '0987654321',
            'account_name' => 'NGUYEN QUOC VINH',
            'amount' => (int) $order->total_amount,
            'content' => $order->order_code,
            'qr_url' => "https://img.vietqr.io/image/MB-0987654321-compact2.png?amount=" . (int)$order->total_amount . "&addInfo=" . urlencode($order->order_code) . "&accountName=" . urlencode("NGUYEN QUOC VINH")
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'order_code' => $order->order_code,
                'amount' => $order->total_amount,
                'status' => $payment?->status ?? 'pending',
                'method' => $methodCode,
                'bank_info' => $bankInfo,
                'expires_at' => $order->expires_at,
            ]
        ]);
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'order_code' => $order->order_code,
                'status' => $order->status,
                'is_paid' => in_array($order->status, ['completed', 'processing']),
            ]
        ]);
    }

    /**
     * Test / Simulation: Mock instant payment (for demo & development sandbox)
     */
    public function mockPay(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->firstOrFail();

        if ($order->status !== 'pending_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này không ở trạng thái chờ thanh toán.',
            ], 400);
        }

        DB::transaction(function () use ($order) {
            // Update payment record
            $payment = $order->payments()->latest()->first();
            if ($payment) {
                $payment->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'gateway_response' => ['simulated' => true, 'timestamp' => now()->toIso8601String()]
                ]);
            }

            // Mark accounts as sold & deliver
            $hasService = false;
            foreach ($order->items as $item) {
                if ($item->game_account_id) {
                    $acc = GameAccount::find($item->game_account_id);
                    if ($acc) {
                        $acc->update([
                            'status' => 'sold',
                            'sold_at' => now(),
                            'order_id' => $order->id,
                            'buyer_id' => $order->user_id,
                        ]);
                    }
                }
                if ($item->item_type === 'service') {
                    $hasService = true;
                }
            }

            // Update order status
            $newStatus = $hasService ? 'processing' : 'completed';
            $order->update([
                'status' => $newStatus,
                'paid_at' => now(),
            ]);

            // Update user loyalty points & total spent
            $user = $order->user;
            if ($user) {
                $user->increment('loyalty_points', (int) ($order->total_amount / 1000));
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Thanh toán thành công! Thông tin tài khoản đã được chuyển giao.',
            'data' => [
                'order_code' => $order->order_code,
                'status' => $order->fresh()->status,
            ]
        ]);
    }
}
