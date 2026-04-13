<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendingProduct extends Model
{
    protected $fillable = [
        'product_id',
        'search_count',
        'view_count',
        'sale_count',
        'score',
        'period',
        'computed_at',
    ];

    protected $casts = [
        'computed_at' => 'datetime',
        'score'        => 'integer',
        'search_count' => 'integer',
        'view_count'   => 'integer',
        'sale_count'   => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
