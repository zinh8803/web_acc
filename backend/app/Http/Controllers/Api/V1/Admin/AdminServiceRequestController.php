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
        $services = ServiceRequest::with(['user', 'order', 'staff'])->orderByDesc('id')->paginate(15);
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function show(int $id): JsonResponse
    {
        $service = ServiceRequest::with(['user', 'order', 'staff'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $service]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $service = ServiceRequest::findOrFail($id);
        $service->update($request->only(['status', 'progress_percentage', 'internal_notes', 'assigned_staff_id']));
        return response()->json(['success' => true, 'data' => $service]);
    }
}
