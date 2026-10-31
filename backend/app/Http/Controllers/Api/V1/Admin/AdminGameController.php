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
            'data' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Hệ thống hiện cố định mặc định game Liên Quân Mobile.'], 200);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['id' => $id, 'name' => 'Liên Quân Mobile']]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Hệ thống hiện cố định mặc định game Liên Quân Mobile.']);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Hệ thống hiện cố định mặc định game Liên Quân Mobile.']);
    }
}
