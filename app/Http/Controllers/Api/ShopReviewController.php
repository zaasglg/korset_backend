<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopReviewController extends Controller
{
    /**
     * Display a listing of the shop's reviews.
     */
    public function index(Shop $shop): JsonResponse
    {
        $reviews = $shop->reviews()
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $reviews
        ]);
    }

    /**
     * Store a newly created review.
     */
    public function store(Request $request, Shop $shop): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        // Проверяем, не оставил ли пользователь уже отзыв
        if ($shop->reviews()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this shop'
            ], 400);
        }

        $review = $shop->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null
        ]);

        // Обновляем рейтинг магазина
        $shop->update([
            'rating' => $shop->reviews()->avg('rating'),
            'reviews_count' => $shop->reviews()->count()
        ]);

        $review->load('user:id,name,avatar');

        return response()->json([
            'status' => 'success',
            'message' => 'Review created successfully',
            'data' => $review
        ], 201);
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, Shop $shop, ShopReview $review): JsonResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'rating' => 'integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $review->update($validated);

        // Обновляем рейтинг магазина
        $shop->update([
            'rating' => $shop->reviews()->avg('rating')
        ]);

        $review->load('user:id,name,avatar');

        return response()->json([
            'status' => 'success',
            'message' => 'Review updated successfully',
            'data' => $review
        ]);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Shop $shop, ShopReview $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        // Обновляем рейтинг магазина
        $shop->update([
            'rating' => $shop->reviews()->avg('rating'),
            'reviews_count' => $shop->reviews()->count()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Review deleted successfully'
        ]);
    }
}
