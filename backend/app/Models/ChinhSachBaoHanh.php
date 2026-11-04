<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChinhSachBaoHanh extends Model
{
    protected $table = 'chinh_sach_bao_hanh';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_chinh_sach',
        'ten_chinh_sach',
        'loai_san_pham',
        'so_ngay_bao_hanh',
        'bao_hanh_tron_doi',
        'pham_vi_bao_hanh',
        'dieu_khoan_loai_tru',
        'so_lan_yeu_cau_toi_da',
        'phuong_an_giai_quyet',
        'thoi_gian_xu_ly_gio',
        'mo_ta',
        'trang_thai',
        'nguoi_tao_id',
    ];

    protected $casts = [
        'bao_hanh_tron_doi' => 'boolean',
        'pham_vi_bao_hanh' => 'array',
        'phuong_an_giai_quyet' => 'array',
        'so_ngay_bao_hanh' => 'integer',
        'so_lan_yeu_cau_toi_da' => 'integer',
        'thoi_gian_xu_ly_gio' => 'integer',
    ];

    public function sanPhams(): HasMany
    {
        return $this->hasMany(SanPham::class, 'chinh_sach_bao_hanh_id');
    }

    public function chiTietDonHangs(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'chinh_sach_bao_hanh_id');
    }
}
