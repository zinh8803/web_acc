<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaGiamGia extends Model
{
    protected $table = 'ma_giam_gia';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_khuyen_mai',
        'ten_ma_giam_gia',
        'mo_ta',
        'loai_giam_gia',
        'gia_tri_giam',
        'giam_toi_da',
        'don_hang_toi_thieu',
        'ap_dung_cho',
        'ap_dung_loai_san_pham',
        'gioi_han_su_dung',
        'gioi_han_moi_nguoi_dung',
        'so_lan_da_dung',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
        'nguoi_tao_id',
    ];

    protected $casts = [
        'gia_tri_giam' => 'decimal:2',
        'giam_toi_da' => 'decimal:0',
        'don_hang_toi_thieu' => 'decimal:0',
        'so_lan_da_dung' => 'integer',
        'gioi_han_su_dung' => 'integer',
        'gioi_han_moi_nguoi_dung' => 'integer',
        'ngay_bat_dau' => 'datetime',
        'ngay_ket_thuc' => 'datetime',
    ];

    public function lichSuDungs(): HasMany
    {
        return $this->hasMany(LichSuDungMaGiamGia::class, 'ma_giam_gia_id');
    }

    /**
     * Kiểm tra mã giảm giá có hợp lệ cho đơn hàng hay không
     */
    public function kiemTraHopLe(?int $nguoiDungId, float $tamTinh, ?string $loaiSanPham = null): array
    {
        if ($this->trang_thai !== 'ACTIVE') {
            return ['hop_le' => false, 'thong_bao' => 'Mã giảm giá hiện không khả dụng'];
        }

        $now = now();
        if ($this->ngay_bat_dau && $now->lt($this->ngay_bat_dau)) {
            return ['hop_le' => false, 'thong_bao' => 'Mã giảm giá chưa đến thời gian áp dụng'];
        }

        if ($this->ngay_ket_thuc && $now->gt($this->ngay_ket_thuc)) {
            return ['hop_le' => false, 'thong_bao' => 'Mã giảm giá đã hết hạn'];
        }

        if ($this->don_hang_toi_thieu && $tamTinh < (float)$this->don_hang_toi_thieu) {
            return [
                'hop_le' => false,
                'thong_bao' => 'Đơn hàng tối thiểu phải từ ' . number_format($this->don_hang_toi_thieu, 0, ',', '.') . ' đ để dùng mã này'
            ];
        }

        if ($this->gioi_han_su_dung && $this->so_lan_da_dung >= $this->gioi_han_su_dung) {
            return ['hop_le' => false, 'thong_bao' => 'Mã giảm giá đã hết lượt sử dụng'];
        }

        if ($nguoiDungId && $this->gioi_han_moi_nguoi_dung) {
            $daDung = $this->lichSuDungs()->where('nguoi_dung_id', $nguoiDungId)->count();
            if ($daDung >= $this->gioi_han_moi_nguoi_dung) {
                return ['hop_le' => false, 'thong_bao' => 'Bạn đã dùng hết số lần cho phép của mã giảm giá này'];
            }
        }

        if ($this->ap_dung_loai_san_pham && $loaiSanPham && $this->ap_dung_loai_san_pham !== $loaiSanPham) {
            return ['hop_le' => false, 'thong_bao' => 'Mã giảm giá không áp dụng cho loại sản phẩm này'];
        }

        return ['hop_le' => true, 'thong_bao' => 'Áp dụng mã giảm giá thành công'];
    }

    /**
     * Tính số tiền được giảm
     */
    public function tinhSoTienGiam(float $tamTinh): float
    {
        $soTienGiam = 0;
        if ($this->loai_giam_gia === 'PERCENT') {
            $soTienGiam = ($tamTinh * (float)$this->gia_tri_giam) / 100;
            if ($this->giam_toi_da && $soTienGiam > (float)$this->giam_toi_da) {
                $soTienGiam = (float)$this->giam_toi_da;
            }
        } else {
            $soTienGiam = (float)$this->gia_tri_giam;
        }

        return min($soTienGiam, $tamTinh);
    }
}
