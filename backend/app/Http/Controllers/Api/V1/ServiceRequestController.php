<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = ServiceRequest::where('user_id', $request->user()->id)
            ->with(['order', 'staff'])
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $service = ServiceRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['order', 'staff'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_item_id' => 'nullable|exists:order_items,id',
            'service_type' => 'required|string',
            'input_data' => 'required|array',
            'customer_notes' => 'nullable|string|max:1000',
        ]);

        $service = ServiceRequest::create([
            'order_id' => $validated['order_id'],
            'order_item_id' => $validated['order_item_id'] ?? null,
            'user_id' => $request->user()->id,
            'service_type' => $validated['service_type'],
            'input_data' => $validated['input_data'],
            'status' => 'pending',
            'progress_percentage' => 0,
            'customer_notes' => $validated['customer_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Yêu cầu dịch vụ đã được gửi.',
            'data' => $service,
        ], 201);
    }
}
