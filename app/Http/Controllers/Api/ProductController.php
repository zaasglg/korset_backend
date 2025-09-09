<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductParameter;
use App\Models\ProductParameterValue;
use App\Models\PublicationPrice;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\VideoService;
use Carbon\Carbon;

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
        $query = Product::with(['category', 'city', 'parameterValues.parameter', 'publicationPrice'])
            ->where('user_id', auth()->user()->id);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтр по активности (не истекшие)
        if ($request->has('active') && $request->boolean('active')) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        $products = $query->latest()->paginate(10);

        // Добавляем информацию об истечении к каждому продукту
        $products->getCollection()->transform(function ($product) {
            $product->is_expired = $product->isExpired();
            $product->is_active_status = $product->isActive();
            return $product;
        });

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

        // Получаем тариф
        $publicationPrice = PublicationPrice::findOrFail($validated['publication_price_id']);

        // Проверяем, что тариф активен и для объявлений
        if (!$publicationPrice->is_active || $publicationPrice->type !== PublicationPrice::TYPE_ANNOUNCEMENT) {
            return response()->json([
                'status' => 'error',
                'message' => 'Выбранный тариф недоступен для объявлений'
            ], 422);
        }

        $user = Auth::user();

        // Проверяем баланс пользователя, если цена больше 0
        if ($publicationPrice->price > 0 && $user->balance < $publicationPrice->price) {
            return response()->json([
                'status' => 'error',
                'message' => 'Недостаточно средств на балансе. Требуется: ' . $publicationPrice->formatted_price
            ], 422);
        }

        return DB::transaction(function () use ($request, $validated, $publicationPrice, $user) {
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

            $videoData = [];
            if ($videoPath) {
                $videoData = [
                    'video' => $result['path'],
                    'video_thumbnail' => isset($result['thumbnail']) ? str_replace(asset('storage/'), '', $result['thumbnail']) : null,
                    'original_video_size' => $result['original_size'] ?? null,
                    'optimized_video_size' => $result['optimized_size'] ?? null,
                    'compression_ratio' => $result['compression_ratio'] ?? null,
                    'video_duration' => $result['duration'] ?? null,
                ];
            }

            // Устанавливаем время истечения на основе тарифа
            $expiresAt = Carbon::now()->addHours($publicationPrice->duration_hours);

            $product = Product::create(array_merge([
                'user_id' => $user->id,
                'publication_price_id' => $publicationPrice->id,
                'paid_amount' => $publicationPrice->price,
                'category_id' => $validated['category_id'],
                'city_id' => $validated['city_id'],
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'],
                'main_photo' => "",
                'price' => $validated['price'],
                'address' => $validated['address'],
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'is_video_call_available' => $validated['is_video_call_available'] ?? false,
                'ready_for_video_demo' => $validated['ready_for_video_demo'] ?? false,
                'views_count' => 0,
                'expires_at' => $expiresAt,
                'is_promoted' => $publicationPrice->price > 1000, // VIP если цена больше 1000
                'status' => 'active'
            ], $videoData));

            // Списываем деньги с баланса, если цена больше 0
            if ($publicationPrice->price > 0) {
                $walletService = app(WalletService::class);
                $paymentReference = 'PRODUCT-' . $user->id . '-' . time();

                try {
                    $walletService->withdraw(
                        $user,
                        $publicationPrice->price,
                        'Оплата публикации объявления: ' . $publicationPrice->name,
                        $paymentReference
                    );

                    $product->payment_reference = $paymentReference;
                    $product->save();
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ошибка при списании средств: ' . $e->getMessage()
                    ], 422);
                }
            }

            if (isset($validated['parameters'])) {
                foreach ($validated['parameters'] as $param) {
                    ProductParameterValue::create([
                        'product_id' => $product->id,
                        'product_parameter_id' => $param['parameter_id'],
                        'value' => $param['value']
                    ]);
                }
            }

            $product->load(['category', 'city', 'parameterValues.parameter', 'publicationPrice']);

            return response()->json([
                'status' => 'success',
                'message' => 'Объявление успешно опубликовано',
                'data' => [
                    'product' => $product,
                    'paid_amount' => $product->formatted_paid_amount,
                    'expires_at' => $product->expires_at->format('Y-m-d H:i:s'),
                    'is_promoted' => $product->is_promoted,
                    'remaining_balance' => number_format($user->fresh()->balance, 2) . ' KZT'
                ]
            ], 201);
        });
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
        $query = Product::with(['category', 'city', 'parameterValues.parameter', 'publicationPrice'])
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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

        // Сортировка: сначала продвигаемые (VIP), потом обычные
        $query->orderByDesc('is_promoted')->latest();

        $products = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'price' => $product->price,
                    'main_photo' => $product->main_photo,
                    'video_url' => $product->video_url,
                    'address' => $product->address,
                    'whatsapp_number' => $product->whatsapp_number,
                    'phone_number' => $product->phone_number,
                    'views_count' => $product->views_count,
                    'is_promoted' => $product->is_promoted,
                    'expires_at' => $product->expires_at,
                    'category' => $product->category,
                    'city' => $product->city,
                    'publication_price' => $product->publicationPrice ? [
                        'name' => $product->publicationPrice->name,
                        'type_name' => $product->publicationPrice->type_name,
                    ] : null,
                    'created_at' => $product->created_at,
                ];
            })
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

    /**
     * Share product - generate share link and increment share count
     */
    public function shareProduct(Product $product): JsonResponse
    {
        // Increment share count
        $product->increment('shares_count');

        // Generate share data
        $shareData = [
            'url' => config('app.frontend_url', env('APP_URL')) . '/share/product/' . $product->id,
            'title' => $product->name,
            'description' => Str::limit($product->description, 150),
            'image' => $product->main_photo ? Storage::url($product->main_photo) : null,
            'price' => $product->price,
            'location' => $product->city->name ?? '',
            'shares_count' => $product->shares_count
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Share data generated successfully',
            'data' => $shareData
        ]);
    }

    /**
     * Get share statistics for product
     */
    public function getShareStats(Product $product): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'product_id' => $product->id,
                'shares_count' => $product->shares_count ?? 0,
                'views_count' => $product->views_count ?? 0,
                'created_at' => $product->created_at
            ]
        ]);
    }

    /**
     * Get video optimization statistics
     */
    public function getVideoStats(): JsonResponse
    {
        $stats = Product::whereNotNull('video')
            ->where('video', '!=', '')
            ->selectRaw('
                COUNT(*) as total_videos,
                SUM(CASE WHEN video LIKE "%_optimized%" THEN 1 ELSE 0 END) as optimized_count,
                SUM(CASE WHEN video_thumbnail IS NOT NULL THEN 1 ELSE 0 END) as thumbnails_count,
                AVG(CASE WHEN compression_ratio IS NOT NULL THEN compression_ratio ELSE NULL END) as avg_compression,
                SUM(CASE WHEN original_video_size IS NOT NULL AND optimized_video_size IS NOT NULL 
                    THEN (original_video_size - optimized_video_size) ELSE 0 END) as total_saved_bytes,
                AVG(CASE WHEN video_duration IS NOT NULL THEN video_duration ELSE NULL END) as avg_duration
            ')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_videos' => $stats->total_videos ?? 0,
                'optimized_count' => $stats->optimized_count ?? 0,
                'thumbnails_count' => $stats->thumbnails_count ?? 0,
                'optimization_percentage' => $stats->total_videos > 0
                    ? round(($stats->optimized_count / $stats->total_videos) * 100, 1)
                    : 0,
                'average_compression' => round($stats->avg_compression ?? 0, 1),
                'total_saved_mb' => round(($stats->total_saved_bytes ?? 0) / 1024 / 1024, 1),
                'average_duration_seconds' => round($stats->avg_duration ?? 0),
            ]
        ]);
    }
}
