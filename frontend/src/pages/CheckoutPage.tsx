import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Row, Col, Radio, Input, Button, Space, message, Tag, Spin } from 'antd';
import { ThunderboltOutlined, TagOutlined } from '@ant-design/icons';
import api from '../services/api';
import { useCartStore } from '../stores/cartStore';

const CheckoutPage: React.FC = () => {
  const navigate = useNavigate();
  const { items, clearCart } = useCartStore();

  const [paymentMethods, setPaymentMethods] = useState<any[]>([]);
  const [paymentMethod, setPaymentMethod] = useState('vietqr');
  const [couponCode, setCouponCode] = useState('');
  const [discountAmount, setDiscountAmount] = useState(0);
  const [couponMessage, setCouponMessage] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [notes, setNotes] = useState('');
  const [loadingMethods, setLoadingMethods] = useState(true);

  useEffect(() => {
    const fetchMethods = async () => {
      try {
        const res = await api.get('/payment-methods');
        if (res.data?.data && Array.isArray(res.data.data) && res.data.data.length > 0) {
          setPaymentMethods(res.data.data);
          setPaymentMethod(res.data.data[0].code);
        }
      } catch (err) {
        console.error('Failed to load payment methods from DB', err);
      } finally {
        setLoadingMethods(false);
      }
    };
    fetchMethods();
  }, []);

  const subtotal = items ? items.reduce((sum, item) => sum + (item.price || 0) * item.quantity, 0) : 0;
  const totalAmount = Math.max(0, subtotal - discountAmount);

  const handleApplyCoupon = async () => {
    if (!couponCode.trim()) return;
    try {
      const res = await api.post('/coupons/validate', {
        code: couponCode.trim(),
        order_total: subtotal,
        subtotal: subtotal,
      });
      if (res.data?.success) {
        const disc = res.data.data.discount_amount || 0;
        setDiscountAmount(disc);
        setCouponMessage(`Áp dụng thành công! Giảm ${Number(disc).toLocaleString('vi-VN')} đ`);
        message.success('Mã giảm giá đã được áp dụng!');
      } else {
        message.error(res.data?.message || 'Mã giảm giá không hợp lệ.');
      }
    } catch (err: any) {
      message.error(err.response?.data?.error?.message || err.response?.data?.message || 'Mã giảm giá không tồn tại hoặc đã hết hạn.');
    }
  };

  const handlePlaceOrder = async () => {
    if (!items || items.length === 0) {
      message.error('Giỏ hàng của bạn đang trống.');
      return;
    }

    setSubmitting(true);
    try {
      const payload = {
        items: items.map((i) => ({
          product_id: i.productId || i.id,
          quantity: i.quantity,
        })),
        payment_method_code: paymentMethod,
        coupon_code: discountAmount > 0 ? couponCode : null,
        customer_notes: notes,
      };

      const res = await api.post('/orders', payload);
      if (res.data?.success) {
        const orderCode = res.data.data.order.order_code;
        clearCart();
        message.success('Đặt hàng thành công! Đang chuyển đến cổng thanh toán.');
        navigate(`/payment/${orderCode}`);
      } else {
        message.error(res.data?.message || 'Không thể tạo đơn hàng.');
      }
    } catch (err: any) {
      message.error(err.response?.data?.message || 'Lỗi khi tạo đơn hàng. Vui lòng thử lại.');
    } finally {
      setSubmitting(false);
    }
  };

  if (!items || items.length === 0) {
    navigate('/cart');
    return null;
  }

  return (
    <div style={{ maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }}>
      <h1 style={{ color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '28px' }}>
        XÁC NHẬN ĐƠN HÀNG & THANH TOÁN ⚡
      </h1>

      <Row gutter={[32, 32]}>
        <Col xs={24} lg={15}>
          <div style={{ background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)', marginBottom: '24px' }}>
            <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }}>
              1. Chọn Phương Thức Thanh Toán (Từ Database)
            </h3>

            {loadingMethods ? (
              <div style={{ textAlign: 'center', padding: '20px' }}><Spin /></div>
            ) : (
              <Radio.Group
                value={paymentMethod}
                onChange={(e) => setPaymentMethod(e.target.value)}
                style={{ width: '100%', display: 'flex', flexDirection: 'column', gap: '14px' }}
              >
                {paymentMethods.map((pm) => (
                  <div
                    key={pm.code}
                    onClick={() => setPaymentMethod(pm.code)}
                    style={{
                      padding: '16px 20px',
                      borderRadius: '10px',
                      border: paymentMethod === pm.code ? '2px solid #6366f1' : '1px solid rgba(255,255,255,0.08)',
                      background: paymentMethod === pm.code ? 'rgba(99, 102, 241, 0.08)' : 'rgba(255,255,255,0.02)',
                      cursor: 'pointer',
                      transition: 'all 0.2s',
                    }}
                  >
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
                      <Radio value={pm.code} style={{ color: '#fff', fontWeight: 700, fontSize: '15px' }}>
                        {pm.name}
                      </Radio>
                      {pm.provider && <Tag color="cyan" style={{ fontWeight: 700 }}>{pm.provider}</Tag>}
                    </div>
                    <div style={{ paddingLeft: '28px', color: '#94a3b8', fontSize: '13px' }}>
                      {pm.code === 'vietqr' ? 'Quét mã VietQR tự động xác nhận sau 3 giây. Hỗ trợ tất cả ngân hàng.' : `Thanh toán bảo mật tức thì qua cổng ${pm.name}.`}
                    </div>
                  </div>
                ))}
              </Radio.Group>
            )}
          </div>

          <div style={{ background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }}>
            <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '16px' }}>
              2. Ghi Chú Đơn Hàng (Tùy chọn)
            </h3>
            <Input.TextArea
              rows={3}
              placeholder="Nhập yêu cầu đặc biệt hoặc ghi chú cho hệ thống..."
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
            />
          </div>
        </Col>

        <Col xs={24} lg={9}>
          <div style={{ background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }}>
            <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }}>
              Chi Tiết Đơn Hàng
            </h3>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '14px', marginBottom: '24px' }}>
              {items.map((i) => (
                <div key={i.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <img
                      src={i.thumbnailUrl || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=100'}
                      alt={i.name || 'Sản phẩm'}
                      style={{ width: '42px', height: '42px', borderRadius: '6px', objectFit: 'cover' }}
                    />
                    <div>
                      <div style={{ color: '#f8fafc', fontSize: '14px', fontWeight: 600, maxWidth: '170px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                        {i.name || 'Tài khoản game'}
                      </div>
                      <div style={{ color: '#94a3b8', fontSize: '12px' }}>Số lượng: {i.quantity}</div>
                    </div>
                  </div>
                  <div style={{ color: '#06b6d4', fontWeight: 700, fontFamily: 'Rajdhani', fontSize: '15px' }}>
                    {((i.price || 0) * i.quantity).toLocaleString('vi-VN')} đ
                  </div>
                </div>
              ))}
            </div>

            <div style={{ marginBottom: '24px' }}>
              <div style={{ color: '#cbd5e1', fontSize: '13px', fontWeight: 600, marginBottom: '8px' }}>
                Mã giảm giá / Voucher:
              </div>
              <Space.Compact style={{ width: '100%' }}>
                <Input
                  placeholder="Ví dụ: CHAOBANMOI"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                  prefix={<TagOutlined style={{ color: '#6366f1' }} />}
                />
                <Button type="primary" onClick={handleApplyCoupon}>
                  Áp Dụng
                </Button>
              </Space.Compact>
              {couponMessage && (
                <div style={{ color: '#10b981', fontSize: '12px', marginTop: '6px', fontWeight: 600 }}>
                  ✓ {couponMessage}
                </div>
              )}
            </div>

            <div style={{ borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '10px' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', color: '#94a3b8', fontSize: '14px' }}>
                <span>Tạm tính:</span>
                <span>{subtotal.toLocaleString('vi-VN')} đ</span>
              </div>
              {discountAmount > 0 && (
                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#10b981', fontSize: '14px', fontWeight: 600 }}>
                  <span>Giảm giá:</span>
                  <span>-{discountAmount.toLocaleString('vi-VN')} đ</span>
                </div>
              )}
              <div style={{ display: 'flex', justifyContent: 'space-between', color: '#94a3b8', fontSize: '14px' }}>
                <span>Phí vận chuyển & bàn giao:</span>
                <span style={{ color: '#10b981' }}>0 đ (Tự động 24/7)</span>
              </div>

              <div
                style={{
                  borderTop: '1px solid rgba(255,255,255,0.08)',
                  paddingTop: '14px',
                  marginTop: '6px',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'baseline',
                }}
              >
                <span style={{ color: '#fff', fontSize: '16px', fontWeight: 700 }}>Tổng cần trả:</span>
                <span style={{ color: '#06b6d4', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                  {totalAmount.toLocaleString('vi-VN')} đ
                </span>
              </div>
            </div>

            <Button
              type="primary"
              size="large"
              block
              loading={submitting}
              icon={<ThunderboltOutlined />}
              onClick={handlePlaceOrder}
              style={{
                marginTop: '24px',
                height: '50px',
                fontSize: '16px',
                fontWeight: 800,
                borderRadius: '8px',
              }}
            >
              THANH TOÁN NGAY
            </Button>
          </div>
        </Col>
      </Row>
    </div>
  );
};

export default CheckoutPage;
