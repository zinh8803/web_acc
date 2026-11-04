<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaGiamGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuanTriMaGiamGiaController extends Controller
{
    /**
     * Danh sách mã giảm giá.
     */
    public function index(): JsonResponse
    {
        $coupons = MaGiamGia::orderByDesc('id')->get()->map(function ($coupon) {
            return [
                'id' => $coupon->id,
                'ma_khuyen_mai' => $coupon->ma_khuyen_mai,
                'code' => $coupon->ma_khuyen_mai,
                'ten_ma_giam_gia' => $coupon->ten_ma_giam_gia,
                'name' => $coupon->ten_ma_giam_gia,
                'mo_ta' => $coupon->mo_ta,
                'description' => $coupon->mo_ta,
                'loai_giam_gia' => $coupon->loai_giam_gia,
                'discount_type' => $coupon->loai_giam_gia,
                'gia_tri_giam' => (float) $coupon->gia_tri_giam,
                'discount_value' => (float) $coupon->gia_tri_giam,
                'don_hang_toi_thieu' => (float) ($coupon->don_hang_toi_thieu ?? 0),
                'min_order_amount' => (float) ($coupon->don_hang_toi_thieu ?? 0),
                'giam_toi_da' => $coupon->giam_toi_da ? (float) $coupon->giam_toi_da : null,
                'max_discount_amount' => $coupon->giam_toi_da ? (float) $coupon->giam_toi_da : null,
                'gioi_han_su_dung' => $coupon->gioi_han_su_dung,
                'usage_limit' => $coupon->gioi_han_su_dung,
                'gioi_han_moi_nguoi_dung' => $coupon->gioi_han_moi_nguoi_dung ?? 1,
                'usage_per_user' => $coupon->gioi_han_moi_nguoi_dung ?? 1,
                'so_lan_da_dung' => $coupon->so_lan_da_dung ?? 0,
                'used_count' => $coupon->so_lan_da_dung ?? 0,
                'ngay_bat_dau' => $coupon->ngay_bat_dau ? $coupon->ngay_bat_dau->toIso8601String() : null,
                'starts_at' => $coupon->ngay_bat_dau ? $coupon->ngay_bat_dau->toIso8601String() : null,
                'ngay_ket_thuc' => $coupon->ngay_ket_thuc ? $coupon->ngay_ket_thuc->toIso8601String() : null,
                'ends_at' => $coupon->ngay_ket_thuc ? $coupon->ngay_ket_thuc->toIso8601String() : null,
                'trang_thai' => $coupon->trang_thai,
                'status' => $coupon->trang_thai,
            ];
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $coupons,
            'data' => $coupons
        ]);
    }

    /**
     * Tạo mã giảm giá.
     */
    public function store(Request $request): JsonResponse
    {
        $code = strtoupper(trim($request->ma_khuyen_mai ?? $request->code ?? ''));
        if (!$code) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Vui lòng nhập mã khuyến mãi.'], 422);
        }

        $name = $request->ten_ma_giam_gia ?? $request->name ?: $code;
        $type = $request->loai_giam_gia ?? $request->discount_type ?? 'PERCENT';
        $val = (float) ($request->gia_tri_giam ?? $request->discount_value ?? 0);

        $coupon = MaGiamGia::create([
            'ma_khuyen_mai' => $code,
            'ten_ma_giam_gia' => $name,
            'mo_ta' => $request->mo_ta ?? $request->description,
            'loai_giam_gia' => $type,
            'gia_tri_giam' => $val,
            'don_hang_toi_thieu' => $request->don_hang_toi_thieu ?? $request->min_order_amount ?? 0,
            'giam_toi_da' => $request->giam_toi_da ?? $request->max_discount_amount,
            'gioi_han_su_dung' => $request->gioi_han_su_dung ?? $request->usage_limit,
            'gioi_han_moi_nguoi_dung' => $request->gioi_han_moi_nguoi_dung ?? $request->usage_per_user ?? 1,
            'ngay_bat_dau' => $request->ngay_bat_dau ?? $request->starts_at,
            'ngay_ket_thuc' => $request->ngay_ket_thuc ?? $request->ends_at,
            'trang_thai' => $request->trang_thai ?? $request->status ?? 'ACTIVE',
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Tạo mã giảm giá thành công.',
            'du_lieu' => $coupon,
            'data' => $coupon,
        ], 201);
    }

    /**
     * Chi tiết mã giảm giá.
     */
    public function show(MaGiamGia $coupon): JsonResponse
    {
        return response()->json(['thanh_cong' => true, 'success' => true, 'du_lieu' => $coupon, 'data' => $coupon]);
    }

    /**
     * Cập nhật mã giảm giá.
     */
    public function update(Request $request, MaGiamGia $coupon): JsonResponse
    {
        $updateData = [];

        if ($request->has('ma_khuyen_mai') || $request->has('code')) {
            $updateData['ma_khuyen_mai'] = strtoupper(trim($request->ma_khuyen_mai ?? $request->code));
        }
        if ($request->has('ten_ma_giam_gia') || $request->has('name')) {
            $updateData['ten_ma_giam_gia'] = $request->ten_ma_giam_gia ?? $request->name;
        }
        if ($request->has('mo_ta') || $request->has('description')) {
            $updateData['mo_ta'] = $request->mo_ta ?? $request->description;
        }
        if ($request->has('loai_giam_gia') || $request->has('discount_type')) {
            $updateData['loai_giam_gia'] = $request->loai_giam_gia ?? $request->discount_type;
        }
        if ($request->has('gia_tri_giam') || $request->has('discount_value')) {
            $updateData['gia_tri_giam'] = (float) ($request->gia_tri_giam ?? $request->discount_value);
        }
        if ($request->has('don_hang_toi_thieu') || $request->has('min_order_amount')) {
            $updateData['don_hang_toi_thieu'] = (float) ($request->don_hang_toi_thieu ?? $request->min_order_amount);
        }
        if ($request->has('giam_toi_da') || $request->has('max_discount_amount')) {
            $updateData['giam_toi_da'] = $request->giam_toi_da ?? $request->max_discount_amount;
        }
        if ($request->has('gioi_han_su_dung') || $request->has('usage_limit')) {
            $updateData['gioi_han_su_dung'] = $request->gioi_han_su_dung ?? $request->usage_limit;
        }
        if ($request->has('gioi_han_moi_nguoi_dung') || $request->has('usage_per_user')) {
            $updateData['gioi_han_moi_nguoi_dung'] = $request->gioi_han_moi_nguoi_dung ?? $request->usage_per_user;
        }
        if ($request->has('ngay_bat_dau') || $request->has('starts_at')) {
            $updateData['ngay_bat_dau'] = $request->ngay_bat_dau ?? $request->starts_at;
        }
        if ($request->has('ngay_ket_thuc') || $request->has('ends_at')) {
            $updateData['ngay_ket_thuc'] = $request->ngay_ket_thuc ?? $request->ends_at;
        }
        if ($request->has('trang_thai') || $request->has('status')) {
            $updateData['trang_thai'] = $request->trang_thai ?? $request->status;
        }

        $coupon->update($updateData);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Cập nhật mã giảm giá thành công.',
            'du_lieu' => $coupon,
            'data' => $coupon
        ]);
    }

    /**
     * Xóa mã giảm giá.
     */
    public function destroy(MaGiamGia $coupon): JsonResponse
    {
        $coupon->delete();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã xóa mã giảm giá.',
            'message' => 'Đã xoá mã giảm giá.'
        ]);
    }
}

