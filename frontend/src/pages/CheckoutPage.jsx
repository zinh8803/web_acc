import React, { useEffect, useState } from 'react';
import { useNavigate, Navigate } from 'react-router-dom';
import { Row, Col, Radio, Input, Button, Space, message, Tag, Spin } from 'antd';
import { ThunderboltOutlined, TagOutlined, CheckCircleFilled, SafetyCertificateOutlined } from '@ant-design/icons';
import api from '../services/api';
import { useCartStore } from '../stores/cartStore';

const CheckoutPage = () => {
    const navigate = useNavigate();
    const { items, clearCart } = useCartStore();
    const [paymentMethods, setPaymentMethods] = useState([]);
    const [paymentMethod, setPaymentMethod] = useState('vnpay');
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
                if (res.data?.data && Array.isArray(res.data.data)) {
                    // Lọc ở frontend: Chỉ giữ lại cổng thanh toán VNPay (Xóa chuyển khoản ngân hàng & ZaloPay)
                    const vnpayMethod = res.data.data.filter(pm => pm.code === 'vnpay');
                    if (vnpayMethod.length > 0) {
                        setPaymentMethods(vnpayMethod);
                        setPaymentMethod('vnpay');
                    } else {
                        setPaymentMethods([{
                            code: 'vnpay',
                            name: 'Cổng thanh toán VNPay (ATM / QR / Visa / Master)',
                            provider: 'VNPay',
                            description: 'Thanh toán trực tuyến bảo mật, an toàn. Xử lý và nhận tài khoản / kích hoạt dịch vụ tự động ngay lập tức.',
                        }]);
                        setPaymentMethod('vnpay');
                    }
                }
            } catch (err) {
                console.error('Failed to load payment methods', err);
                setPaymentMethods([{
                    code: 'vnpay',
                    name: 'Cổng thanh toán VNPay',
                    provider: 'VNPay',
                    description: 'Thanh toán an toàn qua cổng VNPay 24/7.',
                }]);
                setPaymentMethod('vnpay');
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
        } catch (err) {
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
                    variant_id: i.variant_id,
                    game_account_id: i.game_account_id,
                    quantity: i.quantity,
                    service_data: i.service_data || null,
                })),
                payment_method_code: 'vnpay',
                coupon_code: discountAmount > 0 ? couponCode : null,
                customer_notes: notes,
            };

            // Tạo liên kết thanh toán VNPay trực tiếp (Đơn hàng được khởi tạo và kích hoạt tự động sau khi VNPay thành công)
            const res = await api.post('/payments/vnpay/create-url', payload);
            if (res.data?.success && res.data?.data?.payment_url) {
                message.loading('Đang chuyển hướng sang cổng thanh toán VNPay...', 1.5);
                window.location.assign(res.data.data.payment_url);
            } else {
                message.error(res.data?.message || 'Không thể tạo liên kết thanh toán VNPay.');
                setSubmitting(false);
            }
        } catch (err) {
            message.error(err.response?.data?.error?.message || err.response?.data?.message || 'Lỗi khi tạo liên kết thanh toán. Vui lòng thử lại.');
            setSubmitting(false);
        }
    };

    if (!items || items.length === 0) {
        return <Navigate to="/cart" replace />;
    }

    return (
        <div style={{ maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }}>
            <h1 style={{ color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '28px' }}>
                XÁC NHẬN ĐƠN HÀNG & THANH TOÁN
            </h1>
            <Row gutter={[32, 32]}>
                <Col xs={24} lg={15}>
                    <div style={{ background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)', marginBottom: '24px' }}>
                        <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }}>
                            1. Phương Thức Thanh Toán
                        </h3>
                        {loadingMethods ? (
                            <div style={{ textAlign: 'center', padding: '20px' }}>
                                <Spin />
                            </div>
                        ) : (
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
                                {paymentMethods.map((pm) => (
                                    <div
                                        key={pm.code}
                                        style={{
                                            padding: '20px',
                                            borderRadius: '12px',
                                            border: '2px solid #6366f1',
                                            background: 'rgba(99, 102, 241, 0.08)',
                                            boxShadow: '0 4px 20px rgba(99, 102, 241, 0.15)',
                                        }}
                                    >
                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                                            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                                <CheckCircleFilled style={{ color: '#6366f1', fontSize: '18px' }} />
                                                <span style={{ color: '#fff', fontWeight: 700, fontSize: '16px' }}>
                                                    {pm.name || 'Cổng thanh toán VNPay'}
                                                </span>
                                            </div>
                                            <Space>
                                                <Tag color="cyan" style={{ fontWeight: 700 }}>VNPAY</Tag>
                                                <Tag color="green" style={{ fontWeight: 700 }}>TỰ ĐỘNG 24/7</Tag>
                                            </Space>
                                        </div>
                                        <div style={{ paddingLeft: '28px', color: '#94a3b8', fontSize: '13px', lineHeight: '1.6' }}>
                                            {pm.description || 'Thanh toán an toàn qua VNPAY-QR, Thẻ ATM nội địa, Thẻ quốc tế Visa/Mastercard. Bàn giao tài khoản và kích hoạt dịch vụ tự động ngay sau khi thanh toán thành công.'}
                                        </div>
                                        <div style={{ paddingLeft: '28px', marginTop: '10px', display: 'flex', alignItems: 'center', gap: '6px', color: '#10b981', fontSize: '12px', fontWeight: 600 }}>
                                            <SafetyCertificateOutlined /> Cổng thanh toán chính thức & bảo mật
                                        </div>
                                    </div>
                                ))}
                            </div>
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
                                            src={i.thumbnailUrl}
                                            alt={i.name || 'Sản phẩm'}
                                            style={{ width: '42px', height: '42px', borderRadius: '6px', objectFit: 'cover' }}
                                        />
                                        <div>
                                            <div style={{ color: '#f8fafc', fontSize: '14px', fontWeight: 600, maxWidth: '170px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                                {i.name}
                                            </div>
                                            <div style={{ color: '#94a3b8', fontSize: '12px' }}>
                                                Số lượng: {i.quantity}
                                            </div>
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
                                    placeholder="Nhập mã giảm giá"
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
                            <div style={{
                                borderTop: '1px solid rgba(255,255,255,0.08)',
                                paddingTop: '14px',
                                marginTop: '6px',
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'baseline',
                            }}>
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
                                background: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
                            }}
                        >
                            THANH TOÁN QUA VNPAY
                        </Button>
                    </div>
                </Col>
            </Row>
        </div>
    );
};

export default CheckoutPage;
