import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Form, Input, Button, message } from 'antd';
import { UserOutlined, LockOutlined, MailOutlined, PhoneOutlined } from '@ant-design/icons';
import api from '../../services/api';
import { useAuthStore } from '../../stores/authStore';

const RegisterPage: React.FC = () => {
  const navigate = useNavigate();
  const { setAuth } = useAuthStore();
  const [loading, setLoading] = useState(false);

  const onFinish = async (values: any) => {
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
        setAuth(res.data.data.user, res.data.data.token);
        message.success('Đăng ký tài khoản thành công!');
        navigate('/');
      } else {
        message.error(res.data?.message || 'Đăng ký không thành công.');
      }
    } catch (err: any) {
      message.error(err.response?.data?.message || 'Lỗi khi tạo tài khoản.');
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
          boxShadow: '0 20px 40px rgba(0, 0, 0, 0.5)',
        }}
      >
        <div style={{ textAlign: 'center', marginBottom: '28px' }}>
          <h2 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
            ĐĂNG KÝ TÀI KHOẢN MỚI
          </h2>
          <p style={{ color: '#94a3b8', fontSize: '13px', marginTop: '6px' }}>
            Nhận ngay voucher giảm giá 10% cho đơn hàng đầu tiên
          </p>
        </div>

        <Form layout="vertical" onFinish={onFinish}>
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

          <Form.Item name="phone">
            <Input
              size="large"
              prefix={<PhoneOutlined style={{ color: '#6366f1' }} />}
              placeholder="Số điện thoại (Zalo nhận thông báo)"
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

        <div style={{ textAlign: 'center', marginTop: '24px', fontSize: '13px', color: '#94a3b8' }}>
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
