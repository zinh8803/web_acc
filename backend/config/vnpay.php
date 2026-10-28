<?php

return [
    'vnp_TmnCode'    => env('VNP_TMN_CODE', ''), 
    'vnp_HashSecret' => env('VNP_HASH_SECRET', ''), 
    'vnp_Url'        => env('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'), 
    'vnp_Returnurl'  => env('VNP_RETURN_URL', 'http://localhost:8000/api/v1/payments/vnpay/return'),
    'frontend_url'   => env('URL_VNPAY_FRONTEND', env('URL_FRONTEND') ? (str_ends_with(env('URL_FRONTEND'), '/vnpay_return') ? env('URL_FRONTEND') : rtrim(env('URL_FRONTEND'), '/') . '/vnpay_return') : 'http://localhost:5173/vnpay_return'),
];
