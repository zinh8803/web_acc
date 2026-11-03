<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaiDat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => CaiDat::all()]);
    }

    public function update(Request $request): JsonResponse
    {
        $settings = $request->input('settings', []);
        foreach ($settings as $key => $value) {
            CaiDat::updateOrCreate(
                ['khoa_cai_dat' => $key],
                [
                    'gia_tri_cai_dat' => is_array($value) ? json_encode($value) : $value,
                    'nguoi_cap_nhat_id' => $request->user()?->id,
                ]
            );
        }
        return response()->json(['success' => true, 'message' => 'Đã lưu cấu hình.']);
    }
}
