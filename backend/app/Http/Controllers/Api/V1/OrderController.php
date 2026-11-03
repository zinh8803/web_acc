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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = DonHang::where('nguoi_dung_id', $request->user()->id)
            ->with('items.product')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 10));

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show(Request $request, string $orderCode): JsonResponse
    {
        $query = DonHang::where('ma_don_hang', $orderCode)
            ->with(['items.product', 'items.gameAccount', 'items.serviceRequest.staff', 'coupon']);

        $user = $request->user();
        $userRole = $user->vai_tro ?? $user->role ?? '';
        if ($user && !in_array($userRole, ['SUPER_ADMIN', 'ADMIN', 'STAFF_SUPPORT', 'STAFF_SERVICE'], true)) {
            $query->where('nguoi_dung_id', $user->id);
        }

        $order = $query->firstOrFail();

        $data = $order->toArray();
        $isPaid = $order->isPaid();
        $data['items'] = $order->items->map(function (ChiTietDonHang $item) use ($isPaid) {
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
            'items.*.variant_id' => 'required|integer|exists:bien_the_san_pham,id',
            'items.*.game_account_id' => 'nullable|integer|exists:tai_khoan_game,id',
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
                $variant = BienTheSanPham::with('product')->lockForUpdate()->findOrFail($requestedItem['variant_id']);
                $product = $variant->product;
                if (!$product || $product->trang_thai !== 'ACTIVE' || $variant->trang_thai !== 'ACTIVE') {
                    throw ValidationException::withMessages(['items' => ['Một sản phẩm trong giỏ không còn khả dụng.']]);
                }

                $quantity = $requestedItem['quantity'];
                $unitPrice = $variant->gia_khuyen_mai ?? $variant->gia_ban ?? $variant->sale_price ?? $variant->price;
                $lineTotal = (int) $unitPrice * $quantity;
                $accounts = collect();

                if ($product->loai_san_pham === 'ACCOUNT') {
                    if (!empty($requestedItem['game_account_id'])) {
                        if ($quantity !== 1) {
                            throw ValidationException::withMessages(['items' => ['Mỗi tài khoản được bán riêng lẻ, số lượng phải bằng 1.']]);
                        }
                        $accounts = TaiKhoanGame::whereKey($requestedItem['game_account_id'])
                            ->where('bien_the_id', $variant->id)
                            ->where('san_pham_id', $product->id)
                            ->where('trang_thai', 'AVAILABLE')
                            ->lockForUpdate()
                            ->get();
                    } else {
                        $accounts = TaiKhoanGame::where('bien_the_id', $variant->id)
                            ->where('trang_thai', 'AVAILABLE')
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
                $coupon = MaGiamGia::where('ma_khuyen_mai', strtoupper($validated['coupon_code']))
                    ->where('trang_thai', 'ACTIVE')
                    ->lockForUpdate()
                    ->first();
                if (!$coupon) {
                    throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá không tồn tại hoặc đã ngừng hoạt động.']]);
                }
                if ($coupon->bat_dau_luc && now()->lt($coupon->bat_dau_luc)) {
                    throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá chưa đến ngày áp dụng.']]);
                }
                if ($coupon->ket_thuc_luc && now()->gt($coupon->ket_thuc_luc)) {
                    throw ValidationException::withMessages(['coupon_code' => ['Mã giảm giá đã hết hạn sử dụng.']]);
                }
                if ($coupon->gioi_han_tong_luot_dung && $coupon->so_luot_da_dung >= $coupon->gioi_han_tong_luot_dung) {
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

                if ($actualUsed >= $coupon->gioi_han_moi_nguoi) {
                    throw ValidationException::withMessages(['coupon_code' => ['Bạn đã sử dụng mã giảm giá này rồi. Mỗi khách hàng chỉ được sử dụng ' . $coupon->gioi_han_moi_nguoi . ' lần.']]);
                }

                if ($subtotal < $coupon->gia_tri_don_toi_thieu) {
                    throw ValidationException::withMessages(['coupon_code' => ['Đơn hàng tối thiểu ' . number_format($coupon->gia_tri_don_toi_thieu) . 'đ để sử dụng mã này.']]);
                }

                $discount = $coupon->loai_giam_gia === 'PERCENT'
                    ? (int) round(min($subtotal * $coupon->gia_tri_giam / 100, $coupon->giam_toi_da ?: $subtotal))
                    : (int) min($coupon->gia_tri_giam, $subtotal);
            }

            $order = DonHang::create([
                'ma_don_hang' => 'ORD-' . strtoupper(Str::random(12)),
                'nguoi_dung_id' => $user->id,
                'trang_thai' => 'PENDING_PAYMENT',
                'tam_tinh' => $subtotal,
                'tong_giam_gia' => $discount,
                'tong_tien' => max(0, $subtotal - $discount),
                'ma_giam_gia_id' => $coupon?->id,
                'ma_giam_gia_luu_lai' => $coupon?->ma_khuyen_mai,
                'phuong_thuc_thanh_toan' => $validated['payment_method_code'] ?? 'vietqr',
                'ghi_chu_khach_hang' => $validated['customer_notes'] ?? null,
                'het_han_thanh_toan_luc' => now()->addMinutes(30),
                'dia_chi_ip' => $request->ip(),
            ]);

            foreach ($preparedItems as $prepared) {
                $product = $prepared['product'];
                $variant = $prepared['variant'];
                if ($product->loai_san_pham === 'ACCOUNT') {
                    foreach ($prepared['accounts'] as $account) {
                        ChiTietDonHang::create([
                            'don_hang_id' => $order->id,
                            'san_pham_id' => $product->id,
                            'bien_the_id' => $variant->id,
                            'tai_khoan_game_id' => $account->id,
                            'loai_muc' => 'ACCOUNT',
                            'ten_san_pham_luu_lai' => $product->ten_san_pham,
                            'ten_bien_the_luu_lai' => $variant->ten_bien_the,
                            'anh_san_pham_luu_lai' => $account->anh_minh_hoa ?: $product->anh_dai_dien,
                            'don_gia' => $prepared['unitPrice'],
                            'so_luong' => 1,
                            'thanh_tien' => $prepared['unitPrice'],
                            'chinh_sach_bao_hanh_id' => $product->chinh_sach_bao_hanh_id,
                            'het_han_bao_hanh_luc' => $product->warrantyPolicy?->so_ngay_bao_hanh
                                ? now()->addDays($product->warrantyPolicy->so_ngay_bao_hanh)
                                : null,
                            'trang_thai_giao_hang' => 'PENDING',
                            'ngay_tao' => now(),
                        ]);
                        $account->update([
                            'trang_thai' => 'RESERVED',
                            'giu_cho_boi_don_hang_id' => $order->id,
                            'giu_cho_den_khi' => $order->het_han_thanh_toan_luc,
                        ]);
                    }
                    $variant->update([
                        'so_luong_kho' => TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count(),
                    ]);
                } else {
                    $orderItem = ChiTietDonHang::create([
                        'don_hang_id' => $order->id,
                        'san_pham_id' => $product->id,
                        'bien_the_id' => $variant->id,
                        'loai_muc' => 'SERVICE',
                        'ten_san_pham_luu_lai' => $product->ten_san_pham,
                        'ten_bien_the_luu_lai' => $variant->ten_bien_the,
                        'anh_san_pham_luu_lai' => $product->anh_dai_dien,
                        'don_gia' => $prepared['unitPrice'],
                        'so_luong' => $prepared['quantity'],
                        'thanh_tien' => $prepared['lineTotal'],
                        'chinh_sach_bao_hanh_id' => $product->chinh_sach_bao_hanh_id,
                        'trang_thai_giao_hang' => 'PENDING',
                        'ngay_tao' => now(),
                    ]);

                    $serviceData = $prepared['serviceData'] ?? null;
                    if ($serviceData) {
                        $loginInfo = [];
                        if (!empty($serviceData['game_username'])) $loginInfo['username'] = $serviceData['game_username'];
                        if (!empty($serviceData['game_password'])) $loginInfo['password'] = $serviceData['game_password'];
                        if (!empty($serviceData['two_factor'])) $loginInfo['two_factor'] = $serviceData['two_factor'];

                        YeuCauDichVu::create([
                            'ma_yeu_cau' => 'SRV-' . strtoupper(Str::random(8)),
                            'chi_tiet_don_hang_id' => $orderItem->id,
                            'nguoi_dung_id' => $user->id,
                            'du_lieu_bieu_mau' => [
                                'server' => $serviceData['server'] ?? null,
                                'ingame_name' => $serviceData['ingame_name'] ?? null,
                                'current_rank' => $serviceData['current_rank'] ?? null,
                                'target_rank' => $serviceData['target_rank'] ?? null,
                                'target_hero' => $serviceData['target_hero'] ?? null,
                                'variant_name' => $variant->name,
                            ],
                            'thong_tin_dang_nhap_ma_hoa' => !empty($loginInfo) ? \Illuminate\Support\Facades\Crypt::encryptString(json_encode($loginInfo)) : null,
                            'ghi_chu_khach_hang' => $serviceData['notes'] ?? $validated['customer_notes'] ?? null,
                            'trang_thai' => 'PENDING',
                        ]);
                    }
                }
            }

            if ($coupon) {
                LichSuDungMaGiamGia::create([
                    'ma_giam_gia_id' => $coupon->id,
                    'nguoi_dung_id' => $user->id,
                    'don_hang_id' => $order->id,
                    'so_tien_giam' => $discount,
                    'trang_thai' => 'USED',
                    'ngay_tao' => now(),
                ]);
                $coupon->increment('so_lan_da_dung');
            }

            GioHang::where('nguoi_dung_id', $user->id)->first()?->items()->delete();
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
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('trang_thai', 'PENDING_PAYMENT')
            ->with('items')
            ->firstOrFail();

        DB::transaction(function () use ($order, $request) {
            $order->update([
                'trang_thai' => 'CANCELLED',
                'huy_luc' => now(),
                'ly_do_huy' => $request->input('reason'),
            ]);
            $reservedItems = $order->items->whereNotNull('tai_khoan_game_id');
            TaiKhoanGame::whereIn('id', $reservedItems->pluck('tai_khoan_game_id'))
                ->where('trang_thai', 'RESERVED')
                ->update(['trang_thai' => 'AVAILABLE', 'giu_cho_boi_don_hang_id' => null, 'giu_cho_den_khi' => null]);
            foreach ($reservedItems->groupBy('bien_the_id') as $variantId => $items) {
                $availableCount = TaiKhoanGame::where('bien_the_id', $variantId)->where('trang_thai', 'AVAILABLE')->count();
                BienTheSanPham::whereKey($variantId)->update(['so_luong_kho' => $availableCount]);
            }

            if ($order->ma_giam_gia_id) {
                LichSuDungMaGiamGia::where('don_hang_id', $order->id)->where('trang_thai', 'USED')->update(['trang_thai' => 'REVERTED']);
                MaGiamGia::whereKey($order->ma_giam_gia_id)->decrement('so_lan_da_dung');
            }
        });

        return response()->json(['success' => true, 'message' => 'Đã huỷ đơn hàng thành công.']);
    }

    public function viewCredentials(Request $request, string $orderCode, ChiTietDonHang $item): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password_hash)) {
            return response()->json(['success' => false, 'error' => ['message' => 'Mật khẩu không chính xác.']], 422);
        }

        $order = DonHang::where('ma_don_hang', $orderCode)->where('nguoi_dung_id', $user->id)->firstOrFail();
        abort_unless($order->isPaid() && $item->don_hang_id === $order->id && $item->tai_khoan_game_id, 404);
        $credentials = TaiKhoanGame::findOrFail($item->tai_khoan_game_id)->decryptCredentials();

        return response()->json(['success' => true, 'data' => [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'extra_info' => $credentials['extra'],
        ]]);
    }
}
