<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AdminGameController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Game::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'logo_file' => 'nullable|image|max:5120',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,HIDDEN',
            'sort_order' => 'integer',
        ]);
        $slug = Str::slug($validated['name']);
        $validated['slug'] = $slug . (Game::where('slug', $slug)->exists() ? '-' . Str::lower(Str::random(4)) : '');
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('games', 'public');
            $validated['logo_url'] = Storage::disk('public')->url($path);
        }
        unset($validated['logo_file']);
        $game = Game::create($validated);

        return response()->json(['success' => true, 'data' => $game], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Game::findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $game = Game::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'logo_file' => 'nullable|image|max:5120',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:ACTIVE,HIDDEN',
            'sort_order' => 'sometimes|integer',
        ]);
        if (isset($validated['name'])) {
            $slug = Str::slug($validated['name']);
            $validated['slug'] = $slug . (Game::where('slug', $slug)->where('id', '!=', $game->id)->exists() ? '-' . Str::lower(Str::random(4)) : '');
        }
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('games', 'public');
            $validated['logo_url'] = Storage::disk('public')->url($path);
        }
        unset($validated['logo_file']);
        $game->update($validated);
        return response()->json(['success' => true, 'data' => $game]);
    }

    public function destroy(int $id): JsonResponse
    {
        $game = Game::findOrFail($id);
        if ($game->categories()->exists() || $game->products()->exists()) {
            return response()->json(['success' => false, 'message' => 'Không thể xóa game đang được danh mục hoặc sản phẩm sử dụng.'], 409);
        }
        $game->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa game.']);
    }
}
