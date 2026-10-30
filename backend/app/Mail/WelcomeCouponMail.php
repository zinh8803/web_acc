<?php

namespace App\Mail;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeCouponMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Coupon $coupon;
    public string $storeUrl;

    public function __construct(User $user, Coupon $coupon)
    {
        $this->user = $user;
        $this->coupon = $coupon;
        $this->storeUrl = config('vnpay.frontend_url', 'http://localhost:5173');
    }

    public function build()
    {
        return $this->subject('🎉 Chào mừng bạn gia nhập! Tặng bạn mã giảm giá 10% [CHAOBANMOI]')
                    ->view('emails.welcome_coupon')
                    ->with([
                        'user' => $this->user,
                        'coupon' => $this->coupon,
                        'storeUrl' => $this->storeUrl,
                    ]);
    }
}
