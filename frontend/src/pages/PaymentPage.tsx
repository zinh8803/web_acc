import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Row, Col, Button, Spin, Tag, message, Result } from 'antd';
import {
  CopyOutlined,
  ThunderboltOutlined,
  LoadingOutlined,
} from '@ant-design/icons';
import api from '../services/api';

const PaymentPage: React.FC = () => {
  const { orderCode } = useParams<{ orderCode: string }>();
  const navigate = useNavigate();

  const [paymentData, setPaymentData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [simulating, setSimulating] = useState(false);
  const [isPaid, setIsPaid] = useState(false);

  const fetchPaymentInfo = async () => {
    try {
      const res = await api.get(`/orders/${orderCode}/payments`);
      if (res.data?.success) {
        setPaymentData(res.data.data);
      }
    } catch (err: any) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (orderCode) {
      fetchPaymentInfo();

      // Poll status every 4 seconds
      const timer = setInterval(async () => {
        try {
          const res = await api.get(`/orders/${orderCode}`);
          if (res.data?.success) {
            const status = res.data.data.status;
            if (status === 'completed' || status === 'paid' || status === 'COMPLETED' || status === 'PAID') {
              setIsPaid(true);
              clearInterval(timer);
              message.success('Thanh toán thành công! Đang chuyển đến trang chi tiết tài khoản...');
              setTimeout(() => {
                navigate(`/orders/${orderCode}`);
              }, 1500);
            }
          }
        } catch (e) {
          // ignore
        }
      }, 4000);

      return () => clearInterval(timer);
    }
  }, [orderCode]);

  const copyToClipboard = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    message.success(`Đã sao chép ${label}!`);
  };

  const handleSimulatePayment = async () => {
    setSimulating(true);
    try {
      const res = await api.post(`/orders/${orderCode}/mock-pay`);
      if (res.data?.success) {
        setIsPaid(true);
        message.success('Thanh toán giả lập thành công! Tài khoản đã được bàn giao.');
        setTimeout(() => {
          navigate(`/orders/${orderCode}`);
        }, 1500);
      } else {
        message.error(res.data?.message || 'Không thể giả lập thanh toán.');
      }
    } catch (err: any) {
      message.error(err.response?.data?.message || 'Lỗi xử lý giả lập.');
    } finally {
      setSimulating(false);
    }
  };

  if (loading) {
    return (
      <div style={{ textAlign: 'center', padding: '120px 0' }}>
        <Spin size="large" />
      </div>
    );
  }

  if (isPaid) {
    return (
      <div style={{ maxWidth: '600px', margin: '60px auto', padding: '0 20px' }}>
        <Result
          status="success"
          title={<span style={{ color: '#fff' }}>Thanh Toán Thành Công!</span>}
          subTitle={<span style={{ color: '#94a3b8' }}>Đơn hàng {orderCode} đã hoàn tất. Thông tin đăng nhập tài khoản đã sẵn sàng.</span>}
          extra={[
            <Button type="primary" key="order" onClick={() => navigate(`/orders/${orderCode}`)}>
              Xem Tài Khoản Ngay
            </Button>,
          ]}
        />
      </div>
    );
  }

  const bankInfo = paymentData?.bank_info || {
    bank_code: 'MB',
    bank_name: 'Ngân hàng Quân Đội (MB Bank)',
    account_number: '0987654321',
    account_name: 'NGUYEN QUOC VINH',
    amount: paymentData?.amount || 0,
    content: orderCode,
    qr_url: `https://img.vietqr.io/image/MB-0987654321-compact2.png?amount=${paymentData?.amount || 0}&addInfo=${orderCode}&accountName=NGUYEN%20QUOC%20VINH`,
  };

  return (
    <div style={{ maxWidth: '900px', margin: '0 auto', padding: '36px 20px 60px' }}>
      <div style={{ textAlign: 'center', marginBottom: '32px' }}>
        <Tag color="cyan" style={{ fontSize: '13px', padding: '4px 12px', fontWeight: 600, marginBottom: '10px' }}>
          ĐƠN HÀNG: {orderCode}
        </Tag>
        <h1 style={{ color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
          CỔNG THANH TOÁN VIETQR TỰ ĐỘNG 24/7 ⚡
        </h1>
        <p style={{ color: '#94a3b8', fontSize: '14px', marginTop: '6px' }}>
          Mở ứng dụng ngân hàng và quét mã QR bên dưới để hoàn tất giao dịch tự động sau 3 giây.
        </p>
      </div>

      <Row gutter={[28, 28]}>
        {/* QR Code Column */}
        <Col xs={24} md={11}>
          <div
            style={{
              background: '#121324',
              borderRadius: '16px',
              padding: '24px',
              textAlign: 'center',
              border: '1px solid rgba(255,255,255,0.08)',
            }}
          >
            <div
              style={{
                background: '#fff',
                borderRadius: '12px',
                padding: '16px',
                display: 'inline-block',
                boxShadow: '0 8px 24px rgba(0,0,0,0.5)',
                marginBottom: '16px',
              }}
            >
              <img
                src={bankInfo.qr_url}
                alt="VietQR Payment Code"
                style={{ width: '240px', height: '240px', objectFit: 'contain', display: 'block' }}
              />
            </div>

            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', color: '#06b6d4', fontWeight: 600 }}>
              <LoadingOutlined /> Đang chờ hệ thống ngân hàng quét nhận...
            </div>
          </div>
        </Col>

        {/* Bank Details Column */}
        <Col xs={24} md={13}>
          <div
            style={{
              background: '#121324',
              borderRadius: '16px',
              padding: '28px',
              border: '1px solid rgba(255,255,255,0.08)',
              height: '100%',
              display: 'flex',
              flexDirection: 'column',
              justifyContent: 'space-between',
            }}
          >
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
              <div>
                <div style={{ color: '#94a3b8', fontSize: '12px' }}>Ngân hàng thụ hưởng</div>
                <div style={{ color: '#fff', fontSize: '15px', fontWeight: 700 }}>{bankInfo.bank_name}</div>
              </div>

              <div>
                <div style={{ color: '#94a3b8', fontSize: '12px' }}>Chủ tài khoản</div>
                <div style={{ color: '#fff', fontSize: '15px', fontWeight: 700 }}>{bankInfo.account_name}</div>
              </div>

              <div>
                <div style={{ color: '#94a3b8', fontSize: '12px' }}>Số tài khoản</div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginTop: '2px' }}>
                  <span style={{ color: '#06b6d4', fontSize: '20px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                    {bankInfo.account_number}
                  </span>
                  <Button
                    size="small"
                    icon={<CopyOutlined />}
                    onClick={() => copyToClipboard(bankInfo.account_number, 'Số tài khoản')}
                  >
                    Chép
                  </Button>
                </div>
              </div>

              <div>
                <div style={{ color: '#94a3b8', fontSize: '12px' }}>Số tiền thanh toán</div>
                <div style={{ color: '#f43f5e', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                  {Number(bankInfo.amount).toLocaleString('vi-VN')} đ
                </div>
              </div>

              <div
                style={{
                  background: 'rgba(99, 102, 241, 0.1)',
                  padding: '12px 16px',
                  borderRadius: '10px',
                  border: '1px dashed #6366f1',
                }}
              >
                <div style={{ color: '#cbd5e1', fontSize: '12px', fontWeight: 600 }}>Nội dung chuyển khoản (bắt buộc đúng):</div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '4px' }}>
                  <span style={{ color: '#fbbf24', fontSize: '18px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                    {bankInfo.content}
                  </span>
                  <Button
                    size="small"
                    type="primary"
                    icon={<CopyOutlined />}
                    onClick={() => copyToClipboard(bankInfo.content, 'Nội dung chuyển khoản')}
                  >
                    Sao Chép
                  </Button>
                </div>
              </div>
            </div>

            {/* Test Simulation Button */}
            <div style={{ marginTop: '24px', borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: '20px' }}>
              <div style={{ color: '#94a3b8', fontSize: '12px', marginBottom: '8px' }}>
                💡 Môi trường Thử Nghiệm / Đánh giá:
              </div>
              <Button
                type="primary"
                block
                loading={simulating}
                icon={<ThunderboltOutlined />}
                onClick={handleSimulatePayment}
                style={{
                  background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                  height: '44px',
                  fontWeight: 700,
                  borderRadius: '8px',
                }}
              >
                Giả Lập Thanh Toán Thành Công (Test Instant Pay)
              </Button>
            </div>
          </div>
        </Col>
      </Row>
    </div>
  );
};

export default PaymentPage;
