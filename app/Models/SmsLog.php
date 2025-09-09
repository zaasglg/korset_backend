<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'phone_number',
        'message',
        'type',
        'success',
        'sms_id',
        'sms_count',
        'error_message',
        'error_code',
        'user_id'
    ];

    protected $casts = [
        'success' => 'boolean',
        'sms_count' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
