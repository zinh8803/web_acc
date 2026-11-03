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

    protected $appends = ['key', 'value'];

    public function getKeyAttribute(): ?string
    {
        return $this->attributes['khoa_cai_dat'] ?? null;
    }

    public function getValueAttribute(): mixed
    {
        return $this->attributes['gia_tri_cai_dat'] ?? null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);
        return $setting ? $setting->gia_tri_cai_dat : $default;
    }
}

