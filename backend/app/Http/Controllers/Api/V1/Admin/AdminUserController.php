<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::orderByDesc('id')->paginate(20);
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with(['orders'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['status' => $request->get('status', 'active')]);
        return response()->json(['success' => true, 'data' => $user]);
    }
}
