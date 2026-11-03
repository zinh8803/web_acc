<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhatKyHoatDong extends Model
{
    protected $table = 'nhat_ky_hoat_dong';

    public $timestamps = false;

    protected $fillable = [
        'loai_tac_nhan',
        'tac_nhan_id',
        'hanh_dong',
        'loai_doi_tuong',
        'doi_tuong_id',
        'du_lieu_truoc',
        'du_lieu_sau',
        'dia_chi_ip',
        'thiet_bi_trinh_duyet',
        'ngay_tao',
    ];

    protected $casts = [
        'du_lieu_truoc' => 'array',
        'du_lieu_sau' => 'array',
        'ngay_tao' => 'datetime',
    ];

    public function quanTriVien()
    {
        return $this->belongsTo(QuanTriVien::class, 'tac_nhan_id');
    }

    public function adminUser()
    {
        return $this->quanTriVien();
    }
}

