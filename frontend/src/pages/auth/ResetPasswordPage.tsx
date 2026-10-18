import React, { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Form, Input, Button, message } from 'antd';
import { LockOutlined } from '@ant-design/icons';
import api from '../../services/api';

const ResetPasswordPage: React.FC = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [loading, setLoading] = useState(false);

  const token = searchParams.get('token') || '';
  const email = searchParams.get('email') || '';

  const onFinish = async (values: any) => {
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
    } catch (e: any) {
      message.error(e.response?.data?.message || 'Không thể đặt lại mật khẩu.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ maxWidth: '440px', margin: '70px auto', padding: '0 20px' }}>
      <div style={{ background: '#121324', borderRadius: '16px', padding: '36px 32px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <h2 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '16px', textAlign: 'center' }}>
          ĐẶT LẠI MẬT KHẨU
        </h2>
        <Form layout="vertical" onFinish={onFinish}>
          <Form.Item name="password" rules={[{ required: true, min: 8, message: 'Tối thiểu 8 ký tự!' }]}>
            <Input.Password size="large" prefix={<LockOutlined />} placeholder="Mật khẩu mới" />
          </Form.Item>
          <Form.Item name="password_confirmation" rules={[{ required: true, message: 'Nhập lại mật khẩu!' }]}>
            <Input.Password size="large" prefix={<LockOutlined />} placeholder="Xác nhận mật khẩu" />
          </Form.Item>
          <Button type="primary" size="large" block htmlType="submit" loading={loading} style={{ height: '46px', fontWeight: 700 }}>
            Lưu Mật Khẩu Mới
          </Button>
        </Form>
      </div>
    </div>
  );
};

export default ResetPasswordPage;
