<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChiTietDonHang extends Model
{
    protected $table = 'chi_tiet_don_hang';

    public $timestamps = false;

    protected $fillable = [
        'don_hang_id',
        'loai_muc',
        'san_pham_id',
        'bien_the_id',
        'ten_san_pham_luu_lai',
        'ten_bien_the_luu_lai',
        'anh_san_pham_luu_lai',
        'don_gia',
        'so_luong',
        'thanh_tien',
        'tai_khoan_game_id',
        'chinh_sach_bao_hanh_id',
        'thong_tin_bao_hanh_luu_lai',
        'het_han_bao_hanh_luc',
        'trang_thai_giao_hang',
        'giao_hang_luc',
        'ngay_tao',
    ];

    protected $casts = [
        'don_gia' => 'decimal:2',
        'thanh_tien' => 'decimal:0',
        'so_luong' => 'integer',
        'thong_tin_bao_hanh_luu_lai' => 'array',
        'het_han_bao_hanh_luc' => 'datetime',
        'giao_hang_luc' => 'datetime',
    ];

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function bienThe(): BelongsTo
    {
        return $this->belongsTo(BienTheSanPham::class, 'bien_the_id');
    }

    public function taiKhoanGame(): BelongsTo
    {
        return $this->belongsTo(TaiKhoanGame::class, 'tai_khoan_game_id');
    }

    public function yeuCauDichVu(): HasOne
    {
        return $this->hasOne(YeuCauDichVu::class, 'chi_tiet_don_hang_id');
    }

    public function chinhSachBaoHanh(): BelongsTo
    {
        return $this->belongsTo(ChinhSachBaoHanh::class, 'chinh_sach_bao_hanh_id');
    }

    public function yeuCauBaoHanhs(): HasMany
    {
        return $this->hasMany(YeuCauBaoHanh::class, 'chi_tiet_don_hang_id');
    }
}
