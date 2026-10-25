import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Form, Input, Button, message } from 'antd';
import { MailOutlined } from '@ant-design/icons';
import api from '../../services/api';
const ForgotPasswordPage = () => {
    const [loading, setLoading] = useState(false);
    const [sent, setSent] = useState(false);
    const onFinish = async (values) => {
        setLoading(true);
        try {
            await api.post('/auth/forgot-password', { email: values.email });
            setSent(true);
            message.success('Đã gửi link đặt lại mật khẩu về email của bạn.');
        }
        catch (e) {
            setSent(true); // show message for security
        }
        finally {
            setLoading(false);
        }
    };
    return (_jsx("div", { style: { maxWidth: '440px', margin: '70px auto', padding: '0 20px' }, children: _jsxs("div", { style: { background: '#121324', borderRadius: '16px', padding: '36px 32px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsx("h2", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '8px', textAlign: 'center' }, children: "QU\u00CAN M\u1EACT KH\u1EA8U" }), _jsx("p", { style: { color: '#64748b', fontSize: '13px', textAlign: 'center', marginBottom: '24px' }, children: "Nh\u1EADp email \u0111\u0103ng k\u00FD t\u00E0i kho\u1EA3n \u0111\u1EC3 nh\u1EADn li\u00EAn k\u1EBFt \u0111\u1EB7t l\u1EA1i m\u1EADt kh\u1EA9u." }), sent ? (_jsxs("div", { style: { textAlign: 'center', color: '#10b981', padding: '20px 0' }, children: ["\u2713 Vui l\u00F2ng ki\u1EC3m tra h\u1ED9p th\u01B0 email c\u1EE7a b\u1EA1n \u0111\u1EC3 ti\u1EBFn h\u00E0nh \u0111\u1ED5i m\u1EADt kh\u1EA9u.", _jsx("div", { style: { marginTop: '20px' }, children: _jsx(Link, { to: "/login", children: _jsx(Button, { type: "primary", children: "Quay L\u1EA1i \u0110\u0103ng Nh\u1EADp" }) }) })] })) : (_jsxs(Form, { layout: "vertical", onFinish: onFinish, children: [_jsx(Form.Item, { name: "email", rules: [{ required: true, message: 'Vui lòng nhập email!' }], children: _jsx(Input, { size: "large", prefix: _jsx(MailOutlined, { style: { color: '#6366f1' } }), placeholder: "Email c\u1EE7a b\u1EA1n" }) }), _jsx(Button, { type: "primary", size: "large", block: true, htmlType: "submit", loading: loading, style: { height: '46px', fontWeight: 700 }, children: "G\u1EEDi Li\u00EAn K\u1EBFt" })] })), _jsx("div", { style: { textAlign: 'center', marginTop: '20px' }, children: _jsx(Link, { to: "/login", style: { color: '#64748b', fontSize: '13px' }, children: "\u2190 Quay l\u1EA1i \u0111\u0103ng nh\u1EADp" }) })] }) }));
};
export default ForgotPasswordPage;
