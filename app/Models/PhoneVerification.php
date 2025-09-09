<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PhoneVerification extends Model
{
    protected $fillable = [
        'phone_number',
        'code',
        'is_verified',
        'expires_at',
        'registration_data'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at' => 'datetime',
        'registration_data' => 'array'
    ];

    /**
     * Generate verification code
     */
    public static function generateCode(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if code is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if code is valid
     */
    public function isValid(): bool
    {
        return !$this->is_verified && !$this->isExpired();
    }

    /**
     * Mark as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['is_verified' => true]);
    }

    /**
     * Scope for active verifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_verified', false)
                    ->where('expires_at', '>', now());
    }

    /**
     * Clean expired verifications
     */
    public static function cleanExpired(): void
    {
        static::where('expires_at', '<', now())->delete();
    }
}
