<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuanTriVien;
use App\Models\YeuCauDichVu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminServiceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = YeuCauDichVu::with(['user', 'orderItem.order', 'orderItem.product', 'staff'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('trang_thai', $request->query('status'));
        }

        $services = $query->paginate($request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function show(int $id): JsonResponse
    {
        $service = YeuCauDichVu::with(['user', 'orderItem.order', 'orderItem.product', 'staff'])->findOrFail($id);
        $data = $service->toArray();
        $data['credentials'] = $service->decryptGameLogin();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function credentials(int $id): JsonResponse
    {
        $service = YeuCauDichVu::findOrFail($id);
        $credentials = $service->decryptGameLogin();

        return response()->json([
            'success' => true,
            'data' => $credentials,
        ]);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $service = YeuCauDichVu::findOrFail($id);
        $validated = $request->validate([
            'assigned_to' => 'nullable|integer|exists:quan_tri_vien,id',
            'due_at' => 'nullable|date',
        ]);

        $updateData = [];
        if (array_key_exists('assigned_to', $validated)) {
            $updateData['nhan_vien_phu_trach_id'] = $validated['assigned_to'];
            if ($service->trang_thai === 'PENDING') {
                $updateData['trang_thai'] = 'IN_PROGRESS';
                $updateData['bat_dau_luc'] = now();
            }
        }
        if (array_key_exists('due_at', $validated)) {
            $updateData['han_hoan_thanh'] = $validated['due_at'];
        }

        $service->update($updateData);
        return response()->json(['success' => true, 'data' => $service->fresh(['user', 'orderItem.product', 'staff'])]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $service = YeuCauDichVu::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:PENDING,ASSIGNED,IN_PROGRESS,NEED_INFO,DONE,CANCELLED,REFUNDED',
            'priority' => 'sometimes|integer|min:1|max:5',
            'due_at' => 'sometimes|nullable|date',
            'cancel_reason' => 'sometimes|nullable|string|max:255',
            'assigned_to' => 'sometimes|nullable|integer',
        ]);

        $updateData = ['trang_thai' => $validated['status']];
        if (isset($validated['priority'])) $updateData['do_uu_tien'] = $validated['priority'];
        if (array_key_exists('due_at', $validated)) $updateData['han_hoan_thanh'] = $validated['due_at'];
        if (array_key_exists('cancel_reason', $validated)) $updateData['ly_do_huy'] = $validated['cancel_reason'];
        if (array_key_exists('assigned_to', $validated)) $updateData['nhan_vien_phu_trach_id'] = $validated['assigned_to'];

        if ($validated['status'] === 'IN_PROGRESS' && !$service->bat_dau_luc) {
            $updateData['bat_dau_luc'] = now();
        }
        if ($validated['status'] === 'DONE') {
            if (!$service->hoan_thanh_luc) {
                $updateData['hoan_thanh_luc'] = now();
            }
            $updateData['tu_dong_xac_nhan_luc'] = now()->addDays(3);
            $service->orderItem()->update(['trang_thai_giao_hang' => 'COMPLETED']);
        }
        if ($validated['status'] === 'CANCELLED') {
            $service->orderItem()->update(['trang_thai_giao_hang' => 'CANCELLED']);
        }

        $service->update($updateData);
        return response()->json(['success' => true, 'data' => $service->fresh(['user', 'orderItem.product', 'staff'])]);
    }
}
