<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use App\Models\SanPham;
use App\Models\TaiKhoanGame;
use App\Models\BannerQuangCao;
use App\Models\CaiDat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * List all games (Deprecated - single game Liên Quân).
     */
    public function games(): JsonResponse
    {
        return $this->success([]);
    }

    /**
     * Get category list / tree.
     */
    public function categories(Request $request): JsonResponse
    {
        $query = DanhMuc::where('trang_thai', 'ACTIVE')
            ->with(['children'])
            ->orderBy('thu_tu_hien_thi');

        if ($request->type) {
            $query->where('loai_danh_muc', strtoupper($request->type));
        }

        return $this->success($query->get());
    }

    /**
     * List products with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SanPham::query()
            ->where('trang_thai', 'ACTIVE')
            ->with([
                'variants' => function ($q) {
                    $q->where('trang_thai', 'ACTIVE')->orderBy('thu_tu_hien_thi');
                },
                'category:id,ten_danh_muc,duong_dan_tinh',
            ])
            ->select([
                'id',
                'ma_san_pham',
                'loai_san_pham',
                'danh_muc_id',
                'ten_san_pham',
                'duong_dan_tinh',
                'mo_ta_ngan',
                'anh_dai_dien',
                'gia_thap_nhat',
                'trang_thai',
                'noi_bat',
                'so_luong_da_ban',
                'luot_xem',
                'diem_danh_gia_tb',
                'so_luot_danh_gia',
                'ngay_tao',
            ]);

        // Filter by category (by ID or Slug from DB)
        if ($request->category_id) {
            $categoryIds = $this->getCategoryIds((int) $request->category_id);
            $query->whereIn('danh_muc_id', $categoryIds);
        } elseif ($request->category) {
            $category = DanhMuc::where('duong_dan_tinh', $request->category)
                ->orWhere('id', $request->category)
                ->first();
            if ($category) {
                $categoryIds = $this->getCategoryIds($category->id);
                $query->whereIn('danh_muc_id', $categoryIds);
            }
        }

        if ($request->type) {
            $query->where('loai_san_pham', strtoupper($request->type));
        }

        if ($request->min_price) {
            $query->where('gia_thap_nhat', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('gia_thap_nhat', '<=', $request->max_price);
        }

        if ($request->is_featured) {
            $query->where('noi_bat', true);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ten_san_pham', 'like', "%{$search}%")
                  ->orWhere('ma_san_pham', 'like', "%{$search}%")
                  ->orWhere('mo_ta_ngan', 'like', "%{$search}%");
            });
        }

        // Sorting
        match ($request->sort) {
            'price_asc' => $query->orderBy('gia_thap_nhat', 'asc'),
            'price_desc' => $query->orderBy('gia_thap_nhat', 'desc'),
            'sold' => $query->orderBy('so_luong_da_ban', 'desc'),
            'rating' => $query->orderBy('diem_danh_gia_tb', 'desc'),
            default => $query->orderBy('ngay_tao', 'desc'), // newest
        };

        $limit = $request->limit ?? 24;
        $results = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'page' => $results->currentPage(),
                'limit' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    /**
     * Get product detail by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $product = SanPham::where('duong_dan_tinh', $slug)
            ->where('trang_thai', 'ACTIVE')
            ->with([
                'category:id,ten_danh_muc,duong_dan_tinh,danh_muc_cha_id',
                'variants' => fn($q) => $q->where('trang_thai', 'ACTIVE')->orderBy('thu_tu_hien_thi'),
                'images' => fn($q) => $q->orderBy('thu_tu_hien_thi'),
                'warrantyPolicy',
            ])
            ->firstOrFail();

        // Increment view count
        $product->increment('luot_xem');

        // Get available inventory count for ACCOUNT type
        $availableCount = 0;
        if ($product->loai_san_pham === 'ACCOUNT') {
            $availableCount = $product->gameAccounts()->where('trang_thai', 'AVAILABLE')->count();
        }

        $data = $product->toArray();
        $data['available_count'] = $availableCount;

        return $this->success($data);
    }

    /** Return public data for individually selectable accounts, without credentials. */
    public function accounts(Request $request, string $slug): JsonResponse
    {
        $product = SanPham::where('duong_dan_tinh', $slug)
            ->where('loai_san_pham', 'ACCOUNT')
            ->where('trang_thai', 'ACTIVE')
            ->firstOrFail();

        $accounts = TaiKhoanGame::query()
            ->with(['variant:id,gia_ban,gia_khuyen_mai,trang_thai'])
            ->where('san_pham_id', $product->id)
            ->where('trang_thai', 'AVAILABLE')
            ->whereHas('variant', fn ($query) => $query->where('trang_thai', 'ACTIVE'))
            ->orderBy('id')
            ->paginate(min(max((int) $request->get('per_page', 24), 1), 100));

        $accounts->getCollection()->transform(fn (TaiKhoanGame $account) => [
            'id' => $account->id,
            'product_id' => $account->san_pham_id,
            'variant_id' => $account->bien_the_san_pham_id,
            'image_url' => $account->image_url ?: $product->thumbnail_url,
            'public_details' => $account->public_details ?? [],
            'price' => $account->variant?->sale_price ?? $account->variant?->price ?? $product->min_price,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => ['id' => $product->id, 'name' => $product->name, 'slug' => $product->slug],
                'accounts' => $accounts,
            ],
        ]);
    }

    private function getCategoryIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = DanhMuc::where('danh_muc_cha_id', $categoryId)->pluck('id')->toArray();
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getCategoryIds($childId));
        }
        return $ids;
    }

    public function banners(): JsonResponse
    {
        $banners = BannerQuangCao::where('trang_thai', 'ACTIVE')
            ->orderBy('thu_tu_hien_thi')
            ->get();

        return $this->success($banners);
    }

    public function publicSettings(): JsonResponse
    {
        $settings = CaiDat::all();
        $formatted = [];
        foreach ($settings as $setting) {
            $val = $setting->gia_tri_cai_dat ?? $setting->value;
            while (is_string($val) && (str_starts_with($val, '{') || str_starts_with($val, '[') || str_starts_with($val, '"'))) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $val = $decoded;
                } else {
                    break;
                }
            }
            $key = $setting->khoa_cai_dat ?? $setting->key;
            $formatted[$key] = $val;
        }

        return $this->success($formatted);
    }

    private function success(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data]);
    }
}
