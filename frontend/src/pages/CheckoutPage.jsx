import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Row, Col, Radio, Input, Button, Space, message, Tag, Spin } from 'antd';
import { ThunderboltOutlined, TagOutlined } from '@ant-design/icons';
import api from '../services/api';
import { useCartStore } from '../stores/cartStore';

const CheckoutPage = () => {
    const navigate = useNavigate();
    const { items, clearCart } = useCartStore();
    const [paymentMethods, setPaymentMethods] = useState([]);
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
            }
            catch (err) {
                console.error('Failed to load payment methods from DB', err);
            }
            finally {
                setLoadingMethods(false);
            }
        };
        fetchMethods();
    }, []);

    const subtotal = items ? items.reduce((sum, item) => sum + (item.price || 0) * item.quantity, 0) : 0;
    const totalAmount = Math.max(0, subtotal - discountAmount);

    const handleApplyCoupon = async () => {
        if (!couponCode.trim())
            return;
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
            }
            else {
                message.error(res.data?.message || 'Mã giảm giá không hợp lệ.');
            }
        }
        catch (err) {
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
                payment_method_code: paymentMethod,
                coupon_code: discountAmount > 0 ? couponCode : null,
                customer_notes: notes,
            };

            // NẾU LÀ VNPAY: Tạo URL thanh toán VNPay trực tiếp (Đơn hàng chỉ được tạo khi VNPay callback thành công)
            if (paymentMethod === 'vnpay') {
                const res = await api.post('/payments/vnpay/create-url', payload);
                if (res.data?.success && res.data?.data?.payment_url) {
                    message.loading('Đang chuyển hướng sang cổng VNPay...', 1.5);
                    window.location.assign(res.data.data.payment_url);
                    return;
                } else {
                    message.error(res.data?.message || 'Không thể tạo liên kết thanh toán VNPay.');
                    setSubmitting(false);
                    return;
                }
            }

            // CÁC PHƯƠNG THỨC KHÁC: Tạo đơn hàng trước
            const res = await api.post('/orders', payload);
            if (res.data?.success) {
                const orderCode = res.data.data.order.order_code;
                clearCart();
                message.success('Đặt hàng thành công! Đang chuyển đến cổng thanh toán.');
                navigate(`/payment/${orderCode}`);
            }
            else {
                message.error(res.data?.message || 'Không thể tạo đơn hàng.');
            }
        }
        catch (err) {
            message.error(err.response?.data?.message || 'Lỗi khi tạo đơn hàng. Vui lòng thử lại.');
        }
        finally {
            if (paymentMethod !== 'vnpay') {
                setSubmitting(false);
            }
        }
    };

    if (!items || items.length === 0) {
        navigate('/cart');
        return null;
    }

    return (_jsxs("div", { style: { maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }, children: [_jsx("h1", { style: { color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '28px' }, children: "XÁC NHẬN ĐƠN HÀNG & THANH TOÁN ⚡" }), _jsxs(Row, { gutter: [32, 32], children: [_jsxs(Col, { xs: 24, lg: 15, children: [_jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)', marginBottom: '24px' }, children: [_jsx("h3", { style: { color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }, children: "1. Chọn Phương Thức Thanh Toán" }), loadingMethods ? (_jsx("div", { style: { textAlign: 'center', padding: '20px' }, children: _jsx(Spin, {}) })) : (_jsx(Radio.Group, { value: paymentMethod, onChange: (e) => setPaymentMethod(e.target.value), style: { width: '100%', display: 'flex', flexDirection: 'column', gap: '14px' }, children: paymentMethods.map((pm) => (_jsxs("div", { onClick: () => setPaymentMethod(pm.code), style: {
                                                padding: '16px 20px',
                                                borderRadius: '10px',
                                                border: paymentMethod === pm.code ? '2px solid #6366f1' : '1px solid rgba(255,255,255,0.08)',
                                                background: paymentMethod === pm.code ? 'rgba(99, 102, 241, 0.08)' : 'rgba(255,255,255,0.02)',
                                                cursor: 'pointer',
                                                transition: 'all 0.2s',
                                            }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }, children: [_jsx(Radio, { value: pm.code, style: { color: '#fff', fontWeight: 700, fontSize: '15px' }, children: pm.name }), pm.provider && _jsx(Tag, { color: "cyan", style: { fontWeight: 700 }, children: pm.provider })] }), _jsx("div", { style: { paddingLeft: '28px', color: '#94a3b8', fontSize: '13px' }, children: pm.description || '' })] }, pm.code))) }))] }), _jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsx("h3", { style: { color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '16px' }, children: "2. Ghi Chú Đơn Hàng (Tùy chọn)" }), _jsx(Input.TextArea, { rows: 3, placeholder: "Nhập yêu cầu đặc biệt hoặc ghi chú cho hệ thống...", value: notes, onChange: (e) => setNotes(e.target.value) })] })] }), _jsx(Col, { xs: 24, lg: 9, children: _jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsx("h3", { style: { color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }, children: "Chi Tiết Đơn Hàng" }), _jsx("div", { style: { display: 'flex', flexDirection: 'column', gap: '14px', marginBottom: '24px' }, children: items.map((i) => (_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' }, children: [_jsxs("div", { style: { display: 'flex', alignItems: 'center', gap: '12px' }, children: [_jsx("img", { src: i.thumbnailUrl, alt: i.name || 'Sản phẩm', style: { width: '42px', height: '42px', borderRadius: '6px', objectFit: 'cover' } }), _jsxs("div", { children: [_jsx("div", { style: { color: '#f8fafc', fontSize: '14px', fontWeight: 600, maxWidth: '170px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }, children: i.name }), _jsxs("div", { style: { color: '#94a3b8', fontSize: '12px' }, children: ["Số lượng: ", i.quantity] })] })] }), _jsxs("div", { style: { color: '#06b6d4', fontWeight: 700, fontFamily: 'Rajdhani', fontSize: '15px' }, children: [((i.price || 0) * i.quantity).toLocaleString('vi-VN'), " đ"] })] }, i.id))) }), _jsxs("div", { style: { marginBottom: '24px' }, children: [_jsx("div", { style: { color: '#cbd5e1', fontSize: '13px', fontWeight: 600, marginBottom: '8px' }, children: "Mã giảm giá / Voucher:" }), _jsxs(Space.Compact, { style: { width: '100%' }, children: [_jsx(Input, { placeholder: "Nhập mã giảm giá", value: couponCode, onChange: (e) => setCouponCode(e.target.value.toUpperCase()), prefix: _jsx(TagOutlined, { style: { color: '#6366f1' } }) }), _jsx(Button, { type: "primary", onClick: handleApplyCoupon, children: "Áp Dụng" })] }), couponMessage && (_jsxs("div", { style: { color: '#10b981', fontSize: '12px', marginTop: '6px', fontWeight: 600 }, children: ["✓ ", couponMessage] }))] }), _jsxs("div", { style: { borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '10px' }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#94a3b8', fontSize: '14px' }, children: [_jsx("span", { children: "Tạm tính:" }), _jsxs("span", { children: [subtotal.toLocaleString('vi-VN'), " đ"] })] }), discountAmount > 0 && (_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#10b981', fontSize: '14px', fontWeight: 600 }, children: [_jsx("span", { children: "Giảm giá:" }), _jsxs("span", { children: ["-", discountAmount.toLocaleString('vi-VN'), " đ"] })] })), _jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#94a3b8', fontSize: '14px' }, children: [_jsx("span", { children: "Phí vận chuyển & bàn giao:" }), _jsx("span", { style: { color: '#10b981' }, children: "0 đ (Tự động 24/7)" })] }), _jsxs("div", { style: {
                                                borderTop: '1px solid rgba(255,255,255,0.08)',
                                                paddingTop: '14px',
                                                marginTop: '6px',
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                alignItems: 'baseline',
                                            }, children: [_jsx("span", { style: { color: '#fff', fontSize: '16px', fontWeight: 700 }, children: "Tổng cần trả:" }), _jsxs("span", { style: { color: '#06b6d4', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani' }, children: [totalAmount.toLocaleString('vi-VN'), " đ"] })] })] }), _jsx(Button, { type: "primary", size: "large", block: true, loading: submitting, icon: _jsx(ThunderboltOutlined, {}), onClick: handlePlaceOrder, style: {
                                        marginTop: '24px',
                                        height: '50px',
                                        fontSize: '16px',
                                        fontWeight: 800,
                                        borderRadius: '8px',
                                    }, children: paymentMethod === 'vnpay' ? "THANH TOÁN QUA VNPAY" : "THANH TOÁN NGAY" })] }) })] })] }));
};

export default CheckoutPage;
