<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chào mừng bạn gia nhập hệ thống</title>
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
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #1f2937;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #0f172a 100%);
            padding: 35px 24px;
            text-align: center;
            border-bottom: 2px solid #6366f1;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #ffffff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: 800;
        }
        .header p {
            margin: 10px 0 0;
            color: #c7d2fe;
            font-size: 15px;
        }
        .content {
            padding: 28px 24px;
        }
        .welcome-card {
            background: #1e293b;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 24px;
            border-left: 4px solid #6366f1;
        }
        .welcome-card h2 {
            margin: 0 0 8px 0;
            color: #f8fafc;
            font-size: 18px;
        }
        .welcome-card p {
            margin: 0;
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
        }
        .coupon-box {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0.15) 100%);
            border: 2px dashed #818cf8;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }
        .coupon-title {
            color: #a5b4fc;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .coupon-code-badge {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 3px;
            padding: 10px 24px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace, Courier, sans-serif;
            border: 1px solid #818cf8;
        }
        .coupon-desc {
            color: #38bdf8;
            font-size: 16px;
            font-weight: 700;
            margin: 8px 0 4px 0;
        }
        .coupon-note {
            color: #94a3b8;
            font-size: 12px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #0f172a;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #1e293b;
        }
        .info-table td {
            padding: 12px 16px;
            font-size: 14px;
            border-bottom: 1px solid #1e293b;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .info-table td.label {
            color: #94a3b8;
            width: 40%;
        }
        .info-table td.value {
            color: #f1f5f9;
            font-weight: 600;
        }
        .btn-wrapper {
            text-align: center;
            margin: 30px 0 10px 0;
        }
        .btn-shop {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }
        .footer {
            background-color: #0b0f19;
            padding: 24px;
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
        <div class="header">
            <h1>CHÀO MỪNG THÀNH VIÊN MỚI! 🎉</h1>
            <p>Cảm ơn bạn đã tin tưởng và đăng ký tài khoản tại hệ thống của chúng tôi</p>
        </div>

        <div class="content">
            <div class="welcome-card">
                <h2>Xin chào, {{ $user->full_name }}! 👋</h2>
                <p>Tài khoản của bạn đã được kích hoạt thành công. Nhằm tri ân khách hàng mới gia nhập hệ thống, chúng tôi xin gửi tặng bạn một mã giảm giá đặc quyền cho đơn hàng đầu tiên!</p>
            </div>

            <div class="coupon-box">
                <div class="coupon-title">MÃ GIẢM GIÁ ĐỘC QUYỀN CỦA BẠN</div>
                <div class="coupon-code-badge">{{ $coupon->code }}</div>
                <div class="coupon-desc">{{ $coupon->description ?? 'Giảm 10% cho khách hàng mới gia nhập hệ thống' }}</div>
                <div class="coupon-note">Loại giảm: Phần trăm (%) 10.00% &bull; Giới hạn: Sử dụng 1 lần</div>
            </div>

            <table class="info-table">
                <tr>
                    <td class="label">Mã Voucher:</td>
                    <td class="value"><strong style="color: #818cf8;">{{ $coupon->code }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Mức giảm:</td>
                    <td class="value"><span style="color: #10b981;">10.00%</span> toàn bộ giỏ hàng</td>
                </tr>
                <tr>
                    <td class="label">Đối tượng:</td>
                    <td class="value">Khách hàng mới gia nhập hệ thống</td>
                </tr>
                <tr>
                    <td class="label">Số lần áp dụng:</td>
                    <td class="value">Sử dụng 1 lần duy nhất</td>
                </tr>
                <tr>
                    <td class="label">Thời hạn áp dụng:</td>
                    <td class="value">{{ $coupon->ends_at ? $coupon->ends_at->format('d/m/Y H:i') : 'Áp dụng cho đơn hàng đầu tiên' }}</td>
                </tr>
            </table>

            <div class="btn-wrapper">
                <a href="{{ $storeUrl }}" class="btn-shop">KHÁM PHÁ CỬA HÀNG VÀ DÙNG MÃ NGAY &rarr;</a>
            </div>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động đến địa chỉ <strong>{{ $user->email }}</strong> khi tài khoản được kích hoạt thành công.</p>
            <p>Nếu bạn cần hỗ trợ, vui lòng liên hệ bộ phận CSKH trực tuyến 24/7 của chúng tôi.</p>
            <p style="margin-top: 10px; color: #475569;">&copy; {{ date('Y') }} GAMEHUB.VN. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
