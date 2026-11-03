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

    protected $appends = [
        'claim_code',
        'claim_type',
        'description',
        'status',
        'user_id',
        'order_item_id',
        'policy_id',
        'game_account_id',
        'reason',
        'order',
    ];

    public function getClaimCodeAttribute(): ?string
    {
        return $this->attributes['ma_yeu_cau_bao_hanh'] ?? null;
    }

    public function getClaimTypeAttribute(): ?string
    {
        return $this->attributes['loai_yeu_cau'] ?? null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['mo_ta_su_co'] ?? null;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->attributes['nguoi_dung_id'] ?? null;
    }

    public function getOrderItemIdAttribute(): ?int
    {
        return $this->attributes['chi_tiet_don_hang_id'] ?? null;
    }

    public function getPolicyIdAttribute(): ?int
    {
        return $this->attributes['chinh_sach_bao_hanh_id'] ?? null;
    }

    public function getGameAccountIdAttribute(): ?int
    {
        return $this->attributes['tai_khoan_game_id'] ?? null;
    }

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function user(): BelongsTo
    {
        return $this->nguoiDung();
    }

    public function chiTietDonHang(): BelongsTo
    {
        return $this->belongsTo(ChiTietDonHang::class, 'chi_tiet_don_hang_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->chiTietDonHang();
    }

    public function chinhSach(): BelongsTo
    {
        return $this->belongsTo(ChinhSachBaoHanh::class, 'chinh_sach_bao_hanh_id');
    }

    public function policy(): BelongsTo
    {
        return $this->chinhSach();
    }

    public function taiKhoanGame(): BelongsTo
    {
        return $this->belongsTo(TaiKhoanGame::class, 'tai_khoan_game_id');
    }

    public function gameAccount(): BelongsTo
    {
        return $this->taiKhoanGame();
    }

    public function nhanVienPhuTrach(): BelongsTo
    {
        return $this->belongsTo(QuanTriVien::class, 'nhan_vien_phu_trach_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->nhanVienPhuTrach();
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(QuanTriVien::class, 'nguoi_duyet_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->nguoiDuyet();
    }

    public function getOrderAttribute()
    {
        return $this->chiTietDonHang?->donHang;
    }

    public function getReasonAttribute(): string
    {
        $types = [
            'LOSS_ACCESS' => 'Mất quyền truy cập tài khoản',
            'RECOVERED_BY_OLD_OWNER' => 'Bị chủ cũ lấy lại (Back nick)',
            'WRONG_CREDENTIALS' => 'Sai mật khẩu hoặc thông tin đăng nhập',
            'SERVICE_NOT_DONE' => 'Dịch vụ cày thuê không đạt yêu cầu / quá hạn',
            'OTHER' => 'Lý do khác',
        ];

        return $types[$this->loai_yeu_cau] ?? ($this->loai_yeu_cau ?: 'Bảo hành');
    }
}

