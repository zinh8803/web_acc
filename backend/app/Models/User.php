<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'email', 'phone', 'password_hash', 'full_name',
        'avatar_url', 'birth_date', 'status',
        'email_verified_at', 'failed_login_count', 'locked_until',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'birth_date' => 'date',
    ];

    // Override password field
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function isBanned(): bool
    {
        return $this->status === 'BANNED';
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function warrantyClaims()
    {
        return $this->hasMany(WarrantyClaim::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
