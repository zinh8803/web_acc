<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyClaim;
use App\Models\WarrantyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminWarrantyController extends Controller
{
    // ==================== WARRANTY POLICIES CRUD ====================
    public function index(): JsonResponse
    {
        $policies = WarrantyPolicy::orderByDesc('id')->get();
        return response()->json(['success' => true, 'data' => $policies]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'product_type' => 'required|in:ACCOUNT,SERVICE',
            'duration_days' => 'nullable|integer|min:1',
            'is_lifetime' => 'boolean',
            'coverage' => 'nullable|array',
            'exclusions' => 'nullable|string',
            'claim_sla_hours' => 'integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:ACTIVE,DISABLED',
        ]);

        $code = 'WAR-' . strtoupper(Str::random(6));
        $policy = WarrantyPolicy::create(array_merge($validated, [
            'code' => $code,
            'created_by' => $request->user()?->id,
        ]));

        return response()->json(['success' => true, 'data' => $policy], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $policy = WarrantyPolicy::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string|max:150',
            'product_type' => 'in:ACCOUNT,SERVICE',
            'duration_days' => 'nullable|integer',
            'is_lifetime' => 'boolean',
            'coverage' => 'nullable|array',
            'exclusions' => 'nullable|string',
            'claim_sla_hours' => 'integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:ACTIVE,DISABLED',
        ]);

        $policy->update($validated);
        return response()->json(['success' => true, 'data' => $policy]);
    }

    public function destroy(int $id): JsonResponse
    {
        $policy = WarrantyPolicy::findOrFail($id);
        $policy->delete();
        return response()->json(['success' => true, 'message' => 'Đã xoá chính sách bảo hành.']);
    }

    // ==================== WARRANTY CLAIMS MANAGEMENT ====================
    public function claims(Request $request): JsonResponse
    {
        $query = WarrantyClaim::with([
            'user',
            'orderItem.order',
            'orderItem.product',
            'policy',
            'assignedStaff',
            'reviewedBy'
        ])->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('claim_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%")
                         ->orWhere('full_name', 'like', "%{$search}%");
                  });
            });
        }

        $claims = $query->paginate($request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $claims]);
    }

    public function showClaim(string $claimId): JsonResponse
    {
        $claim = WarrantyClaim::where('id', $claimId)
            ->orWhere('claim_code', $claimId)
            ->with([
                'user',
                'orderItem.order',
                'orderItem.product',
                'orderItem.gameAccount',
                'policy',
                'assignedStaff',
                'reviewedBy'
            ])
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $claim]);
    }

    public function decide(Request $request, string $claimId): JsonResponse
    {
        $claim = WarrantyClaim::where('id', $claimId)
            ->orWhere('claim_code', $claimId)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:SUBMITTED,IN_REVIEW,NEED_INFO,APPROVED,REJECTED,RESOLVED,CANCELLED',
            'resolution_type' => 'nullable|string',
            'resolution_note' => 'nullable|string|max:1000',
            'reject_reason' => 'nullable|string|max:1000',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $updateData = [
            'status' => $validated['status'],
            'resolution_type' => $validated['resolution_type'] ?? $claim->resolution_type,
            'resolution_note' => $validated['resolution_note'] ?? $claim->resolution_note,
            'reject_reason' => $validated['reject_reason'] ?? $claim->reject_reason,
            'reviewed_by' => $request->user()?->id,
        ];

        if (isset($validated['assigned_to'])) {
            $updateData['assigned_to'] = $validated['assigned_to'];
        }

        if (in_array($validated['status'], ['APPROVED', 'RESOLVED', 'REJECTED', 'CANCELLED'], true)) {
            $updateData['resolved_at'] = now();
        }

        $claim->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật xử lý khiếu nại bảo hành thành công!',
            'data' => $claim->fresh(['user', 'orderItem.order', 'orderItem.product', 'policy', 'assignedStaff', 'reviewedBy']),
        ]);
    }
}
