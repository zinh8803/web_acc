<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaiDat extends Model
{
    protected $table = 'cai_dat';

    public $timestamps = false;
    protected $primaryKey = 'khoa_cai_dat';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['khoa_cai_dat', 'gia_tri_cai_dat', 'nguoi_cap_nhat_id'];

    protected $casts = [
        'gia_tri_cai_dat' => 'json',
    ];

    public static function lay(string $khoa, mixed $macDinh = null): mixed
    {
        $caiDat = static::find($khoa);
        return $caiDat ? $caiDat->gia_tri_cai_dat : $macDinh;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::lay($key, $default);
    }
}
