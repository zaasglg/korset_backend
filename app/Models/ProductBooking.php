<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ProductBooking extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'publication_price_id',
        'commission_amount',
        'payment_reference',
        'status',
        'booked_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'booked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Константы статусов
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Ожидает подтверждения',
            self::STATUS_CONFIRMED => 'Подтверждено',
            self::STATUS_CANCELLED => 'Отменено',
            self::STATUS_COMPLETED => 'Завершено',
        ];
    }

    // Отношения
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function publicationPrice(): BelongsTo
    {
        return $this->belongsTo(PublicationPrice::class);
    }

    // Скоупы
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Геттеры
    public function getStatusNameAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getFormattedCommissionAmountAttribute(): string
    {
        return number_format($this->commission_amount, 2) . ' KZT';
    }

    // Проверки
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // Методы действий
    public function confirm(): bool
    {
        if ($this->isPending()) {
            return $this->update(['status' => self::STATUS_CONFIRMED]);
        }
        return false;
    }

    public function cancel(): bool
    {
        if ($this->isActive()) {
            return $this->update(['status' => self::STATUS_CANCELLED]);
        }
        return false;
    }

    public function complete(): bool
    {
        if ($this->isConfirmed()) {
            return $this->update(['status' => self::STATUS_COMPLETED]);
        }
        return false;
    }
}
