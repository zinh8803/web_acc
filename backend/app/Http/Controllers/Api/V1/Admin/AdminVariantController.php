<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BienTheSanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVariantController extends Controller
{
    public function index(int $productId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => BienTheSanPham::where('san_pham_id', $productId)->get(),
        ]);
    }
}
