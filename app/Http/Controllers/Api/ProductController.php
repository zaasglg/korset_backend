<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductParameter;
use App\Models\ProductParameterValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\VideoService;

class ProductController extends Controller
{
    use AuthorizesRequests;

    protected VideoService $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'city', 'parameterValues.parameter'])
            ->where('user_id', auth()->user()->id);

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
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Handle video upload if provided
        $videoPath = null;
        if ($request->hasFile('video')) {
            try {
                $result = $this->videoService->uploadVideo($request->file('video'));
                $videoPath = $result['path'];
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        $product = Product::create([
            'user_id' => Auth::user()->id,
            'category_id' => $validated['category_id'],
            'city_id' => $validated['city_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'main_photo' => "",
            'video' => $videoPath,
            'price' => $validated['price'],
            'address' => $validated['address'],
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'is_video_call_available' => $validated['is_video_call_available'] ?? false,
            'ready_for_video_demo' => $validated['ready_for_video_demo'] ?? false,
            'views_count' => 0,
            'expires_at' => now()->addWeeks(2),
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
     * Increment product views count.
     */
    public function incrementViews(Product $product): JsonResponse
    {
        $product->incrementViews();

        return response()->json([
            'status' => 'success',
            'message' => 'Views count incremented',
            'data' => [
                'views_count' => $product->views_count
            ]
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validated();

        // Handle video upload if provided
        if ($request->hasFile('video')) {
            try {
                // Delete old video if exists
                if ($product->video) {
                    $this->videoService->deleteVideo($product->video);
                }

                $result = $this->videoService->uploadVideo($request->file('video'));
                $validated['video'] = $result['path'];
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

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

        // Delete video file if exists
        if ($product->video) {
            $this->videoService->deleteVideo($product->video);
        }

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

        $products = $query->latest()->get();

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

    /**
     * Upload video for product
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm,mkv|max:102400' // 100MB max
        ]);

        try {
            $videoFile = $request->file('video');
            $result = $this->videoService->uploadVideo($videoFile);

            return response()->json([
                'status' => 'success',
                'message' => 'Video uploaded successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get video information
     */
    public function getVideoInfo(Request $request): JsonResponse
    {
        $request->validate([
            'video_path' => 'required|string'
        ]);

        try {
            $videoInfo = $this->videoService->getVideoInfo($request->video_path);
            
            return response()->json([
                'status' => 'success',
                'data' => $videoInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Delete video file
     */
    public function deleteVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video_path' => 'required|string'
        ]);

        try {
            $deleted = $this->videoService->deleteVideo($request->video_path);
            
            return response()->json([
                'status' => 'success',
                'message' => $deleted ? 'Video deleted successfully' : 'Video file not found',
                'deleted' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
