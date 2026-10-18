import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Modal, Form, Input, InputNumber, Select, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import api from '../../services/api';

const AdminCouponsPage: React.FC = () => {
  const [coupons, setCoupons] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [form] = Form.useForm();

  const fetchCoupons = async () => {
    try {
      const res = await api.get('/admin/coupons');
      if (res.data?.data) setCoupons(res.data.data);
    } catch (e) {}
    finally { setLoading(false); }
  };

  useEffect(() => {
    fetchCoupons();
  }, []);

  const handleCreate = async (values: any) => {
    try {
      await api.post('/admin/coupons', values);
      message.success('Tạo coupon thành công!');
      setIsModalOpen(false);
      form.resetFields();
      fetchCoupons();
    } catch (e: any) {
      message.error(e.response?.data?.message || 'Không thể tạo mã giảm giá.');
    }
  };

  const columns = [
    { title: 'Mã Code', dataIndex: 'code', key: 'code', render: (c: string) => <Tag color="cyan" style={{ fontWeight: 800 }}>{c}</Tag> },
    { title: 'Tên / Mô tả', dataIndex: 'description', key: 'description' },
    { title: 'Loại giảm', dataIndex: 'discount_type', key: 'discount_type', render: (t: string) => t === 'PERCENT' ? 'Phần trăm (%)' : 'Số tiền cố định' },
    { title: 'Giá trị', dataIndex: 'discount_value', key: 'discount_value', render: (v: number, r: any) => r.discount_type === 'PERCENT' ? `${v}%` : `${Number(v).toLocaleString('vi-VN')} đ` },
    { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (s: string) => <Tag color="green">{s || 'ACTIVE'}</Tag> },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
        <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
          MÃ GIẢM GIÁ (COUPONS) 🏷️
        </h1>
        <Button type="primary" icon={<PlusOutlined />} onClick={() => setIsModalOpen(true)}>
          Tạo Mã Mới
        </Button>
      </div>

      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={coupons} rowKey="id" loading={loading} />
      </div>

      <Modal title={<span style={{ color: '#fff' }}>Thêm Mã Giảm Giá Mới</span>} open={isModalOpen} onCancel={() => setIsModalOpen(false)} footer={null}>
        <Form form={form} layout="vertical" onFinish={handleCreate}>
          <Form.Item name="code" label="Mã Code" rules={[{ required: true }]}>
            <Input placeholder="CHAOBANMOI" />
          </Form.Item>
          <Form.Item name="description" label="Mô tả">
            <Input placeholder="Giảm 10% cho khách hàng mới" />
          </Form.Item>
          <Form.Item name="discount_type" label="Loại giảm giá" initialValue="PERCENT" rules={[{ required: true }]}>
            <Select>
              <Select.Option value="PERCENT">Phần trăm (%)</Select.Option>
              <Select.Option value="FIXED_AMOUNT">Số tiền cố định (đ)</Select.Option>
            </Select>
          </Form.Item>
          <Form.Item name="discount_value" label="Giá trị giảm" rules={[{ required: true }]}>
            <InputNumber style={{ width: '100%' }} min={0} />
          </Form.Item>
          <Form.Item name="min_order_amount" label="Đơn hàng tối thiểu (đ)">
            <InputNumber style={{ width: '100%' }} min={0} />
          </Form.Item>
          <Button type="primary" block size="large" htmlType="submit">Lưu Mã Giảm Giá</Button>
        </Form>
      </Modal>
    </div>
  );
};

export default AdminCouponsPage;
