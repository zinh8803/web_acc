<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BienTheSanPham;
use Illuminate\Http\JsonResponse;

class QuanTriBienTheController extends Controller
{
    public function index(int $productId): JsonResponse
    {
        $variants = BienTheSanPham::where('san_pham_id', $productId)->get()->map(function ($v) {
            $arr = $v->toArray();
            $arr['name'] = $v->ten_bien_the;
            $arr['price'] = $v->gia_goc;
            $arr['sale_price'] = $v->gia_khuyen_mai;
            $arr['stock_quantity'] = $v->so_luong_ton_kho;
            $arr['status'] = $v->trang_thai;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $variants,
            'data' => $variants,
        ]);
    }
}

