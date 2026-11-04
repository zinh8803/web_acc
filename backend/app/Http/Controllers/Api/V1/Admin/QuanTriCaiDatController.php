<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaiDat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuanTriCaiDatController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = CaiDat::all()->map(function ($s) {
            $arr = $s->toArray();
            $arr['key'] = $s->khoa_cai_dat;
            $arr['value'] = $s->gia_tri_cai_dat;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $settings,
            'data' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $settings = $request->input('cai_dat', $request->input('settings', []));
        foreach ($settings as $key => $value) {
            CaiDat::updateOrCreate(
                ['khoa_cai_dat' => $key],
                [
                    'gia_tri_cai_dat' => is_array($value) ? json_encode($value) : $value,
                    'nguoi_cap_nhat_id' => $request->user()?->id,
                ]
            );
        }

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã lưu cấu hình cài đặt.',
            'message' => 'Đã lưu cấu hình.',
        ]);
    }
}

