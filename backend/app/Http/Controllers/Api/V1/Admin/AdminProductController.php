<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'game'])->orderByDesc('id');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
        }

        if ($gameId = $request->get('game_id')) {
            $query->where('game_id', $gameId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->get('per_page', 15)),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category', 'game', 'images', 'attributeValues.attribute'])
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
            'game_id' => 'required|exists:games,id',
            'category_id' => 'required|exists:categories,id',
            'product_type' => 'required|in:account,service,item',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'thumbnail_url' => 'nullable|url',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,sold_out,draft',
            'is_featured' => 'boolean',
        ]);

        $validated['sku'] = 'PROD-' . strtoupper(Str::random(6));
        $validated['slug'] = Str::slug($validated['name']) . '-' . strtolower(Str::random(4));

        $product = Product::create($validated);

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
            'game_id' => 'sometimes|exists:games,id',
            'category_id' => 'sometimes|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric|min:0',
            'thumbnail_url' => 'nullable|url',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,sold_out,draft',
            'is_featured' => 'boolean',
        ]);

        $product->update($validated);

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
