<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichSuDungMaGiamGia extends Model
{
    protected $table = 'lich_su_dung_ma_giam_gia';

    public $timestamps = false;

    protected $fillable = [
        'ma_giam_gia_id',
        'nguoi_dung_id',
        'don_hang_id',
        'so_tien_giam',
        'trang_thai',
        'thoi_gian_dung',
    ];

    protected $casts = [
        'so_tien_giam' => 'decimal:2',
        'thoi_gian_dung' => 'datetime',
    ];

    public function maGiamGia(): BelongsTo
    {
        return $this->belongsTo(MaGiamGia::class, 'ma_giam_gia_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->maGiamGia();
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function user(): BelongsTo
    {
        return $this->nguoiDung();
    }

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function order(): BelongsTo
    {
        return $this->donHang();
    }
}

