<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'city_id',
        'name',
        'slug',
        'description',
        'main_photo',
        'video',
        'price',
        'address',
        'whatsapp_number',
        'phone_number',
        'is_video_call_available',
        'ready_for_video_demo',
        'views_count',
        'expires_at',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_video_call_available' => 'boolean',
        'ready_for_video_demo' => 'boolean',
        'views_count' => 'integer',
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
     * Add video_url, video_size and whatsapp_link to the appends array.
     */
    protected $appends = ['video_url', 'video_size', 'whatsapp_link'];
}
