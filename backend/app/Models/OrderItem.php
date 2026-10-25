<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'game_account_id',
        'item_type', 'product_name', 'quantity', 'unit_price',
        'variant_name', 'thumbnail_url', 'line_total', 'warranty_policy_id',
        'warranty_snapshot', 'warranty_expires_at', 'fulfillment_status', 'delivered_at'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:0',
        'warranty_snapshot' => 'array',
        'warranty_expires_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function gameAccount(): BelongsTo
    {
        return $this->belongsTo(GameAccount::class);
    }
}
