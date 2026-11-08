import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Form, Input, Button, message } from 'antd';
import { UserOutlined, LockOutlined, MailOutlined, PhoneOutlined } from '@ant-design/icons';
import api from '../../services/api';
import { useAuthStore } from '../../stores/authStore';

const RegisterPage = () => {
    const navigate = useNavigate();
    const { setAuth } = useAuthStore();
    const [form] = Form.useForm();
    const [otpForm] = Form.useForm();
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
            } else {
                message.error(res.data?.message || 'Đăng ký không thành công.');
            }
        } catch (err) {
            message.error(err.response?.data?.message || err.response?.data?.error?.message || 'Lỗi khi tạo tài khoản.');
        } finally {
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
        } catch (err) {
            message.error(err.response?.data?.error?.message || err.response?.data?.message || 'Mã OTP không hợp lệ.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div style={{ maxWidth: '460px', margin: '50px auto', padding: '0 20px' }}>
            <div
                style={{
                    background: '#121324',
                    borderRadius: '16px',
                    padding: '36px 32px',
                    border: '1px solid rgba(255, 255, 255, 0.08)',
                    boxShadow: '0 16px 36px rgba(30, 41, 59, 0.12)',
                }}
            >
                <div style={{ textAlign: 'center', marginBottom: '28px' }}>
                    <h2 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
                        ĐĂNG KÝ TÀI KHOẢN MỚI
                    </h2>
                    <p style={{ color: '#64748b', fontSize: '13px', marginTop: '6px' }}>
                        Nhận ngay voucher giảm giá 10% cho đơn hàng đầu tiên
                    </p>
                </div>

                {!otpSent ? (
                    <Form form={form} layout="vertical" onFinish={onFinish}>
                        <Form.Item
                            name="full_name"
                            rules={[{ required: true, message: 'Vui lòng nhập họ và tên!' }]}
                        >
                            <Input
                                size="large"
                                prefix={<UserOutlined style={{ color: '#6366f1' }} />}
                                placeholder="Họ và tên của bạn"
                            />
                        </Form.Item>

                        <Form.Item
                            name="email"
                            rules={[
                                { required: true, message: 'Vui lòng nhập email!' },
                                { type: 'email', message: 'Email không hợp lệ!' },
                            ]}
                        >
                            <Input
                                size="large"
                                prefix={<MailOutlined style={{ color: '#6366f1' }} />}
                                placeholder="Địa chỉ email"
                            />
                        </Form.Item>

                        <Form.Item
                            name="phone"
                            rules={[
                                { required: true, message: 'Vui lòng nhập số điện thoại!' },
                                { len: 10, message: 'Số điện thoại phải có đúng 10 chữ số!' },
                                { pattern: /^0[0-9]{9}$/, message: 'Số điện thoại phải gồm 10 số và bắt đầu bằng số 0!' },
                            ]}
                        >
                            <Input
                                size="large"
                                prefix={<PhoneOutlined style={{ color: '#6366f1' }} />}
                                placeholder="Số điện thoại (10 chữ số, VD: 0912345678)"
                                maxLength={10}
                                inputMode="numeric"
                                onKeyPress={(e) => {
                                    // Chặn hoàn toàn ký tự chữ và ký tự đặc biệt, chỉ cho nhập số
                                    if (!/[0-9]/.test(e.key)) {
                                        e.preventDefault();
                                    }
                                }}
                                onChange={(e) => {
                                    // Tự động loại bỏ mọi ký tự không phải số nếu paste vào
                                    const digits = e.target.value.replace(/\D/g, '').slice(0, 10);
                                    form.setFieldsValue({ phone: digits });
                                }}
                            />
                        </Form.Item>

                        <Form.Item
                            name="password"
                            rules={[
                                { required: true, message: 'Vui lòng nhập mật khẩu!' },
                                { min: 8, message: 'Mật khẩu tối thiểu 8 ký tự!' },
                            ]}
                        >
                            <Input.Password
                                size="large"
                                prefix={<LockOutlined style={{ color: '#6366f1' }} />}
                                placeholder="Mật khẩu"
                            />
                        </Form.Item>

                        <Form.Item
                            name="password_confirmation"
                            dependencies={['password']}
                            rules={[
                                { required: true, message: 'Vui lòng xác nhận mật khẩu!' },
                                ({ getFieldValue }) => ({
                                    validator(_, value) {
                                        if (!value || getFieldValue('password') === value) {
                                            return Promise.resolve();
                                        }
                                        return Promise.reject(new Error('Mật khẩu xác nhận không khớp!'));
                                    },
                                }),
                            ]}
                        >
                            <Input.Password
                                size="large"
                                prefix={<LockOutlined style={{ color: '#6366f1' }} />}
                                placeholder="Nhập lại mật khẩu"
                            />
                        </Form.Item>

                        <Button
                            type="primary"
                            size="large"
                            block
                            htmlType="submit"
                            loading={loading}
                            style={{
                                height: '46px',
                                fontWeight: 700,
                                fontSize: '15px',
                                borderRadius: '8px',
                                marginTop: '10px',
                            }}
                        >
                            Tạo Tài Khoản
                        </Button>
                    </Form>
                ) : (
                    <>
                        <p style={{ color: '#cbd5e1', textAlign: 'center' }}>
                            Nhập mã xác thực 6 số đã gửi đến <strong>{registrationEmail}</strong>.
                        </p>
                        <Form form={otpForm} layout="vertical" onFinish={onVerifyOtp}>
                            <Form.Item
                                name="otp"
                                rules={[
                                    { required: true, message: 'Vui lòng nhập mã OTP!' },
                                    { len: 6, message: 'Mã OTP gồm 6 chữ số!' },
                                    { pattern: /^\d{6}$/, message: 'Mã OTP chỉ gồm chữ số!' },
                                ]}
                            >
                                <Input
                                    size="large"
                                    inputMode="numeric"
                                    maxLength={6}
                                    placeholder="Mã OTP 6 số"
                                    onKeyPress={(e) => {
                                        if (!/[0-9]/.test(e.key)) {
                                            e.preventDefault();
                                        }
                                    }}
                                />
                            </Form.Item>
                            <Button
                                type="primary"
                                size="large"
                                block
                                htmlType="submit"
                                loading={loading}
                                style={{ height: '44px', fontWeight: 700 }}
                            >
                                Xác thực và tạo tài khoản
                            </Button>
                            <Button
                                type="link"
                                block
                                onClick={() => setOtpSent(false)}
                                style={{ marginTop: '8px' }}
                            >
                                Quay lại chỉnh thông tin đăng ký
                            </Button>
                        </Form>
                    </>
                )}

                <div style={{ textAlign: 'center', marginTop: '24px', fontSize: '13px', color: '#64748b' }}>
                    Đã có tài khoản?{' '}
                    <Link to="/login" style={{ color: '#6366f1', fontWeight: 600 }}>
                        Đăng nhập ngay
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default RegisterPage;
