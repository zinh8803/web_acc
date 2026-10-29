<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\WarrantyClaim;
use App\Models\WarrantyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarrantyClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $claims = WarrantyClaim::where('user_id', $request->user()->id)
            ->with(['orderItem.order', 'orderItem.product', 'policy', 'assignedStaff'])
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $claims,
        ]);
    }

    public function show(Request $request, string $claimCode): JsonResponse
    {
        $claim = WarrantyClaim::where(function ($q) use ($claimCode) {
                $q->where('claim_code', $claimCode)->orWhere('id', $claimCode);
            })
            ->where('user_id', $request->user()->id)
            ->with(['orderItem.order', 'orderItem.product', 'orderItem.gameAccount', 'policy', 'assignedStaff'])
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
            'claim_type' => 'nullable|string',
            'reason' => 'nullable|string',
            'description' => 'required|string|max:2000',
        ]);

        $item = OrderItem::with(['order', 'product.warrantyPolicy', 'warrantyPolicy'])->findOrFail($validated['order_item_id']);

        if ($item->order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền khiếu nại sản phẩm này.'], 403);
        }

        if (!$item->order->isPaid()) {
            return response()->json(['success' => false, 'message' => 'Đơn hàng chưa thanh toán thành công.'], 422);
        }

        // Kiểm tra xem đã có khiếu nại đang xử lý cho sản phẩm này chưa
        $existingPendingClaim = WarrantyClaim::where('order_item_id', $item->id)
            ->whereIn('status', ['SUBMITTED', 'IN_REVIEW', 'NEED_INFO'])
            ->first();
        if ($existingPendingClaim) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm này đang có một yêu cầu bảo hành đang được xử lý (Mã: ' . $existingPendingClaim->claim_code . ').'
            ], 422);
        }

        // Kiểm tra thời hạn bảo hành
        if ($item->warranty_expires_at && now()->gt($item->warranty_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm đã hết thời hạn bảo hành.'], 422);
        }

        // Lấy Policy
        $policy = $item->warrantyPolicy ?: $item->product?->warrantyPolicy;
        if (!$policy) {
            $defaultCode = $item->item_type === 'SERVICE' ? 'WAR-SRV-COMPLETION' : 'WAR-ACC-LIFETIME';
            $policy = WarrantyPolicy::where('code', $defaultCode)->first();
            if (!$policy) {
                $policy = WarrantyPolicy::first();
            }
        }

        // Chuẩn hoá claim_type enum
        $rawType = $validated['claim_type'] ?? $validated['reason'] ?? 'OTHER';
        $validEnums = ['LOSS_ACCESS', 'RECOVERED_BY_OLD_OWNER', 'WRONG_CREDENTIALS', 'SERVICE_NOT_DONE', 'OTHER'];

        if (in_array($rawType, $validEnums, true)) {
            $claimType = $rawType;
        } elseif (str_contains(mb_strtolower($rawType), 'sai mật khẩu') || str_contains(mb_strtolower($rawType), 'sai pass')) {
            $claimType = 'WRONG_CREDENTIALS';
        } elseif (str_contains(mb_strtolower($rawType), 'back') || str_contains(mb_strtolower($rawType), 'đổi thông tin')) {
            $claimType = 'RECOVERED_BY_OLD_OWNER';
        } elseif (str_contains(mb_strtolower($rawType), 'mất') || str_contains(mb_strtolower($rawType), 'truy cập')) {
            $claimType = 'LOSS_ACCESS';
        } elseif (str_contains(mb_strtolower($rawType), 'cày') || str_contains(mb_strtolower($rawType), 'dịch vụ')) {
            $claimType = 'SERVICE_NOT_DONE';
        } else {
            $claimType = 'OTHER';
        }

        $slaHours = $policy?->claim_sla_hours ?? 24;

        $claim = WarrantyClaim::create([
            'claim_code' => 'WAR-' . strtoupper(Str::random(8)),
            'order_item_id' => $item->id,
            'user_id' => $request->user()->id,
            'game_account_id' => $item->game_account_id,
            'policy_id' => $policy->id,
            'claim_type' => $claimType,
            'description' => $validated['description'],
            'status' => 'SUBMITTED',
            'due_at' => now()->addHours($slaHours),
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu bảo hành thành công! Kỹ thuật viên sẽ xử lý trong vòng ' . $slaHours . 'h.',
            'data' => $claim->load(['orderItem.order', 'orderItem.product', 'policy']),
        ], 201);
    }
}
