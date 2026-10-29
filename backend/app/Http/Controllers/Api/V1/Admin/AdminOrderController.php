<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user:id,email,full_name,phone'])->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where('order_code', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', strtoupper($status));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->get('per_page', 20)),
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $order = Order::with(['user', 'items.product', 'items.gameAccount', 'items.serviceRequest.staff', 'coupon'])
            ->where('order_code', $code)
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
        $order = Order::where('order_code', $code)->orWhere('id', $code)->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        $newStatus = strtoupper($validated['status']);
        $wasPaid = $order->isPaid();

        $updateData = ['status' => $newStatus];
        if (!$order->paid_at && in_array($newStatus, ['PAID', 'PROCESSING', 'COMPLETED'], true)) {
            $updateData['paid_at'] = now();
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
