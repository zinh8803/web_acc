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
        'created_at',
        'rating',
        'content',
    ];

    public function getCreatedAtAttribute()
    {
        return $this->ngay_tao;
    }

    public function getRatingAttribute()
    {
        return $this->so_sao;
    }

    public function getContentAttribute()
    {
        return $this->noi_dung_danh_gia;
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function chiTietDonHang(): BelongsTo
    {
        return $this->belongsTo(ChiTietDonHang::class, 'chi_tiet_don_hang_id');
    }
}
