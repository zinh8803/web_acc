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

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'don_hang_id');
    }

    public function maGiamGia(): BelongsTo
    {
        return $this->belongsTo(MaGiamGia::class, 'ma_giam_gia_id');
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
