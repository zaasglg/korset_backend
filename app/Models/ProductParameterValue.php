<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductParameterValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_parameter_id',
        'value'
    ];

    /**
     * Get the product that owns the parameter value.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the parameter that owns the value.
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(ProductParameter::class, 'product_parameter_id');
    }
}
