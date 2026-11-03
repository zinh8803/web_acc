<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\YeuCauDichVu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = YeuCauDichVu::where('nguoi_dung_id', $request->user()->id)
            ->with(['orderItem.order', 'orderItem.product', 'staff'])
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function show(Request $request, string $requestCode): JsonResponse
    {
        $service = YeuCauDichVu::where('ma_yeu_cau', $requestCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->with(['orderItem.order', 'orderItem.product', 'staff'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_item_id' => 'required|exists:chi_tiet_don_hang,id',
            'form_data' => 'nullable|array',
            'game_login' => 'nullable|string|max:2000',
            'customer_note' => 'nullable|string|max:500',
        ]);

        $item = ChiTietDonHang::with(['order', 'product'])->findOrFail($validated['order_item_id']);
        if ($item->order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Không có quyền thực hiện.'], 403);
        }
        if ($item->loai_muc !== 'SERVICE' || !in_array($item->order->trang_thai, ['PAID', 'PROCESSING', 'COMPLETED'], true)) {
            return response()->json(['success' => false, 'message' => 'Yêu cầu dịch vụ chỉ tạo được cho đơn dịch vụ đã thanh toán.'], 422);
        }
        if (YeuCauDichVu::where('chi_tiet_don_hang_id', $item->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Dòng hàng này đã có yêu cầu dịch vụ.'], 409);
        }

        $service = YeuCauDichVu::create([
            'ma_yeu_cau' => 'SRV-' . strtoupper(Str::random(8)),
            'chi_tiet_don_hang_id' => $item->id,
            'nguoi_dung_id' => $request->user()->id,
            'du_lieu_bieu_mau' => $validated['form_data'] ?? [],
            'thong_tin_dang_nhap_ma_hoa' => isset($validated['game_login']) ? Crypt::encryptString($validated['game_login']) : null,
            'ghi_chu_khach_hang' => $validated['customer_note'] ?? null,
            'trang_thai' => 'PENDING',
        ]);

        // Gửi email xác nhận tiếp nhận thông tin cày thuê
        \App\Services\OrderMailService::sendServiceRequestMail($service);

        return response()->json([
            'success' => true,
            'message' => 'Yêu cầu dịch vụ đã được gửi.',
            'data' => $service,
        ], 201);
    }

    public function confirm(Request $request, string $requestCode): JsonResponse
    {
        $service = YeuCauDichVu::where('ma_yeu_cau', $requestCode)
            ->where('nguoi_dung_id', $request->user()->id)
            ->where('trang_thai', 'DONE')
            ->firstOrFail();

        $service->update(['khach_xac_nhan_luc' => now()]);
        $service->orderItem()->update(['trang_thai_giao_hang' => 'COMPLETED']);

        return response()->json(['success' => true, 'data' => $service->fresh()]);
    }
}
