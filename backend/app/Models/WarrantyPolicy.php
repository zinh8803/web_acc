<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyPolicy extends Model
{
    protected $fillable = [
        'code', 'name', 'product_type', 'duration_days', 'is_lifetime', 'coverage',
        'exclusions', 'max_claims', 'resolution_options', 'claim_sla_hours', 'description', 'status', 'created_by',
    ];

    protected $casts = [
        'is_lifetime' => 'boolean',
        'coverage' => 'array',
        'resolution_options' => 'array',
    ];
}
