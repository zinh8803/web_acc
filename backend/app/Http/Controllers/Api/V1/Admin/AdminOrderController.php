<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DonHang::with(['user:id,email,ho_ten,so_dien_thoai'])->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ma_don_hang', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if ($status = $request->get('status')) {
            $query->where('trang_thai', strtoupper($status));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->get('per_page', 20)),
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $order = DonHang::with(['user', 'items.product', 'items.gameAccount', 'items.serviceRequest.staff', 'coupon'])
            ->where('ma_don_hang', $code)
            ->orWhere('id', $code)
            ->firstOrFail();

        $data = $order->toArray();
        $data['items'] = $order->items->map(function ($item) {
            $itemData = $item->toArray();
            if ($item->gameAccount) {
                $credentials = $item->gameAccount->decryptCredentials();
                $itemData['credentials'] = [
                    'username' => $credentials['username'],
                    'password' => $credentials['password'],
                    'extra_info' => $credentials['extra'] ?? null,
                ];
            }
            return $itemData;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function updateStatus(Request $request, string $code): JsonResponse
    {
        $order = DonHang::where('ma_don_hang', $code)->orWhere('id', $code)->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        $newStatus = strtoupper($validated['status']);
        $wasPaid = $order->isPaid();

        $updateData = ['trang_thai' => $newStatus];
        if (!$order->thanh_toan_luc && in_array($newStatus, ['PAID', 'PROCESSING', 'COMPLETED'], true)) {
            $updateData['thanh_toan_luc'] = now();
        }
        $order->update($updateData);

        // Nếu chuyển sang trạng thái đã thanh toán => gửi thông tin tài khoản & dịch vụ về mail
        if (!$wasPaid && in_array($newStatus, ['PAID', 'PROCESSING', 'COMPLETED'], true)) {
            \App\Services\OrderMailService::sendOrderSuccessMail($order);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái đơn hàng.',
            'data' => $order,
        ]);
    }
}
