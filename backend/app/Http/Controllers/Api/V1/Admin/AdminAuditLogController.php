<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NhatKyHoatDong;
use Illuminate\Http\JsonResponse;

class AdminAuditLogController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = NhatKyHoatDong::with('adminUser:id,ten_dang_nhap,ho_ten')->orderByDesc('id')->paginate(30);
        return response()->json(['success' => true, 'data' => $logs]);
    }
}
