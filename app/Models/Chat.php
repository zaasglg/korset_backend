<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_id',
        'product_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Check if user is participant of this chat
     */
    public function isParticipant($userId): bool
    {
        return $this->user_id == $userId || $this->seller_id == $userId;
    }

    /**
     * Get the other participant (not the current user)
     */
    public function getOtherParticipant($currentUserId): ?User
    {
        if ($this->user_id == $currentUserId) {
            return $this->seller;
        } elseif ($this->seller_id == $currentUserId) {
            return $this->user;
        }
        
        return null;
    }
}
