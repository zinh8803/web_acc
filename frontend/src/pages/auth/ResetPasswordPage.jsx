import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Form, Input, Button, message } from 'antd';
import { LockOutlined } from '@ant-design/icons';
import api from '../../services/api';
const ResetPasswordPage = () => {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const [loading, setLoading] = useState(false);
    const token = searchParams.get('token') || '';
    const email = searchParams.get('email') || '';
    const onFinish = async (values) => {
        setLoading(true);
        try {
            await api.post('/auth/reset-password', {
                token,
                email,
                password: values.password,
                password_confirmation: values.password_confirmation,
            });
            message.success('Đặt lại mật khẩu thành công! Vui lòng đăng nhập.');
            navigate('/login');
        }
        catch (e) {
            message.error(e.response?.data?.message || 'Không thể đặt lại mật khẩu.');
        }
        finally {
            setLoading(false);
        }
    };
    return (_jsx("div", { style: { maxWidth: '440px', margin: '70px auto', padding: '0 20px' }, children: _jsxs("div", { style: { background: '#121324', borderRadius: '16px', padding: '36px 32px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsx("h2", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '16px', textAlign: 'center' }, children: "\u0110\u1EB6T L\u1EA0I M\u1EACT KH\u1EA8U" }), _jsxs(Form, { layout: "vertical", onFinish: onFinish, children: [_jsx(Form.Item, { name: "password", rules: [{ required: true, min: 8, message: 'Tối thiểu 8 ký tự!' }], children: _jsx(Input.Password, { size: "large", prefix: _jsx(LockOutlined, {}), placeholder: "M\u1EADt kh\u1EA9u m\u1EDBi" }) }), _jsx(Form.Item, { name: "password_confirmation", rules: [{ required: true, message: 'Nhập lại mật khẩu!' }], children: _jsx(Input.Password, { size: "large", prefix: _jsx(LockOutlined, {}), placeholder: "X\u00E1c nh\u1EADn m\u1EADt kh\u1EA9u" }) }), _jsx(Button, { type: "primary", size: "large", block: true, htmlType: "submit", loading: loading, style: { height: '46px', fontWeight: 700 }, children: "L\u01B0u M\u1EADt Kh\u1EA9u M\u1EDBi" })] })] }) }));
};
export default ResetPasswordPage;
