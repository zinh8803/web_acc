import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Row, Col, Tag, Button, Spin, message, Alert, Divider } from 'antd';
import { CopyOutlined, KeyOutlined, SafetyCertificateOutlined, } from '@ant-design/icons';
import api from '../../services/api';
const OrderDetailPage = () => {
    const { code } = useParams();
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        const fetchDetail = async () => {
            try {
                const res = await api.get(`/orders/${code}`);
                if (res.data?.success) {
                    setOrder(res.data.data);
                }
            }
            catch (err) {
                message.error('Không tìm thấy thông tin đơn hàng.');
            }
            finally {
                setLoading(false);
            }
        };
        if (code)
            fetchDetail();
    }, [code]);
    const copyToClipboard = (text, label) => {
        navigator.clipboard.writeText(text);
        message.success(`Đã sao chép ${label}!`);
    };
    if (loading) {
        return (_jsx("div", { style: { textAlign: 'center', padding: '120px 0' }, children: _jsx(Spin, { size: "large" }) }));
    }
    if (!order) {
        return (_jsxs("div", { style: { textAlign: 'center', padding: '100px 20px', color: '#fff' }, children: [_jsx("h2", { children: "Kh\u00F4ng t\u00ECm th\u1EA5y \u0111\u01A1n h\u00E0ng." }), _jsx(Link, { to: "/orders", children: _jsx(Button, { type: "primary", style: { marginTop: '16px' }, children: "Quay l\u1EA1i danh s\u00E1ch" }) })] }));
    }
    const isPaid = ['completed', 'paid', 'COMPLETED', 'PAID', 'processing', 'PROCESSING'].includes(order.status);
    return (_jsxs("div", { style: { maxWidth: '1000px', margin: '0 auto', padding: '36px 20px 60px' }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }, children: [_jsxs("div", { children: [_jsx("span", { style: { color: '#94a3b8', fontSize: '13px' }, children: "Chi ti\u1EBFt \u0111\u01A1n h\u00E0ng:" }), _jsx("h1", { style: { color: '#06b6d4', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }, children: order.order_code })] }), _jsx("div", { children: isPaid ? (_jsx(Tag, { color: "success", style: { fontSize: '14px', padding: '6px 14px', fontWeight: 700 }, children: "\u2713 \u0110\u00C3 THANH TO\u00C1N & B\u00C0N GIAO" })) : (_jsx(Link, { to: `/payment/${order.order_code}`, children: _jsx(Button, { type: "primary", danger: true, style: { fontWeight: 700 }, children: "Thanh To\u00E1n Ngay" }) })) })] }), isPaid && (_jsxs("div", { style: {
                    background: 'linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(6, 182, 212, 0.1))',
                    borderRadius: '16px',
                    padding: '28px',
                    border: '2px solid rgba(16, 185, 129, 0.4)',
                    marginBottom: '32px',
                    boxShadow: '0 8px 30px rgba(16, 185, 129, 0.15)',
                }, children: [_jsxs("div", { style: { display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '16px' }, children: [_jsx(KeyOutlined, { style: { fontSize: '24px', color: '#10b981' } }), _jsx("h2", { style: { color: '#fff', fontSize: '20px', fontWeight: 800, margin: 0 }, children: "TH\u00D4NG TIN T\u00C0I KHO\u1EA2N \u0110\u00C3 B\u00C0N GIAO (M\u00C3 HO\u00C1 AES-256)" })] }), _jsx(Alert, { type: "info", showIcon: true, message: "L\u01AFU \u00DD B\u1EA2O M\u1EACT QUAN TR\u1ECCNG", description: "Vui l\u00F2ng \u0111\u0103ng nh\u1EADp v\u00E0 ti\u1EBFn h\u00E0nh \u0111\u1ED5i m\u1EADt kh\u1EA9u, c\u1EADp nh\u1EADt s\u1ED1 \u0111i\u1EC7n tho\u1EA1i / email b\u1EA3o m\u1EADt ngay l\u1EADp t\u1EE9c \u0111\u1EC3 b\u1EA3o \u0111\u1EA3m an to\u00E0n 100% cho t\u00E0i kho\u1EA3n c\u1EE7a b\u1EA1n.", style: { marginBottom: '20px', background: 'rgba(15, 23, 42, 0.7)', border: '1px solid rgba(255,255,255,0.1)' } }), _jsx("div", { style: { display: 'flex', flexDirection: 'column', gap: '16px' }, children: order.items?.map((item) => {
                            const creds = item.credentials || null;
                            if (!creds)
                                return null;
                            return (_jsxs("div", { style: {
                                    background: '#121324',
                                    borderRadius: '12px',
                                    padding: '20px',
                                    border: '1px solid rgba(255,255,255,0.08)',
                                }, children: [_jsxs("div", { style: { color: '#06b6d4', fontWeight: 700, fontSize: '15px', marginBottom: '14px' }, children: ["S\u1EA3n ph\u1EA9m: ", item.product_name || item.product?.name] }), _jsxs(Row, { gutter: [16, 16], children: [_jsx(Col, { xs: 24, sm: 12, children: _jsxs("div", { style: { background: 'rgba(255,255,255,0.03)', padding: '12px', borderRadius: '8px', border: '1px solid rgba(255,255,255,0.05)' }, children: [_jsx("div", { style: { color: '#94a3b8', fontSize: '12px', marginBottom: '4px' }, children: "T\u00EAn \u0111\u0103ng nh\u1EADp / Email:" }), _jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' }, children: [_jsx("span", { style: { color: '#fff', fontSize: '16px', fontWeight: 700, fontFamily: 'monospace' }, children: creds.username }), _jsx(Button, { size: "small", icon: _jsx(CopyOutlined, {}), onClick: () => copyToClipboard(creds.username, 'Tài khoản'), children: "Ch\u00E9p" })] })] }) }), _jsx(Col, { xs: 24, sm: 12, children: _jsxs("div", { style: { background: 'rgba(255,255,255,0.03)', padding: '12px', borderRadius: '8px', border: '1px solid rgba(255,255,255,0.05)' }, children: [_jsx("div", { style: { color: '#94a3b8', fontSize: '12px', marginBottom: '4px' }, children: "M\u1EADt kh\u1EA9u \u0111\u0103ng nh\u1EADp:" }), _jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' }, children: [_jsx("span", { style: { color: '#fbbf24', fontSize: '16px', fontWeight: 700, fontFamily: 'monospace' }, children: creds.password }), _jsx(Button, { size: "small", type: "primary", icon: _jsx(CopyOutlined, {}), onClick: () => copyToClipboard(creds.password, 'Mật khẩu'), children: "Ch\u00E9p" })] })] }) })] }), creds.additional_info && (_jsxs("div", { style: { marginTop: '12px', padding: '10px 12px', background: 'rgba(99,102,241,0.08)', borderRadius: '6px', color: '#cbd5e1', fontSize: '13px' }, children: ["\uD83D\uDCCC ", _jsx("strong", { children: "Th\u00F4ng tin b\u1ED5 sung:" }), " ", creds.additional_info] }))] }, item.id));
                        }) })] })), _jsxs("div", { style: { background: '#121324', borderRadius: '16px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsx("h3", { style: { color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }, children: "Danh S\u00E1ch M\u1EB7t H\u00E0ng Trong \u0110\u01A1n" }), _jsx("div", { style: { display: 'flex', flexDirection: 'column', gap: '14px', marginBottom: '24px' }, children: order.items?.map((item) => (_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' }, children: [_jsxs("div", { children: [_jsx("div", { style: { color: '#fff', fontSize: '15px', fontWeight: 600 }, children: item.product_name || item.product?.name }), _jsxs("div", { style: { color: '#94a3b8', fontSize: '13px' }, children: ["S\u1ED1 l\u01B0\u1EE3ng: ", item.quantity, " x ", Number(item.unit_price).toLocaleString('vi-VN'), " \u0111"] })] }), _jsxs("div", { style: { color: '#06b6d4', fontWeight: 700, fontFamily: 'Rajdhani', fontSize: '16px' }, children: [Number(item.total_price || item.line_total || 0).toLocaleString('vi-VN'), " \u0111"] })] }, item.id))) }), _jsx(Divider, { style: { borderColor: 'rgba(255,255,255,0.08)' } }), _jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#cbd5e1', marginBottom: '10px' }, children: [_jsx("span", { children: "T\u1EA1m t\u00EDnh:" }), _jsxs("span", { children: [Number(order.subtotal || 0).toLocaleString('vi-VN'), " \u0111"] })] }), Number(order.discount_amount || 0) > 0 && (_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#10b981', marginBottom: '10px' }, children: [_jsx("span", { children: "Gi\u1EA3m gi\u00E1 (Coupon):" }), _jsxs("span", { children: ["-", Number(order.discount_amount).toLocaleString('vi-VN'), " \u0111"] })] })), _jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', color: '#fff', fontSize: '18px', fontWeight: 700, marginTop: '16px' }, children: [_jsx("span", { children: "T\u1ED5ng thanh to\u00E1n:" }), _jsxs("span", { style: { color: '#06b6d4', fontSize: '26px', fontFamily: 'Rajdhani', fontWeight: 800 }, children: [Number(order.total_amount || order.total || 0).toLocaleString('vi-VN'), " \u0111"] })] }), _jsxs("div", { style: { marginTop: '24px', display: 'flex', gap: '12px' }, children: [_jsx(Link, { to: "/warranty-claims", children: _jsx(Button, { icon: _jsx(SafetyCertificateOutlined, {}), children: "Y\u00EAu C\u1EA7u B\u1EA3o H\u00E0nh" }) }), _jsx(Link, { to: "/orders", children: _jsx(Button, { type: "default", children: "Quay l\u1EA1i danh s\u00E1ch \u0111\u01A1n h\u00E0ng" }) })] })] })] }));
};
export default OrderDetailPage;
