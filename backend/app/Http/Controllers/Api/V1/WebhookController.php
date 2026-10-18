<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * VietQR / SePay / Casso bank transfer webhook
     */
    public function vietqr(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('VietQR Webhook received:', $payload);

        // Typical bank webhook contains description/content with order_code
        $content = $payload['content'] ?? $payload['description'] ?? '';
        $amount = $payload['amount'] ?? $payload['transferAmount'] ?? 0;

        // Search for order code like ORD-xxxx-xxxx in content
        if (preg_match('/ORD-[A-Z0-9-]+/', $content, $matches)) {
            $orderCode = $matches[0];
            $order = Order::where('order_code', $orderCode)
                ->where('status', 'pending_payment')
                ->first();

            if ($order && (float) $amount >= (float) $order->total_amount) {
                DB::transaction(function () use ($order, $payload) {
                    $payment = $order->payments()->latest()->first();
                    if ($payment) {
                        $payment->update([
                            'status' => 'success',
                            'paid_at' => now(),
                            'gateway_response' => $payload,
                        ]);
                    }

                    $hasService = false;
                    foreach ($order->items as $item) {
                        if ($item->game_account_id) {
                            GameAccount::where('id', $item->game_account_id)->update([
                                'status' => 'sold',
                                'sold_at' => now(),
                                'order_id' => $order->id,
                                'buyer_id' => $order->user_id,
                            ]);
                        }
                        if ($item->item_type === 'service') {
                            $hasService = true;
                        }
                    }

                    $order->update([
                        'status' => $hasService ? 'processing' : 'completed',
                        'paid_at' => now(),
                    ]);
                });

                return response()->json(['success' => true, 'message' => 'Processed successfully']);
            }
        }

        return response()->json(['success' => true, 'message' => 'Acknowledged']);
    }

    public function momo(Request $request): JsonResponse
    {
        return response()->json(['status' => 0, 'message' => 'Success']);
    }

    public function vnpay(Request $request): JsonResponse
    {
        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }
}
