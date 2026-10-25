<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminServiceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = ServiceRequest::with(['user', 'orderItem.order', 'orderItem.product', 'staff'])
            ->orderByDesc('id')->paginate(15);
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function show(int $id): JsonResponse
    {
        $service = ServiceRequest::with(['user', 'orderItem.order', 'orderItem.product', 'staff'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $service]);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $service = ServiceRequest::findOrFail($id);
        $validated = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);
        $staff = \App\Models\User::where('id', $validated['assigned_to'])
            ->whereIn('role', ['SUPER_ADMIN', 'STAFF_SERVICE', 'STAFF_SUPPORT'])
            ->firstOrFail();

        $service->update(['assigned_to' => $staff->id, 'status' => 'ASSIGNED']);
        return response()->json(['success' => true, 'data' => $service]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $service = ServiceRequest::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:PENDING,ASSIGNED,IN_PROGRESS,NEED_INFO,DONE,CANCELLED,REFUNDED',
            'priority' => 'sometimes|integer|min:1|max:5',
            'due_at' => 'sometimes|nullable|date',
            'cancel_reason' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validated['status'] === 'IN_PROGRESS' && !$service->started_at) {
            $validated['started_at'] = now();
        }
        if ($validated['status'] === 'DONE' && !$service->completed_at) {
            $validated['completed_at'] = now();
            $validated['auto_confirm_at'] = now()->addDays(3);
        }

        $service->update($validated);
        return response()->json(['success' => true, 'data' => $service->fresh()]);
    }
}
