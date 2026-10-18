<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AdminUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'email', 'password_hash', 'full_name', 'role_id',
        'status', 'two_factor_secret_enc', 'last_login_at', 'created_by'
    ];

    protected $hidden = [
        'password_hash',
        'two_factor_secret_enc',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
