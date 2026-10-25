<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    protected $hidden = ['game_login_enc'];

    protected $fillable = [
        'request_code', 'order_item_id', 'user_id', 'game_id', 'form_data',
        'game_login_enc', 'customer_note', 'assigned_to', 'status', 'priority',
        'due_at', 'started_at', 'completed_at', 'auto_confirm_at',
        'confirmed_at', 'cancel_reason'
    ];

    protected $casts = [
        'form_data' => 'array',
        'priority' => 'integer',
        'due_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'auto_confirm_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_to');
    }
}
