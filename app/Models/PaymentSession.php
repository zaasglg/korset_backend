<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'currency',
        'description',
        'status',
        'payment_provider',
        'provider_data',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_data' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Проверить, был ли уже пополнен баланс для этой платежной сессии
     */
    public function hasBalanceToppedUp(): bool
    {
        return $this->user->walletTransactions()
            ->where('reference_id', $this->order_id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Получить транзакцию пополнения баланса для этой сессии
     */
    public function getBalanceTopUpTransaction()
    {
        return $this->user->walletTransactions()
            ->where('reference_id', $this->order_id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->first();
    }
}