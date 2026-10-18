<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'discount_type', 'discount_value',
        'max_discount_amount', 'min_order_amount', 'applies_to',
        'applies_product_type', 'usage_limit', 'usage_per_user',
        'used_count', 'starts_at', 'ends_at', 'status', 'created_by'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:0',
        'min_order_amount' => 'decimal:0',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
