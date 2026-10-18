<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminStaffController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => AdminUser::with('roles')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:admin_users',
            'email' => 'required|email|unique:admin_users',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string',
            'role_ids' => 'array',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $admin = AdminUser::create($validated);

        if (!empty($validated['role_ids'])) {
            $admin->roles()->sync($validated['role_ids']);
        }

        return response()->json(['success' => true, 'data' => $admin], 201);
    }
}
