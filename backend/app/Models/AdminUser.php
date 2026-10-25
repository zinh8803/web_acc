<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class AdminUser extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'email', 'password_hash', 'full_name', 'role',
        'status', 'two_factor_secret_enc', 'last_login_at'
    ];

    protected $hidden = [
        'password_hash',
        'two_factor_secret_enc',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPER_ADMIN';
    }
}
