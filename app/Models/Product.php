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
        'is_video_call_available',
        'expires_at',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_video_call_available' => 'boolean',
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
}
