<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category'])
            ->withCount([
                'gameAccounts as available_accounts_count' => fn ($accounts) => $accounts->where('status', 'AVAILABLE'),
                'gameAccounts as reserved_accounts_count' => fn ($accounts) => $accounts->where('status', 'RESERVED'),
                'gameAccounts as sold_accounts_count' => fn ($accounts) => $accounts->where('status', 'SOLD'),
            ])
            ->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(min(max((int) $request->get('per_page', 50), 1), 100)),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category', 'images', 'attributeValues.attribute', 'variants'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:ACCOUNT,SERVICE',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'thumbnail_file' => 'nullable|image|max:5120',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,DRAFT,HIDDEN,SOLD_OUT',
            'is_featured' => 'boolean',
        ]);

        $price = $validated['price'];
        $salePrice = $validated['sale_price'] ?? null;
        $thumbnailUrl = $request->hasFile('thumbnail_file')
            ? Storage::disk('public')->url($request->file('thumbnail_file')->store('products', 'public'))
            : null;
        $product = Product::create([
            'sku' => 'PROD-' . strtoupper(Str::random(6)),
            'slug' => Str::slug($validated['name']) . '-' . strtolower(Str::random(4)),
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'type' => $validated['type'],
            'min_price' => $salePrice ?? $price,
            'thumbnail_url' => $thumbnailUrl,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_featured' => $validated['is_featured'] ?? false,
        ]);
        $product->variants()->create([
            'sku' => $product->sku . '-DEFAULT',
            'name' => 'Mặc định',
            'price' => $price,
            'sale_price' => $salePrice,
            'stock_quantity' => 0,
            'status' => 'ACTIVE',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo sản phẩm thành công.',
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric|min:0',
            'thumbnail_file' => 'nullable|image|max:5120',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:ACTIVE,DRAFT,HIDDEN,SOLD_OUT',
            'is_featured' => 'boolean',
        ]);

        $price = $validated['price'] ?? $product->variants()->orderBy('sort_order')->value('price');
        $salePrice = $validated['sale_price'] ?? $product->variants()->orderBy('sort_order')->value('sale_price');
        if ($request->hasFile('thumbnail_file')) {
            $validated['thumbnail_url'] = Storage::disk('public')->url($request->file('thumbnail_file')->store('products', 'public'));
        }
        $product->update([
            ...collect($validated)->except(['price', 'sale_price', 'cost_price', 'thumbnail_file'])->all(),
            'min_price' => $salePrice ?? $price,
        ]);
        if ($request->has('variants')) {
            $variantsData = is_string($request->variants) ? json_decode($request->variants, true) : $request->variants;
            if (is_array($variantsData)) {
                foreach ($variantsData as $vItem) {
                    if (!empty($vItem['id'])) {
                        $variant = ProductVariant::where('product_id', $product->id)->find($vItem['id']);
                        if ($variant) {
                            $variant->update([
                                'name' => $vItem['name'] ?? $variant->name,
                                'price' => isset($vItem['price']) ? (float)$vItem['price'] : $variant->price,
                                'sale_price' => !empty($vItem['sale_price']) ? (float)$vItem['sale_price'] : null,
                            ]);
                        }
                    }
                }
                $minPrice = $product->variants()->min('sale_price') ?: $product->variants()->min('price');
                if ($minPrice) {
                    $product->update(['min_price' => $minPrice]);
                }
            }
        } elseif (isset($validated['price'])) {
            $variant = $product->variants()->orderBy('sort_order')->first();
            if ($variant) {
                $variant->update(['price' => $price, 'sale_price' => $salePrice]);
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
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xoá sản phẩm.',
        ]);
    }
}
