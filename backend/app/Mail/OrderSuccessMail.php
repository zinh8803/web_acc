<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $customerName;
    public array $accounts;
    public array $services;
    public string $orderUrl;

    public function __construct(
        Order $order,
        string $customerName,
        array $accounts,
        array $services,
        string $orderUrl
    ) {
        $this->order = $order;
        $this->customerName = $customerName;
        $this->accounts = $accounts;
        $this->services = $services;
        $this->orderUrl = $orderUrl;
    }

    public function build()
    {
        $hasAccounts = !empty($this->accounts);
        $hasServices = !empty($this->services);

        if ($hasAccounts && $hasServices) {
            $subject = "[Shop Acc] Đơn hàng #{$this->order->order_code} - Bàn giao tài khoản & Xác nhận cày thuê";
        } elseif ($hasAccounts) {
            $subject = "[Shop Acc] Đơn hàng #{$this->order->order_code} - Thông tin tài khoản game đã mua";
        } else {
            $subject = "[Shop Acc] Đơn hàng #{$this->order->order_code} - Xác nhận đăng ký dịch vụ cày thuê";
        }

        return $this->subject($subject)
                    ->view('emails.order_success')
                    ->with([
                        'order' => $this->order,
                        'customerName' => $this->customerName,
                        'accounts' => $this->accounts,
                        'services' => $this->services,
                        'orderUrl' => $this->orderUrl,
                    ]);
    }
}
