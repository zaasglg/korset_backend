<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'banner',
        'logo',
        'phone',
        'email',
        'address',
        'city_id',
        'working_hours',
        'social_links',
        'is_verified',
        'is_active',
        'rating',
        'reviews_count'
    ];

    protected $casts = [
        'working_hours' => 'array',
        'social_links' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'integer',
        'reviews_count' => 'integer'
    ];

    /**
     * Get the user that owns the shop.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the city of the shop.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the products of the shop.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the reviews of the shop.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ShopReview::class);
    }
}
