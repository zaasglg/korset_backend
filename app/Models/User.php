<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone_number',
        'avatar',
        'city_id',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the city that the user belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the tariff requests for the user.
     */
    public function tariffRequests(): HasMany
    {
        return $this->hasMany(TariffRequest::class);
    }

    /**
     * Get the passport verification for the user.
     */
    public function passportVerification(): HasOne
    {
        return $this->hasOne(PassportVerification::class);
    }

    /**
     * Get the user's favorite products.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the user's favorite products.
     */
    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'favorites')
            ->withTimestamps();
    }

    /**
     * Get the user's products.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the user's shop.
     */
    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    /**
     * Get the user's shop reviews.
     */
    public function shopReviews()
    {
        return $this->hasMany(ShopReview::class);
    }

    /**
     * Get the user's stories.
     */
    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    /**
     * Get the story views by this user.
     */
    public function storyViews()
    {
        return $this->hasMany(StoryView::class);
    }

    /**
     * Get referrals made by this user (as referrer).
     */
    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Get referral where this user was referred.
     */
    public function referralReceived()
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    /**
     * Get the user who referred this user.
     */
    public function referrer()
    {
        return $this->hasOneThrough(User::class, Referral::class, 'referred_id', 'id', 'id', 'referrer_id');
    }
}
