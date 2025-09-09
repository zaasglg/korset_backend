<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductBookingRequest;
use App\Models\Product;
use App\Models\ProductBooking;
use App\Models\PublicationPrice;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @tags Product Bookings
 */
class ProductBookingController extends Controller
{
    /**
     * Получить список бронирований пользователя
     * 
     * Возвращает список всех бронирований текущего пользователя с пагинацией
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductBooking::with(['product.category', 'product.city', 'publicationPrice'])
            ->where('user_id', auth()->id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $bookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'product' => [
                        'id' => $booking->product->id,
                        'name' => $booking->product->name,
                        'price' => $booking->product->price,
                        'address' => $booking->product->address,
                        'category' => $booking->product->category->name ?? null,
                        'city' => $booking->product->city->name ?? null,
                    ],
                    'status' => $booking->status,
                    'status_name' => $booking->status_name,
                    'commission_amount' => $booking->formatted_commission_amount,
                    'booked_at' => $booking->booked_at,
                    'expires_at' => $booking->expires_at,
                    'notes' => $booking->notes,
                ];
            }),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ]
        ]);
    }

    /**
     * Забронировать продукт
     * 
     * Создает новое бронирование для объявления субаренды с оплатой комиссии
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StoreProductBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = Product::with(['category', 'activeBooking'])->findOrFail($validated['product_id']);
        $user = auth()->user();

        // Временная отладка - можно удалить после исправления
        $debugInfo = $product->debugBookingAvailability();
        
        // Если нужна отладка, раскомментируйте:
        // return response()->json(['debug' => $debugInfo]);

        // Проверяем, что это субаренда
        if (!$product->isAvailableForBooking()) {
            if ($product->isBooked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Это объявление уже забронировано'
                ], 422);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Данное объявление не подлежит бронированию'
                ], 422);
            }
        }

        // Проверяем, что пользователь не бронирует свое объявление
        if ($product->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Вы не можете забронировать свое собственное объявление'
            ], 422);
        }

        // Получаем активный тариф комиссии за бронирование
        $commissionPrice = PublicationPrice::bookingCommissions()->active()->first();
        
        if (!$commissionPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Тариф комиссии за бронирование не найден'
            ], 422);
        }

        // Проверяем баланс пользователя
        if ($commissionPrice->price > 0 && $user->balance < $commissionPrice->price) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно средств на балансе для оплаты комиссии. Требуется: ' . $commissionPrice->formatted_price
            ], 422);
        }

        return DB::transaction(function () use ($validated, $product, $user, $commissionPrice) {
            // Проверяем еще раз, что продукт не забронирован (защита от race condition)
            if ($product->activeBooking()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Это объявление уже забронировано другим пользователем'
                ], 422);
            }

            // Создаем бронирование
            $booking = ProductBooking::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'publication_price_id' => $commissionPrice->id,
                'commission_amount' => $commissionPrice->price,
                'status' => ProductBooking::STATUS_PENDING,
                'booked_at' => now(),
                'expires_at' => now()->addHours(24), // Бронирование действует 24 часа
                'notes' => $validated['notes'] ?? null,
            ]);

            // Списываем комиссию с баланса, если цена больше 0
            if ($commissionPrice->price > 0) {
                $walletService = app(WalletService::class);
                $paymentReference = 'BOOKING-' . $user->id . '-' . $booking->id . '-' . time();
                
                try {
                    $walletService->withdraw(
                        $user,
                        $commissionPrice->price,
                        'Комиссия за бронирование: ' . $product->name,
                        $paymentReference
                    );
                    
                    $booking->payment_reference = $paymentReference;
                    $booking->save();
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ошибка при списании комиссии: ' . $e->getMessage()
                    ], 422);
                }
            }

            $booking->load(['product.category', 'product.city', 'publicationPrice']);

            return response()->json([
                'success' => true,
                'message' => 'Объявление успешно забронировано',
                'data' => [
                    'booking' => [
                        'id' => $booking->id,
                        'status' => $booking->status,
                        'status_name' => $booking->status_name,
                        'commission_amount' => $booking->formatted_commission_amount,
                        'booked_at' => $booking->booked_at,
                        'expires_at' => $booking->expires_at,
                        'notes' => $booking->notes,
                    ],
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'address' => $product->address,
                    ],
                    'remaining_balance' => number_format($user->fresh()->balance, 2) . ' KZT'
                ]
            ], 201);
        });
    }

    /**
     * Получить информацию о бронировании
     */
    public function show(ProductBooking $productBooking): JsonResponse
    {
        // Проверяем права доступа
        if ($productBooking->user_id !== auth()->id() && $productBooking->product->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому бронированию'
            ], 403);
        }

        $productBooking->load(['product.category', 'product.city', 'user', 'publicationPrice']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $productBooking->id,
                'product' => [
                    'id' => $productBooking->product->id,
                    'name' => $productBooking->product->name,
                    'price' => $productBooking->product->price,
                    'address' => $productBooking->product->address,
                    'category' => $productBooking->product->category->name ?? null,
                    'city' => $productBooking->product->city->name ?? null,
                ],
                'user' => [
                    'id' => $productBooking->user->id,
                    'name' => $productBooking->user->name,
                    'phone_number' => $productBooking->user->phone_number,
                ],
                'status' => $productBooking->status,
                'status_name' => $productBooking->status_name,
                'commission_amount' => $productBooking->formatted_commission_amount,
                'payment_reference' => $productBooking->payment_reference,
                'booked_at' => $productBooking->booked_at,
                'expires_at' => $productBooking->expires_at,
                'notes' => $productBooking->notes,
                'created_at' => $productBooking->created_at,
            ]
        ]);
    }

    /**
     * Отменить бронирование
     */
    public function destroy(ProductBooking $productBooking): JsonResponse
    {
        // Проверяем права доступа (может отменить только тот, кто бронировал)
        if ($productBooking->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав для отмены этого бронирования'
            ], 403);
        }

        if (!$productBooking->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Это бронирование уже неактивно'
            ], 422);
        }

        $productBooking->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Бронирование успешно отменено'
        ]);
    }

    /**
     * Подтвердить бронирование (только владелец объявления)
     */
    public function confirm(ProductBooking $productBooking): JsonResponse
    {
        // Проверяем права доступа (может подтвердить только владелец объявления)
        if ($productBooking->product->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав для подтверждения этого бронирования'
            ], 403);
        }

        if (!$productBooking->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Это бронирование не ожидает подтверждения'
            ], 422);
        }

        $productBooking->confirm();

        return response()->json([
            'success' => true,
            'message' => 'Бронирование подтверждено'
        ]);
    }

    /**
     * Завершить бронирование (только владелец объявления)
     */
    public function complete(ProductBooking $productBooking): JsonResponse
    {
        // Проверяем права доступа (может завершить только владелец объявления)
        if ($productBooking->product->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав для завершения этого бронирования'
            ], 403);
        }

        if (!$productBooking->isConfirmed()) {
            return response()->json([
                'success' => false,
                'message' => 'Это бронирование не подтверждено'
            ], 422);
        }

        $productBooking->complete();

        return response()->json([
            'success' => true,
            'message' => 'Бронирование завершено'
        ]);
    }

    /**
     * Проверить статус бронирования продукта
     * 
     * Возвращает информацию о возможности бронирования и текущем статусе
     * 
     * @param Product $product
     * @return JsonResponse
     */
    public function checkStatus(Product $product): JsonResponse
    {
        $product->load(['activeBooking.user', 'category']);

        // Получаем отладочную информацию
        $debugInfo = $product->debugBookingAvailability();

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'is_bookable' => $product->isAvailableForBooking(),
                'booking_status' => $product->booking_status,
                'debug_info' => $debugInfo, // Временно добавляем для отладки
                'active_booking' => $product->activeBooking ? [
                    'id' => $product->activeBooking->id,
                    'user_name' => $product->activeBooking->user->name,
                    'user_id' => $product->activeBooking->user->id,
                    'status' => $product->activeBooking->status,
                    'status_name' => $product->activeBooking->status_name,
                    'booked_at' => $product->activeBooking->booked_at,
                    'expires_at' => $product->activeBooking->expires_at,
                ] : null,
            ]
        ]);
    }
}
