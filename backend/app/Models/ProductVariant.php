<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'name', 'price', 'compare_at_price',
        'sale_price', 'sale_start_at', 'sale_end_at', 'cost_price',
        'stock_quantity', 'estimated_hours', 'status', 'sort_order'
    ];

    protected $casts = [
        'price' => 'decimal:0',
        'compare_at_price' => 'decimal:0',
        'sale_price' => 'decimal:0',
        'cost_price' => 'decimal:0',
        'stock_quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function gameAccounts(): HasMany
    {
        return $this->hasMany(GameAccount::class, 'variant_id');
    }
}
