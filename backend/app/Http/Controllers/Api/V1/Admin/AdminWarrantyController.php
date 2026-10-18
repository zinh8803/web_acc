<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWarrantyController extends Controller
{
    public function index(): JsonResponse
    {
        $claims = WarrantyClaim::with(['user', 'order'])->orderByDesc('id')->paginate(15);
        return response()->json(['success' => true, 'data' => $claims]);
    }

    public function show(int $id): JsonResponse
    {
        $claim = WarrantyClaim::with(['user', 'order', 'orderItem'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $claim]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $claim = WarrantyClaim::findOrFail($id);
        $claim->update($request->only(['status', 'resolution_type', 'resolution_notes', 'assigned_staff_id']));
        return response()->json(['success' => true, 'data' => $claim]);
    }
}
