<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'publication_price_id',
        'paid_amount',
        'payment_reference',
        'content',
        'media_url',
        'media_type',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the story.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the views for the story.
     */
    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    /**
     * Get the publication price for the story.
     */
    public function publicationPrice(): BelongsTo
    {
        return $this->belongsTo(PublicationPrice::class);
    }

    /**
     * Check if the story is expired based on publication price duration
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get formatted paid amount
     */
    public function getFormattedPaidAmountAttribute(): string
    {
        return number_format($this->paid_amount, 2) . ' KZT';
    }

    /**
     * Check if story was paid
     */
    public function isPaid(): bool
    {
        return $this->paid_amount > 0;
    }
}
