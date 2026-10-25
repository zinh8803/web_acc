<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'variant_id', 'url', 'thumbnail_url', 'alt_text', 'sort_order', 'is_primary', 'created_at',
    ];

    protected $casts = ['is_primary' => 'boolean', 'sort_order' => 'integer'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
