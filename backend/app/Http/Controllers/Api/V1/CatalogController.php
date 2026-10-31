<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\GameAccount;
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
        $query = Category::where('status', 'ACTIVE')
            ->with(['children'])
            ->orderBy('sort_order');

        if ($request->type) {
            $query->where('type', strtoupper($request->type));
        }

        return $this->success($query->get());
    }

    /**
     * List products with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('status', 'ACTIVE')
            ->with(['variants' => function ($q) {
                $q->where('status', 'ACTIVE')->orderBy('sort_order');
            }, 'category:id,name,slug'])
            ->select([
                'id', 'sku', 'type', 'category_id', 'name', 'slug',
                'short_description', 'thumbnail_url', 'min_price', 'status',
                'is_featured', 'sold_count', 'view_count', 'rating_avg', 'rating_count',
            ]);

        // Filter by category (by ID or Slug from DB)
        if ($request->category_id) {
            $categoryIds = $this->getCategoryIds($request->category_id);
            $query->whereIn('category_id', $categoryIds);
        } elseif ($request->category) {
            $category = Category::where('slug', $request->category)->orWhere('id', $request->category)->first();
            if ($category) {
                $categoryIds = $this->getCategoryIds($category->id);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        if ($request->type) {
            $query->where('type', strtoupper($request->type));
        }

        if ($request->min_price) {
            $query->where('min_price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('min_price', '<=', $request->max_price);
        }

        if ($request->is_featured) {
            $query->where('is_featured', true);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Dynamic attribute filters (e.g., attr_rank=DIAMOND, attr_hero_count_min=100)
        foreach ($request->except(['category_id', 'type', 'min_price', 'max_price', 'is_featured', 'search', 'sort', 'page', 'limit']) as $key => $value) {
            if (str_starts_with($key, 'attr_')) {
                $attributeCode = str_replace('attr_', '', $key);
                $query->whereHas('attributeValues', function ($q) use ($attributeCode, $value) {
                    $q->whereHas('attribute', fn($a) => $a->where('code', $attributeCode))
                        ->where(function ($av) use ($value) {
                            $av->where('value_text', $value)
                                ->orWhereHas('option', fn($o) => $o->where('value', $value));
                        });
                });
            }
        }

        // Sorting
        match ($request->sort) {
            'price_asc' => $query->orderBy('min_price', 'asc'),
            'price_desc' => $query->orderBy('min_price', 'desc'),
            'sold' => $query->orderBy('sold_count', 'desc'),
            'rating' => $query->orderBy('rating_avg', 'desc'),
            default => $query->orderBy('created_at', 'desc'), // newest
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
        $product = Product::where('slug', $slug)
            ->where('status', 'ACTIVE')
            ->with([
                'category:id,name,slug,parent_id',
                'variants' => fn($q) => $q->where('status', 'ACTIVE')->orderBy('sort_order'),
                'images' => fn($q) => $q->orderBy('sort_order'),
                'attributeValues.attribute',
                'attributeValues.option',
                'warrantyPolicy',
            ])
            ->firstOrFail();

        // Increment view count
        $product->increment('view_count');

        // Get available inventory count for ACCOUNT type
        $availableCount = 0;
        if ($product->type === 'ACCOUNT') {
            $availableCount = $product->gameAccounts()->where('status', 'AVAILABLE')->count();
        }

        $data = $product->toArray();
        $data['available_count'] = $availableCount;

        return $this->success($data);
    }

    /** Return public data for individually selectable accounts, without credentials. */
    public function accounts(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('type', 'ACCOUNT')
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        $accounts = GameAccount::query()
            ->with(['variant:id,price,sale_price,status'])
            ->where('product_id', $product->id)
            ->where('status', 'AVAILABLE')
            ->whereHas('variant', fn ($query) => $query->where('status', 'ACTIVE'))
            ->orderBy('id')
            ->paginate(min(max((int) $request->get('per_page', 24), 1), 100));

        $accounts->getCollection()->transform(fn (GameAccount $account) => [
            'id' => $account->id,
            'product_id' => $account->product_id,
            'variant_id' => $account->variant_id,
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
        $children = Category::where('parent_id', $categoryId)->pluck('id')->toArray();
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getCategoryIds($childId));
        }
        return $ids;
    }

    public function banners(): JsonResponse
    {
        $banners = \App\Models\Banner::where('status', 'ACTIVE')
            ->orderBy('sort_order')
            ->get();

        return $this->success($banners);
    }

    public function publicSettings(): JsonResponse
    {
        $settings = \App\Models\Setting::all();
        $formatted = [];
        foreach ($settings as $setting) {
            $val = $setting->value;
            $formatted[$setting->key] = is_string($val) && (str_starts_with($val, '{') || str_starts_with($val, '['))
                ? json_decode($val, true)
                : $val;
        }

        return $this->success($formatted);
    }

    private function success(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data]);
    }
}
