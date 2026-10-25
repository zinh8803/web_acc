import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Form, Input, Button, message } from 'antd';
import { UserOutlined, LockOutlined, MailOutlined, PhoneOutlined } from '@ant-design/icons';
import api from '../../services/api';
import { useAuthStore } from '../../stores/authStore';
const RegisterPage = () => {
    const navigate = useNavigate();
    const { setAuth } = useAuthStore();
    const [loading, setLoading] = useState(false);
    const [otpSent, setOtpSent] = useState(false);
    const [registrationEmail, setRegistrationEmail] = useState('');
    const onFinish = async (values) => {
        setLoading(true);
        try {
            const res = await api.post('/auth/register', {
                full_name: values.full_name,
                email: values.email,
                phone: values.phone,
                password: values.password,
                password_confirmation: values.password_confirmation,
            });
            if (res.data?.success) {
                setRegistrationEmail(values.email);
                setOtpSent(true);
                message.success('Mã OTP đã được gửi đến email của bạn.');
            }
            else {
                message.error(res.data?.message || 'Đăng ký không thành công.');
            }
        }
        catch (err) {
            message.error(err.response?.data?.message || 'Lỗi khi tạo tài khoản.');
        }
        finally {
            setLoading(false);
        }
    };
    const onVerifyOtp = async (values) => {
        setLoading(true);
        try {
            const res = await api.post('/auth/register/verify-otp', {
                email: registrationEmail,
                otp: values.otp,
            });
            if (res.data?.success) {
                setAuth(res.data.data.user, res.data.data.access_token || res.data.data.token);
                message.success('Xác thực email và đăng ký thành công!');
                navigate('/');
            }
        }
        catch (err) {
            message.error(err.response?.data?.error?.message || err.response?.data?.message || 'Mã OTP không hợp lệ.');
        }
        finally {
            setLoading(false);
        }
    };
    return (_jsx("div", { style: { maxWidth: '460px', margin: '50px auto', padding: '0 20px' }, children: _jsxs("div", { style: {
                background: '#121324',
                borderRadius: '16px',
                padding: '36px 32px',
                border: '1px solid rgba(255, 255, 255, 0.08)',
                boxShadow: '0 16px 36px rgba(30, 41, 59, 0.12)',
            }, children: [_jsxs("div", { style: { textAlign: 'center', marginBottom: '28px' }, children: [_jsx("h2", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }, children: "\u0110\u0102NG K\u00DD T\u00C0I KHO\u1EA2N M\u1EDAI" }), _jsx("p", { style: { color: '#64748b', fontSize: '13px', marginTop: '6px' }, children: "Nh\u1EADn ngay voucher gi\u1EA3m gi\u00E1 10% cho \u0111\u01A1n h\u00E0ng \u0111\u1EA7u ti\u00EAn" })] }), !otpSent ? _jsxs(Form, { layout: "vertical", onFinish: onFinish, children: [_jsx(Form.Item, { name: "full_name", rules: [{ required: true, message: 'Vui lòng nhập họ và tên!' }], children: _jsx(Input, { size: "large", prefix: _jsx(UserOutlined, { style: { color: '#6366f1' } }), placeholder: "H\u1ECD v\u00E0 t\u00EAn c\u1EE7a b\u1EA1n" }) }), _jsx(Form.Item, { name: "email", rules: [
                                { required: true, message: 'Vui lòng nhập email!' },
                                { type: 'email', message: 'Email không hợp lệ!' },
                            ], children: _jsx(Input, { size: "large", prefix: _jsx(MailOutlined, { style: { color: '#6366f1' } }), placeholder: "\u0110\u1ECBa ch\u1EC9 email" }) }), _jsx(Form.Item, { name: "phone", children: _jsx(Input, { size: "large", prefix: _jsx(PhoneOutlined, { style: { color: '#6366f1' } }), placeholder: "S\u1ED1 \u0111i\u1EC7n tho\u1EA1i (Zalo nh\u1EADn th\u00F4ng b\u00E1o)" }) }), _jsx(Form.Item, { name: "password", rules: [
                                { required: true, message: 'Vui lòng nhập mật khẩu!' },
                                { min: 8, message: 'Mật khẩu tối thiểu 8 ký tự!' },
                            ], children: _jsx(Input.Password, { size: "large", prefix: _jsx(LockOutlined, { style: { color: '#6366f1' } }), placeholder: "M\u1EADt kh\u1EA9u" }) }), _jsx(Form.Item, { name: "password_confirmation", dependencies: ['password'], rules: [
                                { required: true, message: 'Vui lòng xác nhận mật khẩu!' },
                                ({ getFieldValue }) => ({
                                    validator(_, value) {
                                        if (!value || getFieldValue('password') === value) {
                                            return Promise.resolve();
                                        }
                                        return Promise.reject(new Error('Mật khẩu xác nhận không khớp!'));
                                    },
                                }),
                            ], children: _jsx(Input.Password, { size: "large", prefix: _jsx(LockOutlined, { style: { color: '#6366f1' } }), placeholder: "Nh\u1EADp l\u1EA1i m\u1EADt kh\u1EA9u" }) }), _jsx(Button, { type: "primary", size: "large", block: true, htmlType: "submit", loading: loading, style: {
                                height: '46px',
                                fontWeight: 700,
                                fontSize: '15px',
                                borderRadius: '8px',
                                marginTop: '10px',
                            }, children: "T\u1EA1o T\u00E0i Kho\u1EA3n" })] }) : _jsxs(_Fragment, { children: [_jsxs("p", { style: { color: '#cbd5e1', textAlign: 'center' }, children: ["Nh\u1EADp m\u00E3 x\u00E1c th\u1EF1c 6 s\u1ED1 \u0111\u00E3 g\u1EEDi \u0111\u1EBFn ", _jsx("strong", { children: registrationEmail }), "."] }), _jsxs(Form, { layout: "vertical", onFinish: onVerifyOtp, children: [_jsx(Form.Item, { name: "otp", rules: [{ required: true, message: 'Vui lòng nhập mã OTP!' }, { len: 6, message: 'Mã OTP gồm 6 chữ số!' }, { pattern: /^\d{6}$/, message: 'Mã OTP chỉ gồm chữ số!' }], children: _jsx(Input, { size: "large", inputMode: "numeric", maxLength: 6, placeholder: "M\u00E3 OTP 6 s\u1ED1" }) }), _jsx(Button, { type: "primary", size: "large", block: true, htmlType: "submit", loading: loading, children: "X\u00E1c th\u1EF1c v\u00E0 t\u1EA1o t\u00E0i kho\u1EA3n" }), _jsx(Button, { type: "link", block: true, onClick: () => setOtpSent(false), children: "Quay l\u1EA1i ch\u1EC9nh th\u00F4ng tin \u0111\u0103ng k\u00FD" })] })] }), _jsxs("div", { style: { textAlign: 'center', marginTop: '24px', fontSize: '13px', color: '#64748b' }, children: ["\u0110\u00E3 c\u00F3 t\u00E0i kho\u1EA3n?", ' ', _jsx(Link, { to: "/login", style: { color: '#6366f1', fontWeight: 600 }, children: "\u0110\u0103ng nh\u1EADp ngay" })] })] }) }));
};
export default RegisterPage;
