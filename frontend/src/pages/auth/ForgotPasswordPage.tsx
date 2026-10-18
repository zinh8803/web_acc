import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Form, Input, Button, message } from 'antd';
import { MailOutlined } from '@ant-design/icons';
import api from '../../services/api';

const ForgotPasswordPage: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);

  const onFinish = async (values: any) => {
    setLoading(true);
    try {
      await api.post('/auth/forgot-password', { email: values.email });
      setSent(true);
      message.success('Đã gửi link đặt lại mật khẩu về email của bạn.');
    } catch (e: any) {
      setSent(true); // show message for security
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ maxWidth: '440px', margin: '70px auto', padding: '0 20px' }}>
      <div style={{ background: '#121324', borderRadius: '16px', padding: '36px 32px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <h2 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '8px', textAlign: 'center' }}>
          QUÊN MẬT KHẨU
        </h2>
        <p style={{ color: '#94a3b8', fontSize: '13px', textAlign: 'center', marginBottom: '24px' }}>
          Nhập email đăng ký tài khoản để nhận liên kết đặt lại mật khẩu.
        </p>

        {sent ? (
          <div style={{ textAlign: 'center', color: '#10b981', padding: '20px 0' }}>
            ✓ Vui lòng kiểm tra hộp thư email của bạn để tiến hành đổi mật khẩu.
            <div style={{ marginTop: '20px' }}>
              <Link to="/login"><Button type="primary">Quay Lại Đăng Nhập</Button></Link>
            </div>
          </div>
        ) : (
          <Form layout="vertical" onFinish={onFinish}>
            <Form.Item name="email" rules={[{ required: true, message: 'Vui lòng nhập email!' }]}>
              <Input size="large" prefix={<MailOutlined style={{ color: '#6366f1' }} />} placeholder="Email của bạn" />
            </Form.Item>
            <Button type="primary" size="large" block htmlType="submit" loading={loading} style={{ height: '46px', fontWeight: 700 }}>
              Gửi Liên Kết
            </Button>
          </Form>
        )}

        <div style={{ textAlign: 'center', marginTop: '20px' }}>
          <Link to="/login" style={{ color: '#94a3b8', fontSize: '13px' }}>← Quay lại đăng nhập</Link>
        </div>
      </div>
    </div>
  );
};

export default ForgotPasswordPage;
