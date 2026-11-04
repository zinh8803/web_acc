<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class YeuCauDichVu extends Model
{
    protected $table = 'yeu_cau_dich_vu';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $hidden = ['thong_tin_dang_nhap_ma_hoa'];

    protected $fillable = [
        'ma_yeu_cau',
        'chi_tiet_don_hang_id',
        'nguoi_dung_id',
        'du_lieu_bieu_mau',
        'thong_tin_dang_nhap_ma_hoa',
        'ghi_chu_khach_hang',
        'nhan_vien_phu_trach_id',
        'trang_thai',
        'do_uu_tien',
        'han_hoan_thanh',
        'bat_dau_luc',
        'hoan_thanh_luc',
        'tu_dong_xac_nhan_luc',
        'khach_xac_nhan_luc',
        'ly_do_huy',
    ];

    protected $casts = [
        'du_lieu_bieu_mau' => 'array',
        'do_uu_tien' => 'integer',
        'han_hoan_thanh' => 'datetime',
        'bat_dau_luc' => 'datetime',
        'hoan_thanh_luc' => 'datetime',
        'tu_dong_xac_nhan_luc' => 'datetime',
        'khach_xac_nhan_luc' => 'datetime',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function chiTietDonHang(): BelongsTo
    {
        return $this->belongsTo(ChiTietDonHang::class, 'chi_tiet_don_hang_id');
    }

    public function nhanVienPhuTrach(): BelongsTo
    {
        return $this->belongsTo(QuanTriVien::class, 'nhan_vien_phu_trach_id');
    }

    public function giaiMaThongTinDangNhap(): ?array
    {
        if (empty($this->thong_tin_dang_nhap_ma_hoa)) {
            return null;
        }
        try {
            $decrypted = Crypt::decryptString($this->thong_tin_dang_nhap_ma_hoa);
            $data = json_decode($decrypted, true);
            if (is_array($data)) {
                return $data;
            }
            return ['thong_tin_dang_nhap' => $decrypted];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
