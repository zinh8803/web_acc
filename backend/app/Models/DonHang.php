<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonHang extends Model
{
    protected $table = 'don_hang';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_don_hang',
        'nguoi_dung_id',
        'trang_thai',
        'tam_tinh',
        'tong_giam_gia',
        'tong_tien',
        'ma_giam_gia_id',
        'ma_giam_gia_luu_lai',
        'phuong_thuc_thanh_toan',
        'ma_giao_dich_thanh_toan',
        'ghi_chu_khach_hang',
        'het_han_thanh_toan_luc',
        'thanh_toan_luc',
        'hoan_thanh_luc',
        'huy_luc',
        'ly_do_huy',
        'dia_chi_ip',
    ];

    protected $casts = [
        'het_han_thanh_toan_luc' => 'datetime',
        'thanh_toan_luc' => 'datetime',
        'hoan_thanh_luc' => 'datetime',
        'huy_luc' => 'datetime',
        'tam_tinh' => 'integer',
        'tong_giam_gia' => 'integer',
        'tong_tien' => 'integer',
    ];

    protected $appends = [
        'order_code',
        'status',
        'total',
        'subtotal',
        'discount_total',
        'payment_method_code',
        'payment_reference',
        'paid_at',
        'user_id',
    ];

    public function getOrderCodeAttribute(): ?string
    {
        return $this->attributes['ma_don_hang'] ?? null;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }

    public function getTotalAttribute(): mixed
    {
        return $this->attributes['tong_tien'] ?? 0;
    }

    public function getSubtotalAttribute(): mixed
    {
        return $this->attributes['tam_tinh'] ?? 0;
    }

    public function getDiscountTotalAttribute(): mixed
    {
        return $this->attributes['tong_giam_gia'] ?? 0;
    }

    public function getPaymentMethodCodeAttribute(): ?string
    {
        return $this->attributes['phuong_thuc_thanh_toan'] ?? null;
    }

    public function getPaymentReferenceAttribute(): ?string
    {
        return $this->attributes['ma_giao_dich_thanh_toan'] ?? null;
    }

    public function getPaidAtAttribute(): mixed
    {
        return $this->attributes['thanh_toan_luc'] ?? null;
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->attributes['nguoi_dung_id'] ?? null;
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function user(): BelongsTo
    {
        return $this->nguoiDung();
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'don_hang_id');
    }

    public function items(): HasMany
    {
        return $this->chiTiets();
    }

    public function maGiamGia(): BelongsTo
    {
        return $this->belongsTo(MaGiamGia::class, 'ma_giam_gia_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->maGiamGia();
    }

    // Helper trạng thái
    public function isPendingPayment(): bool
    {
        return $this->trang_thai === 'PENDING_PAYMENT';
    }

    public function isPaid(): bool
    {
        return in_array($this->trang_thai, ['PAID', 'PROCESSING', 'COMPLETED']);
    }

    public function isExpired(): bool
    {
        return $this->trang_thai === 'EXPIRED' || ($this->isPendingPayment() && $this->het_han_thanh_toan_luc && $this->het_han_thanh_toan_luc->isPast());
    }

    public function canCancel(): bool
    {
        return $this->trang_thai === 'PENDING_PAYMENT';
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('nguoi_dung_id', $userId);
    }

    public function scopePendingExpired($query)
    {
        return $query->where('trang_thai', 'PENDING_PAYMENT')
            ->where('het_han_thanh_toan_luc', '<', now());
    }
}
