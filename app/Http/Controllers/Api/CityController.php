<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    /**
     * Display a listing of cities.
     */
    public function index(Request $request): JsonResponse
    {
        $query = City::with('region');

        // Filter by region if provided
        if ($request->has('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // Search by name if provided
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $cities = $query->get();

        return response()->json([
            'success' => true,
            'data' => $cities,
            'message' => 'Города успешно получены'
        ]);
    }

    /**
     * Store a newly created city.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region_id' => 'required|exists:regions,id'
        ]);

        // Check if city name is unique within the region
        $existingCity = City::where('name', $validated['name'])
            ->where('region_id', $validated['region_id'])
            ->first();

        if ($existingCity) {
            return response()->json([
                'success' => false,
                'message' => 'Город с таким названием уже существует в данном регионе'
            ], 400);
        }

        $city = City::create($validated);
        $city->load('region');

        return response()->json([
            'success' => true,
            'data' => $city,
            'message' => 'Город успешно создан'
        ], 201);
    }

    /**
     * Display the specified city.
     */
    public function show(City $city): JsonResponse
    {
        $city->load('region');

        return response()->json([
            'success' => true,
            'data' => $city,
            'message' => 'Город успешно получен'
        ]);
    }

    /**
     * Update the specified city.
     */
    public function update(Request $request, City $city): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'region_id' => 'required|exists:regions,id'
        ]);

        // Check if city name is unique within the region (excluding current city)
        $existingCity = City::where('name', $validated['name'])
            ->where('region_id', $validated['region_id'])
            ->where('id', '!=', $city->id)
            ->first();

        if ($existingCity) {
            return response()->json([
                'success' => false,
                'message' => 'Город с таким названием уже существует в данном регионе'
            ], 400);
        }

        $city->update($validated);
        $city->load('region');

        return response()->json([
            'success' => true,
            'data' => $city,
            'message' => 'Город успешно обновлен'
        ]);
    }

    /**
     * Remove the specified city.
     */
    public function destroy(City $city): JsonResponse
    {
        // Check if city is used in products or other relationships
        $productCount = 0;
        
        // Check if the products relationship exists
        if (method_exists($city, 'products')) {
            $productCount = $city->products()->count();
        }
        
        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Невозможно удалить город, который используется в товарах'
            ], 400);
        }

        $city->delete();

        return response()->json([
            'success' => true,
            'message' => 'Город успешно удален'
        ]);
    }

    /**
     * Get cities by region.
     */
    public function byRegion(Region $region): JsonResponse
    {
        $cities = $region->cities()->get();

        return response()->json([
            'success' => true,
            'data' => $cities,
            'message' => 'Города региона успешно получены'
        ]);
    }
}
