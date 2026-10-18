<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GameAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGameAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GameAccount::with('product')->orderByDesc('id');

        if ($productId = $request->get('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $accounts = $query->paginate($request->get('per_page', 20));

        // For listing, do NOT return plaintext credentials unless requested specifically
        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $account = GameAccount::with('product')->findOrFail($id);
        $credentials = $account->decryptCredentials();

        // Audit log that an admin viewed credentials
        AuditLog::create([
            'admin_user_id' => $request->user()->id,
            'action' => 'view_credentials',
            'entity_type' => 'game_accounts',
            'entity_id' => $account->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => array_merge($account->toArray(), [
                'credentials' => $credentials,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'username' => 'required|string',
            'password' => 'required|string',
            'sec_email' => 'nullable|string',
            'sec_phone' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        $account = GameAccount::createAccountWithEncryption(
            productId: $validated['product_id'],
            username: $validated['username'],
            password: $validated['password'],
            extra: [
                'sec_email' => $validated['sec_email'] ?? null,
                'sec_phone' => $validated['sec_phone'] ?? null,
                'additional_info' => $validated['additional_info'] ?? null,
                'cost_price' => $validated['cost_price'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã nhập tài khoản vào kho an toàn (Mã hoá AES-256-GCM).',
            'data' => $account,
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $account = GameAccount::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:available,reserved,sold,defective,warranty_held',
            'notes' => 'nullable|string',
        ]);

        $account->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái kho.',
            'data' => $account,
        ]);
    }
}
