<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminStaffController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => AdminUser::whereIn('role', ['SUPER_ADMIN', 'STAFF_SERVICE', 'STAFF_SUPPORT'])->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string',
            'role' => 'nullable|string|in:SUPER_ADMIN,STAFF_SERVICE,STAFF_SUPPORT',
        ]);

        $admin = AdminUser::create([
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'full_name' => $validated['full_name'],
            'role' => $validated['role'] ?? 'STAFF_SUPPORT',
        ]);

        return response()->json(['success' => true, 'data' => $admin], 201);
    }
}
