<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyClaim extends Model
{
    protected $fillable = [
        'claim_code', 'order_item_id', 'user_id', 'game_account_id',
        'policy_id', 'claim_type', 'description', 'status',
        'resolution_type', 'resolution_note', 'reject_reason',
        'replacement_order_item_id', 'assigned_to', 'reviewed_by',
        'due_at', 'submitted_at', 'resolved_at'
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected $appends = ['reason', 'order'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(WarrantyPolicy::class, 'policy_id');
    }

    public function gameAccount(): BelongsTo
    {
        return $this->belongsTo(GameAccount::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assigned_to');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by');
    }

    public function getOrderAttribute()
    {
        return $this->orderItem?->order;
    }

    public function getReasonAttribute(): string
    {
        $types = [
            'LOSS_ACCESS' => 'Mất quyền truy cập tài khoản',
            'RECOVERED_BY_OLD_OWNER' => 'Bị chủ cũ lấy lại (Back nick)',
            'WRONG_CREDENTIALS' => 'Sai mật khẩu hoặc thông tin đăng nhập',
            'SERVICE_NOT_DONE' => 'Dịch vụ cày thuê không đạt yêu cầu / quá hạn',
            'OTHER' => 'Lý do khác',
        ];

        return $types[$this->claim_type] ?? ($this->claim_type ?: 'Bảo hành');
    }
}
