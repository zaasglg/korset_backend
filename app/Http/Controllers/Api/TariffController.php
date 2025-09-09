<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tariff;
use App\Models\UserTariff;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TariffController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Get all tariffs
     */
    public function index(): JsonResponse
    {
        $tariffs = Tariff::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $tariffs
        ]);
    }

    /**
     * Get a specific tariff
     */
    public function show(Tariff $tariff): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $tariff
        ]);
    }

    /**
     * Purchase a tariff using wallet balance
     */
    public function purchase(Request $request, Tariff $tariff): JsonResponse
    {
        $user = $request->user();

        // Проверяем активен ли тариф
        if (!$tariff->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Тариф недоступен для покупки',
            ], 400);
        }

        // Определяем цену (со скидкой если есть)
        $price = $tariff->discount_price ?? $tariff->price;

        // Проверяем достаточность средств
        if (!$this->walletService->hasSufficientFunds($user, $price)) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно средств на балансе',
                'required_amount' => $price,
                'current_balance' => $this->walletService->getBalance($user),
            ], 400);
        }

        // Проверяем, нет ли уже активного тарифа
        if ($user->hasActiveTariff($tariff->id)) {
            return response()->json([
                'success' => false,
                'message' => 'У вас уже есть активный тариф этого типа',
            ], 400);
        }

        try {
            return DB::transaction(function () use ($user, $tariff, $price) {
                // Списываем средства с кошелька
                $transaction = $this->walletService->withdraw(
                    $user,
                    $price,
                    "Покупка тарифа: {$tariff->name}",
                    "TARIFF-{$tariff->id}-" . time()
                );

                // Создаем запись о покупке тарифа
                $userTariff = UserTariff::create([
                    'user_id' => $user->id,
                    'tariff_id' => $tariff->id,
                    'paid_amount' => $price,
                    'purchased_at' => now(),
                    'expires_at' => now()->addMonth(), // Тариф на месяц
                    'status' => 'active',
                    'transaction_reference' => $transaction->reference_id,
                ]);

                Log::info('Tariff purchased successfully', [
                    'user_id' => $user->id,
                    'tariff_id' => $tariff->id,
                    'amount' => $price,
                    'user_tariff_id' => $userTariff->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Тариф успешно приобретен',
                    'data' => [
                        'user_tariff' => $userTariff->load('tariff'),
                        'new_balance' => $this->walletService->getBalance($user),
                        'expires_at' => $userTariff->expires_at,
                    ],
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Tariff purchase failed', [
                'user_id' => $user->id,
                'tariff_id' => $tariff->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при покупке тарифа: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's purchased tariffs
     */
    public function myTariffs(Request $request): JsonResponse
    {
        $user = $request->user();

        $tariffs = $user->userTariffs()
            ->with('tariff')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tariffs,
            'active_count' => $user->activeTariffs()->count(),
        ]);
    }
}
