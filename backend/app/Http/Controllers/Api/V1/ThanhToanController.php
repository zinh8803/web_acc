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
use App\Models\YeuCauDichVu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ThanhToanController extends Controller
{
    /**
     * Danh sách phương thức thanh toán khả dụng.
     */
    public function phuongThuc(): JsonResponse
    {
        $methods = [
            [
                'code' => 'vnpay',
                'ma' => 'vnpay',
                'name' => 'Cổng thanh toán VNPay',
                'ten' => 'Cổng thanh toán VNPay',
                'provider' => 'VNPay',
                'description' => 'Thanh toán trực tuyến bảo mật qua cổng VNPay (VNPAY-QR, Thẻ ATM nội địa, Thẻ Visa/Mastercard).',
                'mo_ta' => 'Thanh toán trực tuyến bảo mật qua cổng VNPay (VNPAY-QR, Thẻ ATM nội địa, Thẻ Visa/Mastercard).'
            ],
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $methods,
            'data' => $methods,
        ]);
    }

    public function methods(): JsonResponse
    {
        return $this->phuongThuc();
    }

    /**
     * Khởi tạo thông tin thanh toán cho đơn hàng.
     */
    public function khoiTaoThanhToan(Request $request, string $orderCode): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->with(['chiTiets'])
            ->firstOrFail();

        if ($order->trang_thai !== 'PENDING_PAYMENT') {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Đơn hàng này không ở trạng thái chờ thanh toán.',
                'message' => 'Đơn hàng này không ở trạng thái chờ thanh toán.',
            ], 400);
        }

        $methodCode = $order->phuong_thuc_thanh_toan ?? 'vnpay';

        if (in_array($methodCode, ['vnpay', 'zalopay'], true)) {
            $res = [
                'ma_don_hang' => $order->ma_don_hang,
                'order_code' => $order->ma_don_hang,
                'so_tien' => $order->tong_tien,
                'amount' => $order->tong_tien,
                'trang_thai' => $order->trang_thai,
                'status' => $order->trang_thai,
                'phuong_thuc' => $methodCode,
                'method' => $methodCode,
                'bat_dau_thanh_toan' => true,
                'start_payment' => true,
                'het_han_luc' => $order->het_han_thanh_toan_luc,
                'expires_at' => $order->het_han_thanh_toan_luc,
            ];
            return response()->json([
                'thanh_cong' => true,
                'success' => true,
                'du_lieu' => $res,
                'data' => $res,
            ]);
        }

        $bankInfo = [
            'bank_code' => 'MB',
            'bank_name' => 'Ngân hàng Quân Đội (MB Bank)',
            'account_number' => '0987654321',
            'account_name' => 'NGUYEN QUOC VINH',
            'amount' => (int) $order->tong_tien,
            'content' => $order->ma_don_hang,
            'qr_url' => "https://img.vietqr.io/image/MB-0987654321-compact2.png?amount=" . (int)$order->tong_tien . "&addInfo=" . urlencode($order->ma_don_hang) . "&accountName=" . urlencode("NGUYEN QUOC VINH")
        ];

        $res = [
            'ma_don_hang' => $order->ma_don_hang,
            'order_code' => $order->ma_don_hang,
            'so_tien' => $order->tong_tien,
            'amount' => $order->tong_tien,
            'trang_thai' => $order->trang_thai,
            'status' => $order->trang_thai,
            'phuong_thuc' => $methodCode,
            'method' => $methodCode,
            'thong_tin_ngan_hang' => $bankInfo,
            'bank_info' => $bankInfo,
            'het_han_luc' => $order->het_han_thanh_toan_luc,
            'expires_at' => $order->het_han_thanh_toan_luc,
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $res,
            'data' => $res,
        ]);
    }

    public function initiate(Request $request, string $orderCode): JsonResponse
    {
        return $this->khoiTaoThanhToan($request, $orderCode);
    }

    /**
     * Tạo đường dẫn thanh toán VNPay khi checkout.
     */
    public function taoDuongDanThanhToanVnPay(Request $request): JsonResponse
    {
        $danhSachMuc = $request->chi_tiets ?? $request->items ?? [];
        if (empty($danhSachMuc) || !is_array($danhSachMuc)) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Giỏ hàng trống.'], 422);
        }

        $tmnCode = config('vnpay.vnp_TmnCode');
        $hashSecret = config('vnpay.vnp_HashSecret');
        $vnpUrl = config('vnpay.vnp_Url');
        $returnUrl = config('vnpay.vnp_Returnurl');

        if (!$tmnCode || !$hashSecret || !$vnpUrl || !$returnUrl) {
            Log::error('Cấu hình VNPay chưa đầy đủ.');
            return response()->json(['thanh_cong' => false, 'success' => false, 'message' => 'Cấu hình VNPay chưa đầy đủ.'], 503);
        }

        $user = $request->user();

        try {
            $checkoutData = DB::transaction(function () use ($danhSachMuc, $user, $request) {
                $subtotal = 0;
                $preparedItems = [];
                $reservedAccountIds = [];

                foreach ($danhSachMuc as $requestedItem) {
                    $variantId = $requestedItem['bien_the_id'] ?? $requestedItem['variant_id'] ?? null;
                    $gameAccountId = $requestedItem['tai_khoan_game_id'] ?? $requestedItem['game_account_id'] ?? null;
                    $quantity = (int) ($requestedItem['so_luong'] ?? $requestedItem['quantity'] ?? 1);
                    $serviceData = $requestedItem['du_lieu_dich_vu'] ?? $requestedItem['service_data'] ?? null;

                    $variant = BienTheSanPham::with('sanPham')->lockForUpdate()->findOrFail($variantId);
                    $product = $variant->sanPham;

                    if (!$product || $product->trang_thai !== 'ACTIVE' || $variant->trang_thai !== 'ACTIVE') {
                        throw ValidationException::withMessages(['items' => ['Một sản phẩm trong giỏ không còn khả dụng.']]);
                    }

                    $unitPrice = $variant->gia_khuyen_mai ?: $variant->gia_goc;
                    $lineTotal = (int) $unitPrice * $quantity;
                    $accountIds = [];

                    if ($product->loai_san_pham === 'ACCOUNT') {
                        if (!empty($gameAccountId)) {
                            if ($quantity !== 1) {
                                throw ValidationException::withMessages(['items' => ['Mỗi tài khoản được bán riêng lẻ, số lượng luôn là 1.']]);
                            }
                            $acc = TaiKhoanGame::whereKey($gameAccountId)
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
                                throw ValidationException::withMessages(['items' => ["Sản phẩm '{$product->ten_san_pham}' không đủ hàng trong kho."]]);
                            }
                            $accountIds = $accounts->pluck('id')->toArray();
                        }

                        TaiKhoanGame::whereIn('id', $accountIds)->update([
                            'trang_thai' => 'RESERVED',
                            'giu_cho_den_khi' => now()->addMinutes(20),
                        ]);

                        $variant->update([
                            'so_luong_ton_kho' => TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count(),
                        ]);
                        $reservedAccountIds = array_merge($reservedAccountIds, $accountIds);
                    }

                    $preparedItems[] = [
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'product_name' => $product->ten_san_pham,
                        'variant_name' => $variant->ten_bien_the,
                        'thumbnail_url' => $product->anh_dai_dien,
                        'item_type' => $product->loai_san_pham,
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'line_total' => $lineTotal,
                        'account_ids' => $accountIds,
                        'warranty_policy_id' => $product->chinh_sach_bao_hanh_id,
                        'warranty_duration_days' => $product->chinhSachBaoHanh?->so_ngay_bao_hanh,
                        'service_data' => $serviceData,
                    ];
                    $subtotal += $lineTotal;
                }

                $couponCode = $request->ma_giam_gia ?? $request->coupon_code;
                $coupon = null;
                $discount = 0;
                if (!empty($couponCode)) {
                    $coupon = MaGiamGia::where('ma_khuyen_mai', strtoupper($couponCode))
                        ->where('trang_thai', 'ACTIVE')
                        ->lockForUpdate()
                        ->first();

                    if (!$coupon) {
                        throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá không tồn tại hoặc đã ngừng hoạt động.']]);
                    }

                    $kiemTra = $coupon->kiemTraHopLe($user->id, $subtotal);
                    if (!$kiemTra['hop_le']) {
                        throw ValidationException::withMessages(['coupon_code' => [$kiemTra['thong_bao']]]);
                    }

                    $discount = (int) $coupon->tinhSoTienGiam($subtotal);
                }

                $total = max(0, $subtotal - $discount);

                return [
                    'user_id' => $user->id,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->ma_khuyen_mai,
                    'customer_notes' => $request->ghi_chu_khach_hang ?? $request->customer_notes ?? null,
                    'items' => $preparedItems,
                    'reserved_account_ids' => $reservedAccountIds,
                    'ip' => $request->ip(),
                ];
            });
        } catch (ValidationException $e) {
            return response()->json([
                'thanh_cong' => false,
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
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => [
                'duong_dan_thanh_toan' => $paymentUrl,
                'payment_url' => $paymentUrl,
                'ma_giao_dich' => $txnRef,
                'txn_ref' => $txnRef,
                'tong_tien' => $checkoutData['total'],
                'total' => $checkoutData['total'],
            ],
            'data' => [
                'payment_url' => $paymentUrl,
                'txn_ref' => $txnRef,
                'total' => $checkoutData['total'],
            ],
        ]);
    }

    public function createVnPayCheckoutUrl(Request $request): JsonResponse
    {
        return $this->taoDuongDanThanhToanVnPay($request);
    }

    /**
     * Tạo URL thanh toán VNPay cho đơn hàng đã tạo trước đó.
     */
    public function thanhToanVnPay(Request $request, string $orderCode): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->firstOrFail();

        if ($order->phuong_thuc_thanh_toan !== 'vnpay' || $order->trang_thai !== 'PENDING_PAYMENT') {
            return response()->json(['thanh_cong' => false, 'success' => false, 'message' => 'Đơn hàng không thể thanh toán qua VNPay.'], 422);
        }

        $tmnCode = config('vnpay.vnp_TmnCode');
        $hashSecret = config('vnpay.vnp_HashSecret');
        $vnpUrl = config('vnpay.vnp_Url');
        $returnUrl = config('vnpay.vnp_Returnurl');

        if (!$tmnCode || !$hashSecret || !$vnpUrl || !$returnUrl) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'message' => 'Cấu hình VNPay chưa đầy đủ.'], 503);
        }

        $txnRef = $order->ma_giao_dich_thanh_toan;
        if (!$txnRef || !str_starts_with($txnRef, 'SP')) {
            do {
                $txnRef = 'SP' . random_int(100000, 999999);
            } while (DonHang::where('ma_giao_dich_thanh_toan', $txnRef)->exists());
            $order->update(['ma_giao_dich_thanh_toan' => $txnRef]);
        }

        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) $order->tong_tien * 100,
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
        $paymentUrl = $vnpUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => ['duong_dan_thanh_toan' => $paymentUrl, 'payment_url' => $paymentUrl],
            'data' => ['payment_url' => $paymentUrl],
        ]);
    }

    public function createVnPayPayment(Request $request, string $orderCode): JsonResponse
    {
        return $this->thanhToanVnPay($request, $orderCode);
    }

    /**
     * Nhận kết quả từ VNPay Redirect Return.
     */
    public function vnpayKetQua(Request $request)
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

        $checkoutData = Cache::get('vnpay_checkout_' . $txnRef);
        if ($checkoutData) {
            $isSuccess = ($responseCode === '00' && $transactionStatus === '00' && (int) $request->query('vnp_Amount') === (int) $checkoutData['total'] * 100);

            if ($isSuccess) {
                $order = DB::transaction(function () use ($checkoutData, $transactionNo) {
                    $couponId = $checkoutData['coupon_id'] ?? null;
                    $discount = $checkoutData['discount'] ?? 0;
                    if ($couponId) {
                        $coupon = MaGiamGia::find($couponId);
                        $usedCount = LichSuDungMaGiamGia::where('ma_giam_gia_id', $couponId)->where('nguoi_dung_id', $checkoutData['user_id'])->where('trang_thai', 'USED')->count();
                        if (!$coupon || ($coupon->gioi_han_moi_nguoi_dung && $usedCount >= $coupon->gioi_han_moi_nguoi_dung)) {
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
                                    'thoi_gian_ban' => now(),
                                    'chi_tiet_don_hang_da_ban_id' => $orderItem->id,
                                    'giu_cho_boi_don_hang_id' => null,
                                    'giu_cho_den_khi' => null,
                                ]);
                            }
                            BienTheSanPham::whereKey($itemData['variant_id'])->update([
                                'so_luong_ton_kho' => TaiKhoanGame::where('bien_the_id', $itemData['variant_id'])->where('trang_thai', 'AVAILABLE')->count(),
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
                            if (!empty($serviceData) && is_array($serviceData)) {
                                $loginInfo = [];
                                $tenDangNhap = $serviceData['game_username'] ?? $serviceData['ten_dang_nhap'] ?? null;
                                if (!empty($tenDangNhap)) {
                                    $loginInfo['ten_dang_nhap'] = $tenDangNhap;
                                }
                                $matKhau = $serviceData['game_password'] ?? $serviceData['mat_khau'] ?? null;
                                if (!empty($matKhau)) {
                                    $loginInfo['mat_khau'] = $matKhau;
                                }
                                $ma2fa = $serviceData['two_factor'] ?? $serviceData['ma_2fa'] ?? null;
                                if (!empty($ma2fa)) {
                                    $loginInfo['ma_2fa'] = $ma2fa;
                                }

                                YeuCauDichVu::create([
                                    'ma_yeu_cau' => 'SRV-' . strtoupper(Str::random(8)),
                                    'chi_tiet_don_hang_id' => $orderItem->id,
                                    'nguoi_dung_id' => $checkoutData['user_id'],
                                    'du_lieu_bieu_mau' => [
                                        'server' => $serviceData['server'] ?? $serviceData['may_chu'] ?? null,
                                        'ingame_name' => $serviceData['ingame_name'] ?? $serviceData['ten_nhan_vat'] ?? null,
                                        'current_rank' => $serviceData['current_rank'] ?? $serviceData['rank_hien_tai'] ?? null,
                                        'target_rank' => $serviceData['target_rank'] ?? $serviceData['rank_mong_muon'] ?? null,
                                        'target_hero' => $serviceData['target_hero'] ?? $serviceData['tuong_yeu_cau'] ?? null,
                                        'variant_name' => $itemData['variant_name'] ?? null,
                                    ],
                                    'thong_tin_dang_nhap_ma_hoa' => !empty($loginInfo) ? \Illuminate\Support\Facades\Crypt::encryptString(json_encode($loginInfo)) : null,
                                    'ghi_chu_khach_hang' => $serviceData['notes'] ?? $serviceData['ghi_chu'] ?? $checkoutData['customer_notes'] ?? null,
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

                    GioHang::where('nguoi_dung_id', $checkoutData['user_id'])->first()?->chiTiets()->delete();

                    return $order;
                });

                Cache::forget('vnpay_checkout_' . $txnRef);

                $returnData = $request->all();
                $returnData['order_code'] = $order->ma_don_hang;
                $returnData['status'] = 'success';
                return redirect($frontend . '?' . http_build_query($returnData));
            } else {
                if (!empty($checkoutData['reserved_account_ids'])) {
                    TaiKhoanGame::whereIn('id', $checkoutData['reserved_account_ids'])
                        ->where('trang_thai', 'RESERVED')
                        ->update([
                            'trang_thai' => 'AVAILABLE',
                            'giu_cho_den_khi' => null,
                        ]);
                    foreach ($checkoutData['items'] as $itemData) {
                        if ($itemData['item_type'] === 'ACCOUNT') {
                            BienTheSanPham::whereKey($itemData['variant_id'])->update([
                                'so_luong_ton_kho' => TaiKhoanGame::where('bien_the_id', $itemData['variant_id'])->where('trang_thai', 'AVAILABLE')->count(),
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

        $order = DonHang::where('ma_giao_dich_thanh_toan', $txnRef)->first();
        $orderCode = $order?->ma_don_hang ?? '';
        $validPayment = $order
            && $order->phuong_thuc_thanh_toan === 'vnpay'
            && $responseCode === '00'
            && $transactionStatus === '00'
            && (int) $request->query('vnp_Amount') === (int) $order->tong_tien * 100;

        if ($validPayment && $order->trang_thai === 'PENDING_PAYMENT') {
            $this->hoanTatDonHang($order);
            $order->update(['ma_giao_dich_thanh_toan' => $transactionNo]);
        }
        $result = $validPayment && $order->refresh()->isPaid() ? 'success' : 'fail';
        if ($order && $result === 'fail' && $order->trang_thai === 'PENDING_PAYMENT') {
            $order->update(['ma_giao_dich_thanh_toan' => null]);
        }

        $returnData = $request->all();
        $returnData['order_code'] = $orderCode;
        $returnData['status'] = $result;
        return redirect($frontend . '?' . http_build_query($returnData));
    }

    public function vnpayReturn(Request $request)
    {
        return $this->vnpayKetQua($request);
    }

    /**
     * Kiểm tra trạng thái thanh toán.
     */
    public function kiemTraTrangThai(Request $request, string $orderCode): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->firstOrFail();

        $res = [
            'ma_don_hang' => $order->ma_don_hang,
            'order_code' => $order->ma_don_hang,
            'trang_thai' => $order->trang_thai,
            'status' => $order->trang_thai,
            'da_thanh_toan' => $order->isPaid(),
            'is_paid' => $order->isPaid(),
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $res,
            'data' => $res,
        ]);
    }

    public function checkStatus(Request $request, string $orderCode): JsonResponse
    {
        return $this->kiemTraTrangThai($request, $orderCode);
    }

    /**
     * Giả lập thanh toán tức thì (Mock Pay cho test & dev sandbox).
     */
    public function giaLapThanhToan(Request $request, string $orderCode): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->with(['chiTiets.sanPham'])
            ->firstOrFail();

        if ($order->trang_thai !== 'PENDING_PAYMENT') {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Đơn hàng này không ở trạng thái chờ thanh toán.',
                'message' => 'Đơn hàng không ở trạng thái chờ thanh toán.',
            ], 400);
        }

        $this->hoanTatDonHang($order);

        $res = [
            'ma_don_hang' => $order->ma_don_hang,
            'order_code' => $order->ma_don_hang,
            'trang_thai' => $order->fresh()->trang_thai,
            'status' => $order->fresh()->trang_thai,
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Thanh toán thành công! Tài khoản đã được bàn giao.',
            'message' => 'Thanh toán thành công! Thông tin tài khoản đã được chuyển giao.',
            'du_lieu' => $res,
            'data' => $res,
        ]);
    }

    public function mockPay(Request $request, string $orderCode): JsonResponse
    {
        return $this->giaLapThanhToan($request, $orderCode);
    }

    private function hoanTatDonHang(DonHang $order): void
    {
        DB::transaction(function () use ($order) {
            $order = DonHang::whereKey($order->id)->lockForUpdate()->with('chiTiets')->firstOrFail();
            if ($order->trang_thai !== 'PENDING_PAYMENT') return;

            $coDichVu = false;
            foreach ($order->chiTiets as $item) {
                if ($item->tai_khoan_game_id) {
                    TaiKhoanGame::whereKey($item->tai_khoan_game_id)->where('trang_thai', 'RESERVED')->update([
                        'trang_thai' => 'SOLD',
                        'thoi_gian_ban' => now(),
                        'giu_cho_boi_don_hang_id' => null,
                        'giu_cho_den_khi' => null,
                        'chi_tiet_don_hang_da_ban_id' => $item->id,
                    ]);
                    $item->update([
                        'trang_thai_giao_hang' => 'DELIVERED',
                        'giao_hang_luc' => now(),
                    ]);
                }
                if ($item->loai_muc === 'SERVICE') {
                    $coDichVu = true;
                }
            }

            $order->update([
                'trang_thai' => $coDichVu ? 'PROCESSING' : 'COMPLETED',
                'thanh_toan_luc' => now(),
                'hoan_thanh_luc' => $coDichVu ? null : now(),
            ]);
        });
    }
}

