<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the user's favorite products.
     */
    public function index(): JsonResponse
    {
        $favorites = auth()->user()->favoriteProducts()
            ->with(['category', 'city', 'parameterValues.parameter'])
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $favorites
        ]);
    }

    /**
     * Add a product to favorites.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Проверяем, не добавлен ли уже продукт в избранное
        if (auth()->user()->favoriteProducts()->where('product_id', $product->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product is already in favorites'
            ], 400);
        }

        auth()->user()->favoriteProducts()->attach($product->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to favorites'
        ]);
    }

    /**
     * Remove a product from favorites.
     */
    public function destroy(Product $product): JsonResponse
    {
        auth()->user()->favoriteProducts()->detach($product->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product removed from favorites'
        ]);
    }

    /**
     * Check if a product is in favorites.
     */
    public function check(Product $product): JsonResponse
    {
        $isFavorite = auth()->user()->favoriteProducts()
            ->where('product_id', $product->id)
            ->exists();

        return response()->json([
            'status' => 'success',
            'data' => [
                'is_favorite' => $isFavorite
            ]
        ]);
    }
}
