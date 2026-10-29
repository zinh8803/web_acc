<?php

namespace App\Services;

use App\Mail\OrderSuccessMail;
use App\Mail\ServiceRequestSubmittedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderMailService
{
    /**
     * Send email to customer upon successful payment / order fulfillment.
     * Contains account credentials (if purchased accounts) and/or service confirmation (if registered boosting).
     */
    public static function sendOrderSuccessMail(Order|int|string $order): bool
    {
        try {
            if (!$order instanceof Order) {
                $order = Order::where('id', $order)->orWhere('order_code', $order)->first();
            }

            if (!$order) {
                return false;
            }

            $order->loadMissing([
                'user',
                'items.gameAccount',
                'items.product.warrantyPolicy',
                'items.warrantyPolicy',
                'items.serviceRequest',
            ]);

            $user = $order->user;
            if (!$user || empty($user->email)) {
                Log::warning("Order #{$order->order_code} does not have a valid customer email.");
                return false;
            }

            // Prevent duplicate emails for the same order (e.g. return URL + IPN webhook)
            $cacheKey = 'order_success_mail_sent_' . $order->id;
            if (!Cache::add($cacheKey, true, now()->addDays(7))) {
                return false;
            }

            $accounts = [];
            $services = [];

            foreach ($order->items as $item) {
                if ($item->item_type === 'ACCOUNT' && $item->gameAccount) {
                    $creds = $item->gameAccount->decryptCredentials();
                    $policy = $item->warrantyPolicy ?: $item->product?->warrantyPolicy;
                    $policyName = $policy ? $policy->name : 'Bảo hành 1 đổi 1 trọn đời';

                    $accounts[] = [
                        'product_name' => $item->product_name ?: $item->product?->name ?: 'Tài khoản game',
                        'variant_name' => $item->variant_name,
                        'username' => $creds['username'] ?? 'N/A',
                        'password' => $creds['password'] ?? 'N/A',
                        'extra_info' => self::formatExtraInfo($creds['extra'] ?? null),
                        'warranty_policy' => $policyName,
                    ];
                } elseif ($item->item_type === 'SERVICE') {
                    $srv = $item->serviceRequest;
                    $formData = [];
                    if ($srv) {
                        $formData = is_array($srv->form_data) ? $srv->form_data : (json_decode($srv->form_data, true) ?: []);
                    }

                    $services[] = [
                        'product_name' => $item->product_name ?: $item->product?->name ?: 'Dịch vụ cày thuê',
                        'variant_name' => $item->variant_name,
                        'request_code' => $srv?->request_code,
                        'ingame_name' => $formData['ingame_name'] ?? null,
                        'current_rank' => $formData['current_rank'] ?? null,
                        'target_rank' => $formData['target_rank'] ?? null,
                        'target_hero' => $formData['target_hero'] ?? null,
                        'customer_note' => $srv?->customer_note,
                        'status' => $srv?->status ?? 'PENDING',
                    ];
                }
            }

            $frontendUrl = env('APP_FRONTEND_URL', env('URL_FRONTEND', 'http://localhost:5173'));
            $frontendUrl = preg_replace('#/vnpay_return.*$#', '', $frontendUrl);
            $orderUrl = rtrim($frontendUrl, '/') . '/orders/' . $order->order_code;

            $customerName = $user->full_name ?: $user->email;

            Mail::to($user->email)->send(
                new OrderSuccessMail($order, $customerName, $accounts, $services, $orderUrl)
            );

            Log::info("Sent order success email for order #{$order->order_code} to {$user->email}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send order success email for order: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            // If failed, remove cache key so it can be retried
            if (isset($order->id)) {
                Cache::forget('order_success_mail_sent_' . $order->id);
            }
            return false;
        }
    }

    /**
     * Send email when customer submits service request information.
     */
    public static function sendServiceRequestMail(ServiceRequest $service): bool
    {
        try {
            $service->loadMissing(['user', 'orderItem.order']);
            $user = $service->user;
            if (!$user || empty($user->email)) {
                return false;
            }

            $cacheKey = 'srv_mail_sent_' . $service->id;
            if (!Cache::add($cacheKey, true, now()->addDays(7))) {
                return false;
            }

            $formData = is_array($service->form_data) ? $service->form_data : (json_decode($service->form_data, true) ?: []);
            $order = $service->orderItem?->order;
            $orderCode = $order?->order_code;

            $frontendUrl = env('APP_FRONTEND_URL', env('URL_FRONTEND', 'http://localhost:5173'));
            $frontendUrl = preg_replace('#/vnpay_return.*$#', '', $frontendUrl);
            $orderUrl = rtrim($frontendUrl, '/') . ($orderCode ? '/orders/' . $orderCode : '/orders');

            $customerName = $user->full_name ?: $user->email;

            Mail::to($user->email)->send(
                new ServiceRequestSubmittedMail(
                    $service,
                    $customerName,
                    $orderCode,
                    $formData,
                    $service->customer_note,
                    $orderUrl
                )
            );

            Log::info("Sent service request confirmation email for #{$service->request_code} to {$user->email}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to send service request email: " . $e->getMessage());
            Cache::forget('srv_mail_sent_' . $service->id);
            return false;
        }
    }

    /**
     * Format raw extra_info into readable label-value pairs for customers.
     */
    public static function formatExtraInfo($extra): array
    {
        if (empty($extra)) {
            return [];
        }

        if (is_string($extra)) {
            $decoded = json_decode($extra, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $extra = $decoded;
            } else {
                return [['label' => 'Ghi chú', 'value' => $extra]];
            }
        }

        if (!is_array($extra)) {
            return [];
        }

        $labelMap = [
            'sec_email' => 'Email liên kết',
            'email' => 'Email liên kết',
            'sec_phone' => 'Số điện thoại',
            'phone' => 'Số điện thoại',
            'additional_info' => 'Ghi chú bổ sung',
            'note' => 'Ghi chú',
            'notes' => 'Ghi chú',
            'pin' => 'Mã PIN cấp 2',
            'pin_code' => 'Mã PIN cấp 2',
            'two_factor' => 'Mã xác thực (2FA)',
            '2fa' => 'Mã xác thực (2FA)',
            'backup_code' => 'Mã dự phòng',
            'login_method' => 'Hình thức đăng nhập',
            'clean_account' => 'Tình trạng thông tin',
            'server' => 'Máy chủ',
            'birth_date' => 'Ngày sinh tài khoản',
        ];

        $formatted = [];
        foreach ($extra as $key => $value) {
            // Loại bỏ giá vốn và thông tin nội bộ của shop
            if (in_array(strtolower($key), ['cost_price', 'supplier', 'supplier_id', 'created_by'], true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $label = $labelMap[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
            $valStr = is_array($value) ? implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($value), $value)) : (string) $value;

            $formatted[] = [
                'label' => $label,
                'value' => $valStr,
            ];
        }

        return $formatted;
    }
}
