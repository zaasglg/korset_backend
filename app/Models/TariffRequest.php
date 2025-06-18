<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tariff_id',
        'status',
        'admin_comment',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Get the user that made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tariff that was requested.
     */
    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }
}
