<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GioHang;
use App\Models\ChiTietGioHang;
use App\Models\MaGiamGia;
use App\Models\LichSuDungMaGiamGia;
use App\Models\TaiKhoanGame;
use App\Models\BienTheSanPham;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GioHangController extends Controller
{
    /**
     * Xem giỏ hàng của người dùng hiện tại.
     */
    public function xemGioHang(Request $request): JsonResponse
    {
        TaiKhoanGame::giaiPhongGiuChoHetHan();

        $gioHang = GioHang::firstOrCreate(['nguoi_dung_id' => $request->user()->id]);
        $gioHang->load(['chiTiets.bienThe.sanPham', 'chiTiets.taiKhoanGame']);

        $data = $this->dinhDangGioHang($gioHang);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $data,
            'data' => $data,
        ]);
    }

    /**
     * Thêm sản phẩm vào giỏ hàng.
     */
    public function themSanPham(Request $request): JsonResponse
    {
        TaiKhoanGame::giaiPhongGiuChoHetHan();

        $bienTheId = $request->bien_the_id ?? $request->variant_id;
        $taiKhoanGameId = $request->tai_khoan_game_id ?? $request->game_account_id;
        $soLuong = (int) ($request->so_luong ?? $request->quantity ?? 1);

        if (!$bienTheId) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Vui lòng chọn biến thể sản phẩm.',
                'error' => ['code' => 'MISSING_VARIANT', 'message' => 'Vui lòng chọn biến thể sản phẩm.']
            ], 422);
        }

        $bienThe = BienTheSanPham::with('sanPham')->findOrFail($bienTheId);

        if ($bienThe->sanPham->trang_thai !== 'ACTIVE' || $bienThe->trang_thai !== 'ACTIVE') {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Sản phẩm hoặc gói này hiện không khả dụng.',
                'error' => ['code' => 'UNAVAILABLE', 'message' => 'Sản phẩm không khả dụng.']
            ], 422);
        }

        if (in_array($bienThe->sanPham->loai_san_pham, ['ACCOUNT', 'TAI_KHOAN'])) {
            $tonKho = TaiKhoanGame::where('bien_the_id', $bienThe->id)->where('trang_thai', 'AVAILABLE')->count();
            if ($tonKho <= 0) {
                return response()->json([
                    'thanh_cong' => false,
                    'success' => false,
                    'thong_bao' => 'Sản phẩm tài khoản này đã hết hàng.',
                    'error' => ['code' => 'OUT_OF_STOCK', 'message' => 'Sản phẩm tài khoản này đã hết hàng.']
                ], 422);
            }
        }

        $taiKhoan = null;
        if (!empty($taiKhoanGameId)) {
            $taiKhoan = TaiKhoanGame::whereKey($taiKhoanGameId)
                ->where('san_pham_id', $bienThe->san_pham_id)
                ->where('bien_the_id', $bienThe->id)
                ->where('trang_thai', 'AVAILABLE')
                ->first();
            if (!$taiKhoan) {
                return response()->json([
                    'thanh_cong' => false,
                    'success' => false,
                    'thong_bao' => 'Tài khoản này vừa được người khác chọn hoặc đã hết.',
                    'error' => ['code' => 'ACCOUNT_UNAVAILABLE', 'message' => 'Tài khoản không khả dụng.']
                ], 409);
            }
        }

        $gioHang = GioHang::firstOrCreate(['nguoi_dung_id' => $request->user()->id]);

        $item = $taiKhoan
            ? ChiTietGioHang::firstOrCreate(
                ['gio_hang_id' => $gioHang->id, 'tai_khoan_game_id' => $taiKhoan->id],
                ['bien_the_id' => $bienThe->id, 'so_luong' => 1]
            )
            : ChiTietGioHang::firstOrCreate(
                ['gio_hang_id' => $gioHang->id, 'bien_the_id' => $bienThe->id, 'tai_khoan_game_id' => null],
                ['so_luong' => $soLuong]
            );

        if (!$taiKhoan && !$item->wasRecentlyCreated) {
            $item->increment('so_luong', $soLuong);
        }

        $item->load(['bienThe.sanPham', 'taiKhoanGame']);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $item,
            'data' => $item,
        ], 201);
    }

    /**
     * Cập nhật số lượng mục giỏ hàng.
     */
    public function capNhatSoLuong(Request $request, ChiTietGioHang $item): JsonResponse
    {
        $soLuong = (int) ($request->so_luong ?? $request->quantity ?? 1);

        $gioHang = GioHang::where('nguoi_dung_id', $request->user()->id)->firstOrFail();
        if ($item->gio_hang_id !== $gioHang->id) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Không tìm thấy mục này trong giỏ hàng.',
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy mục trong giỏ hàng.']
            ], 404);
        }

        if ($item->tai_khoan_game_id && $soLuong !== 1) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Mỗi tài khoản game là duy nhất, số lượng luôn là 1.',
                'error' => ['code' => 'INVALID_QUANTITY', 'message' => 'Số lượng tài khoản luôn là 1.']
            ], 422);
        }

        $item->update(['so_luong' => $soLuong]);
        $item->load(['bienThe.sanPham', 'taiKhoanGame']);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $item,
            'data' => $item,
        ]);
    }

    /**
     * Xóa mục khỏi giỏ hàng.
     */
    public function xoaMuc(Request $request, ChiTietGioHang $item): JsonResponse
    {
        $gioHang = GioHang::where('nguoi_dung_id', $request->user()->id)->firstOrFail();
        if ($item->gio_hang_id !== $gioHang->id) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Không tìm thấy mục trong giỏ hàng.',
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy mục trong giỏ hàng.']
            ], 404);
        }

        $item->delete();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
        ]);
    }

    /**
     * Kiểm tra tính hợp lệ của mã giảm giá.
     */
    public function kiemTraMaGiamGia(Request $request): JsonResponse
    {
        $ma = $request->ma_khuyen_mai ?? $request->ma_giam_gia ?? $request->code;
        $tongTien = (float) ($request->tong_tien ?? $request->order_total ?? $request->subtotal ?? 0);

        if (!$ma) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Vui lòng nhập mã giảm giá.',
                'error' => ['code' => 'COUPON_REQUIRED', 'message' => 'Vui lòng nhập mã giảm giá.']
            ], 422);
        }

        $maGiamGia = MaGiamGia::where('ma_khuyen_mai', strtoupper($ma))
            ->where('trang_thai', 'ACTIVE')
            ->first();

        if (!$maGiamGia) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Mã giảm giá không tồn tại hoặc đã hết hạn.',
                'error' => ['code' => 'COUPON_INVALID', 'message' => 'Mã giảm giá không hợp lệ.']
            ], 422);
        }

        $kiemTra = $maGiamGia->kiemTraHopLe($request->user()->id, $tongTien);
        if (!$kiemTra['hop_le']) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => $kiemTra['thong_bao'],
                'error' => ['code' => 'COUPON_UNAVAILABLE', 'message' => $kiemTra['thong_bao']]
            ], 422);
        }

        $soTienGiam = $maGiamGia->tinhSoTienGiam($tongTien);

        $payload = [
            'so_tien_giam' => (int) $soTienGiam,
            'discount_amount' => (int) $soTienGiam,
            'ma_giam_gia' => [
                'id' => $maGiamGia->id,
                'ma_khuyen_mai' => $maGiamGia->ma_khuyen_mai,
                'ten_ma_giam_gia' => $maGiamGia->ten_ma_giam_gia,
                'loai_giam_gia' => $maGiamGia->loai_giam_gia,
                'gia_tri_giam' => $maGiamGia->gia_tri_giam,
                'giam_toi_da' => $maGiamGia->giam_toi_da,
                // aliases
                'code' => $maGiamGia->ma_khuyen_mai,
                'name' => $maGiamGia->ten_ma_giam_gia,
                'discount_type' => $maGiamGia->loai_giam_gia,
                'discount_value' => $maGiamGia->gia_tri_giam,
                'max_discount_amount' => $maGiamGia->giam_toi_da,
            ],
            'coupon' => [
                'id' => $maGiamGia->id,
                'code' => $maGiamGia->ma_khuyen_mai,
                'name' => $maGiamGia->ten_ma_giam_gia,
                'discount_type' => $maGiamGia->loai_giam_gia,
                'discount_value' => $maGiamGia->gia_tri_giam,
                'max_discount_amount' => $maGiamGia->giam_toi_da,
            ],
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $payload,
            'data' => $payload,
        ]);
    }

    private function dinhDangGioHang(GioHang $gioHang): array
    {
        $arr = $gioHang->toArray();
        $items = collect($gioHang->chiTiets)->map(function ($ct) {
            $bienThe = $ct->bienThe;
            $sanPham = $bienThe?->sanPham;
            return [
                'id' => $ct->id,
                'gio_hang_id' => $ct->gio_hang_id,
                'bien_the_id' => $ct->bien_the_id,
                'tai_khoan_game_id' => $ct->tai_khoan_game_id,
                'variant_id' => $ct->bien_the_id,
                'game_account_id' => $ct->tai_khoan_game_id,
                'so_luong' => $ct->so_luong,
                'quantity' => $ct->so_luong,
                'bien_the' => $bienThe,
                'variant' => $bienThe ? [
                    'id' => $bienThe->id,
                    'name' => $bienThe->ten_bien_the,
                    'sku' => $bienThe->ma_bien_the,
                    'price' => $bienThe->gia_goc,
                    'sale_price' => $bienThe->gia_khuyen_mai,
                    'stock_quantity' => $bienThe->so_luong_ton_kho,
                    'product' => $sanPham ? [
                        'id' => $sanPham->id,
                        'name' => $sanPham->ten_san_pham,
                        'slug' => $sanPham->duong_dan_tinh,
                        'type' => in_array($sanPham->loai_san_pham, ['TAI_KHOAN', 'ACCOUNT']) ? 'ACCOUNT' : 'SERVICE',
                        'loai_san_pham' => $sanPham->loai_san_pham,
                        'thumbnail_url' => $sanPham->anh_dai_dien,
                    ] : null,
                ] : null,
                'tai_khoan_game' => $ct->taiKhoanGame,
                'game_account' => $ct->taiKhoanGame ? [
                    'id' => $ct->taiKhoanGame->id,
                    'image_url' => $ct->taiKhoanGame->anh_minh_hoa ?: $sanPham?->anh_dai_dien,
                    'public_details' => $ct->taiKhoanGame->thong_tin_cong_khai ?? [],
                    'price' => $bienThe?->gia_khuyen_mai ?? $bienThe?->gia_goc ?? $sanPham?->gia_thap_nhat,
                ] : null,
            ];
        });

        $arr['chi_tiets'] = $items;
        $arr['items'] = $items;
        return $arr;
    }
}

