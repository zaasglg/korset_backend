<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RegionController extends Controller
{
    /**
     * Display a listing of regions.
     */
    public function index(): JsonResponse
    {
        $regions = Region::with('cities')->get();

        return response()->json([
            'success' => true,
            'data' => $regions,
            'message' => 'Регионы успешно получены'
        ]);
    }

    /**
     * Store a newly created region.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions',
            'description' => 'nullable|string'
        ]);

        $region = Region::create($validated);

        return response()->json([
            'success' => true,
            'data' => $region,
            'message' => 'Регион успешно создан'
        ], 201);
    }

    /**
     * Display the specified region.
     */
    public function show(Region $region): JsonResponse
    {
        $region->load('cities');

        return response()->json([
            'success' => true,
            'data' => $region,
            'message' => 'Регион успешно получен'
        ]);
    }

    /**
     * Update the specified region.
     */
    public function update(Request $request, Region $region): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name,' . $region->id,
            'description' => 'nullable|string'
        ]);

        $region->update($validated);

        return response()->json([
            'success' => true,
            'data' => $region,
            'message' => 'Регион успешно обновлен'
        ]);
    }

    /**
     * Remove the specified region.
     */
    public function destroy(Region $region): JsonResponse
    {
        // Check if region has cities
        if ($region->cities()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Невозможно удалить регион, в котором есть города'
            ], 400);
        }

        $region->delete();

        return response()->json([
            'success' => true,
            'message' => 'Регион успешно удален'
        ]);
    }

    /**
     * Get cities for a specific region.
     */
    public function cities(Region $region): JsonResponse
    {
        $cities = $region->cities()->get();

        return response()->json([
            'success' => true,
            'data' => $cities,
            'message' => 'Города региона успешно получены'
        ]);
    }
}
