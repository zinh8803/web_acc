<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyClaim extends Model
{
    protected $fillable = [
        'claim_code', 'order_id', 'order_item_id', 'user_id',
        'reason', 'description', 'evidence_images', 'status',
        'resolution_type', 'resolution_notes', 'assigned_staff_id',
        'resolved_at'
    ];

    protected $casts = [
        'evidence_images' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_staff_id');
    }
}
