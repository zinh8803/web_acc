<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminServiceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ServiceRequest::with(['user', 'orderItem.order', 'orderItem.product', 'staff'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $services = $query->paginate($request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function show(int $id): JsonResponse
    {
        $service = ServiceRequest::with(['user', 'orderItem.order', 'orderItem.product', 'staff'])->findOrFail($id);
        $data = $service->toArray();
        $data['credentials'] = $service->decryptGameLogin();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function credentials(int $id): JsonResponse
    {
        $service = ServiceRequest::findOrFail($id);
        $credentials = $service->decryptGameLogin();

        return response()->json([
            'success' => true,
            'data' => $credentials,
        ]);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $service = ServiceRequest::findOrFail($id);
        $validated = $request->validate([
            'assigned_to' => 'nullable|integer|exists:admin_users,id',
            'due_at' => 'nullable|date',
        ]);

        $updateData = [];
        if (array_key_exists('assigned_to', $validated)) {
            $updateData['assigned_to'] = $validated['assigned_to'];
            if ($service->status === 'PENDING') {
                $updateData['status'] = 'IN_PROGRESS';
                $updateData['started_at'] = now();
            }
        }
        if (array_key_exists('due_at', $validated)) {
            $updateData['due_at'] = $validated['due_at'];
        }

        $service->update($updateData);
        return response()->json(['success' => true, 'data' => $service->fresh(['user', 'orderItem.product', 'staff'])]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $service = ServiceRequest::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:PENDING,ASSIGNED,IN_PROGRESS,NEED_INFO,DONE,CANCELLED,REFUNDED',
            'priority' => 'sometimes|integer|min:1|max:5',
            'due_at' => 'sometimes|nullable|date',
            'cancel_reason' => 'sometimes|nullable|string|max:255',
            'assigned_to' => 'sometimes|nullable|integer',
        ]);

        if ($validated['status'] === 'IN_PROGRESS' && !$service->started_at) {
            $validated['started_at'] = now();
        }
        if ($validated['status'] === 'DONE') {
            if (!$service->completed_at) {
                $validated['completed_at'] = now();
            }
            $validated['auto_confirm_at'] = now()->addDays(3);
            $service->orderItem()->update(['fulfillment_status' => 'COMPLETED']);
        }
        if ($validated['status'] === 'CANCELLED') {
            $service->orderItem()->update(['fulfillment_status' => 'CANCELLED']);
        }

        $service->update($validated);
        return response()->json(['success' => true, 'data' => $service->fresh(['user', 'orderItem.product', 'staff'])]);
    }
}
