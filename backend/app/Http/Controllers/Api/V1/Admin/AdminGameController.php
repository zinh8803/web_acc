<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'logo_url' => 'nullable|url',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,HIDDEN',
            'sort_order' => 'integer',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
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
        $game->update($request->all());
        return response()->json(['success' => true, 'data' => $game]);
    }

    public function destroy(int $id): JsonResponse
    {
        Game::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa game.']);
    }
}
