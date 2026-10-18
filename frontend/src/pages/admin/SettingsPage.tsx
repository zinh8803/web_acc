import React, { useEffect, useState } from 'react';
import { Form, Input, Button, message, Spin } from 'antd';
import api from '../../services/api';

const AdminSettingsPage: React.FC = () => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const res = await api.get('/admin/settings');
        if (res.data?.data && Array.isArray(res.data.data)) {
          const general = res.data.data.find((s: any) => s.key === 'general_settings');
          if (general && general.value) {
            const val = typeof general.value === 'string' ? JSON.parse(general.value) : general.value;
            form.setFieldsValue(val);
          }
        }
      } catch (e) {
        console.error('Failed to load settings', e);
      } finally {
        setLoading(false);
      }
    };
    fetchSettings();
  }, [form]);

  const onFinish = async (values: any) => {
    try {
      await api.put('/admin/settings', {
        settings: {
          general_settings: values,
        },
      });
      message.success('Đã lưu cấu hình vào cơ sở dữ liệu thành công!');
    } catch (e: any) {
      message.error('Không thể lưu cấu hình.');
    }
  };

  return (
    <div style={{ maxWidth: '800px' }}>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        CẤU HÌNH HỆ THỐNG & THANH TOÁN (DB) ⚙️
      </h1>

      <div style={{ background: '#121324', borderRadius: '14px', padding: '32px', border: '1px solid rgba(255,255,255,0.08)' }}>
        {loading ? (
          <div style={{ textAlign: 'center', padding: '40px' }}><Spin /></div>
        ) : (
          <Form
            form={form}
            layout="vertical"
            onFinish={onFinish}
            initialValues={{
              site_name: 'SHOPACCLIENQUAN.VN - Sàn Mua Bán Acc Liên Quân Uy Tín',
              hotline: '0987.654.321',
              zalo: '0987654321',
              telegram: 'https://t.me/shopacclienquan_vn',
            }}
          >
            <Form.Item name="site_name" label="Tên website">
              <Input />
            </Form.Item>
            <Form.Item name="hotline" label="Hotline CSKH">
              <Input />
            </Form.Item>
            <Form.Item name="zalo" label="Số Zalo hỗ trợ">
              <Input />
            </Form.Item>
            <Form.Item name="telegram" label="Telegram hỗ trợ">
              <Input />
            </Form.Item>
            <Button type="primary" size="large" htmlType="submit" style={{ fontWeight: 700, marginTop: '10px' }}>
              Lưu Vào Database
            </Button>
          </Form>
        )}
      </div>
    </div>
  );
};

export default AdminSettingsPage;
