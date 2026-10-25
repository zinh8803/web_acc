<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GameAccount;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGameAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'status' => 'nullable|in:AVAILABLE,RESERVED,SOLD,LOCKED,RECOVERED,RETIRED',
            'search' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $query = GameAccount::with('product')->orderByDesc('id');

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('product', fn ($products) => $products->where('name', 'like', '%' . $filters['search'] . '%'));
        }

        $accounts = $query->paginate($filters['per_page'] ?? 20);

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
            'actor_type' => 'ADMIN',
            'actor_id' => $request->user()->id,
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
        if (is_string($request->input('public_details'))) {
            $request->merge(['public_details' => json_decode($request->input('public_details'), true)]);
        }
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'username' => 'required|string',
            'password' => 'required|string',
            'sec_email' => 'nullable|string',
            'sec_phone' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'image_file' => 'nullable|image|max:5120',
            'public_details' => 'nullable|array',
            'public_details.*.label' => 'required_with:public_details|string|max:80',
            'public_details.*.value' => 'required_with:public_details|string|max:255',
        ]);

        $product = Product::with('variants')->findOrFail($validated['product_id']);
        $variant = $product->variants->first();
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm chưa có phiên bản để nhập kho.'], 422);
        }

        $imageUrl = $request->hasFile('image_file')
            ? Storage::disk('public')->url($request->file('image_file')->store('game-accounts', 'public'))
            : null;

        $account = DB::transaction(function () use ($validated, $product, $variant, $imageUrl) {
            $account = GameAccount::createWithEncryption(
                productId: $product->id,
                variantId: $variant->id,
                username: $validated['username'],
                password: $validated['password'],
                extra: [
                    'sec_email' => $validated['sec_email'] ?? null,
                    'sec_phone' => $validated['sec_phone'] ?? null,
                    'additional_info' => $validated['additional_info'] ?? null,
                    'cost_price' => $validated['cost_price'] ?? null,
                ]
            );
            $account->update([
                'image_url' => $imageUrl,
                'public_details' => $validated['public_details'] ?? null,
            ]);
            $variant->update([
                'stock_quantity' => GameAccount::where('variant_id', $variant->id)->where('status', 'AVAILABLE')->count(),
            ]);
            return $account;
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã nhập tài khoản vào kho an toàn (Mã hoá AES-256-GCM).',
            'data' => $account,
        ], 201);
    }

    public function updateListing(Request $request, int $id): JsonResponse
    {
        if (is_string($request->input('public_details'))) {
            $request->merge(['public_details' => json_decode($request->input('public_details'), true)]);
        }
        $validated = $request->validate([
            'image_file' => 'nullable|image|max:5120',
            'public_details' => 'nullable|array',
            'public_details.*.label' => 'required_with:public_details|string|max:80',
            'public_details.*.value' => 'required_with:public_details|string|max:255',
        ]);

        $account = GameAccount::findOrFail($id);
        $account->public_details = $validated['public_details'] ?? [];
        if ($request->hasFile('image_file')) {
            $account->image_url = Storage::disk('public')->url($request->file('image_file')->store('game-accounts', 'public'));
        }
        $account->save();

        return response()->json(['success' => true, 'data' => $account]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (is_string($request->input('public_details'))) {
            $request->merge(['public_details' => json_decode($request->input('public_details'), true)]);
        }
        $validated = $request->validate([
            'username' => 'sometimes|required|string|max:255',
            'password' => 'sometimes|required|string|max:255',
            'sec_email' => 'sometimes|nullable|string|max:255',
            'sec_phone' => 'sometimes|nullable|string|max:50',
            'additional_info' => 'sometimes|nullable|string|max:5000',
            'cost_price' => 'sometimes|nullable|numeric|min:0',
            'image_file' => 'nullable|image|max:5120',
            'public_details' => 'sometimes|nullable|array',
            'public_details.*.label' => 'required_with:public_details|string|max:80',
            'public_details.*.value' => 'required_with:public_details|string|max:255',
        ]);

        $account = GameAccount::findOrFail($id);
        $credentials = $account->decryptCredentials();
        $extra = is_array($credentials['extra'] ?? null) ? $credentials['extra'] : [];
        foreach (['sec_email', 'sec_phone', 'additional_info', 'cost_price'] as $key) {
            if (array_key_exists($key, $validated)) {
                $extra[$key] = $validated[$key];
            }
        }

        if (array_key_exists('username', $validated)) {
            $account->account_username_enc = Crypt::encryptString($validated['username']);
        }
        if (array_key_exists('password', $validated)) {
            $account->account_password_enc = Crypt::encryptString($validated['password']);
        }
        $account->extra_info_enc = $extra ? Crypt::encryptString(json_encode($extra, JSON_UNESCAPED_UNICODE)) : null;
        if (array_key_exists('cost_price', $validated)) {
            $account->cost_price = $validated['cost_price'];
        }
        if (array_key_exists('public_details', $validated)) {
            $account->public_details = $validated['public_details'] ?? [];
        }
        if ($request->hasFile('image_file')) {
            $account->image_url = Storage::disk('public')->url($request->file('image_file')->store('game-accounts', 'public'));
        }
        $account->save();

        return response()->json(['success' => true, 'message' => 'Đã cập nhật thông tin tài khoản.', 'data' => $account]);
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
