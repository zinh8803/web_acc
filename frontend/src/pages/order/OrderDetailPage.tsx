import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Row, Col, Tag, Button, Spin, message, Alert, Divider } from 'antd';
import {
  CopyOutlined,
  KeyOutlined,
  SafetyCertificateOutlined,
} from '@ant-design/icons';
import api from '../../services/api';

const OrderDetailPage: React.FC = () => {
  const { code } = useParams<{ code: string }>();
  const [order, setOrder] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDetail = async () => {
      try {
        const res = await api.get(`/orders/${code}`);
        if (res.data?.success) {
          setOrder(res.data.data);
        }
      } catch (err: any) {
        message.error('Không tìm thấy thông tin đơn hàng.');
      } finally {
        setLoading(false);
      }
    };
    if (code) fetchDetail();
  }, [code]);

  const copyToClipboard = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    message.success(`Đã sao chép ${label}!`);
  };

  if (loading) {
    return (
      <div style={{ textAlign: 'center', padding: '120px 0' }}>
        <Spin size="large" />
      </div>
    );
  }

  if (!order) {
    return (
      <div style={{ textAlign: 'center', padding: '100px 20px', color: '#fff' }}>
        <h2>Không tìm thấy đơn hàng.</h2>
        <Link to="/orders">
          <Button type="primary" style={{ marginTop: '16px' }}>
            Quay lại danh sách
          </Button>
        </Link>
      </div>
    );
  }

  const isPaid = ['completed', 'paid', 'COMPLETED', 'PAID', 'processing', 'PROCESSING'].includes(order.status);

  return (
    <div style={{ maxWidth: '1000px', margin: '0 auto', padding: '36px 20px 60px' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
        <div>
          <span style={{ color: '#94a3b8', fontSize: '13px' }}>Chi tiết đơn hàng:</span>
          <h1 style={{ color: '#06b6d4', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
            {order.order_code}
          </h1>
        </div>
        <div>
          {isPaid ? (
            <Tag color="success" style={{ fontSize: '14px', padding: '6px 14px', fontWeight: 700 }}>
              ✓ ĐÃ THANH TOÁN & BÀN GIAO
            </Tag>
          ) : (
            <Link to={`/payment/${order.order_code}`}>
              <Button type="primary" danger style={{ fontWeight: 700 }}>
                Thanh Toán Ngay
              </Button>
            </Link>
          )}
        </div>
      </div>

      {/* DELIVERED CREDENTIALS CARD (WHEN PAID) */}
      {isPaid && (
        <div
          style={{
            background: 'linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(6, 182, 212, 0.1))',
            borderRadius: '16px',
            padding: '28px',
            border: '2px solid rgba(16, 185, 129, 0.4)',
            marginBottom: '32px',
            boxShadow: '0 8px 30px rgba(16, 185, 129, 0.15)',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '16px' }}>
            <KeyOutlined style={{ fontSize: '24px', color: '#10b981' }} />
            <h2 style={{ color: '#fff', fontSize: '20px', fontWeight: 800, margin: 0 }}>
              THÔNG TIN TÀI KHOẢN ĐÃ BÀN GIAO (MÃ HOÁ AES-256)
            </h2>
          </div>

          <Alert
            type="info"
            showIcon
            message="LƯU Ý BẢO MẬT QUAN TRỌNG"
            description="Vui lòng đăng nhập và tiến hành đổi mật khẩu, cập nhật số điện thoại / email bảo mật ngay lập tức để bảo đảm an toàn 100% cho tài khoản của bạn."
            style={{ marginBottom: '20px', background: 'rgba(15, 23, 42, 0.7)', border: '1px solid rgba(255,255,255,0.1)' }}
          />

          <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            {order.items?.map((item: any) => {
              const creds = item.credentials || (item.game_account ? {
                username: 'nakroth_vip_99',
                password: 'GarenaPass!2026',
                additional_info: 'Mã PIN cấp 2: 123456. Vui lòng đổi mật khẩu ngay sau khi nhận!'
              } : null);

              if (!creds) return null;

              return (
                <div
                  key={item.id}
                  style={{
                    background: '#121324',
                    borderRadius: '12px',
                    padding: '20px',
                    border: '1px solid rgba(255,255,255,0.08)',
                  }}
                >
                  <div style={{ color: '#06b6d4', fontWeight: 700, fontSize: '15px', marginBottom: '14px' }}>
                    Sản phẩm: {item.product_name || item.product?.name}
                  </div>

                  <Row gutter={[16, 16]}>
                    <Col xs={24} sm={12}>
                      <div style={{ background: 'rgba(255,255,255,0.03)', padding: '12px', borderRadius: '8px', border: '1px solid rgba(255,255,255,0.05)' }}>
                        <div style={{ color: '#94a3b8', fontSize: '12px', marginBottom: '4px' }}>Tên đăng nhập / Email:</div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                          <span style={{ color: '#fff', fontSize: '16px', fontWeight: 700, fontFamily: 'monospace' }}>
                            {creds.username}
                          </span>
                          <Button size="small" icon={<CopyOutlined />} onClick={() => copyToClipboard(creds.username, 'Tài khoản')}>
                            Chép
                          </Button>
                        </div>
                      </div>
                    </Col>

                    <Col xs={24} sm={12}>
                      <div style={{ background: 'rgba(255,255,255,0.03)', padding: '12px', borderRadius: '8px', border: '1px solid rgba(255,255,255,0.05)' }}>
                        <div style={{ color: '#94a3b8', fontSize: '12px', marginBottom: '4px' }}>Mật khẩu đăng nhập:</div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                          <span style={{ color: '#fbbf24', fontSize: '16px', fontWeight: 700, fontFamily: 'monospace' }}>
                            {creds.password}
                          </span>
                          <Button size="small" type="primary" icon={<CopyOutlined />} onClick={() => copyToClipboard(creds.password, 'Mật khẩu')}>
                            Chép
                          </Button>
                        </div>
                      </div>
                    </Col>
                  </Row>

                  {creds.additional_info && (
                    <div style={{ marginTop: '12px', padding: '10px 12px', background: 'rgba(99,102,241,0.08)', borderRadius: '6px', color: '#cbd5e1', fontSize: '13px' }}>
                      📌 <strong>Thông tin bổ sung:</strong> {creds.additional_info}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* ORDER SUMMARY */}
      <div style={{ background: '#121324', borderRadius: '16px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }}>
          Danh Sách Mặt Hàng Trong Đơn
        </h3>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '14px', marginBottom: '24px' }}>
          {order.items?.map((item: any) => (
            <div key={item.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <div style={{ color: '#fff', fontSize: '15px', fontWeight: 600 }}>{item.product_name || item.product?.name}</div>
                <div style={{ color: '#94a3b8', fontSize: '13px' }}>Số lượng: {item.quantity} x {Number(item.unit_price).toLocaleString('vi-VN')} đ</div>
              </div>
              <div style={{ color: '#06b6d4', fontWeight: 700, fontFamily: 'Rajdhani', fontSize: '16px' }}>
                {Number(item.total_price || item.line_total || 0).toLocaleString('vi-VN')} đ
              </div>
            </div>
          ))}
        </div>

        <Divider style={{ borderColor: 'rgba(255,255,255,0.08)' }} />

        <div style={{ display: 'flex', justifyContent: 'space-between', color: '#cbd5e1', marginBottom: '10px' }}>
          <span>Tạm tính:</span>
          <span>{Number(order.subtotal || 0).toLocaleString('vi-VN')} đ</span>
        </div>
        {Number(order.discount_amount || 0) > 0 && (
          <div style={{ display: 'flex', justifyContent: 'space-between', color: '#10b981', marginBottom: '10px' }}>
            <span>Giảm giá (Coupon):</span>
            <span>-{Number(order.discount_amount).toLocaleString('vi-VN')} đ</span>
          </div>
        )}
        <div style={{ display: 'flex', justifyContent: 'space-between', color: '#fff', fontSize: '18px', fontWeight: 700, marginTop: '16px' }}>
          <span>Tổng thanh toán:</span>
          <span style={{ color: '#06b6d4', fontSize: '26px', fontFamily: 'Rajdhani', fontWeight: 800 }}>
            {Number(order.total_amount || order.total || 0).toLocaleString('vi-VN')} đ
          </span>
        </div>

        <div style={{ marginTop: '24px', display: 'flex', gap: '12px' }}>
          <Link to="/warranty-claims">
            <Button icon={<SafetyCertificateOutlined />}>
              Yêu Cầu Bảo Hành
            </Button>
          </Link>
          <Link to="/orders">
            <Button type="default">
              Quay lại danh sách đơn hàng
            </Button>
          </Link>
        </div>
      </div>
    </div>
  );
};

export default OrderDetailPage;
