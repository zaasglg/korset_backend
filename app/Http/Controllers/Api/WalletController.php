<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Получить информацию о кошельке
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->walletService->getWalletStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Получить историю транзакций
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min($request->get('limit', 20), 100);
        $offset = $request->get('offset', 0);

        $transactions = $this->walletService->getTransactionHistory($user, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $transactions->count() === $limit,
            ],
        ]);
    }

    /**
     * Перевод средств другому пользователю
     */
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $recipientId = $request->input('recipient_id');
        $amount = $request->input('amount');
        $description = $request->input('description');

        if ($user->id == $recipientId) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя переводить средства самому себе',
            ], 400);
        }

        if (!$this->walletService->hasSufficientFunds($user, $amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно средств на балансе',
            ], 400);
        }

        try {
            $recipient = \App\Models\User::findOrFail($recipientId);
            $result = $this->walletService->transfer($user, $recipient, $amount, $description);

            return response()->json([
                'success' => true,
                'message' => 'Перевод выполнен успешно',
                'data' => [
                    'reference_id' => $result['reference_id'],
                    'amount' => $amount,
                    'recipient' => [
                        'id' => $recipient->id,
                        'name' => $recipient->name,
                    ],
                    'new_balance' => $this->walletService->getBalance($user),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при выполнении перевода: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Проверить баланс
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = $this->walletService->getBalance($user);

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'currency' => 'KZT',
            ],
        ]);
    }
}