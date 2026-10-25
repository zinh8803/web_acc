import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Row, Col, Button, Spin, Tag, message, Result } from 'antd';
import { CopyOutlined, LoadingOutlined } from '@ant-design/icons';
import api from '../services/api';
const PaymentPage = () => {
    const { orderCode } = useParams();
    const navigate = useNavigate();
    const [paymentData, setPaymentData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [isPaid, setIsPaid] = useState(false);
    const [loadError, setLoadError] = useState('');
    const returnResult = new URLSearchParams(window.location.search).get('vnp_result');
    const fetchPaymentInfo = async () => {
        try {
            const res = await api.get(`/orders/${orderCode}/payments`);
            if (res.data?.success) {
                setPaymentData(res.data.data);
                if (['vnpay', 'zalopay'].includes(res.data.data.method) && !returnResult) {
                    const providerPath = res.data.data.method === 'vnpay' ? 'vnpay' : 'zalopay';
                    const payment = await api.post(`/orders/${orderCode}/payments/${providerPath}`);
                    const paymentUrl = payment.data?.data?.payment_url || payment.data?.payment_url;
                    if (!paymentUrl) {
                        throw new Error(payment.data?.message || 'API không trả về payment_url.');
                    }
                    window.location.assign(paymentUrl);
                }
            }
        }
        catch (err) {
            setLoadError(err.response?.data?.message || err.message || 'Không tạo được yêu cầu thanh toán.');
        }
        finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        if (returnResult === 'success') {
            setIsPaid(true);
            setLoading(false);
            const timer = setTimeout(() => navigate(`/orders/${orderCode}`), 1500);
            return () => clearTimeout(timer);
        }
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
                }
                catch (e) {
                    // ignore
                }
            }, 4000);
            return () => clearInterval(timer);
        }
    }, [orderCode, returnResult, navigate]);
    const copyToClipboard = (text, label) => {
        navigator.clipboard.writeText(text);
        message.success(`Đã sao chép ${label}!`);
    };
    if (loading) {
        return (_jsx("div", { style: { textAlign: 'center', padding: '120px 0' }, children: _jsx(Spin, { size: "large" }) }));
    }
    if (isPaid) {
        return (_jsx("div", { style: { maxWidth: '600px', margin: '60px auto', padding: '0 20px' }, children: _jsx(Result, { status: "success", title: _jsx("span", { style: { color: '#fff' }, children: "Thanh To\u00E1n Th\u00E0nh C\u00F4ng!" }), subTitle: _jsxs("span", { style: { color: '#94a3b8' }, children: ["\u0110\u01A1n h\u00E0ng ", orderCode, " \u0111\u00E3 ho\u00E0n t\u1EA5t. Th\u00F4ng tin \u0111\u0103ng nh\u1EADp t\u00E0i kho\u1EA3n \u0111\u00E3 s\u1EB5n s\u00E0ng."] }), extra: [
                    _jsx(Button, { type: "primary", onClick: () => navigate(`/orders/${orderCode}`), children: "Xem T\u00E0i Kho\u1EA3n Ngay" }, "order"),
                ] }) }));
    }
    const bankInfo = paymentData?.bank_info;
    if (returnResult === 'fail') {
        return <Result status="error" title="Thanh toán VNPay chưa thành công" subTitle="Giao dịch chưa được xác nhận. Đơn hàng vẫn đang chờ thanh toán." extra={<Button type="primary" onClick={async () => { try { const res = await api.post(`/orders/${orderCode}/payments/vnpay`); window.location.assign(res.data.data.payment_url); } catch (error) { message.error(error.response?.data?.message || 'Không thể tạo giao dịch VNPay.'); } }}>Thử thanh toán lại</Button>} />;
    }
    if (['vnpay', 'zalopay'].includes(paymentData?.method)) {
        return <Result status="info" title={`Đang chuyển đến ${paymentData.method === 'vnpay' ? 'VNPay' : 'ZaloPay'}`} subTitle="Sau khi thanh toán, bạn sẽ được chuyển về trang đơn hàng." />;
    }
    if (!bankInfo) {
        return <Result status="warning" title="Chưa thể tạo thanh toán" subTitle={loadError || 'Không nhận được thông tin thanh toán từ máy chủ.'} extra={<Button onClick={() => navigate('/orders')}>Xem đơn hàng</Button>} />;
    }
    return (_jsxs("div", { style: { maxWidth: '900px', margin: '0 auto', padding: '36px 20px 60px' }, children: [_jsxs("div", { style: { textAlign: 'center', marginBottom: '32px' }, children: [_jsxs(Tag, { color: "cyan", style: { fontSize: '13px', padding: '4px 12px', fontWeight: 600, marginBottom: '10px' }, children: ["\u0110\u01A0N H\u00C0NG: ", orderCode] }), _jsx("h1", { style: { color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }, children: "C\u1ED4NG THANH TO\u00C1N VIETQR T\u1EF0 \u0110\u1ED8NG 24/7 \u26A1" }), _jsx("p", { style: { color: '#94a3b8', fontSize: '14px', marginTop: '6px' }, children: "M\u1EDF \u1EE9ng d\u1EE5ng ng\u00E2n h\u00E0ng v\u00E0 qu\u00E9t m\u00E3 QR b\u00EAn d\u01B0\u1EDBi \u0111\u1EC3 ho\u00E0n t\u1EA5t giao d\u1ECBch t\u1EF1 \u0111\u1ED9ng sau 3 gi\u00E2y." })] }), _jsxs(Row, { gutter: [28, 28], children: [_jsx(Col, { xs: 24, md: 11, children: _jsxs("div", { style: {
                                background: '#121324',
                                borderRadius: '16px',
                                padding: '24px',
                                textAlign: 'center',
                                border: '1px solid rgba(255,255,255,0.08)',
                            }, children: [_jsx("div", { style: {
                                        background: '#fff',
                                        borderRadius: '12px',
                                        padding: '16px',
                                        display: 'inline-block',
                                        boxShadow: '0 8px 24px rgba(0,0,0,0.5)',
                                        marginBottom: '16px',
                                    }, children: _jsx("img", { src: bankInfo.qr_url, alt: "VietQR Payment Code", style: { width: '240px', height: '240px', objectFit: 'contain', display: 'block' } }) }), _jsxs("div", { style: { display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px', color: '#06b6d4', fontWeight: 600 }, children: [_jsx(LoadingOutlined, {}), " \u0110ang ch\u1EDD h\u1EC7 th\u1ED1ng ng\u00E2n h\u00E0ng qu\u00E9t nh\u1EADn..."] })] }) }), _jsx(Col, { xs: 24, md: 13, children: _jsxs("div", { style: {
                                background: '#121324',
                                borderRadius: '16px',
                                padding: '28px',
                                border: '1px solid rgba(255,255,255,0.08)',
                                height: '100%',
                                display: 'flex',
                                flexDirection: 'column',
                                justifyContent: 'space-between',
                            }, children: [_jsxs("div", { style: { display: 'flex', flexDirection: 'column', gap: '16px' }, children: [_jsxs("div", { children: [_jsx("div", { style: { color: '#94a3b8', fontSize: '12px' }, children: "Ng\u00E2n h\u00E0ng th\u1EE5 h\u01B0\u1EDFng" }), _jsx("div", { style: { color: '#fff', fontSize: '15px', fontWeight: 700 }, children: bankInfo.bank_name })] }), _jsxs("div", { children: [_jsx("div", { style: { color: '#94a3b8', fontSize: '12px' }, children: "Ch\u1EE7 t\u00E0i kho\u1EA3n" }), _jsx("div", { style: { color: '#fff', fontSize: '15px', fontWeight: 700 }, children: bankInfo.account_name })] }), _jsxs("div", { children: [_jsx("div", { style: { color: '#94a3b8', fontSize: '12px' }, children: "S\u1ED1 t\u00E0i kho\u1EA3n" }), _jsxs("div", { style: { display: 'flex', alignItems: 'center', gap: '8px', marginTop: '2px' }, children: [_jsx("span", { style: { color: '#06b6d4', fontSize: '20px', fontWeight: 800, fontFamily: 'Rajdhani' }, children: bankInfo.account_number }), _jsx(Button, { size: "small", icon: _jsx(CopyOutlined, {}), onClick: () => copyToClipboard(bankInfo.account_number, 'Số tài khoản'), children: "Ch\u00E9p" })] })] }), _jsxs("div", { children: [_jsx("div", { style: { color: '#94a3b8', fontSize: '12px' }, children: "S\u1ED1 ti\u1EC1n thanh to\u00E1n" }), _jsxs("div", { style: { color: '#f43f5e', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani' }, children: [Number(bankInfo.amount).toLocaleString('vi-VN'), " \u0111"] })] }), _jsxs("div", { style: {
                                                background: 'rgba(99, 102, 241, 0.1)',
                                                padding: '12px 16px',
                                                borderRadius: '10px',
                                                border: '1px dashed #6366f1',
                                            }, children: [_jsx("div", { style: { color: '#cbd5e1', fontSize: '12px', fontWeight: 600 }, children: "N\u1ED9i dung chuy\u1EC3n kho\u1EA3n (b\u1EAFt bu\u1ED9c \u0111\u00FAng):" }), _jsxs("div", { style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '4px' }, children: [_jsx("span", { style: { color: '#fbbf24', fontSize: '18px', fontWeight: 800, fontFamily: 'Rajdhani' }, children: bankInfo.content }), _jsx(Button, { size: "small", type: "primary", icon: _jsx(CopyOutlined, {}), onClick: () => copyToClipboard(bankInfo.content, 'Nội dung chuyển khoản'), children: "Sao Ch\u00E9p" })] })] })] }), _jsxs("div", { style: { marginTop: '24px', borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: '20px' }, children: [_jsx("div", { style: { color: '#94a3b8', fontSize: '12px', marginBottom: '8px' }, children: "Thanh toán đang chờ ngân hàng xác nhận:" }), _jsx(Button, { type: "primary", block: true, disabled: true, style: {
                                                background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                                                height: '44px',
                                                fontWeight: 700,
                                                borderRadius: '8px',
                                            }, children: "\u0110ang ch\u1EDD x\u00E1c nh\u1EADn thanh to\u00E1n" })] })] }) })] })] }));
};
export default PaymentPage;
