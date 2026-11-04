<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeuCauBaoHanh extends Model
{
    protected $table = 'yeu_cau_bao_hanh';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_yeu_cau_bao_hanh',
        'chi_tiet_don_hang_id',
        'nguoi_dung_id',
        'tai_khoan_game_id',
        'chinh_sach_bao_hanh_id',
        'loai_yeu_cau',
        'mo_ta_su_co',
        'trang_thai',
        'hinh_thuc_giai_quyet',
        'ghi_chu_giai_quyet',
        'ly_do_tu_choi',
        'chi_tiet_don_hang_thay_the_id',
        'nhan_vien_phu_trach_id',
        'nguoi_duyet_id',
        'han_xu_ly',
        'ngay_gui_yeu_cau',
        'ngay_giai_quyet',
    ];

    protected $casts = [
        'han_xu_ly' => 'datetime',
        'ngay_gui_yeu_cau' => 'datetime',
        'ngay_giai_quyet' => 'datetime',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function chiTietDonHang(): BelongsTo
    {
        return $this->belongsTo(ChiTietDonHang::class, 'chi_tiet_don_hang_id');
    }

    public function chinhSach(): BelongsTo
    {
        return $this->belongsTo(ChinhSachBaoHanh::class, 'chinh_sach_bao_hanh_id');
    }

    public function taiKhoanGame(): BelongsTo
    {
        return $this->belongsTo(TaiKhoanGame::class, 'tai_khoan_game_id');
    }

    public function nhanVienPhuTrach(): BelongsTo
    {
        return $this->belongsTo(QuanTriVien::class, 'nhan_vien_phu_trach_id');
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(QuanTriVien::class, 'nguoi_duyet_id');
    }
}
