<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicationPriceResource;
use App\Models\PublicationPrice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @tags Publication Prices
 */
class PublicationPriceController extends Controller
{
    /**
     * Получить список всех тарифов
     * 
     * Возвращает список тарифов для публикации сторис и объявлений
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = PublicationPrice::query();

        // Фильтр по типу
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Фильтр по активности
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Только активные по умолчанию для публичного API
        if (!$request->has('include_inactive')) {
            $query->active();
        }

        $prices = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => PublicationPriceResource::collection($prices)
        ]);
    }

    /**
     * Получить тарифы для сторис
     * 
     * Возвращает только активные тарифы для публикации сторис
     * 
     * @return JsonResponse
     */
    public function stories(): JsonResponse
    {
        $prices = PublicationPrice::stories()->active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $prices->map(function ($price) {
                return [
                    'id' => $price->id,
                    'name' => $price->name,
                    'description' => $price->description,
                    'price' => $price->price,
                    'formatted_price' => $price->formatted_price,
                    'duration_hours' => $price->duration_hours,
                    'duration_text' => $price->duration_text,
                    'features' => $price->features,
                ];
            })
        ]);
    }

    /**
     * Получить тарифы для объявлений
     * 
     * Возвращает только активные тарифы для публикации объявлений
     * 
     * @return JsonResponse
     */
    public function announcements(): JsonResponse
    {
        $prices = PublicationPrice::announcements()->active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $prices->map(function ($price) {
                return [
                    'id' => $price->id,
                    'name' => $price->name,
                    'description' => $price->description,
                    'price' => $price->price,
                    'formatted_price' => $price->formatted_price,
                    'duration_hours' => $price->duration_hours,
                    'duration_text' => $price->duration_text,
                    'features' => $price->features,
                ];
            })
        ]);
    }



    /**
     * Получить конкретный тариф
     */
    public function show(PublicationPrice $publicationPrice): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $publicationPrice->id,
                'type' => $publicationPrice->type,
                'type_name' => $publicationPrice->type_name,
                'name' => $publicationPrice->name,
                'description' => $publicationPrice->description,
                'price' => $publicationPrice->price,
                'formatted_price' => $publicationPrice->formatted_price,
                'duration_hours' => $publicationPrice->duration_hours,
                'duration_text' => $publicationPrice->duration_text,
                'features' => $publicationPrice->features,
                'is_active' => $publicationPrice->is_active,
                'sort_order' => $publicationPrice->sort_order,
                'created_at' => $publicationPrice->created_at,
                'updated_at' => $publicationPrice->updated_at,
            ]
        ]);
    }



    /**
     * Получить тарифы комиссии за бронирование
     * 
     * Возвращает активные тарифы комиссии за бронирование субаренды
     * 
     * @return JsonResponse
     */
    public function bookingCommissions(): JsonResponse
    {
        $prices = PublicationPrice::bookingCommissions()->active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => PublicationPriceResource::collection($prices)
        ]);
    }

    /**
     * Получить статистику по тарифам
     */
    public function stats(): JsonResponse
    {
        $totalPrices = PublicationPrice::count();
        $activePrices = PublicationPrice::active()->count();
        $storyPrices = PublicationPrice::stories()->active()->count();
        $announcementPrices = PublicationPrice::announcements()->active()->count();

        $avgStoryPrice = PublicationPrice::stories()->active()->avg('price') ?? 0;
        $avgAnnouncementPrice = PublicationPrice::announcements()->active()->avg('price') ?? 0;

        $minStoryPrice = PublicationPrice::stories()->active()->min('price') ?? 0;
        $maxStoryPrice = PublicationPrice::stories()->active()->max('price') ?? 0;

        $minAnnouncementPrice = PublicationPrice::announcements()->active()->min('price') ?? 0;
        $maxAnnouncementPrice = PublicationPrice::announcements()->active()->max('price') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_prices' => $totalPrices,
                'active_prices' => $activePrices,
                'story_prices' => [
                    'count' => $storyPrices,
                    'avg_price' => round($avgStoryPrice, 2),
                    'min_price' => $minStoryPrice,
                    'max_price' => $maxStoryPrice,
                ],
                'announcement_prices' => [
                    'count' => $announcementPrices,
                    'avg_price' => round($avgAnnouncementPrice, 2),
                    'min_price' => $minAnnouncementPrice,
                    'max_price' => $maxAnnouncementPrice,
                ],
            ]
        ]);
    }
}
