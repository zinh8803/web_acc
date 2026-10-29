<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng và bàn giao tài khoản</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0b0f19;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #e2e8f0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #121827;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #1f2937;
        }
        .header {
            background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
            padding: 30px 24px;
            text-align: center;
            border-bottom: 2px solid #6366f1;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            color: #ffffff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header p {
            margin: 8px 0 0;
            color: #a5b4fc;
            font-size: 14px;
        }
        .content {
            padding: 24px;
        }
        .order-badge {
            background: #1e293b;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            border-left: 4px solid #10b981;
        }
        .order-badge table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-badge td {
            padding: 4px 0;
            font-size: 14px;
        }
        .order-badge .label {
            color: #94a3b8;
            width: 40%;
        }
        .order-badge .value {
            color: #f8fafc;
            font-weight: 600;
        }
        .section-title {
            color: #38bdf8;
            font-size: 16px;
            font-weight: 700;
            margin: 24px 0 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }
        .card {
            background: #1a2234;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 16px;
        }
        .card-header {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
            border-bottom: 1px solid #2d3748;
            padding-bottom: 8px;
        }
        .credential-box {
            background: #0f172a;
            border: 1px solid #4338ca;
            border-radius: 8px;
            padding: 14px;
            margin-top: 10px;
        }
        .cred-row {
            margin-bottom: 8px;
            font-size: 14px;
        }
        .cred-row:last-child {
            margin-bottom: 0;
        }
        .cred-label {
            color: #94a3b8;
            display: inline-block;
            width: 120px;
        }
        .cred-val {
            font-family: 'Courier New', Courier, monospace;
            font-size: 15px;
            font-weight: 700;
            background: #1e293b;
            padding: 2px 8px;
            border-radius: 4px;
            color: #38bdf8;
            user-select: all;
        }
        .cred-val.pwd {
            color: #f43f5e;
        }
        .service-row {
            margin-bottom: 6px;
            font-size: 13px;
        }
        .service-label {
            color: #94a3b8;
            display: inline-block;
            width: 130px;
        }
        .service-val {
            color: #f1f5f9;
            font-weight: 600;
        }
        .notice-box {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 20px 0;
            color: #fbbf24;
            font-size: 13px;
            line-height: 1.5;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 10px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            color: #ffffff !important;
            font-weight: 700;
            font-size: 15px;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            letter-spacing: 0.5px;
        }
        .footer {
            background: #0d1117;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #1f2937;
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>SHOP ACC LIÊN QUÂN MOBILE</h1>
            <p>Xác nhận thanh toán thành công & Bàn giao sản phẩm</p>
        </div>

        <div class="content">
            <p style="font-size: 15px; margin-top: 0;">
                Xin chào <strong style="color: #ffffff;">{{ $customerName }}</strong>,
            </p>
            <p style="font-size: 14px; color: #cbd5e1; line-height: 1.5;">
                Đơn hàng của bạn đã được thanh toán thành công và hoàn tất giao dịch. Dưới đây là thông tin chi tiết về sản phẩm và dịch vụ đã mua:
            </p>

            <!-- ORDER SUMMARY -->
            <div class="order-badge">
                <table>
                    <tr>
                        <td class="label">Mã đơn hàng:</td>
                        <td class="value" style="color: #38bdf8;">#{{ $order->order_code }}</td>
                    </tr>
                    <tr>
                        <td class="label">Thời gian:</td>
                        <td class="value">{{ ($order->paid_at ?? now())->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Phương thức:</td>
                        <td class="value" style="text-transform: uppercase;">{{ $order->payment_method_code ?? 'VNPay / Chuyển khoản' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tổng thanh toán:</td>
                        <td class="value" style="color: #10b981; font-size: 16px;">{{ number_format($order->total, 0, ',', '.') }} đ</td>
                    </tr>
                </table>
            </div>

            <!-- TÀI KHOẢN GAME (NẾU CÓ) -->
            @if(!empty($accounts) && count($accounts) > 0)
                <div class="section-title">
                    🔑 THÔNG TIN TÀI KHOẢN BÀN GIAO ({{ count($accounts) }})
                </div>

                @foreach($accounts as $acc)
                    <div class="card">
                        <div class="card-header">
                            {{ $acc['product_name'] }}
                            @if(!empty($acc['variant_name']))
                                <span style="font-size: 12px; color: #94a3b8; font-weight: normal;">({{ $acc['variant_name'] }})</span>
                            @endif
                        </div>

                        <div class="credential-box">
                            <div class="cred-row">
                                <span class="cred-label">Tài khoản:</span>
                                <span class="cred-val">{{ $acc['username'] }}</span>
                            </div>
                            <div class="cred-row">
                                <span class="cred-label">Mật khẩu:</span>
                                <span class="cred-val pwd">{{ $acc['password'] }}</span>
                            </div>
                            @if(!empty($acc['extra_info']) && is_array($acc['extra_info']))
                                @foreach($acc['extra_info'] as $info)
                                    <div class="cred-row" style="margin-top: 4px;">
                                        <span class="cred-label">{{ $info['label'] }}:</span>
                                        <span style="color: #f1f5f9; font-weight: 600;">{{ $info['value'] }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @if(!empty($acc['warranty_policy']))
                            <div style="margin-top: 10px; font-size: 12px; color: #10b981;">
                                🛡️ <strong>Chính sách:</strong> {{ $acc['warranty_policy'] }}
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="notice-box">
                    ⚠️ <strong>Lưu ý bảo mật quan trọng:</strong>
                    <ul style="margin: 6px 0 0; padding-left: 18px;">
                        <li>Vui lòng đăng nhập và <strong>đổi mật khẩu ngay</strong> để bảo vệ tài sản của bạn.</li>
                        <li>Cài đặt số điện thoại / email cá nhân để đảm bảo an toàn tuyệt đối.</li>
                        <li>Shop hỗ trợ bảo hành 1 đổi 1 hoặc hoàn tiền nếu tài khoản gặp lỗi trong thời hạn cam kết.</li>
                    </ul>
                </div>
            @endif

            <!-- DỊCH VỤ CÀY THUÊ (NẾU CÓ) -->
            @if(!empty($services) && count($services) > 0)
                <div class="section-title" style="color: #fbbf24;">
                    ⚡ DỊCH VỤ CÀY THUÊ ĐÃ ĐĂNG KÝ ({{ count($services) }})
                </div>

                @foreach($services as $srv)
                    <div class="card" style="border-left: 3px solid #fbbf24;">
                        <div class="card-header" style="color: #fef08a;">
                            {{ $srv['product_name'] }} - {{ $srv['variant_name'] }}
                        </div>

                        @if(!empty($srv['request_code']))
                            <div class="service-row">
                                <span class="service-label">Mã yêu cầu:</span>
                                <span class="service-val" style="color: #38bdf8;">{{ $srv['request_code'] }}</span>
                            </div>
                        @endif
                        @if(!empty($srv['ingame_name']))
                            <div class="service-row">
                                <span class="service-label">Tên nhân vật:</span>
                                <span class="service-val">{{ $srv['ingame_name'] }}</span>
                            </div>
                        @endif
                        @if(!empty($srv['current_rank']))
                            <div class="service-row">
                                <span class="service-label">Rank hiện tại:</span>
                                <span class="service-val">{{ $srv['current_rank'] }}</span>
                            </div>
                        @endif
                        @if(!empty($srv['target_rank']))
                            <div class="service-row">
                                <span class="service-label">Mục tiêu cày:</span>
                                <span class="service-val" style="color: #facc15;">{{ $srv['target_rank'] }}</span>
                            </div>
                        @endif
                        @if(!empty($srv['target_hero']))
                            <div class="service-row">
                                <span class="service-label">Tướng yêu cầu:</span>
                                <span class="service-val">{{ $srv['target_hero'] }}</span>
                            </div>
                        @endif
                        @if(!empty($srv['customer_note']))
                            <div class="service-row">
                                <span class="service-label">Ghi chú của bạn:</span>
                                <span class="service-val" style="color: #cbd5e1; font-style: italic;">"{{ $srv['customer_note'] }}"</span>
                            </div>
                        @endif

                        <div style="margin-top: 10px; font-size: 12px; color: #38bdf8;">
                            🔄 <strong>Trạng thái:</strong> Đang chờ phân công kỹ thuật viên tiến hành cày nick.
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- ACTION BUTTON -->
            <div class="btn-wrapper">
                <a href="{{ $orderUrl }}" class="btn" target="_blank">XEM CHI TIẾT ĐƠN HÀNG</a>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p style="margin: 0 0 6px;">
                Cảm ơn bạn đã lựa chọn dịch vụ của chúng tôi!
            </p>
            <p style="margin: 0; color: #475569;">
                Nếu có bất kỳ thắc mắc hoặc cần hỗ trợ bảo hành, vui lòng liên hệ qua Zalo / Hotline hỗ trợ 24/7.<br>
                Email này được gửi tự động từ hệ thống web_acc.
            </p>
        </div>
    </div>
</body>
</html>
