<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassportVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'passport_number',
        'passport_photo',
        'selfie_photo',
        'status',
        'admin_comment',
        'verified_at'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
