<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use App\Models\BienTheSanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SanPham::with(['category', 'danhMuc'])
            ->withCount([
                'gameAccounts as available_accounts_count' => fn ($accounts) => $accounts->where('trang_thai', 'AVAILABLE'),
                'gameAccounts as reserved_accounts_count' => fn ($accounts) => $accounts->where('trang_thai', 'RESERVED'),
                'gameAccounts as sold_accounts_count' => fn ($accounts) => $accounts->where('trang_thai', 'SOLD'),
            ])
            ->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ten_san_pham', 'like', "%{$search}%")
                  ->orWhere('ma_san_pham', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('trang_thai', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(min(max((int) $request->get('per_page', 50), 1), 100)),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = SanPham::with(['category', 'danhMuc', 'images', 'hinhAnhs', 'variants', 'bienThes'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $catTable = Schema::hasTable('danh_muc') ? 'danh_muc' : 'categories';
        $validated = $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'ten_san_pham' => 'sometimes|nullable|string|max:255',
            'category_id' => "sometimes|nullable|exists:{$catTable},id",
            'danh_muc_id' => "sometimes|nullable|exists:{$catTable},id",
            'type' => 'sometimes|nullable|in:ACCOUNT,SERVICE,TAI_KHOAN,DICH_VU',
            'loai_san_pham' => 'sometimes|nullable|in:ACCOUNT,SERVICE,TAI_KHOAN,DICH_VU',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'thumbnail_file' => 'nullable|image|max:5120',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|in:ACTIVE,DRAFT,HIDDEN,SOLD_OUT',
            'is_featured' => 'boolean',
        ]);

        $name = $validated['ten_san_pham'] ?? $validated['name'] ?? 'Sản phẩm mới';
        $catId = $validated['danh_muc_id'] ?? $validated['category_id'];
        $type = $validated['loai_san_pham'] ?? $validated['type'] ?? 'ACCOUNT';
        if ($type === 'TAI_KHOAN') $type = 'ACCOUNT';
        if ($type === 'DICH_VU') $type = 'SERVICE';

        $price = $validated['price'];
        $salePrice = $validated['sale_price'] ?? null;
        $thumbnailUrl = $request->hasFile('thumbnail_file')
            ? Storage::disk('public')->url($request->file('thumbnail_file')->store('products', 'public'))
            : null;

        $product = SanPham::create([
            'ma_san_pham' => 'PROD-' . strtoupper(Str::random(6)),
            'duong_dan_tinh' => Str::slug($name) . '-' . strtolower(Str::random(4)),
            'ten_san_pham' => $name,
            'danh_muc_id' => $catId,
            'loai_san_pham' => $type,
            'gia_thap_nhat' => $salePrice ?? $price,
            'anh_dai_dien' => $thumbnailUrl,
            'mo_ta_ngan' => $validated['short_description'] ?? null,
            'noi_dung_chi_tiet' => $validated['description'] ?? null,
            'trang_thai' => $validated['status'] ?? 'ACTIVE',
            'noi_bat' => $validated['is_featured'] ?? false,
        ]);

        $product->bienThes()->create([
            'ma_bien_the' => $product->ma_san_pham . '-DEFAULT',
            'ten_bien_the' => 'Mặc định',
            'gia_goc' => $price,
            'gia_khuyen_mai' => $salePrice,
            'so_luong_ton_kho' => 0,
            'trang_thai' => 'ACTIVE',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo sản phẩm thành công.',
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = SanPham::findOrFail($id);
        $catTable = Schema::hasTable('danh_muc') ? 'danh_muc' : 'categories';

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'ten_san_pham' => 'sometimes|string|max:255',
            'category_id' => "sometimes|exists:{$catTable},id",
            'danh_muc_id' => "sometimes|exists:{$catTable},id",
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric|min:0',
            'thumbnail_file' => 'nullable|image|max:5120',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:ACTIVE,DRAFT,HIDDEN,SOLD_OUT',
            'is_featured' => 'boolean',
        ]);

        $updateData = [];
        if (isset($validated['name']) || isset($validated['ten_san_pham'])) {
            $updateData['ten_san_pham'] = $validated['ten_san_pham'] ?? $validated['name'];
        }
        if (isset($validated['danh_muc_id']) || isset($validated['category_id'])) {
            $updateData['danh_muc_id'] = $validated['danh_muc_id'] ?? $validated['category_id'];
        }
        if (array_key_exists('short_description', $validated)) {
            $updateData['mo_ta_ngan'] = $validated['short_description'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['noi_dung_chi_tiet'] = $validated['description'];
        }
        if (isset($validated['status'])) {
            $updateData['trang_thai'] = $validated['status'];
        }
        if (isset($validated['is_featured'])) {
            $updateData['noi_bat'] = $validated['is_featured'];
        }

        if ($request->hasFile('thumbnail_file')) {
            $updateData['anh_dai_dien'] = Storage::disk('public')->url($request->file('thumbnail_file')->store('products', 'public'));
        }

        $price = $validated['price'] ?? $product->bienThes()->orderBy('thu_tu_hien_thi')->value('gia_goc');
        $salePrice = $validated['sale_price'] ?? $product->bienThes()->orderBy('thu_tu_hien_thi')->value('gia_khuyen_mai');

        if (isset($validated['price']) || isset($validated['sale_price'])) {
            $updateData['gia_thap_nhat'] = $salePrice ?? $price;
        }

        $product->update($updateData);

        if (isset($validated['price'])) {
            $variant = $product->bienThes()->orderBy('thu_tu_hien_thi')->first();
            if ($variant) {
                $variant->update(['gia_goc' => $price, 'gia_khuyen_mai' => $salePrice]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công.',
            'data' => $product,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = SanPham::findOrFail($id);

        if ($product->taiKhoanGames()->exists()) {
            $product->update(['trang_thai' => 'ARCHIVED']);
            return response()->json([
                'success' => true,
                'message' => 'Sản phẩm đã có tài khoản trong kho, đã chuyển trạng thái sang Lưu trữ/Ẩn.',
            ]);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xoá sản phẩm thành công.',
        ]);
    }
}
