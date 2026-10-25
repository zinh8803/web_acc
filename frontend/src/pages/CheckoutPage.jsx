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
            }
            else {
                message.error(res.data?.message || 'Không thể tạo đơn hàng.');
            }
        }
        catch (err) {
            message.error(err.response?.data?.message || 'Lỗi khi tạo đơn hàng. Vui lòng thử lại.');
        }
        finally {
            setSubmitting(false);
        }
    };
    if (!items || items.length === 0) {
        navigate('/cart');
        return null;
    }
    return (_jsxs("div", { style: { maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }, children: [_jsx("h1", { style: { color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '28px' }, children: "X\u00C1C NH\u1EACN \u0110\u01A0N H\u00C0NG & THANH TO\u00C1N \u26A1" }), _jsxs(Row, { gutter: [32, 32], children: [_jsxs(Col, { xs: 24, lg: 15, children: [_jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)', marginBottom: '24px' }, children: [_jsx("h3", { style: { color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }, children: "1. Ch\u1ECDn Ph\u01B0\u01A1ng Th\u1EE9c Thanh To\u00E1n (T\u1EEB Database)" }), loadingMethods ? (_jsx("div", { style: { textAlign: 'center', padding: '20px' }, children: _jsx(Spin, {}) })) : (_jsx(Radio.Group, { value: paymentMethod, onChange: (e) => setPaymentMethod(e.target.value), style: { width: '100%', display: 'flex', flexDirection: 'column', gap: '14px' }, children: paymentMethods.map((pm) => (_jsxs("div", { onClick: () => setPaymentMethod(pm.code), style: {
                                                padding: '16px 20px',
                                                borderRadius: '10px',
                                                border: paymentMethod === pm.code ? '2px solid #6366f1' : '1px solid rgba(255,255,255,0.08)',
                                                background: paymentMethod === pm.code ? 'rgba(99, 102, 241, 0.08)' : 'rgba(255,255,255,0.02)',
                                                cursor: 'pointer',
                                                transition: 'all 0.2s',
                                            }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }, children: [_jsx(Radio, { value: pm.code, style: { color: '#fff', fontWeight: 700, fontSize: '15px' }, children: pm.name }), pm.provider && _jsx(Tag, { color: "cyan", style: { fontWeight: 700 }, children: pm.provider })] }), _jsx("div", { style: { paddingLeft: '28px', color: '#94a3b8', fontSize: '13px' }, children: pm.description || '' })] }, pm.code))) }))] }), _jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsx("h3", { style: { color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '16px' }, children: "2. Ghi Ch\u00FA \u0110\u01A1n H\u00E0ng (T\u00F9y ch\u1ECDn)" }), _jsx(Input.TextArea, { rows: 3, placeholder: "Nh\u1EADp y\u00EAu c\u1EA7u \u0111\u1EB7c bi\u1EC7t ho\u1EB7c ghi ch\u00FA cho h\u1EC7 th\u1ED1ng...", value: notes, onChange: (e) => setNotes(e.target.value) })] })] }), _jsx(Col, { xs: 24, lg: 9, children: _jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsx("h3", { style: { color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }, children: "Chi Ti\u1EBFt \u0110\u01A1n H\u00E0ng" }), _jsx("div", { style: { display: 'flex', flexDirection: 'column', gap: '14px', marginBottom: '24px' }, children: items.map((i) => (_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' }, children: [_jsxs("div", { style: { display: 'flex', alignItems: 'center', gap: '12px' }, children: [_jsx("img", { src: i.thumbnailUrl, alt: i.name || 'Sản phẩm', style: { width: '42px', height: '42px', borderRadius: '6px', objectFit: 'cover' } }), _jsxs("div", { children: [_jsx("div", { style: { color: '#f8fafc', fontSize: '14px', fontWeight: 600, maxWidth: '170px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }, children: i.name }), _jsxs("div", { style: { color: '#94a3b8', fontSize: '12px' }, children: ["S\u1ED1 l\u01B0\u1EE3ng: ", i.quantity] })] })] }), _jsxs("div", { style: { color: '#06b6d4', fontWeight: 700, fontFamily: 'Rajdhani', fontSize: '15px' }, children: [((i.price || 0) * i.quantity).toLocaleString('vi-VN'), " \u0111"] })] }, i.id))) }), _jsxs("div", { style: { marginBottom: '24px' }, children: [_jsx("div", { style: { color: '#cbd5e1', fontSize: '13px', fontWeight: 600, marginBottom: '8px' }, children: "M\u00E3 gi\u1EA3m gi\u00E1 / Voucher:" }), _jsxs(Space.Compact, { style: { width: '100%' }, children: [_jsx(Input, { placeholder: "Nh\u1EADp m\u00E3 gi\u1EA3m gi\u00E1", value: couponCode, onChange: (e) => setCouponCode(e.target.value.toUpperCase()), prefix: _jsx(TagOutlined, { style: { color: '#6366f1' } }) }), _jsx(Button, { type: "primary", onClick: handleApplyCoupon, children: "\u00C1p D\u1EE5ng" })] }), couponMessage && (_jsxs("div", { style: { color: '#10b981', fontSize: '12px', marginTop: '6px', fontWeight: 600 }, children: ["\u2713 ", couponMessage] }))] }), _jsxs("div", { style: { borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '10px' }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#94a3b8', fontSize: '14px' }, children: [_jsx("span", { children: "T\u1EA1m t\u00EDnh:" }), _jsxs("span", { children: [subtotal.toLocaleString('vi-VN'), " \u0111"] })] }), discountAmount > 0 && (_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#10b981', fontSize: '14px', fontWeight: 600 }, children: [_jsx("span", { children: "Gi\u1EA3m gi\u00E1:" }), _jsxs("span", { children: ["-", discountAmount.toLocaleString('vi-VN'), " \u0111"] })] })), _jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#94a3b8', fontSize: '14px' }, children: [_jsx("span", { children: "Ph\u00ED v\u1EADn chuy\u1EC3n & b\u00E0n giao:" }), _jsx("span", { style: { color: '#10b981' }, children: "0 \u0111 (T\u1EF1 \u0111\u1ED9ng 24/7)" })] }), _jsxs("div", { style: {
                                                borderTop: '1px solid rgba(255,255,255,0.08)',
                                                paddingTop: '14px',
                                                marginTop: '6px',
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                alignItems: 'baseline',
                                            }, children: [_jsx("span", { style: { color: '#fff', fontSize: '16px', fontWeight: 700 }, children: "T\u1ED5ng c\u1EA7n tr\u1EA3:" }), _jsxs("span", { style: { color: '#06b6d4', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani' }, children: [totalAmount.toLocaleString('vi-VN'), " \u0111"] })] })] }), _jsx(Button, { type: "primary", size: "large", block: true, loading: submitting, icon: _jsx(ThunderboltOutlined, {}), onClick: handlePlaceOrder, style: {
                                        marginTop: '24px',
                                        height: '50px',
                                        fontSize: '16px',
                                        fontWeight: 800,
                                        borderRadius: '8px',
                                    }, children: "THANH TO\u00C1N NGAY" })] }) })] })] }));
};
export default CheckoutPage;
