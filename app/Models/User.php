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
        'balance',
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
            'balance' => 'decimal:2',
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

    /**
     * Get the user's wallet transactions.
     */
    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get the user's payment sessions.
     */
    public function paymentSessions()
    {
        return $this->hasMany(PaymentSession::class);
    }

    /**
     * Add funds to user's wallet.
     */
    public function addFunds(float $amount, string $description = null, string $referenceId = null): WalletTransaction
    {
        $balanceBefore = $this->balance;
        $this->increment('balance', $amount);
        $this->refresh();

        return $this->walletTransactions()->create([
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Пополнение баланса',
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);
    }

    /**
     * Deduct funds from user's wallet.
     */
    public function deductFunds(float $amount, string $description = null, string $referenceId = null): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Недостаточно средств на балансе');
        }

        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->refresh();

        return $this->walletTransactions()->create([
            'type' => 'withdrawal',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Списание с баланса',
            'reference_id' => $referenceId,
            'status' => 'completed',
        ]);
    }

    /**
     * Check if user has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get the user's purchased tariffs.
     */
    public function userTariffs()
    {
        return $this->hasMany(UserTariff::class);
    }

    /**
     * Get the user's active tariffs.
     */
    public function activeTariffs()
    {
        return $this->userTariffs()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if user has active tariff.
     */
    public function hasActiveTariff(int $tariffId = null): bool
    {
        $query = $this->activeTariffs();
        
        if ($tariffId) {
            $query->where('tariff_id', $tariffId);
        }
        
        return $query->exists();
    }
}
