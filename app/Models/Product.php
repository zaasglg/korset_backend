<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'publication_price_id',
        'paid_amount',
        'payment_reference',
        'category_id',
        'city_id',
        'name',
        'slug',
        'description',
        'main_photo',
        'video',
        'video_thumbnail',
        'original_video_size',
        'optimized_video_size',
        'compression_ratio',
        'video_duration',
        'price',
        'address',
        'whatsapp_number',
        'phone_number',
        'is_video_call_available',
        'ready_for_video_demo',
        'views_count',
        'shares_count',
        'expires_at',
        'is_promoted',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_video_call_available' => 'boolean',
        'ready_for_video_demo' => 'boolean',
        'is_promoted' => 'boolean',
        'views_count' => 'integer',
        'shares_count' => 'integer',
        'original_video_size' => 'integer',
        'optimized_video_size' => 'integer',
        'compression_ratio' => 'decimal:2',
        'video_duration' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the product.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the city of the product.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the parameter values for the product.
     */
    public function parameterValues(): HasMany
    {
        return $this->hasMany(ProductParameterValue::class);
    }

    /**
     * Get the users who favorited this product.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * Get the publication price for the product.
     */
    public function publicationPrice(): BelongsTo
    {
        return $this->belongsTo(PublicationPrice::class);
    }

    /**
     * Get the bookings for the product.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ProductBooking::class);
    }

    /**
     * Get the active booking for the product.
     */
    public function activeBooking(): HasOne
    {
        return $this->hasOne(ProductBooking::class)->active()->notExpired();
    }

    /**
     * Get the video URL accessor.
     */
    public function getVideoUrlAttribute(): ?string
    {
        return $this->video ? asset('storage/' . $this->video) : null;
    }

    /**
     * Get the video file size
     */
    public function getVideoSizeAttribute(): ?int
    {
        if (!$this->video || !\Storage::disk('public')->exists($this->video)) {
            return null;
        }
        
        return \Storage::disk('public')->size($this->video);
    }

    /**
     * Check if product has video
     */
    public function hasVideo(): bool
    {
        return !empty($this->video) && \Storage::disk('public')->exists($this->video);
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Check if user is ready for video demonstration
     */
    public function isReadyForVideoDemo(): bool
    {
        return $this->ready_for_video_demo;
    }

    /**
     * Get formatted WhatsApp number for links
     */
    public function getWhatsappLinkAttribute(): ?string
    {
        if (!$this->whatsapp_number) {
            return null;
        }
        
        // Remove all non-numeric characters
        $cleanNumber = preg_replace('/[^0-9]/', '', $this->whatsapp_number);
        
        return "https://wa.me/{$cleanNumber}";
    }

    /**
     * Check if the product is expired based on publication price duration
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
     * Check if product was paid
     */
    public function isPaid(): bool
    {
        return $this->paid_amount > 0;
    }

    /**
     * Check if product is active (not expired and status is active)
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    /**
     * Check if product is available for booking (sublet category and not booked)
     */
    public function isAvailableForBooking(): bool
    {
        // Проверяем, что категория загружена
        if (!$this->category) {
            $this->load('category');
        }
        
        if (!$this->category) {
            return false;
        }

        // Проверяем, что это субаренда - улучшенная логика поиска
        $categoryName = mb_strtolower($this->category->name);
        $categorySlug = mb_strtolower($this->category->slug ?? '');
        
        $isSublet = str_contains($categoryName, 'субаренда') ||
                   str_contains($categoryName, 'sublet') ||
                   str_contains($categorySlug, 'subarenda') ||
                   str_contains($categorySlug, 'sublet') ||
                   $categoryName === 'субаренда' ||
                   $categorySlug === 'subarenda';
        
        if (!$isSublet) {
            return false;
        }

        // Проверяем, что нет активного бронирования
        return !$this->activeBooking()->exists();
    }

    /**
     * Debug method to check category for booking availability
     */
    public function debugBookingAvailability(): array
    {
        if (!$this->category) {
            $this->load('category');
        }

        $categoryName = $this->category ? mb_strtolower($this->category->name) : 'no category';
        $categorySlug = $this->category ? mb_strtolower($this->category->slug ?? '') : 'no slug';
        
        $checks = [
            'contains_субаренда' => str_contains($categoryName, 'субаренда'),
            'contains_sublet' => str_contains($categoryName, 'sublet'),
            'slug_contains_subarenda' => str_contains($categorySlug, 'subarenda'),
            'slug_contains_sublet' => str_contains($categorySlug, 'sublet'),
            'exact_match_name' => $categoryName === 'субаренда',
            'exact_match_slug' => $categorySlug === 'subarenda',
        ];

        return [
            'category_id' => $this->category_id,
            'category_name' => $this->category->name ?? 'No category',
            'category_slug' => $this->category->slug ?? 'No slug',
            'checks' => $checks,
            'is_sublet' => array_sum($checks) > 0,
            'has_active_booking' => $this->activeBooking()->exists(),
            'is_available_for_booking' => $this->isAvailableForBooking(),
        ];
    }

    /**
     * Check if product is booked
     */
    public function isBooked(): bool
    {
        return $this->activeBooking()->exists();
    }

    /**
     * Get booking status for sublet products
     */
    public function getBookingStatusAttribute(): string
    {
        if (!$this->isAvailableForBooking() && !$this->isBooked()) {
            return 'not_bookable'; // Не подлежит бронированию
        }
        
        return $this->isBooked() ? 'booked' : 'available';
    }

    /**
     * Add video_url, video_size and whatsapp_link to the appends array.
     */
    protected $appends = ['video_url', 'video_size', 'whatsapp_link'];
}
