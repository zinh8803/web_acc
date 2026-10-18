<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBannerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Banner::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'image_url' => 'required|url',
            'link_url' => 'nullable|string',
            'sort_order' => 'integer',
            'status' => 'required|in:ACTIVE,HIDDEN',
        ]);

        $banner = Banner::create($validated);
        return response()->json(['success' => true, 'data' => $banner], 201);
    }
}
