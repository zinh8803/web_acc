import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Form, Input, Button, Divider, message, Space, Tabs } from 'antd';
import { UserOutlined, LockOutlined, ThunderboltOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import api from '../../services/api';
import { useAuthStore } from '../../stores/authStore';

const LoginPage: React.FC = () => {
  const navigate = useNavigate();
  const { setAuth } = useAuthStore();
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState<'customer' | 'admin'>('customer');
  const [form] = Form.useForm();

  const handleLogin = async (values: any) => {
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
        } else {
          message.error(res.data?.message || 'Đăng nhập admin không thành công.');
        }
      } else {
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
        } else {
          message.error(res.data?.message || 'Đăng nhập không thành công.');
        }
      }
    } catch (err: any) {
      const errMsg =
        err.response?.data?.message ||
        err.response?.data?.error?.message ||
        'Email hoặc mật khẩu không chính xác.';
      message.error(errMsg);
    } finally {
      setLoading(false);
    }
  };

  const handleQuickFill = (email: string, pass: string, role: 'customer' | 'admin') => {
    setActiveTab(role);
    form.setFieldsValue({ email, password: pass });
    handleLogin({ email, password: pass });
  };

  return (
    <div style={{ maxWidth: '440px', margin: '50px auto', padding: '0 20px' }}>
      <div
        style={{
          background: '#121324',
          borderRadius: '16px',
          padding: '36px 32px',
          border: '1px solid rgba(255, 255, 255, 0.08)',
          boxShadow: '0 20px 40px rgba(0, 0, 0, 0.5)',
        }}
      >
        <div style={{ textAlign: 'center', marginBottom: '24px' }}>
          <div
            style={{
              width: '50px',
              height: '50px',
              borderRadius: '12px',
              background: 'linear-gradient(135deg, #6366f1, #a855f7)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
              boxShadow: '0 0 20px rgba(99, 102, 241, 0.4)',
            }}
          >
            <ThunderboltOutlined style={{ fontSize: '26px', color: '#fff' }} />
          </div>
          <h2 style={{ color: '#fff', fontSize: '22px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
            ĐĂNG NHẬP SHOPACCLIENQUAN
          </h2>
          <p style={{ color: '#94a3b8', fontSize: '13px', marginTop: '6px' }}>
            Truy cập để quản lý đơn hàng & nhận tài khoản tức thì
          </p>
        </div>

        {/* Tab switch between Customer and Admin */}
        <Tabs
          activeKey={activeTab}
          onChange={(key) => setActiveTab(key as 'customer' | 'admin')}
          centered
          items={[
            {
              key: 'customer',
              label: (
                <span style={{ fontWeight: 600 }}>
                  <UserOutlined /> Khách Hàng
                </span>
              ),
            },
            {
              key: 'admin',
              label: (
                <span style={{ fontWeight: 600 }}>
                  <SafetyCertificateOutlined /> Quản Trị Viên
                </span>
              ),
            },
          ]}
          style={{ marginBottom: '16px' }}
        />

        <Form form={form} layout="vertical" onFinish={handleLogin}>
          <Form.Item
            name="email"
            rules={[
              { required: true, message: 'Vui lòng nhập email!' },
              { type: 'email', message: 'Email không hợp lệ!' },
            ]}
          >
            <Input
              size="large"
              prefix={<UserOutlined style={{ color: '#6366f1' }} />}
              placeholder={activeTab === 'admin' ? 'Email quản trị viên' : 'Email khách hàng'}
              style={{ borderRadius: '8px' }}
            />
          </Form.Item>

          <Form.Item
            name="password"
            rules={[{ required: true, message: 'Vui lòng nhập mật khẩu!' }]}
          >
            <Input.Password
              size="large"
              prefix={<LockOutlined style={{ color: '#6366f1' }} />}
              placeholder="Mật khẩu"
              style={{ borderRadius: '8px' }}
            />
          </Form.Item>

          {activeTab === 'customer' && (
            <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: '20px' }}>
              <Link to="/forgot-password" style={{ color: '#6366f1', fontSize: '13px' }}>
                Quên mật khẩu?
              </Link>
            </div>
          )}

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
              marginTop: activeTab === 'admin' ? '12px' : '0',
            }}
          >
            {activeTab === 'admin' ? 'Đăng Nhập Quản Trị (Admin Portal)' : 'Đăng Nhập Khách Hàng'}
          </Button>
        </Form>

        <Divider style={{ borderColor: 'rgba(255, 255, 255, 0.08)', margin: '24px 0 16px' }}>
          <span style={{ color: '#64748b', fontSize: '12px' }}>1-CLICK ĐĂNG NHẬP NHANH DEMO</span>
        </Divider>

        <Space direction="vertical" style={{ width: '100%' }}>
          <Button
            block
            onClick={() => handleQuickFill('khachhang@shopacclienquan.vn', 'KhachHang@123', 'customer')}
            style={{
              background: 'rgba(99, 102, 241, 0.1)',
              borderColor: '#6366f1',
              color: '#c7d2fe',
              fontSize: '13px',
              fontWeight: 600,
              height: '38px',
            }}
          >
            👤 Khách Hàng: khachhang@shopacclienquan.vn
          </Button>

          <Button
            block
            onClick={() => handleQuickFill('admin@shopacclienquan.vn', 'Admin@123', 'admin')}
            style={{
              background: 'rgba(244, 63, 94, 0.1)',
              borderColor: '#f43f5e',
              color: '#fecdd3',
              fontSize: '13px',
              fontWeight: 600,
              height: '38px',
            }}
          >
            🛡️ Quản Trị Viên: admin@shopacclienquan.vn
          </Button>
        </Space>

        {activeTab === 'customer' && (
          <div style={{ textAlign: 'center', marginTop: '24px', fontSize: '13px', color: '#94a3b8' }}>
            Chưa có tài khoản?{' '}
            <Link to="/register" style={{ color: '#06b6d4', fontWeight: 600 }}>
              Đăng ký ngay
            </Link>
          </div>
        )}
      </div>
    </div>
  );
};

export default LoginPage;
