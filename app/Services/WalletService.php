<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Получить баланс пользователя
     */
    public function getBalance(User $user): float
    {
        return (float) $user->balance;
    }

    /**
     * Получить историю транзакций
     */
    public function getTransactionHistory(User $user, int $limit = 50, int $offset = 0)
    {
        return $user->walletTransactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    /**
     * Пополнить баланс
     */
    public function deposit(User $user, float $amount, string $description = null, string $referenceId = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $referenceId) {
            return $user->addFunds($amount, $description, $referenceId);
        });
    }

    /**
     * Списать средства
     */
    public function withdraw(User $user, float $amount, string $description = null, string $referenceId = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $referenceId) {
            return $user->deductFunds($amount, $description, $referenceId);
        });
    }

    /**
     * Перевод между пользователями
     */
    public function transfer(User $fromUser, User $toUser, float $amount, string $description = null): array
    {
        return DB::transaction(function () use ($fromUser, $toUser, $amount, $description) {
            $referenceId = 'TRANSFER-' . time() . '-' . $fromUser->id . '-' . $toUser->id;
            
            $withdrawTransaction = $this->withdraw(
                $fromUser,
                $amount,
                $description ?? "Перевод пользователю {$toUser->name}",
                $referenceId
            );

            $depositTransaction = $this->deposit(
                $toUser,
                $amount,
                $description ?? "Перевод от пользователя {$fromUser->name}",
                $referenceId
            );

            return [
                'withdraw_transaction' => $withdrawTransaction,
                'deposit_transaction' => $depositTransaction,
                'reference_id' => $referenceId,
            ];
        });
    }

    /**
     * Проверить достаточность средств
     */
    public function hasSufficientFunds(User $user, float $amount): bool
    {
        return $user->hasSufficientBalance($amount);
    }

    /**
     * Получить статистику кошелька
     */
    public function getWalletStats(User $user): array
    {
        $transactions = $user->walletTransactions()->completed();

        return [
            'current_balance' => $this->getBalance($user),
            'total_deposits' => $transactions->byType('deposit')->sum('amount'),
            'total_withdrawals' => $transactions->byType('withdrawal')->sum('amount'),
            'total_transactions' => $transactions->count(),
            'last_transaction' => $user->walletTransactions()
                ->completed()
                ->latest()
                ->first(),
        ];
    }
}