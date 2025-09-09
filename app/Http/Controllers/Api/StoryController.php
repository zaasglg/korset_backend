<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\PublicationPrice;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StoryController extends Controller
{
    /**
     * Display a listing of stories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get stories that haven't expired yet
        $stories = Story::with(['user', 'publicationPrice'])
            ->where('expires_at', '>', now())
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stories->map(function ($story) {
                return [
                    'id' => $story->id,
                    'user' => $story->user,
                    'content' => $story->content,
                    'media_url' => $story->media_url,
                    'media_type' => $story->media_type,
                    'expires_at' => $story->expires_at,
                    'is_active' => $story->is_active,
                    'publication_price' => $story->publicationPrice ? [
                        'name' => $story->publicationPrice->name,
                        'price' => $story->publicationPrice->formatted_price,
                    ] : null,
                    'paid_amount' => $story->formatted_paid_amount,
                    'created_at' => $story->created_at,
                ];
            })
        ]);
    }

    /**
     * Store a newly created story in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:500',
            'media' => 'required_without:content|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480',
            'publication_price_id' => 'required|exists:publication_prices,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Получаем тариф
        $publicationPrice = PublicationPrice::findOrFail(id: $request->publication_price_id);
        
        // Проверяем, что тариф активен и для сторис
        if (!$publicationPrice->is_active || $publicationPrice->type !== PublicationPrice::TYPE_STORY) {
            return response()->json([
                'success' => false,
                'message' => 'Выбранный тариф недоступен для сторис'
            ], 422);
        }

        $user = auth()->user();
        
        // Проверяем баланс пользователя, если цена больше 0
        if ($publicationPrice->price > 0 && $user->balance < $publicationPrice->price) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно средств на балансе. Требуется: ' . $publicationPrice->formatted_price
            ], 422);
        }

        return DB::transaction(function () use ($request, $publicationPrice, $user) {
            // Создаем сторис
            $story = new Story();
            $story->user_id = $user->id;
            $story->publication_price_id = $publicationPrice->id;
            $story->content = $request->input('content');
            $story->paid_amount = $publicationPrice->price;
            $story->is_active = true;
            
            // Устанавливаем время истечения на основе тарифа
            $story->expires_at = Carbon::now()->addHours($publicationPrice->duration_hours);

            // Обрабатываем загрузку медиа
            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $mediaType = explode('/', $file->getMimeType())[0]; // 'image' or 'video'
                $path = $file->store('stories', 'public');
                
                $story->media_url = Storage::url($path);
                $story->media_type = $mediaType;
            }

            // Списываем деньги с баланса, если цена больше 0
            if ($publicationPrice->price > 0) {
                $walletService = app(WalletService::class);
                $paymentReference = 'STORY-' . $user->id . '-' . time();
                
                try {
                    $walletService->withdraw(
                        $user,
                        $publicationPrice->price,
                        'Оплата публикации сторис: ' . $publicationPrice->name,
                        $paymentReference
                    );
                    
                    $story->payment_reference = $paymentReference;
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ошибка при списании средств: ' . $e->getMessage()
                    ], 422);
                }
            }

            $story->save();

            return response()->json([
                'success' => true,
                'message' => 'Сторис успешно опубликован',
                'data' => [
                    'story' => $story->load('publicationPrice'),
                    'paid_amount' => $story->formatted_paid_amount,
                    'expires_at' => $story->expires_at->format('Y-m-d H:i:s'),
                    'remaining_balance' => number_format($user->fresh()->balance, 2) . ' KZT'
                ]
            ], 201);
        });
    }

    /**
     * Display the specified story.
     *
     * @param  \App\Models\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function show(Story $story)
    {
        // Check if story has expired or is inactive
        if ($story->expires_at < now() || !$story->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Story not available'
            ], 404);
        }

        // Record view if authenticated user is not the owner
        if (auth()->id() !== $story->user_id) {
            StoryView::firstOrCreate([
                'story_id' => $story->id,
                'user_id' => auth()->id()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $story->load('user', 'views.user')
        ]);
    }

    /**
     * Update the specified story.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Story $story)
    {
        // Check if user is the owner of the story
        if (auth()->id() !== $story->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update story
        if ($request->has('content')) {
            $story->content = $request->input('content');
        }

        if ($request->has('is_active')) {
            $story->is_active = $request->input('is_active');
        }

        $story->save();

        return response()->json([
            'success' => true,
            'message' => 'Story updated successfully',
            'data' => $story
        ]);
    }

    /**
     * Remove the specified story from storage.
     *
     * @param  \App\Models\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function destroy(Story $story)
    {
        // Check if user is the owner of the story
        if (auth()->id() !== $story->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete media if exists
        if ($story->media_url) {
            $path = str_replace('/storage/', '', $story->media_url);
            Storage::disk('public')->delete($path);
        }

        $story->delete();

        return response()->json([
            'success' => true,
            'message' => 'Story deleted successfully'
        ]);
    }

    /**
     * Get stories by user.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\Response
     */
    public function userStories($userId)
    {
        $stories = Story::with('user')
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stories
        ]);
    }

    /**
     * Get current user's stories.
     *
     * @return \Illuminate\Http\Response
     */
    public function myStories()
    {
        $stories = Story::with(['views.user', 'publicationPrice'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stories->map(function ($story) {
                return [
                    'id' => $story->id,
                    'content' => $story->content,
                    'media_url' => $story->media_url,
                    'media_type' => $story->media_type,
                    'expires_at' => $story->expires_at,
                    'is_active' => $story->is_active,
                    'is_expired' => $story->isExpired(),
                    'publication_price' => $story->publicationPrice ? [
                        'name' => $story->publicationPrice->name,
                        'price' => $story->publicationPrice->formatted_price,
                        'duration_text' => $story->publicationPrice->duration_text,
                    ] : null,
                    'paid_amount' => $story->formatted_paid_amount,
                    'payment_reference' => $story->payment_reference,
                    'views_count' => $story->views->count(),
                    'views' => $story->views,
                    'created_at' => $story->created_at,
                ];
            })
        ]);
    }

    /**
     * Mark a story as viewed.
     *
     * @param  \App\Models\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function view(Story $story)
    {
        // Check if story has expired or is inactive
        if ($story->expires_at < now() || !$story->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Story not available'
            ], 404);
        }

        // Don't record view if user is the owner
        if (auth()->id() === $story->user_id) {
            return response()->json([
                'success' => true,
                'message' => 'This is your own story'
            ]);
        }

        // Record view
        StoryView::firstOrCreate([
            'story_id' => $story->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Story viewed successfully'
        ]);
    }
}
