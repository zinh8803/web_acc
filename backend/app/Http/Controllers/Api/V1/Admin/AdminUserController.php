<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = NguoiDung::orderByDesc('id')->paginate(20);
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function show(int $id): JsonResponse
    {
        $user = NguoiDung::with(['donHangs'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = NguoiDung::findOrFail($id);
        $status = $request->get('status', $request->get('trang_thai', 'ACTIVE'));
        $user->update(['trang_thai' => strtoupper($status)]);
        return response()->json(['success' => true, 'data' => $user]);
    }
}
