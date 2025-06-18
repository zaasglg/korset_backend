<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductParameter;
use App\Models\ProductParameterValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'city', 'parameterValues.parameter'])
            ->where('user_id', auth()->id());

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'main_photo' => 'required|string',
            'video' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'address' => 'required|string',
            'is_video_call_available' => 'boolean',
            'expires_at' => 'required|date|after:now',
            'parameters' => 'array',
            'parameters.*.parameter_id' => 'required|exists:product_parameters,id',
            'parameters.*.value' => 'required|string'
        ]);

        $product = Product::create([
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'],
            'city_id' => $validated['city_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'main_photo' => $validated['main_photo'],
            'video' => $validated['video'] ?? null,
            'price' => $validated['price'],
            'address' => $validated['address'],
            'is_video_call_available' => $validated['is_video_call_available'] ?? false,
            'expires_at' => $validated['expires_at'],
            'status' => 'active'
        ]);

        if (isset($validated['parameters'])) {
            foreach ($validated['parameters'] as $param) {
                ProductParameterValue::create([
                    'product_id' => $product->id,
                    'product_parameter_id' => $param['parameter_id'],
                    'value' => $param['value']
                ]);
            }
        }

        $product->load(['category', 'city', 'parameterValues.parameter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'city', 'parameterValues.parameter']);

        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'category_id' => 'exists:categories,id',
            'city_id' => 'exists:cities,id',
            'name' => 'string|max:255',
            'description' => 'string',
            'main_photo' => 'string',
            'video' => 'nullable|string',
            'price' => 'numeric|min:0',
            'address' => 'string',
            'is_video_call_available' => 'boolean',
            'expires_at' => 'date|after:now',
            'status' => Rule::in(['active', 'inactive', 'sold'])
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);
        $product->load(['category', 'city', 'parameterValues.parameter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Update product parameters.
     */
    public function updateParameters(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'parameters' => 'required|array',
            'parameters.*.parameter_id' => 'required|exists:product_parameters,id',
            'parameters.*.value' => 'required|string'
        ]);

        // Delete existing parameter values
        $product->parameterValues()->delete();

        // Create new parameter values
        foreach ($validated['parameters'] as $param) {
            ProductParameterValue::create([
                'product_id' => $product->id,
                'product_parameter_id' => $param['parameter_id'],
                'value' => $param['value']
            ]);
        }

        $product->load(['parameterValues.parameter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Product parameters updated successfully',
            'data' => $product->parameterValues
        ]);
    }

    /**
     * Display a listing of public products.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'city', 'parameterValues.parameter'])
            ->where('status', 'active')
            ->where('expires_at', '>', now());

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

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

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $products = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Display the specified public product.
     */
    public function publicShow(Product $product): JsonResponse
    {
        if ($product->status !== 'active' || $product->expires_at < now()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product is not available'
            ], 404);
        }

        $product->load(['category', 'city', 'parameterValues.parameter', 'user:id,name,avatar']);

        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }
}
