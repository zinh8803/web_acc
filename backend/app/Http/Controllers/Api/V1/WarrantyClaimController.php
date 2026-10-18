<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\WarrantyClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarrantyClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $claims = WarrantyClaim::where('user_id', $request->user()->id)
            ->with(['order', 'orderItem.product'])
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $claims,
        ]);
    }

    public function show(Request $request, string $claimCode): JsonResponse
    {
        $claim = WarrantyClaim::where('claim_code', $claimCode)
            ->where('user_id', $request->user()->id)
            ->with(['order', 'orderItem.product'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $claim,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_item_id' => 'required|exists:order_items,id',
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'evidence_images' => 'nullable|array',
        ]);

        $item = OrderItem::with('order')->findOrFail($validated['order_item_id']);

        if ($item->order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Không có quyền thực hiện.'], 403);
        }

        if ($item->warranty_until && now()->gt($item->warranty_until)) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm đã hết hạn bảo hành.'], 422);
        }

        $claim = WarrantyClaim::create([
            'claim_code' => 'WAR-' . strtoupper(Str::random(6)),
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'user_id' => $request->user()->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'evidence_images' => $validated['evidence_images'] ?? [],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu bảo hành thành công.',
            'data' => $claim,
        ], 201);
    }
}
