<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_code', 'user_id', 'status', 'subtotal', 'discount_total', 'total',
        'coupon_id', 'coupon_code', 'payment_method_code', 'customer_note',
        'expires_at', 'paid_at', 'completed_at', 'cancelled_at', 'cancel_reason', 'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'integer',
        'discount_total' => 'integer',
        'total' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    // Status helpers
    public function isPendingPayment(): bool
    {
        return $this->status === 'PENDING_PAYMENT';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['PAID', 'PROCESSING', 'COMPLETED']);
    }

    public function isExpired(): bool
    {
        return $this->status === 'EXPIRED' || ($this->isPendingPayment() && $this->expires_at->isPast());
    }

    public function canCancel(): bool
    {
        return $this->status === 'PENDING_PAYMENT';
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePendingExpired($query)
    {
        return $query->where('status', 'PENDING_PAYMENT')
            ->where('expires_at', '<', now());
    }
}
