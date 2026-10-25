<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GameAccount;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * List enabled payment methods
     */
    public function methods(): JsonResponse
    {
        $methods = [
            ['code' => 'vietqr', 'name' => 'Chuyển khoản ngân hàng (VietQR)'],
            ['code' => 'vnpay', 'name' => 'Cổng thanh toán VNPay', 'provider' => 'VNPay'],
            ['code' => 'zalopay', 'name' => 'Ví điện tử ZaloPay', 'provider' => 'ZaloPay'],
        ];

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
            ->with(['items'])
            ->firstOrFail();

        if ($order->status !== 'PENDING_PAYMENT') {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này không ở trạng thái chờ thanh toán.',
            ], 400);
        }

        $methodCode = $order->payment_method_code ?? 'vietqr';

        if (in_array($methodCode, ['vnpay', 'zalopay'], true)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'order_code' => $order->order_code,
                    'amount' => $order->total,
                    'status' => $order->status,
                    'method' => $methodCode,
                    'start_payment' => true,
                    'expires_at' => $order->expires_at,
                ],
            ]);
        }

        $bankInfo = [
            'bank_code' => 'MB',
            'bank_name' => 'Ngân hàng Quân Đội (MB Bank)',
            'account_number' => '0987654321',
            'account_name' => 'NGUYEN QUOC VINH',
            'amount' => (int) $order->total,
            'content' => $order->order_code,
            'qr_url' => "https://img.vietqr.io/image/MB-0987654321-compact2.png?amount=" . (int)$order->total . "&addInfo=" . urlencode($order->order_code) . "&accountName=" . urlencode("NGUYEN QUOC VINH")
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'order_code' => $order->order_code,
                'amount' => $order->total,
                'status' => $order->status,
                'method' => $methodCode,
                'bank_info' => $bankInfo,
                'expires_at' => $order->expires_at,
            ]
        ]);
    }

    public function createVnPayPayment(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        if ($order->payment_method_code !== 'vnpay' || $order->status !== 'PENDING_PAYMENT') {
            return response()->json(['success' => false, 'message' => 'Đơn hàng không thể thanh toán qua VNPay.'], 422);
        }

        $tmnCode = config('vnpay.vnp_TmnCode');
        $hashSecret = config('vnpay.vnp_HashSecret');
        $vnpUrl = config('vnpay.vnp_Url');
        $returnUrl = config('vnpay.vnp_Returnurl');
        if (!$tmnCode || !$hashSecret || !$vnpUrl || !$returnUrl) {
            Log::error('VNPay configuration invalid');
            return response()->json(['success' => false, 'message' => 'Cấu hình VNPay chưa đầy đủ.'], 503);
        }

        $orderCode = $order->order_code;
        $txnRef = $order->payment_reference;
        if (!$txnRef || !str_starts_with($txnRef, 'SP')) {
            do {
                $txnRef = 'SP' . random_int(100000, 999999);
            } while (Order::where('payment_reference', $txnRef)->exists());
            $order->update(['payment_reference' => $txnRef]);
        }

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) $order->total * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $request->ip(),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toán đơn hàng ' . $txnRef,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_TxnRef' => $txnRef,
        ];
        ksort($inputData);
        $query = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $query, $hashSecret);

        return response()->json([
            'success' => true,
            'data' => ['payment_url' => $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash],
        ]);
    }

    public function vnpayReturn(Request $request)
    {
        $inputData = $request->all();
        $receivedHash = (string) $request->query('vnp_SecureHash', '');
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);
        $hashData = '';
        $separator = '';
        foreach ($inputData as $key => $value) {
            $hashData .= $separator . urlencode($key) . '=' . urlencode((string) $value);
            $separator = '&';
        }
        $hashSecret = (string) config('vnpay.vnp_HashSecret', '');
        $expectedHash = $hashSecret !== '' ? hash_hmac('sha512', $hashData, $hashSecret) : '';
        $txnRef = (string) $request->query('vnp_TxnRef', '');
        $order = Order::where('payment_reference', $txnRef)->first();
        $orderCode = $order?->order_code ?? '';
        $validSignature = $expectedHash !== '' && $receivedHash !== '' && hash_equals($expectedHash, $receivedHash);
        $validPayment = $validSignature
            && $order
            && $order->payment_method_code === 'vnpay'
            && (string) $request->query('vnp_ResponseCode') === '00'
            && (string) $request->query('vnp_TransactionStatus') === '00'
            && (int) $request->query('vnp_Amount') === (int) $order->total * 100;

        if ($validPayment && $order->status === 'PENDING_PAYMENT') {
            $this->settleOrder($order);
            $order->update(['payment_reference' => (string) $request->query('vnp_TransactionNo', '')]);
        }
        $result = $validPayment && $order->refresh()->isPaid() ? 'success' : 'fail';
        if ($validSignature && $order && $result === 'fail' && $order->status === 'PENDING_PAYMENT') {
            $order->update(['payment_reference' => null]);
        }
        $frontend = rtrim((string) config('vnpay.frontend_url'), '/');
        if ($validSignature) {
            $returnData = $request->all();
            $returnData['order_code'] = $orderCode;
            $returnData['status'] = $result;
            return redirect($frontend . '?' . http_build_query($returnData));
        }
        return redirect($frontend . '?status=fail&message=' . urlencode('Chữ ký không hợp lệ') . '&order_code=' . urlencode($orderCode));
    }

    public function initiateZaloPayPayment(Request $request, string $orderCode): JsonResponse
    {
        $order = Order::where('order_code', $orderCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        if ($order->payment_method_code !== 'zalopay' || $order->status !== 'PENDING_PAYMENT') {
            return response()->json(['success' => false, 'message' => 'Đơn hàng không thể thanh toán qua ZaloPay.'], 422);
        }

        $result = $this->createZaloPayPayment([
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'amount' => (int) $order->total,
        ]);
        $httpStatus = $result['_http_status'] ?? 502;
        unset($result['_http_status']);
        return response()->json($result, $httpStatus);
    }

    private function createZaloPayPayment(array $validated): array
    {
        $orderCode = $validated['order_code'] ?? '';
        if (empty($orderCode)) {
            $orderCode = 'SP' . random_int(100000, 999999);
        }

        $config = config('zalopay');
        foreach (['appid', 'key1', 'key2', 'endpoint', 'callback_url', 'return_url', 'frontend_url'] as $key) {
            if (empty($config[$key])) {
                Log::error('ZaloPay configuration invalid', ['missing' => $key]);
                return ['success' => false, 'message' => 'Cấu hình ZaloPay chưa đầy đủ.', '_http_status' => 503];
            }
        }

        $items = json_encode([]);
        $embeddata = json_encode([
            'order_code' => $orderCode,
            'redirecturl' => rtrim($config['return_url'], '/') . '?order_code=' . urlencode($orderCode),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $transID = random_int(0, 1000000);
        $appTransId = now('Asia/Ho_Chi_Minh')->format('ymd') . '_' . $transID;
        while (Order::where('payment_reference', $appTransId)->exists()) {
            $transID = random_int(0, 1000000);
            $appTransId = now('Asia/Ho_Chi_Minh')->format('ymd') . '_' . $transID;
        }

        $zaloOrder = [
            'app_id' => (int) $config['appid'],
            'app_time' => (int) round(microtime(true) * 1000),
            'app_trans_id' => $appTransId,
            'app_user' => 'demo',
            'item' => $items,
            'embed_data' => $embeddata,
            'amount' => (int) $validated['amount'],
            'description' => 'ZaloPay Integration Demo - ' . $orderCode,
            'bank_code' => '',
            'callback_url' => $config['callback_url'],
            'expire_duration_seconds' => 1800,
        ];
        $macData = $zaloOrder['app_id'] . '|' . $zaloOrder['app_trans_id'] . '|' . $zaloOrder['app_user'] . '|' . $zaloOrder['amount']
            . '|' . $zaloOrder['app_time'] . '|' . $zaloOrder['embed_data'] . '|' . $zaloOrder['item'];
        $zaloOrder['mac'] = hash_hmac('sha256', $macData, $config['key1']);

        $order = Order::find($validated['order_id'] ?? 0);
        if ($order) {
            $order->update(['payment_reference' => $appTransId]);
        }
        $zaloUrl = rtrim($config['endpoint'], '/') . '/v2/create';

        try {
            $response = Http::asForm()->timeout(20)->post($zaloUrl, $zaloOrder);
            $result = $response->json();
            if (!$response->successful()) {
                $result = null;
            }
        } catch (\Throwable $exception) {
            if ($order) {
                Order::whereKey($order->id)->where('payment_reference', $appTransId)->update(['payment_reference' => null]);
            }
            Log::error('ZaloPay API call failed', ['url' => $zaloUrl, 'error' => $exception->getMessage()]);
            return ['success' => false, 'message' => 'Không thể kết nối ZaloPay: ' . $exception->getMessage(), '_http_status' => 502];
        }

        if (isset($result['return_code']) && (int) $result['return_code'] === 1 && !empty($result['order_url'])) {
            return [
                'success' => true,
                'payment_url' => $result['order_url'] ?? '',
                'order_code' => $zaloOrder['app_trans_id'],
                'method' => 'zalo',
            ];
        }

        if ($order) {
            Order::whereKey($order->id)->where('payment_reference', $appTransId)->update(['payment_reference' => null]);
        }
        Log::error('ZaloPay order creation failed', [
            'url' => $zaloUrl,
            'return_code' => $result['return_code'] ?? null,
            'return_message' => $result['return_message'] ?? null,
        ]);
        return [
            'success' => false,
            'message' => $result['return_message'] ?? 'Lỗi tạo đơn hàng ZaloPay',
            '_http_status' => 502,
        ];
    }

    public function zaloPayCallback(Request $request): JsonResponse
    {
        $callback = $request->validate([
            'data' => 'required|string',
            'mac' => 'required|string',
        ]);
        $key2 = (string) config('zalopay.key2', '');
        $expectedMac = $key2 === '' ? '' : hash_hmac('sha256', $callback['data'], $key2);
        if ($expectedMac === '' || !hash_equals($expectedMac, $callback['mac'])) {
            return response()->json(['return_code' => -1, 'return_message' => 'MAC không hợp lệ.'], 400);
        }

        $data = json_decode($callback['data'], true);
        if (!is_array($data)) {
            return response()->json(['return_code' => 2, 'return_message' => 'Dữ liệu callback không hợp lệ.'], 422);
        }
        $order = Order::where('payment_reference', $data['app_trans_id'] ?? '')
            ->where('payment_method_code', 'zalopay')
            ->first();
        if (!$order
            || (int) ($data['app_id'] ?? 0) !== (int) config('zalopay.appid')
            || (int) ($data['amount'] ?? 0) !== (int) $order->total) {
            return response()->json(['return_code' => 2, 'return_message' => 'Không tìm thấy đơn hàng hoặc số tiền không khớp.'], 422);
        }

        if ($order->status === 'PENDING_PAYMENT') {
            $this->settleOrder($order);
        }
        return response()->json(['return_code' => 1, 'return_message' => 'success']);
    }

    public function zaloPayReturn(Request $request)
    {
        $frontendUrl = rtrim((string) config('zalopay.frontend_url'), '/');
        $params = $request->query();
        $key2 = (string) config('zalopay.key2', '');
        $required = ['appid', 'apptransid', 'pmcid', 'bankcode', 'amount', 'discountamount', 'status', 'checksum'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $params)) {
                return redirect($frontendUrl . '?' . http_build_query([
                    'status' => 'fail', 'message' => 'Thiếu dữ liệu trả về từ ZaloPay',
                ]));
            }
        }

        $checksumData = implode('|', [
            $params['appid'], $params['apptransid'], $params['pmcid'], $params['bankcode'],
            $params['amount'], $params['discountamount'], $params['status'],
        ]);
        $expectedChecksum = $key2 === '' ? '' : hash_hmac('sha256', $checksumData, $key2);
        if ($expectedChecksum === '' || !hash_equals($expectedChecksum, (string) $params['checksum'])) {
            return redirect($frontendUrl . '?' . http_build_query([
                'status' => 'fail', 'message' => 'Checksum ZaloPay không hợp lệ',
            ]));
        }

        $order = Order::where('payment_reference', (string) $params['apptransid'])
            ->where('payment_method_code', 'zalopay')
            ->first();
        if (!$order) {
            return redirect($frontendUrl . '?' . http_build_query([
                'status' => 'fail', 'message' => 'Không tìm thấy đơn hàng ZaloPay',
            ]));
        }

        $params['order_code'] = $order->order_code;
        return redirect($frontendUrl . '?' . http_build_query($params));
    }

    private function settleOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();
            if ($order->status !== 'PENDING_PAYMENT') return;
            $hasService = false;
            foreach ($order->items as $item) {
                if ($item->game_account_id) {
                    GameAccount::whereKey($item->game_account_id)->where('status', 'RESERVED')->update([
                        'status' => 'SOLD', 'sold_at' => now(), 'reserved_by_order_id' => null,
                        'reserved_until' => null, 'sold_order_item_id' => $item->id,
                    ]);
                }
                if ($item->item_type === 'SERVICE') $hasService = true;
            }
            $order->update([
                'status' => $hasService ? 'PROCESSING' : 'COMPLETED',
                'paid_at' => now(),
            ]);
        });
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
                'is_paid' => in_array($order->status, ['PAID', 'PROCESSING', 'COMPLETED']),
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

        if ($order->status !== 'PENDING_PAYMENT') {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này không ở trạng thái chờ thanh toán.',
            ], 400);
        }

        $this->settleOrder($order);

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
