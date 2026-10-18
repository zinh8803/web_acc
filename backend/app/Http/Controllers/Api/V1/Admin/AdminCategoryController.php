<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Category::with('game')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'game_id' => 'required|exists:games,id',
            'parent_id' => 'nullable|exists:categories,id',
            'type' => 'nullable|in:ACCOUNT,SERVICE',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
            'sort_order' => 'nullable|integer',
        ]);
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(4);
        $validated['type'] = $validated['type'] ?? 'ACCOUNT';
        $validated['status'] = 'ACTIVE';
        $category = Category::create($validated);

        return response()->json(['success' => true, 'data' => $category->load('game')], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $validated = $request->all();
        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $category->update($validated);
        return response()->json(['success' => true, 'data' => $category->load('game')]);
    }

    public function destroy(int $id): JsonResponse
    {
        Category::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa danh mục.']);
    }
}
