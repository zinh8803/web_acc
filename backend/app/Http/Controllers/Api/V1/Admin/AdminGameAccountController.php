<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NhatKyHoatDong;
use App\Models\TaiKhoanGame;
use App\Models\SanPham;
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
            'product_id' => 'nullable|integer|exists:san_pham,id',
            'status' => 'nullable|in:AVAILABLE,RESERVED,SOLD,LOCKED,RECOVERED,RETIRED',
            'search' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $query = TaiKhoanGame::with('product')->orderByDesc('id');

        if (!empty($filters['product_id'])) {
            $query->where('san_pham_id', $filters['product_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('trang_thai', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('product', fn ($products) => $products->where('ten_san_pham', 'like', '%' . $filters['search'] . '%'));
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
        $account = TaiKhoanGame::with('product')->findOrFail($id);
        $credentials = $account->decryptCredentials();

        // Audit log that an admin viewed credentials
        NhatKyHoatDong::create([
            'loai_tac_nhan' => 'ADMIN',
            'tac_nhan_id' => $request->user()->id,
            'hanh_dong' => 'view_credentials',
            'loai_doi_tuong' => 'tai_khoan_game',
            'doi_tuong_id' => $account->id,
            'dia_chi_ip' => $request->ip(),
            'thiet_bi_trinh_duyet' => $request->userAgent(),
            'ngay_tao' => now(),
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
            'product_id' => 'required|exists:san_pham,id',
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

        $product = SanPham::with('variants')->findOrFail($validated['product_id']);
        $variant = $product->variants->first();
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm chưa có phiên bản để nhập kho.'], 422);
        }

        $imageUrl = $request->hasFile('image_file')
            ? Storage::disk('public')->url($request->file('image_file')->store('game-accounts', 'public'))
            : null;

        $account = DB::transaction(function () use ($validated, $product, $variant, $imageUrl) {
            $account = TaiKhoanGame::createWithEncryption(
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
                'anh_minh_hoa' => $imageUrl,
                'thong_tin_cong_khai' => $validated['public_details'] ?? null,
            ]);
            $variant->update([
                'so_luong_kho' => TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count(),
            ]);
            return $account;
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã nhập tài khoản vào kho thành công.',
            'data' => $account,
        ], 201);
    }

    public function updateListing(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        if ($account->trang_thai === 'SOLD') {
            return response()->json(['success' => false, 'message' => 'Tài khoản đã bán, không thể thay đổi thông tin.'], 422);
        }

        if (is_string($request->input('public_details'))) {
            $request->merge(['public_details' => json_decode($request->input('public_details'), true)]);
        }
        $validated = $request->validate([
            'image_file' => 'nullable|image|max:5120',
            'public_details' => 'nullable|array',
            'public_details.*.label' => 'required_with:public_details|string|max:80',
            'public_details.*.value' => 'required_with:public_details|string|max:255',
        ]);

        $account->thong_tin_cong_khai = $validated['public_details'] ?? [];
        if ($request->hasFile('image_file')) {
            $account->anh_minh_hoa = Storage::disk('public')->url($request->file('image_file')->store('game-accounts', 'public'));
        }
        $account->save();

        return response()->json(['success' => true, 'data' => $account]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        if ($account->trang_thai === 'SOLD') {
            return response()->json(['success' => false, 'message' => 'Tài khoản đã bán, không thể thay đổi thông tin.'], 422);
        }
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

        $credentials = $account->decryptCredentials();
        $extra = is_array($credentials['extra'] ?? null) ? $credentials['extra'] : [];
        foreach (['sec_email', 'sec_phone', 'additional_info', 'cost_price'] as $key) {
            if (array_key_exists($key, $validated)) {
                $extra[$key] = $validated[$key];
            }
        }

        if (array_key_exists('username', $validated)) {
            $account->ten_dang_nhap_ma_hoa = Crypt::encryptString($validated['username']);
        }
        if (array_key_exists('password', $validated)) {
            $account->mat_khau_ma_hoa = Crypt::encryptString($validated['password']);
        }
        $account->thong_tin_phu_ma_hoa = $extra ? Crypt::encryptString(json_encode($extra, JSON_UNESCAPED_UNICODE)) : null;
        if (array_key_exists('cost_price', $validated)) {
            $account->gia_nhap = $validated['cost_price'];
        }
        if (array_key_exists('public_details', $validated)) {
            $account->thong_tin_cong_khai = $validated['public_details'] ?? [];
        }
        if ($request->hasFile('image_file')) {
            $account->anh_minh_hoa = Storage::disk('public')->url($request->file('image_file')->store('game-accounts', 'public'));
        }
        $account->save();

        return response()->json(['success' => true, 'message' => 'Đã cập nhật thông tin tài khoản.', 'data' => $account]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:AVAILABLE,RESERVED,SOLD,LOCKED,RECOVERED,RETIRED,available,reserved,sold',
            'notes' => 'nullable|string',
        ]);

        $status = strtoupper($validated['status']);
        $account->update([
            'trang_thai' => $status,
            'ghi_chu_noi_bo' => $validated['notes'] ?? $account->ghi_chu_noi_bo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái kho.',
            'data' => $account,
        ]);
    }

    public function viewCredentials(Request $request, int $id): JsonResponse
    {
        return $this->show($request, $id);
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        $account->update([
            'xac_minh_luc' => now(),
            'nguoi_xac_minh_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã xác minh tài khoản thành công.',
            'data' => $account,
        ]);
    }

    public function lock(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        $account->update(['trang_thai' => 'LOCKED']);

        return response()->json([
            'success' => true,
            'message' => 'Đã khóa tài khoản thành công.',
            'data' => $account,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:san_pham,id',
            'accounts' => 'required|array|min:1',
            'accounts.*.username' => 'required|string',
            'accounts.*.password' => 'required|string',
            'accounts.*.cost_price' => 'nullable|numeric|min:0',
            'accounts.*.additional_info' => 'nullable|string',
        ]);

        $product = SanPham::with('variants')->findOrFail($validated['product_id']);
        $variant = $product->variants->first();
        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm chưa có phiên bản.'], 422);
        }

        $imported = 0;
        foreach ($validated['accounts'] as $acc) {
            TaiKhoanGame::createWithEncryption(
                productId: $product->id,
                variantId: $variant->id,
                username: $acc['username'],
                password: $acc['password'],
                extra: [
                    'cost_price' => $acc['cost_price'] ?? null,
                    'additional_info' => $acc['additional_info'] ?? null,
                ]
            );
            $imported++;
        }

        $variant->update([
            'so_luong_kho' => TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Đã nhập thành công {$imported} tài khoản.",
        ]);
    }
}
