<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    /**
     * Display a listing of shops.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shop::with(['city', 'user:id,name,avatar'])
            ->where('is_active', true);

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        $shops = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $shops
        ]);
    }

    /**
     * Store a newly created shop.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'banner' => 'nullable|string',
            'logo' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'working_hours' => 'nullable|array',
            'social_links' => 'nullable|array'
        ]);

        // Проверяем, не создал ли пользователь уже магазин
        if (auth()->user()->shop()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'User already has a shop'
            ], 400);
        }

        $shop = Shop::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'banner' => $validated['banner'] ?? null,
            'logo' => $validated['logo'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'working_hours' => $validated['working_hours'] ?? null,
            'social_links' => $validated['social_links'] ?? null
        ]);

        $shop->load(['city', 'user:id,name,avatar']);

        return response()->json([
            'status' => 'success',
            'message' => 'Shop created successfully',
            'data' => $shop
        ], 201);
    }

    /**
     * Display the specified shop.
     */
    public function show(Shop $shop): JsonResponse
    {
        $shop->load(['city', 'user:id,name,avatar', 'products' => function($query) {
            $query->where('status', 'active')
                  ->where('expires_at', '>', now())
                  ->with(['category', 'city', 'parameterValues.parameter']);
        }]);

        return response()->json([
            'status' => 'success',
            'data' => $shop
        ]);
    }

    /**
     * Update the specified shop.
     */
    public function update(Request $request, Shop $shop): JsonResponse
    {
        $this->authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'banner' => 'nullable|string',
            'logo' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'working_hours' => 'nullable|array',
            'social_links' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $shop->update($validated);
        $shop->load(['city', 'user:id,name,avatar']);

        return response()->json([
            'status' => 'success',
            'message' => 'Shop updated successfully',
            'data' => $shop
        ]);
    }

    /**
     * Remove the specified shop.
     */
    public function destroy(Shop $shop): JsonResponse
    {
        $this->authorize('delete', $shop);

        $shop->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Shop deleted successfully'
        ]);
    }

    /**
     * Get the current user's shop.
     */
    public function myShop(): JsonResponse
    {
        $shop = auth()->user()->shop;

        if (!$shop) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shop not found'
            ], 404);
        }

        $shop->load(['city', 'user:id,name,avatar', 'products' => function($query) {
            $query->with(['category', 'city', 'parameterValues.parameter']);
        }]);

        return response()->json([
            'status' => 'success',
            'data' => $shop
        ]);
    }
}
