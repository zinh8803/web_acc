<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    // cart_items uses added_at and intentionally has no created_at/updated_at columns.
    public $timestamps = false;

    protected $fillable = ['cart_id', 'variant_id', 'game_account_id', 'quantity'];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function gameAccount(): BelongsTo
    {
        return $this->belongsTo(GameAccount::class, 'game_account_id');
    }
}
