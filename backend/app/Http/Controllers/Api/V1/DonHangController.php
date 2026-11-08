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

class DonHangController extends Controller
{
    /**
     * Danh sách đơn hàng của người dùng.
     */
    public function danhSach(Request $request): JsonResponse
    {
        $orders = DonHang::where('nguoi_dung_id', $request->user()->id)
            ->with('chiTiets.sanPham')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 10));

        $orders->getCollection()->transform(function ($order) {
            $arr = $order->toArray();
            $arr['items'] = collect($order->chiTiets)->map(function ($ct) {
                $sub = $ct->toArray();
                $sub['product'] = $ct->sanPham ? [
                    'id' => $ct->sanPham->id,
                    'name' => $ct->sanPham->ten_san_pham,
                    'slug' => $ct->sanPham->duong_dan_tinh,
                    'thumbnail_url' => $ct->sanPham->anh_dai_dien,
                ] : null;
                $sub['product_name'] = $ct->ten_san_pham_luu_lai;
                $sub['variant_name'] = $ct->ten_bien_the_luu_lai;
                $sub['unit_price'] = $ct->don_gia;
                $sub['line_total'] = $ct->thanh_tien;
                $sub['quantity'] = $ct->so_luong;
                return $sub;
            });
            $arr['order_code'] = $order->ma_don_hang;
            $arr['total'] = $order->tong_tien;
            $arr['status'] = $order->trang_thai;
            $arr['created_at'] = $order->ngay_tao;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $orders,
            'data' => $orders,
        ]);
    }

    /**
     * Chi tiết đơn hàng.
     */
    public function chiTiet(Request $request, string $orderCode): JsonResponse
    {
        $query = DonHang::where('ma_don_hang', $orderCode)
            ->with(['chiTiets.sanPham', 'chiTiets.taiKhoanGame', 'chiTiets.yeuCauDichVu.nhanVienPhuTrach', 'maGiamGia']);

        $user = $request->user();
        $vaiTro = $user->vai_tro ?? '';
        if ($user && !in_array($vaiTro, ['SUPER_ADMIN', 'ADMIN', 'STAFF_SUPPORT', 'STAFF_SERVICE'], true)) {
            $query->where('nguoi_dung_id', $user->id);
        }

        $order = $query->firstOrFail();

        $data = $order->toArray();
        $isPaid = $order->isPaid();

        $data['order_code'] = $order->ma_don_hang;
        $data['total'] = $order->tong_tien;
        $data['subtotal'] = $order->tam_tinh;
        $data['discount_total'] = $order->tong_giam_gia;
        $data['status'] = $order->trang_thai;
        $data['payment_method_code'] = $order->phuong_thuc_thanh_toan;
        $data['created_at'] = $order->ngay_tao;

        $data['items'] = collect($order->chiTiets)->map(function (ChiTietDonHang $item) use ($isPaid) {
            $itemData = $item->toArray();
            $itemData['product_name'] = $item->ten_san_pham_luu_lai;
            $itemData['variant_name'] = $item->ten_bien_the_luu_lai;
            $itemData['unit_price'] = $item->don_gia;
            $itemData['line_total'] = $item->thanh_tien;
            $itemData['quantity'] = $item->so_luong;
            $itemData['thumbnail_url'] = $item->anh_san_pham_luu_lai;
            $itemData['product'] = $item->sanPham;

            if ($isPaid && $item->taiKhoanGame) {
                $thongTin = $item->taiKhoanGame->giaiMaThongTin();
                $extra = is_array($thongTin['thong_tin_phu'] ?? null) ? $thongTin['thong_tin_phu'] : [];
                unset($extra['cost_price'], $extra['gia_nhap']);
                $itemData['credentials'] = [
                    'username' => $thongTin['ten_dang_nhap'],
                    'password' => $thongTin['mat_khau'],
                    'extra_info' => !empty($extra) ? $extra : null,
                    'ten_dang_nhap' => $thongTin['ten_dang_nhap'],
                    'mat_khau' => $thongTin['mat_khau'],
                    'thong_tin_phu' => !empty($extra) ? $extra : null,
                ];
                $itemData['thong_tin_tai_khoan'] = $itemData['credentials'];
            }
            return $itemData;
        });

        $data['chi_tiets'] = $data['items'];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $data,
            'data' => $data,
        ]);
    }

    /**
     * Tạo đơn hàng mới (Checkout).
     */
    public function taoDonHang(Request $request): JsonResponse
    {
        $danhSachMuc = $request->chi_tiets ?? $request->items ?? [];
        if (empty($danhSachMuc) || !is_array($danhSachMuc)) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Giỏ hàng của bạn đang trống.',
                'error' => ['code' => 'EMPTY_CART', 'message' => 'Giỏ hàng trống.']
            ], 422);
        }

        $maGiamGiaCode = $request->ma_giam_gia ?? $request->ma_khuyen_mai ?? $request->coupon_code;
        $phuongThucThanhToan = $request->phuong_thuc_thanh_toan ?? $request->payment_method_code ?? 'vnpay';
        $ghiChuKhachHang = $request->ghi_chu_khach_hang ?? $request->customer_notes ?? null;

        $user = $request->user();

        $order = DB::transaction(function () use ($danhSachMuc, $maGiamGiaCode, $phuongThucThanhToan, $ghiChuKhachHang, $user, $request) {
            $preparedItems = [];
            $subtotal = 0;

            foreach ($danhSachMuc as $requestedItem) {
                $variantId = $requestedItem['bien_the_id'] ?? $requestedItem['variant_id'] ?? null;
                $gameAccountId = $requestedItem['tai_khoan_game_id'] ?? $requestedItem['game_account_id'] ?? null;
                $quantity = (int) ($requestedItem['so_luong'] ?? $requestedItem['quantity'] ?? 1);
                $serviceData = $requestedItem['du_lieu_dich_vu'] ?? $requestedItem['service_data'] ?? null;

                if (!$variantId) {
                    throw ValidationException::withMessages(['items' => ['Thông tin sản phẩm không hợp lệ.']]);
                }

                $variant = BienTheSanPham::with('sanPham')->lockForUpdate()->findOrFail($variantId);
                $product = $variant->sanPham;

                if (!$product || $product->trang_thai !== 'ACTIVE' || $variant->trang_thai !== 'ACTIVE') {
                    throw ValidationException::withMessages(['items' => ['Một sản phẩm trong đơn không còn khả dụng.']]);
                }

                $unitPrice = $variant->gia_khuyen_mai ?: $variant->gia_goc;
                $lineTotal = (int) $unitPrice * $quantity;
                $accounts = collect();

                if ($product->loai_san_pham === 'ACCOUNT') {
                    if (!empty($gameAccountId)) {
                        if ($quantity !== 1) {
                            throw ValidationException::withMessages(['items' => ['Mỗi tài khoản game được bán riêng lẻ, số lượng luôn là 1.']]);
                        }
                        $accounts = TaiKhoanGame::whereKey($gameAccountId)
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
                        throw ValidationException::withMessages(['items' => ["Sản phẩm '{$product->ten_san_pham}' không đủ hàng trong kho."]]);
                    }
                }

                $preparedItems[] = compact('variant', 'product', 'quantity', 'unitPrice', 'lineTotal', 'accounts', 'serviceData');
                $subtotal += $lineTotal;
            }

            $coupon = null;
            $discount = 0;
            if (!empty($maGiamGiaCode)) {
                $coupon = MaGiamGia::where('ma_khuyen_mai', strtoupper($maGiamGiaCode))
                    ->where('trang_thai', 'ACTIVE')
                    ->lockForUpdate()
                    ->first();

                if (!$coupon) {
                    throw ValidationException::withMessages(['ma_giam_gia' => ['Mã giảm giá không tồn tại hoặc đã ngừng hoạt động.']]);
                }

                $kiemTra = $coupon->kiemTraHopLe($user->id, $subtotal);
                if (!$kiemTra['hop_le']) {
                    throw ValidationException::withMessages(['ma_giam_gia' => [$kiemTra['thong_bao']]]);
                }

                $discount = (int) $coupon->tinhSoTienGiam($subtotal);
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
                'phuong_thuc_thanh_toan' => $phuongThucThanhToan,
                'ghi_chu_khach_hang' => $ghiChuKhachHang,
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
                            'het_han_bao_hanh_luc' => $product->chinhSachBaoHanh?->so_ngay_bao_hanh
                                ? now()->addDays($product->chinhSachBaoHanh->so_ngay_bao_hanh)
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
                        'so_luong_ton_kho' => TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count(),
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
                            'nguoi_dung_id' => $user->id,
                            'du_lieu_bieu_mau' => [
                                'server' => $serviceData['server'] ?? $serviceData['may_chu'] ?? null,
                                'ingame_name' => $serviceData['ingame_name'] ?? $serviceData['ten_nhan_vat'] ?? null,
                                'current_rank' => $serviceData['current_rank'] ?? $serviceData['rank_hien_tai'] ?? null,
                                'target_rank' => $serviceData['target_rank'] ?? $serviceData['rank_mong_muon'] ?? null,
                                'target_hero' => $serviceData['target_hero'] ?? $serviceData['tuong_yeu_cau'] ?? null,
                                'variant_name' => $variant->ten_bien_the,
                            ],
                            'thong_tin_dang_nhap_ma_hoa' => !empty($loginInfo) ? \Illuminate\Support\Facades\Crypt::encryptString(json_encode($loginInfo)) : null,
                            'ghi_chu_khach_hang' => $serviceData['notes'] ?? $serviceData['ghi_chu'] ?? $ghiChuKhachHang ?? null,
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
                    'thoi_gian_dung' => now(),
                ]);
                $coupon->increment('so_lan_da_dung');
            }

            // Xóa sạch giỏ hàng của user sau khi đặt hàng thành công
            GioHang::where('nguoi_dung_id', $user->id)->first()?->chiTiets()->delete();

            return $order->load('chiTiets');
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đặt hàng thành công. Vui lòng tiến hành thanh toán.',
            'message' => 'Đặt hàng thành công. Vui lòng thanh toán.',
            'du_lieu' => [
                'don_hang' => $order,
                'order' => $order,
                'thanh_toan' => [
                    'so_tien' => $order->tong_tien,
                    'amount' => $order->tong_tien,
                    'phuong_thuc' => $order->phuong_thuc_thanh_toan,
                    'method_code' => $order->phuong_thuc_thanh_toan,
                ],
                'payment' => [
                    'amount' => $order->tong_tien,
                    'method_code' => $order->phuong_thuc_thanh_toan,
                ],
            ],
            'data' => [
                'order' => $order,
                'payment' => [
                    'amount' => $order->tong_tien,
                    'method_code' => $order->phuong_thuc_thanh_toan,
                ],
            ],
        ], 201);
    }

    /**
     * Hủy đơn hàng.
     */
    public function huyDonHang(Request $request, string $orderCode): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $orderCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('trang_thai', 'PENDING_PAYMENT')
            ->with('chiTiets')
            ->firstOrFail();

        DB::transaction(function () use ($order, $request) {
            $order->update([
                'trang_thai' => 'CANCELLED',
                'huy_luc' => now(),
                'ly_do_huy' => $request->ly_do ?? $request->input('reason') ?? 'Người dùng tự hủy',
            ]);

            $reservedItems = $order->chiTiets->whereNotNull('tai_khoan_game_id');
            TaiKhoanGame::whereIn('id', $reservedItems->pluck('tai_khoan_game_id'))
                ->where('trang_thai', 'RESERVED')
                ->update(['trang_thai' => 'AVAILABLE', 'giu_cho_boi_don_hang_id' => null, 'giu_cho_den_khi' => null]);

            foreach ($reservedItems->groupBy('bien_the_id') as $variantId => $items) {
                $availableCount = TaiKhoanGame::where('bien_the_id', $variantId)->where('trang_thai', 'AVAILABLE')->count();
                BienTheSanPham::whereKey($variantId)->update(['so_luong_ton_kho' => $availableCount]);
            }

            if ($order->ma_giam_gia_id) {
                LichSuDungMaGiamGia::where('don_hang_id', $order->id)->where('trang_thai', 'USED')->update(['trang_thai' => 'REVERTED']);
                MaGiamGia::whereKey($order->ma_giam_gia_id)->decrement('so_lan_da_dung');
            }
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã hủy đơn hàng thành công.',
            'message' => 'Đã huỷ đơn hàng thành công.',
        ]);
    }

    /**
     * Xem tài khoản mật khẩu sau khi đơn hàng đã thanh toán.
     */
    public function xemThongTinTaiKhoan(Request $request, string $orderCode, ChiTietDonHang $item): JsonResponse
    {
        $matKhau = $request->mat_khau ?? $request->password;
        if (!$matKhau) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Vui lòng nhập mật khẩu tài khoản để xem thông tin.',
                'error' => ['message' => 'Mật khẩu là bắt buộc.']
            ], 422);
        }

        $user = $request->user();
        if (!Hash::check($matKhau, $user->mat_khau_ma_hoa)) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Mật khẩu không chính xác.',
                'error' => ['message' => 'Mật khẩu không chính xác.']
            ], 422);
        }

        $order = DonHang::where('ma_don_hang', $orderCode)->where('nguoi_dung_id', $user->id)->firstOrFail();
        abort_unless($order->isPaid() && $item->don_hang_id === $order->id && $item->tai_khoan_game_id, 404);

        $taiKhoan = TaiKhoanGame::findOrFail($item->tai_khoan_game_id);
        $thongTin = $taiKhoan->giaiMaThongTin();

        $res = [
            'ten_dang_nhap' => $thongTin['ten_dang_nhap'],
            'mat_khau' => $thongTin['mat_khau'],
            'thong_tin_phu' => $thongTin['thong_tin_phu'],
            'username' => $thongTin['ten_dang_nhap'],
            'password' => $thongTin['mat_khau'],
            'extra_info' => $thongTin['thong_tin_phu'],
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $res,
            'data' => $res,
        ]);
    }
}

