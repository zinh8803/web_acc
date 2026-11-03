<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GioHang;
use App\Models\MaGiamGia;
use App\Models\LichSuDungMaGiamGia;
use App\Models\TaiKhoanGame;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use App\Models\BienTheSanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * List enabled payment methods
     */
    public function methods(): JsonResponse
    {
        $methods = [
            ['code' => 'vnpay', 'name' => 'Cổng thanh toán VNPay', 'provider' => 'VNPay', 'description' => 'Thanh toán trực tuyến bảo mật qua cổng VNPay (Hỗ trợ VNPAY-QR, Thẻ ATM nội địa, Thẻ quốc tế Visa/Mastercard).'],
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
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
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

    public function createVnPayCheckoutUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:bien_the_san_pham,id',
            'items.*.game_account_id' => 'nullable|integer|exists:tai_khoan_game,id',
            'items.*.quantity' => 'required|integer|min:1|max:10',
            'items.*.service_data' => 'nullable|array',
            'coupon_code' => 'nullable|string|max:40',
            'customer_notes' => 'nullable|string|max:500',
        ]);

        $tmnCode = config('vnpay.vnp_TmnCode');
        $hashSecret = config('vnpay.vnp_HashSecret');
        $vnpUrl = config('vnpay.vnp_Url');
        $returnUrl = config('vnpay.vnp_Returnurl');

        if (!$tmnCode || !$hashSecret || !$vnpUrl || !$returnUrl) {
            Log::error('VNPay configuration invalid');
            return response()->json(['success' => false, 'message' => 'Cấu hình VNPay chưa đầy đủ.'], 503);
        }

        $user = $request->user();

        try {
            $checkoutData = DB::transaction(function () use ($validated, $user, $request) {
                $subtotal = 0;
                $preparedItems = [];
                $reservedAccountIds = [];

                foreach ($validated['items'] as $requestedItem) {
                    $variant = BienTheSanPham::with('product')->lockForUpdate()->findOrFail($requestedItem['variant_id']);
                    $product = $variant->product;
                    if (!$product || $product->status !== 'ACTIVE' || $variant->status !== 'ACTIVE') {
                        throw ValidationException::withMessages(['items' => ['Một sản phẩm trong giỏ không còn khả dụng.']]);
                    }

                    $quantity = $requestedItem['quantity'];
                    $unitPrice = $variant->sale_price ?? $variant->price;
                    $lineTotal = (int) $unitPrice * $quantity;
                    $accountIds = [];

                    if ($product->type === 'ACCOUNT') {
                        if (!empty($requestedItem['game_account_id'])) {
                            if ($quantity !== 1) {
                                throw ValidationException::withMessages(['items' => ['Mỗi tài khoản được bán riêng lẻ, số lượng phải bằng 1.']]);
                            }
                            $acc = TaiKhoanGame::whereKey($requestedItem['game_account_id'])
                                ->where('bien_the_id', $variant->id)
                                ->where('san_pham_id', $product->id)
                                ->where('trang_thai', 'AVAILABLE')
                                ->lockForUpdate()
                                ->first();
                            if (!$acc) {
                                throw ValidationException::withMessages(['items' => ['Tài khoản bạn chọn không còn khả dụng.']]);
                            }
                            $accountIds = [$acc->id];
                        } else {
                            $accounts = TaiKhoanGame::where('bien_the_id', $variant->id)
                                ->where('trang_thai', 'AVAILABLE')
                                ->lockForUpdate()
                                ->limit($quantity)
                                ->get();
                            if ($accounts->count() < $quantity) {
                                throw ValidationException::withMessages(['items' => ["Sản phẩm '{$product->name}' không đủ hàng trong kho."]]);
                            }
                            $accountIds = $accounts->pluck('id')->toArray();
                        }

                        // Tạm giữ chỗ trong 20 phút để khách thanh toán
                        TaiKhoanGame::whereIn('id', $accountIds)->update([
                            'status' => 'RESERVED',
                            'reserved_until' => now()->addMinutes(20),
                        ]);
                        $variant->update([
                            'stock_quantity' => TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count(),
                        ]);
                        $reservedAccountIds = array_merge($reservedAccountIds, $accountIds);
                    }

                    $preparedItems[] = [
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'product_name' => $product->name,
                        'variant_name' => $variant->name,
                        'thumbnail_url' => $product->thumbnail_url,
                        'item_type' => $product->type,
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'line_total' => $lineTotal,
                        'account_ids' => $accountIds,
                        'warranty_policy_id' => $product->warranty_policy_id,
                        'warranty_duration_days' => $product->warrantyPolicy?->duration_days,
                        'service_data' => $requestedItem['service_data'] ?? null,
                    ];
                    $subtotal += $lineTotal;
                }

                $coupon = null;
                $discount = 0;
                if (!empty($validated['coupon_code'])) {
                    $coupon = MaGiamGia::where('ma_khuyen_mai', strtoupper($validated['coupon_code']))
                        ->where('trang_thai', 'ACTIVE')
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

                    $userUsageCount = LichSuDungMaGiamGia::where('ma_giam_gia_id', $coupon->id)
                        ->where('nguoi_dung_id', $user->id)
                        ->where('trang_thai', 'USED')
                        ->count();
                    $orderUsageCount = DonHang::where('ma_giam_gia_id', $coupon->id)
                        ->where('nguoi_dung_id', $user->id)
                        ->whereIn('trang_thai', ['PAID', 'COMPLETED', 'PROCESSING', 'DELIVERED'])
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

                $total = max(0, $subtotal - $discount);

                return [
                    'user_id' => $user->id,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'customer_notes' => $validated['customer_notes'] ?? null,
                    'items' => $preparedItems,
                    'reserved_account_ids' => $reservedAccountIds,
                    'ip' => $request->ip(),
                ];
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        }

        $txnRef = 'VNP' . date('YmdHis') . random_int(1000, 9999);
        Cache::put('vnpay_checkout_' . $txnRef, $checkoutData, now()->addMinutes(25));

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) $checkoutData['total'] * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => $request->ip(),
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang ' . $txnRef,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_TxnRef' => $txnRef,
        ];
        ksort($inputData);
        $query = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $query, $hashSecret);
        $paymentUrl = $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        return response()->json([
            'success' => true,
            'data' => [
                'payment_url' => $paymentUrl,
                'txn_ref' => $txnRef,
                'total' => $checkoutData['total'],
            ],
        ]);
    }

    public function createVnPayPayment(Request $request, string $orderCode): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
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
            } while (DonHang::where('ma_giao_dich_thanh_toan', $txnRef)->exists());
            $order->update(['ma_giao_dich_thanh_toan' => $txnRef]);
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
        $validSignature = $expectedHash !== '' && $receivedHash !== '' && hash_equals($expectedHash, $receivedHash);
        $frontend = rtrim((string) config('vnpay.frontend_url'), '/');

        if (!$validSignature) {
            return redirect($frontend . '?status=fail&message=' . urlencode('Chữ ký không hợp lệ'));
        }

        $txnRef = (string) $request->query('vnp_TxnRef', '');
        $responseCode = (string) $request->query('vnp_ResponseCode', '');
        $transactionStatus = (string) $request->query('vnp_TransactionStatus', '');
        $transactionNo = (string) $request->query('vnp_TransactionNo', $txnRef);

        // Trường hợp 1: Luồng thanh toán tạo Order sau khi VNPay thành công (Checkout qua VNPay)
        $checkoutData = Cache::get('vnpay_checkout_' . $txnRef);
        if ($checkoutData) {
            $isSuccess = ($responseCode === '00' && $transactionStatus === '00' && (int) $request->query('vnp_Amount') === (int) $checkoutData['total'] * 100);

            if ($isSuccess) {
                // Thanh toán VNPay thành công => GỌI HÀM TẠO ORDER
                $order = DB::transaction(function () use ($checkoutData, $transactionNo) {
                    $couponId = $checkoutData['coupon_id'] ?? null;
                    $discount = $checkoutData['discount'] ?? 0;
                    if ($couponId) {
                        $coupon = MaGiamGia::find($couponId);
                        $usedCount = LichSuDungMaGiamGia::where('ma_giam_gia_id', $couponId)->where('nguoi_dung_id', $checkoutData['user_id'])->where('trang_thai', 'USED')->count();
                        $orderUsedCount = DonHang::where('ma_giam_gia_id', $couponId)->where('nguoi_dung_id', $checkoutData['user_id'])->whereIn('trang_thai', ['PAID', 'COMPLETED', 'PROCESSING'])->count();
                        if (!$coupon || max($usedCount, $orderUsedCount) >= $coupon->usage_per_user) {
                            $couponId = null;
                            $discount = 0;
                        }
                    }

                    $order = DonHang::create([
                        'ma_don_hang' => 'ORD-' . strtoupper(Str::random(12)),
                        'nguoi_dung_id' => $checkoutData['user_id'],
                        'trang_thai' => 'COMPLETED',
                        'tam_tinh' => $checkoutData['subtotal'],
                        'tong_giam_gia' => $discount,
                        'tong_tien' => max(0, $checkoutData['subtotal'] - $discount),
                        'ma_giam_gia_id' => $couponId,
                        'ma_giam_gia_luu_lai' => $couponId ? ($checkoutData['coupon_code'] ?? null) : null,
                        'phuong_thuc_thanh_toan' => 'vnpay',
                        'ma_giao_dich_thanh_toan' => $transactionNo,
                        'ghi_chu_khach_hang' => $checkoutData['customer_notes'],
                        'het_han_thanh_toan_luc' => now()->addMinutes(30),
                        'thanh_toan_luc' => now(),
                        'hoan_thanh_luc' => now(),
                        'dia_chi_ip' => $checkoutData['ip'] ?? null,
                    ]);

                    foreach ($checkoutData['items'] as $itemData) {
                        if ($itemData['item_type'] === 'ACCOUNT') {
                            foreach ($itemData['account_ids'] as $accId) {
                                $orderItem = ChiTietDonHang::create([
                                    'don_hang_id' => $order->id,
                                    'san_pham_id' => $itemData['product_id'],
                                    'bien_the_id' => $itemData['variant_id'],
                                    'tai_khoan_game_id' => $accId,
                                    'loai_muc' => 'ACCOUNT',
                                    'ten_san_pham_luu_lai' => $itemData['product_name'],
                                    'ten_bien_the_luu_lai' => $itemData['variant_name'],
                                    'anh_san_pham_luu_lai' => $itemData['thumbnail_url'],
                                    'don_gia' => $itemData['unit_price'],
                                    'so_luong' => 1,
                                    'thanh_tien' => $itemData['unit_price'],
                                    'chinh_sach_bao_hanh_id' => $itemData['warranty_policy_id'],
                                    'het_han_bao_hanh_luc' => !empty($itemData['warranty_duration_days'])
                                        ? now()->addDays($itemData['warranty_duration_days'])
                                        : null,
                                    'trang_thai_giao_hang' => 'DELIVERED',
                                    'giao_hang_luc' => now(),
                                    'ngay_tao' => now(),
                                ]);

                                TaiKhoanGame::whereKey($accId)->update([
                                    'trang_thai' => 'SOLD',
                                    'ban_luc' => now(),
                                    'chi_tiet_don_hang_id' => $orderItem->id,
                                    'giu_cho_boi_don_hang_id' => null,
                                    'giu_cho_den_khi' => null,
                                ]);
                            }
                            \App\Models\BienTheSanPham::whereKey($itemData['variant_id'])->update([
                                'so_luong_kho' => TaiKhoanGame::where('bien_the_id', $itemData['variant_id'])->where('trang_thai', 'AVAILABLE')->count(),
                            ]);
                        } else {
                            $orderItem = ChiTietDonHang::create([
                                'don_hang_id' => $order->id,
                                'san_pham_id' => $itemData['product_id'],
                                'bien_the_id' => $itemData['variant_id'],
                                'loai_muc' => 'SERVICE',
                                'ten_san_pham_luu_lai' => $itemData['product_name'],
                                'ten_bien_the_luu_lai' => $itemData['variant_name'],
                                'anh_san_pham_luu_lai' => $itemData['thumbnail_url'],
                                'don_gia' => $itemData['unit_price'],
                                'so_luong' => $itemData['quantity'],
                                'thanh_tien' => $itemData['line_total'],
                                'chinh_sach_bao_hanh_id' => $itemData['warranty_policy_id'],
                                'trang_thai_giao_hang' => 'PENDING',
                                'ngay_tao' => now(),
                            ]);

                            $serviceData = $itemData['service_data'] ?? null;
                            if ($serviceData) {
                                $loginInfo = [];
                                if (!empty($serviceData['game_username'])) $loginInfo['username'] = $serviceData['game_username'];
                                if (!empty($serviceData['game_password'])) $loginInfo['password'] = $serviceData['game_password'];
                                if (!empty($serviceData['two_factor'])) $loginInfo['two_factor'] = $serviceData['two_factor'];

                                \App\Models\YeuCauDichVu::create([
                                    'ma_yeu_cau' => 'SRV-' . strtoupper(Str::random(8)),
                                    'chi_tiet_don_hang_id' => $orderItem->id,
                                    'nguoi_dung_id' => $checkoutData['user_id'],
                                    'du_lieu_bieu_mau' => [
                                        'server' => $serviceData['server'] ?? null,
                                        'ingame_name' => $serviceData['ingame_name'] ?? null,
                                        'current_rank' => $serviceData['current_rank'] ?? null,
                                        'target_rank' => $serviceData['target_rank'] ?? null,
                                        'target_hero' => $serviceData['target_hero'] ?? null,
                                        'variant_name' => $itemData['variant_name'] ?? null,
                                    ],
                                    'thong_tin_dang_nhap_ma_hoa' => !empty($loginInfo) ? \Illuminate\Support\Facades\Crypt::encryptString(json_encode($loginInfo)) : null,
                                    'ghi_chu_khach_hang' => $serviceData['notes'] ?? $checkoutData['customer_notes'] ?? null,
                                    'trang_thai' => 'PENDING',
                                    'ngay_tao' => now(),
                                ]);
                            }
                        }
                    }

                    if (!empty($couponId)) {
                        LichSuDungMaGiamGia::create([
                            'ma_giam_gia_id' => $couponId,
                            'nguoi_dung_id' => $checkoutData['user_id'],
                            'don_hang_id' => $order->id,
                            'so_tien_giam' => $discount,
                            'trang_thai' => 'USED',
                            'thoi_gian_dung' => now(),
                        ]);
                        MaGiamGia::whereKey($couponId)->increment('so_lan_da_dung');
                    }

                    GioHang::where('nguoi_dung_id', $checkoutData['user_id'])->first()?->items()->delete();

                    return $order;
                });

                // Gửi thông tin tài khoản & xác nhận dịch vụ về email của khách
                \App\Services\OrderMailService::sendOrderSuccessMail($order);

                Cache::forget('vnpay_checkout_' . $txnRef);

                $returnData = $request->all();
                $returnData['order_code'] = $order->order_code;
                $returnData['status'] = 'success';
                return redirect($frontend . '?' . http_build_query($returnData));
            } else {
                // Thanh toán thất bại hoặc người dùng huỷ => KHÔNG TẠO ORDER!
                if (!empty($checkoutData['reserved_account_ids'])) {
                    TaiKhoanGame::whereIn('id', $checkoutData['reserved_account_ids'])
                        ->where('trang_thai', 'RESERVED')
                        ->update([
                            'status' => 'AVAILABLE',
                            'reserved_until' => null,
                        ]);
                    foreach ($checkoutData['items'] as $itemData) {
                        if ($itemData['item_type'] === 'ACCOUNT') {
                            \App\Models\BienTheSanPham::whereKey($itemData['variant_id'])->update([
                                'stock_quantity' => TaiKhoanGame::where('bien_the_id', $itemData['variant_id'])->where('trang_thai', 'AVAILABLE')->count(),
                            ]);
                        }
                    }
                }

                Cache::forget('vnpay_checkout_' . $txnRef);

                $returnData = $request->all();
                $returnData['status'] = 'fail';
                $returnData['message'] = 'Thanh toán VNPay không thành công hoặc đã bị huỷ.';
                return redirect($frontend . '?' . http_build_query($returnData));
            }
        }

        // Trường hợp 2: Đơn hàng đã tồn tại trước đó (tương thích ngược)
        $order = DonHang::where('ma_giao_dich_thanh_toan', $txnRef)->first();
        $orderCode = $order?->order_code ?? '';
        $validPayment = $order
            && $order->payment_method_code === 'vnpay'
            && $responseCode === '00'
            && $transactionStatus === '00'
            && (int) $request->query('vnp_Amount') === (int) $order->total * 100;

        if ($validPayment && $order->status === 'PENDING_PAYMENT') {
            $this->settleOrder($order);
            $order->update(['ma_giao_dich_thanh_toan' => $transactionNo]);
        }
        $result = $validPayment && $order->refresh()->isPaid() ? 'success' : 'fail';
        if ($order && $result === 'fail' && $order->status === 'PENDING_PAYMENT') {
            $order->update(['ma_giao_dich_thanh_toan' => null]);
        }

        $returnData = $request->all();
        $returnData['order_code'] = $orderCode;
        $returnData['status'] = $result;
        return redirect($frontend . '?' . http_build_query($returnData));
    }

    public function initiateZaloPayPayment(Request $request, string $orderCode): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
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
        while (DonHang::where('ma_giao_dich_thanh_toan', $appTransId)->exists()) {
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

        $order = DonHang::find($validated['order_id'] ?? 0);
        if ($order) {
            $order->update(['ma_giao_dich_thanh_toan' => $appTransId]);
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
                DonHang::whereKey($order->id)->where('ma_giao_dich_thanh_toan', $appTransId)->update(['ma_giao_dich_thanh_toan' => null]);
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
            DonHang::whereKey($order->id)->where('ma_giao_dich_thanh_toan', $appTransId)->update(['ma_giao_dich_thanh_toan' => null]);
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
        $order = DonHang::where('ma_giao_dich_thanh_toan', $data['app_trans_id'] ?? '')
            ->where('phuong_thuc_thanh_toan', 'zalopay')
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

        $order = DonHang::where('ma_giao_dich_thanh_toan', (string) $params['apptransid'])
            ->where('phuong_thuc_thanh_toan', 'zalopay')
            ->first();
        if (!$order) {
            return redirect($frontendUrl . '?' . http_build_query([
                'status' => 'fail', 'message' => 'Không tìm thấy đơn hàng ZaloPay',
            ]));
        }

        $params['order_code'] = $order->order_code;
        return redirect($frontendUrl . '?' . http_build_query($params));
    }

    private function settleOrder(DonHang $order): void
    {
        DB::transaction(function () use ($order) {
            $order = DonHang::whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();
            if ($order->status !== 'PENDING_PAYMENT') return;
            $hasService = false;
            foreach ($order->items as $item) {
                if ($item->game_account_id) {
                    TaiKhoanGame::whereKey($item->game_account_id)->where('trang_thai', 'RESERVED')->update([
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

        // Gửi thông tin tài khoản & xác nhận dịch vụ về email của khách
        \App\Services\OrderMailService::sendOrderSuccessMail($order);
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request, string $orderCode): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
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
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
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

