<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận tiếp nhận thông tin cày thuê</title>
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
            border-bottom: 2px solid #eab308;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #ffffff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header p {
            margin: 8px 0 0;
            color: #fde047;
            font-size: 14px;
        }
        .content {
            padding: 24px;
        }
        .badge {
            background: #1e293b;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            border-left: 4px solid #eab308;
        }
        .badge table {
            width: 100%;
            border-collapse: collapse;
        }
        .badge td {
            padding: 4px 0;
            font-size: 14px;
        }
        .badge .label {
            color: #94a3b8;
            width: 40%;
        }
        .badge .value {
            color: #f8fafc;
            font-weight: 600;
        }
        .card {
            background: #1a2234;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 16px;
        }
        .row-item {
            margin-bottom: 8px;
            font-size: 14px;
        }
        .row-label {
            color: #94a3b8;
            display: inline-block;
            width: 140px;
        }
        .row-val {
            color: #f8fafc;
            font-weight: 600;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 10px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
            color: #000000 !important;
            font-weight: 800;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SHOP ACC LIÊN QUÂN MOBILE</h1>
            <p>Xác nhận tiếp nhận thông tin cày thuê</p>
        </div>

        <div class="content">
            <p style="font-size: 15px; margin-top: 0;">
                Xin chào <strong style="color: #ffffff;">{{ $customerName }}</strong>,
            </p>
            <p style="font-size: 14px; color: #cbd5e1; line-height: 1.5;">
                Hệ thống đã nhận được thông tin tài khoản cày thuê của bạn cho yêu cầu <strong>#{{ $service->request_code }}</strong>. Thông tin đăng nhập game của bạn đã được mã hoá bảo mật an toàn.
            </p>

            <div class="badge">
                <table>
                    <tr>
                        <td class="label">Mã yêu cầu:</td>
                        <td class="value" style="color: #eab308;">{{ $service->request_code }}</td>
                    </tr>
                    @if(!empty($orderCode))
                    <tr>
                        <td class="label">Đơn hàng:</td>
                        <td class="value" style="color: #38bdf8;">#{{ $orderCode }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Thời gian gửi:</td>
                        <td class="value">{{ now()->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Trạng thái:</td>
                        <td class="value" style="color: #38bdf8;">CHỜ KỸ THUẬT TIẾP NHẬN</td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <div style="font-size: 15px; font-weight: 700; color: #ffffff; margin-bottom: 12px; border-bottom: 1px solid #334155; padding-bottom: 8px;">
                    Thông tin chi tiết gói cày:
                </div>

                @if(!empty($formData['ingame_name']))
                    <div class="row-item">
                        <span class="row-label">Tên nhân vật (Ingame):</span>
                        <span class="row-val">{{ $formData['ingame_name'] }}</span>
                    </div>
                @endif
                @if(!empty($formData['current_rank']))
                    <div class="row-item">
                        <span class="row-label">Rank hiện tại:</span>
                        <span class="row-val">{{ $formData['current_rank'] }}</span>
                    </div>
                @endif
                @if(!empty($formData['target_rank']))
                    <div class="row-item">
                        <span class="row-label">Mục tiêu cày:</span>
                        <span class="row-val" style="color: #facc15;">{{ $formData['target_rank'] }}</span>
                    </div>
                @endif
                @if(!empty($formData['target_hero']))
                    <div class="row-item">
                        <span class="row-label">Tướng yêu cầu:</span>
                        <span class="row-val">{{ $formData['target_hero'] }}</span>
                    </div>
                @endif
                @if(!empty($customerNote))
                    <div class="row-item">
                        <span class="row-label">Ghi chú:</span>
                        <span class="row-val" style="color: #cbd5e1; font-style: italic;">"{{ $customerNote }}"</span>
                    </div>
                @endif
            </div>

            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px; padding: 12px 16px; margin: 20px 0; color: #6ee7b7; font-size: 13px; line-height: 1.5;">
                🛡️ <strong>Cam kết bảo mật & tiến độ:</strong>
                <ul style="margin: 6px 0 0; padding-left: 18px;">
                    <li>Kỹ thuật viên sẽ kiểm tra và bắt đầu cày trong thời gian sớm nhất.</li>
                    <li>Tuyệt đối bảo mật thông tin tài khoản và tài sản trong game của bạn.</li>
                    <li>Vui lòng không đăng nhập vào game trong khung giờ kỹ thuật viên đang tiến hành cày.</li>
                </ul>
            </div>

            <div class="btn-wrapper">
                <a href="{{ $orderUrl }}" class="btn" target="_blank">THEO DÕI TIẾN ĐỘ ĐƠN HÀNG</a>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0;">
                Cần hỗ trợ gấp? Vui lòng liên hệ Hotline / Zalo của cửa hàng để được phục vụ 24/7.
            </p>
        </div>
    </div>
</body>
</html>
