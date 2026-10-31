<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AdminCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Category::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'type' => 'nullable|in:ACCOUNT,SERVICE',
            'description' => 'nullable|string',
            'image_file' => 'nullable|image|max:5120',
            'sort_order' => 'nullable|integer',
        ]);
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(4);
        $validated['type'] = $validated['type'] ?? 'ACCOUNT';
        $validated['status'] = 'ACTIVE';
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('categories', 'public');
            $validated['image_url'] = Storage::disk('public')->url($path);
        }
        unset($validated['image_file']);
        $category = Category::create($validated);

        return response()->json(['success' => true, 'data' => $category], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|required|in:ACCOUNT,SERVICE',
            'description' => 'nullable|string',
            'image_file' => 'nullable|image|max:5120',
            'sort_order' => 'sometimes|integer',
        ]);
        if (isset($validated['name']) && $validated['name'] !== $category->name) {
            $slug = Str::slug($validated['name']);
            $validated['slug'] = $slug . '-' . Str::lower(Str::random(4));
        }
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('categories', 'public');
            $validated['image_url'] = Storage::disk('public')->url($path);
        }
        unset($validated['image_file']);
        $category->update($validated);
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        if ($category->products()->exists() || $category->children()->exists()) {
            return response()->json(['success' => false, 'message' => 'Không thể xóa danh mục đang có sản phẩm hoặc danh mục con.'], 409);
        }
        $category->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa danh mục.']);
    }
}
