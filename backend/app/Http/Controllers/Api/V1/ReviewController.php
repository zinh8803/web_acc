<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $reviews = Review::where('product_id', $product->id)
            ->where('is_approved', true)
            ->with('user:id,full_name,avatar_url')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|min:5|max:1000',
        ]);

        $review = Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'content' => $validated['content'],
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá đã được gửi.',
            'data' => $review,
        ], 201);
    }
}
