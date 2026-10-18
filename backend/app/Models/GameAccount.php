<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class GameAccount extends Model
{
    protected $fillable = [
        'product_id', 'variant_id', 'supplier_id',
        'account_username_enc', 'account_password_enc', 'extra_info_enc',
        'original_owner_info_enc', 'status', 'reserved_by_order_id',
        'reserved_until', 'sold_order_item_id', 'sold_at',
        'cost_price', 'verified_at', 'verified_by', 'note', 'created_by'
    ];

    protected $casts = [
        'reserved_until' => 'datetime',
        'sold_at' => 'datetime',
        'verified_at' => 'datetime',
        'cost_price' => 'decimal:0',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Create account encrypting credentials using AES-256
     */
    public static function createWithEncryption(
        int $productId,
        int $variantId,
        string $username,
        string $password,
        array $extra = []
    ): self {
        return self::create([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'account_username_enc' => Crypt::encryptString($username),
            'account_password_enc' => Crypt::encryptString($password),
            'extra_info_enc' => !empty($extra) ? Crypt::encryptString(json_encode($extra)) : null,
            'status' => 'AVAILABLE',
            'cost_price' => $extra['cost_price'] ?? null,
        ]);
    }

    /**
     * Decrypt credentials safely
     */
    public function decryptCredentials(): array
    {
        try {
            $username = Crypt::decryptString($this->account_username_enc);
            $password = Crypt::decryptString($this->account_password_enc);
            $extra = $this->extra_info_enc ? json_decode(Crypt::decryptString($this->extra_info_enc), true) : [];

            return [
                'username' => $username,
                'password' => $password,
                'extra' => $extra,
            ];
        } catch (\Throwable $e) {
            return [
                'username' => 'DECRYPTION_ERROR',
                'password' => 'DECRYPTION_ERROR',
                'extra' => [],
            ];
        }
    }
}
