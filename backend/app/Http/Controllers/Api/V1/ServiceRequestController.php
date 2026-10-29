<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = ServiceRequest::where('user_id', $request->user()->id)
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
        $service = ServiceRequest::where('request_code', $requestCode)
            ->where('user_id', $request->user()->id)
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
            'order_item_id' => 'required|exists:order_items,id',
            'form_data' => 'nullable|array',
            'game_login' => 'nullable|string|max:2000',
            'customer_note' => 'nullable|string|max:500',
        ]);

        $item = OrderItem::with(['order', 'product'])->findOrFail($validated['order_item_id']);
        if ($item->order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Không có quyền thực hiện.'], 403);
        }
        if ($item->item_type !== 'SERVICE' || !in_array($item->order->status, ['PAID', 'PROCESSING', 'COMPLETED'], true)) {
            return response()->json(['success' => false, 'message' => 'Yêu cầu dịch vụ chỉ tạo được cho đơn dịch vụ đã thanh toán.'], 422);
        }
        if (ServiceRequest::where('order_item_id', $item->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Dòng hàng này đã có yêu cầu dịch vụ.'], 409);
        }

        $service = ServiceRequest::create([
            'request_code' => 'SRV-' . strtoupper(Str::random(8)),
            'order_item_id' => $item->id,
            'user_id' => $request->user()->id,
            'game_id' => $item->product?->game_id,
            'form_data' => $validated['form_data'] ?? [],
            'game_login_enc' => isset($validated['game_login']) ? Crypt::encryptString($validated['game_login']) : null,
            'customer_note' => $validated['customer_note'] ?? null,
            'status' => 'PENDING',
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
        $service = ServiceRequest::where('request_code', $requestCode)
            ->where('user_id', $request->user()->id)
            ->where('status', 'DONE')
            ->firstOrFail();

        $service->update(['confirmed_at' => now()]);
        $service->orderItem()->update(['fulfillment_status' => 'COMPLETED']);

        return response()->json(['success' => true, 'data' => $service->fresh()]);
    }
}
