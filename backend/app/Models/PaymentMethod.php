<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'provider', 'icon_url',
        'fee_percent', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'fee_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
