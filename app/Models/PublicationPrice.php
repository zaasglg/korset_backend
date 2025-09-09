<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PublicationPrice extends Model
{
    protected $fillable = [
        'type',
        'name',
        'description',
        'price',
        'duration_hours',
        'is_active',
        'features',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
        'duration_hours' => 'integer',
        'sort_order' => 'integer',
    ];

    // Константы для типов публикаций
    const TYPE_STORY = 'story';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_BOOKING_COMMISSION = 'booking_commission';

    public static function getTypes(): array
    {
        return [
            self::TYPE_STORY => 'Сторис',
            self::TYPE_ANNOUNCEMENT => 'Объявление',
            self::TYPE_BOOKING_COMMISSION => 'Комиссия за бронирование',
        ];
    }

    // Скоупы для фильтрации
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeStories(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_STORY);
    }

    public function scopeAnnouncements(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ANNOUNCEMENT);
    }

    public function scopeBookingCommissions(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BOOKING_COMMISSION);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // Геттеры
    public function getTypeNameAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' KZT';
    }

    public function getDurationTextAttribute(): string
    {
        if ($this->duration_hours < 24) {
            return $this->duration_hours . ' ч.';
        }
        
        $days = $this->duration_hours / 24;
        return $days . ' дн.';
    }

    // Проверки
    public function isStory(): bool
    {
        return $this->type === self::TYPE_STORY;
    }

    public function isAnnouncement(): bool
    {
        return $this->type === self::TYPE_ANNOUNCEMENT;
    }

    public function isBookingCommission(): bool
    {
        return $this->type === self::TYPE_BOOKING_COMMISSION;
    }
}
