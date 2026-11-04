<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SanPham extends Model
{
    use HasFactory;

    protected $table = 'san_pham';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_san_pham',
        'loai_san_pham',
        'danh_muc_id',
        'ten_san_pham',
        'duong_dan_tinh',
        'mo_ta_ngan',
        'noi_dung_chi_tiet',
        'anh_dai_dien',
        'chinh_sach_bao_hanh_id',
        'gia_thap_nhat',
        'trang_thai',
        'noi_bat',
        'cau_hinh_bieu_mau_dich_vu',
        'so_luong_da_ban',
        'luot_xem',
        'diem_danh_gia_tb',
        'so_luot_danh_gia',
        'nguoi_tao_id',
    ];

    protected $casts = [
        'gia_thap_nhat' => 'decimal:0',
        'noi_bat' => 'boolean',
        'cau_hinh_bieu_mau_dich_vu' => 'array',
        'diem_danh_gia_tb' => 'float',
        'so_luong_da_ban' => 'integer',
        'luot_xem' => 'integer',
        'so_luot_danh_gia' => 'integer',
    ];

    public function danhMuc(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    public function bienThes(): HasMany
    {
        return $this->hasMany(BienTheSanPham::class, 'san_pham_id');
    }

    public function hinhAnhs(): HasMany
    {
        return $this->hasMany(HinhAnhSanPham::class, 'san_pham_id')->orderBy('thu_tu_hien_thi');
    }

    public function anhChinh(): HasOne
    {
        return $this->hasOne(HinhAnhSanPham::class, 'san_pham_id')->where('anh_chinh', true);
    }

    public function chinhSachBaoHanh(): BelongsTo
    {
        return $this->belongsTo(ChinhSachBaoHanh::class, 'chinh_sach_bao_hanh_id');
    }

    public function taiKhoanGames(): HasMany
    {
        return $this->hasMany(TaiKhoanGame::class, 'san_pham_id');
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGia::class, 'san_pham_id');
    }
}
