<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanhGia extends Model
{
    protected $table = 'danh_gia';

    public $timestamps = false;

    protected $fillable = [
        'nguoi_dung_id',
        'san_pham_id',
        'chi_tiet_don_hang_id',
        'so_sao',
        'noi_dung_danh_gia',
        'trang_thai',
        'ngay_tao',
    ];

    protected $casts = [
        'so_sao' => 'integer',
        'ngay_tao' => 'datetime',
    ];

    protected $appends = [
        'rating',
        'content',
        'status',
        'user_id',
        'product_id',
    ];

    public function getRatingAttribute(): int
    {
        return $this->attributes['so_sao'] ?? 5;
    }

    public function getContentAttribute(): ?string
    {
        return $this->attributes['noi_dung_danh_gia'] ?? null;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->attributes['nguoi_dung_id'] ?? null;
    }

    public function getProductIdAttribute(): ?int
    {
        return $this->attributes['san_pham_id'] ?? null;
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function user(): BelongsTo
    {
        return $this->nguoiDung();
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function product(): BelongsTo
    {
        return $this->sanPham();
    }

    public function chiTietDonHang(): BelongsTo
    {
        return $this->belongsTo(ChiTietDonHang::class, 'chi_tiet_don_hang_id');
    }
}

