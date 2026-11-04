<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class TaiKhoanGame extends Model
{
    protected $table = 'tai_khoan_game';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'san_pham_id',
        'bien_the_id',
        'ten_dang_nhap_ma_hoa',
        'mat_khau_ma_hoa',
        'thong_tin_phu_ma_hoa',
        'thong_tin_chu_cu_ma_hoa',
        'anh_minh_hoa',
        'thong_tin_cong_khai',
        'trang_thai',
        'giu_cho_boi_don_hang_id',
        'giu_cho_den_khi',
        'chi_tiet_don_hang_da_ban_id',
        'thoi_gian_ban',
        'gia_nhap',
        'xac_minh_luc',
        'nguoi_xac_minh_id',
        'ghi_chu_noi_bo',
        'nguoi_tao_id',
    ];

    protected $hidden = [
        'ten_dang_nhap_ma_hoa',
        'mat_khau_ma_hoa',
        'thong_tin_phu_ma_hoa',
        'thong_tin_chu_cu_ma_hoa',
    ];

    protected $casts = [
        'giu_cho_den_khi' => 'datetime',
        'thoi_gian_ban' => 'datetime',
        'xac_minh_luc' => 'datetime',
        'gia_nhap' => 'decimal:0',
        'thong_tin_cong_khai' => 'array',
    ];

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function bienThe(): BelongsTo
    {
        return $this->belongsTo(BienTheSanPham::class, 'bien_the_id');
    }

    public function chiTietDonHangDaBan(): BelongsTo
    {
        return $this->belongsTo(ChiTietDonHang::class, 'chi_tiet_don_hang_da_ban_id');
    }

    public function donHangGiuCho(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'giu_cho_boi_don_hang_id');
    }

    /**
     * Tạo tài khoản game mã hóa thông tin bảo mật (AES-256)
     */
    public static function taoTaiKhoanMaHoa(
        int $sanPhamId,
        int $bienTheId,
        string $tenDangNhap,
        string $matKhau,
        array $thongTinPhu = []
    ): self {
        return self::create([
            'san_pham_id' => $sanPhamId,
            'bien_the_id' => $bienTheId,
            'ten_dang_nhap_ma_hoa' => Crypt::encryptString($tenDangNhap),
            'mat_khau_ma_hoa' => Crypt::encryptString($matKhau),
            'thong_tin_phu_ma_hoa' => !empty($thongTinPhu) ? Crypt::encryptString(json_encode($thongTinPhu)) : null,
            'trang_thai' => 'AVAILABLE',
            'gia_nhap' => $thongTinPhu['gia_nhap'] ?? null,
            'anh_minh_hoa' => $thongTinPhu['anh_minh_hoa'] ?? null,
            'thong_tin_cong_khai' => $thongTinPhu['thong_tin_cong_khai'] ?? null,
        ]);
    }

    /**
     * Giải mã thông tin đăng nhập an toàn
     */
    public function giaiMaThongTin(): array
    {
        try {
            $tenDangNhap = Crypt::decryptString($this->ten_dang_nhap_ma_hoa);
            $matKhau = Crypt::decryptString($this->mat_khau_ma_hoa);
            $thongTinPhu = $this->thong_tin_phu_ma_hoa ? json_decode(Crypt::decryptString($this->thong_tin_phu_ma_hoa), true) : [];

            return [
                'ten_dang_nhap' => $tenDangNhap,
                'mat_khau' => $matKhau,
                'thong_tin_phu' => $thongTinPhu,
            ];
        } catch (\Throwable $e) {
            return [
                'ten_dang_nhap' => 'LỖI_GIẢI_MÃ',
                'mat_khau' => 'LỖI_GIẢI_MÃ',
                'thong_tin_phu' => [],
            ];
        }
    }
}
