<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    protected $fillable = [
        'order_id', 'order_item_id', 'user_id', 'service_type',
        'input_data', 'assigned_staff_id', 'status', 'progress_percentage',
        'customer_notes', 'internal_notes', 'proof_images', 'started_at', 'completed_at'
    ];

    protected $casts = [
        'input_data' => 'array',
        'proof_images' => 'array',
        'progress_percentage' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_staff_id');
    }
}
