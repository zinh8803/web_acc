import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Form, Input, Button, Divider, message, Space, Tabs } from 'antd';
import { UserOutlined, LockOutlined, ThunderboltOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import api from '../../services/api';
import { useAuthStore } from '../../stores/authStore';
const LoginPage = () => {
    const navigate = useNavigate();
    const { setAuth } = useAuthStore();
    const [loading, setLoading] = useState(false);
    const [activeTab, setActiveTab] = useState('customer');
    const [form] = Form.useForm();
    const handleLogin = async (values) => {
        setLoading(true);
        try {
            if (activeTab === 'admin') {
                // Admin Login flow
                const res = await api.post('/admin/auth/login', {
                    email: values.email,
                    password: values.password,
                });
                if (res.data?.success) {
                    const token = res.data.data.token || res.data.data.access_token;
                    localStorage.setItem('admin_token', token);
                    localStorage.setItem('admin_access_token', token);
                    localStorage.setItem('admin_info', JSON.stringify(res.data.data.admin));
                    message.success('Đăng nhập Quản Trị Viên thành công!');
                    navigate('/admin');
                }
                else {
                    message.error(res.data?.message || 'Đăng nhập admin không thành công.');
                }
            }
            else {
                // Customer Login flow
                const res = await api.post('/auth/login', {
                    email: values.email,
                    password: values.password,
                });
                if (res.data?.success) {
                    const token = res.data.data.token || res.data.data.access_token;
                    setAuth(res.data.data.user, token);
                    message.success('Đăng nhập thành công!');
                    navigate('/');
                }
                else {
                    message.error(res.data?.message || 'Đăng nhập không thành công.');
                }
            }
        }
        catch (err) {
            const errMsg = err.response?.data?.message ||
                err.response?.data?.error?.message ||
                'Email hoặc mật khẩu không chính xác.';
            message.error(errMsg);
        }
        finally {
            setLoading(false);
        }
    };
    const handleQuickFill = (email, pass, role) => {
        setActiveTab(role);
        form.setFieldsValue({ email, password: pass });
        handleLogin({ email, password: pass });
    };
    return (_jsx("div", { style: { maxWidth: '440px', margin: '50px auto', padding: '0 20px' }, children: _jsxs("div", { style: {
                background: '#121324',
                borderRadius: '16px',
                padding: '36px 32px',
                border: '1px solid rgba(255, 255, 255, 0.08)',
                boxShadow: '0 16px 36px rgba(30, 41, 59, 0.12)',
            }, children: [_jsxs("div", { style: { textAlign: 'center', marginBottom: '24px' }, children: [_jsx("div", { style: {
                                width: '50px',
                                height: '50px',
                                borderRadius: '12px',
                                background: 'linear-gradient(135deg, #6366f1, #a855f7)',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                margin: '0 auto 16px',
                                boxShadow: '0 0 20px rgba(99, 102, 241, 0.4)',
                            }, children: _jsx(ThunderboltOutlined, { style: { fontSize: '26px', color: '#fff' } }) }), _jsx("h2", { style: { color: '#fff', fontSize: '22px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }, children: "\u0110\u0102NG NH\u1EACP SHOPACCLIENQUAN" }), _jsx("p", { style: { color: '#64748b', fontSize: '13px', marginTop: '6px' }, children: "Truy c\u1EADp \u0111\u1EC3 qu\u1EA3n l\u00FD \u0111\u01A1n h\u00E0ng & nh\u1EADn t\u00E0i kho\u1EA3n t\u1EE9c th\u00EC" })] }), _jsx(Tabs, { activeKey: activeTab, onChange: (key) => setActiveTab(key), centered: true, items: [
                        {
                            key: 'customer',
                            label: (_jsxs("span", { style: { fontWeight: 600 }, children: [_jsx(UserOutlined, {}), " Kh\u00E1ch H\u00E0ng"] })),
                        },
                        {
                            key: 'admin',
                            label: (_jsxs("span", { style: { fontWeight: 600 }, children: [_jsx(SafetyCertificateOutlined, {}), " Qu\u1EA3n Tr\u1ECB Vi\u00EAn"] })),
                        },
                    ], style: { marginBottom: '16px' } }), _jsxs(Form, { form: form, layout: "vertical", onFinish: handleLogin, children: [_jsx(Form.Item, { name: "email", rules: [
                                { required: true, message: 'Vui lòng nhập email!' },
                                { type: 'email', message: 'Email không hợp lệ!' },
                            ], children: _jsx(Input, { size: "large", prefix: _jsx(UserOutlined, { style: { color: '#6366f1' } }), placeholder: activeTab === 'admin' ? 'Email quản trị viên' : 'Email khách hàng', style: { borderRadius: '8px' } }) }), _jsx(Form.Item, { name: "password", rules: [{ required: true, message: 'Vui lòng nhập mật khẩu!' }], children: _jsx(Input.Password, { size: "large", prefix: _jsx(LockOutlined, { style: { color: '#6366f1' } }), placeholder: "M\u1EADt kh\u1EA9u", style: { borderRadius: '8px' } }) }), activeTab === 'customer' && (_jsx("div", { style: { display: 'flex', justifyContent: 'flex-end', marginBottom: '20px' }, children: _jsx(Link, { to: "/forgot-password", style: { color: '#6366f1', fontSize: '13px' }, children: "Qu\u00EAn m\u1EADt kh\u1EA9u?" }) })), _jsx(Button, { type: "primary", size: "large", block: true, htmlType: "submit", loading: loading, style: {
                                height: '46px',
                                fontWeight: 700,
                                fontSize: '15px',
                                borderRadius: '8px',
                                marginTop: activeTab === 'admin' ? '12px' : '0',
                            }, children: activeTab === 'admin' ? 'Đăng Nhập Quản Trị (Admin Portal)' : 'Đăng Nhập Khách Hàng' })] }), _jsx(Divider, { style: { borderColor: '#e2e8f0', margin: '24px 0 16px' }, children: _jsx("span", { style: { color: '#64748b', fontSize: '12px' }, children: "1-CLICK \u0110\u0102NG NH\u1EACP NHANH DEMO" }) }), _jsxs(Space, { direction: "vertical", style: { width: '100%' }, children: [_jsx(Button, { block: true, onClick: () => handleQuickFill('customer@gamehub.vn', 'Customer@123456', 'customer'), style: {
                                background: 'rgba(99, 102, 241, 0.1)',
                                borderColor: '#6366f1',
                                color: '#4338ca',
                                fontSize: '13px',
                                fontWeight: 600,
                                height: '38px',
                            }, children: "\uD83D\uDC64 Kh\u00E1ch H\u00E0ng: customer@gamehub.vn" }), _jsx(Button, { block: true, onClick: () => handleQuickFill('admin@gamehub.vn', 'Admin@123456', 'admin'), style: {
                                background: 'rgba(244, 63, 94, 0.1)',
                                borderColor: '#f43f5e',
                                color: '#be123c',
                                fontSize: '13px',
                                fontWeight: 600,
                                height: '38px',
                            }, children: "\uD83D\uDEE1\uFE0F Qu\u1EA3n Tr\u1ECB Vi\u00EAn: admin@gamehub.vn" })] }), activeTab === 'customer' && (_jsxs("div", { style: { textAlign: 'center', marginTop: '24px', fontSize: '13px', color: '#64748b' }, children: ["Ch\u01B0a c\u00F3 t\u00E0i kho\u1EA3n?", ' ', _jsx(Link, { to: "/register", style: { color: '#06b6d4', fontWeight: 600 }, children: "\u0110\u0103ng k\u00FD ngay" })] }))] }) }));
};
export default LoginPage;
