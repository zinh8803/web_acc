import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Space, Modal, Form, Input, Select, message, Descriptions } from 'antd';
import { KeyOutlined, PlusOutlined, LockOutlined, EyeOutlined } from '@ant-design/icons';
import api from '../../services/api';

const InventoryPage: React.FC = () => {
  const [accounts, setAccounts] = useState<any[]>([]);
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [viewModalData, setViewModalData] = useState<any>(null);
  const [form] = Form.useForm();

  const fetchAccounts = async () => {
    try {
      const res = await api.get('/admin/game-accounts');
      const list = res.data?.data?.data || res.data?.data || [];
      setAccounts(Array.isArray(list) ? list : []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const fetchProducts = async () => {
    try {
      const res = await api.get('/admin/products');
      const list = res.data?.data?.data || res.data?.data || [];
      setProducts(list.filter((p: any) => p.type === 'ACCOUNT'));
    } catch (e) {}
  };

  useEffect(() => {
    fetchAccounts();
    fetchProducts();
  }, []);

  const handleAddAccount = async (values: any) => {
    try {
      await api.post('/admin/game-accounts', values);
      message.success('Đã nhập tài khoản vào kho và mã hoá AES-256 thành công!');
      setIsAddModalOpen(false);
      form.resetFields();
      fetchAccounts();
    } catch (e: any) {
      message.error(e.response?.data?.message || 'Không thể thêm tài khoản.');
    }
  };

  const handleViewCredentials = async (id: number) => {
    try {
      const res = await api.get(`/admin/game-accounts/${id}`);
      if (res.data?.success) {
        setViewModalData(res.data.data);
      }
    } catch (e: any) {
      message.error('Không thể giải mã tài khoản.');
    }
  };

  const columns = [
    {
      title: 'ID',
      dataIndex: 'id',
      key: 'id',
      width: 70,
    },
    {
      title: 'Sản phẩm tương ứng',
      dataIndex: 'product',
      key: 'product',
      render: (p: any) => <span style={{ color: '#fff', fontWeight: 600 }}>{p?.name || 'Tài khoản lẻ'}</span>,
    },
    {
      title: 'Bảo mật Lưu Trữ',
      key: 'security',
      render: () => <Tag color="green"><LockOutlined /> AES-256-GCM Encrypted</Tag>,
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      render: (st: string) => {
        switch (st?.toUpperCase()) {
          case 'AVAILABLE':
            return <Tag color="cyan">SẴN SÀNG BÁN</Tag>;
          case 'RESERVED':
            return <Tag color="warning">ĐANG GIỮ CHỖ</Tag>;
          case 'SOLD':
            return <Tag color="magenta">ĐÃ BÁN</Tag>;
          default:
            return <Tag color="default">{st}</Tag>;
        }
      },
    },
    {
      title: 'Thao tác',
      key: 'action',
      render: (_: any, record: any) => (
        <Button
          size="small"
          type="primary"
          icon={<EyeOutlined />}
          onClick={() => handleViewCredentials(record.id)}
        >
          Giải Mã Xem Credentials
        </Button>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
        <div>
          <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
            KHO TÀI KHOẢN GAME (BẢO MẬT AES-256) 🔐
          </h1>
          <p style={{ color: '#94a3b8', fontSize: '13px', margin: 0 }}>
            Mọi thông tin tài khoản và mật khẩu được mã hoá bằng thuật toán AES-256-GCM chuẩn ngân hàng
          </p>
        </div>

        <Button type="primary" icon={<PlusOutlined />} onClick={() => setIsAddModalOpen(true)}>
          Nhập Kho Tài Khoản Mới
        </Button>
      </div>

      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={accounts} rowKey="id" loading={loading} />
      </div>

      {/* Add Account Modal */}
      <Modal
        title={<span style={{ color: '#fff' }}>Nhập Tài Khoản Vào Kho (Tự Động Mã Hoá)</span>}
        open={isAddModalOpen}
        onCancel={() => setIsAddModalOpen(false)}
        footer={null}
      >
        <Form form={form} layout="vertical" onFinish={handleAddAccount}>
          <Form.Item name="product_id" label="Gán vào sản phẩm" rules={[{ required: true }]}>
            <Select placeholder="Chọn sản phẩm">
              {products.map((p) => (
                <Select.Option key={p.id} value={p.id}>{p.name}</Select.Option>
              ))}
            </Select>
          </Form.Item>

          <Form.Item name="username" label="Tài khoản / Tên đăng nhập" rules={[{ required: true }]}>
            <Input placeholder="Tài khoản Garena, Riot, HoYoverse..." />
          </Form.Item>

          <Form.Item name="password" label="Mật khẩu" rules={[{ required: true }]}>
            <Input.Password placeholder="Mật khẩu đăng nhập" />
          </Form.Item>

          <Form.Item name="sec_email" label="Email bảo mật">
            <Input placeholder="email@gmail.com (nếu có)" />
          </Form.Item>

          <Form.Item name="sec_phone" label="Số điện thoại bảo mật">
            <Input placeholder="0988xxxxxx (nếu có)" />
          </Form.Item>

          <Form.Item name="additional_info" label="Thông tin bổ sung (PIN cấp 2, ghi chú)">
            <Input.TextArea rows={2} placeholder="Mã PIN, code 2FA hoặc hướng dẫn đổi pass..." />
          </Form.Item>

          <Button type="primary" block size="large" htmlType="submit" style={{ fontWeight: 700 }}>
            Lưu & Mã Hoá AES-256 Vào Kho
          </Button>
        </Form>
      </Modal>

      {/* View Decrypted Credentials Modal */}
      <Modal
        title={<span style={{ color: '#fff' }}>Thông Tin Tài Khoản Đã Giải Mã (Audit Logged)</span>}
        open={!!viewModalData}
        onCancel={() => setViewModalData(null)}
        footer={[
          <Button key="close" type="primary" onClick={() => setViewModalData(null)}>
            Đóng
          </Button>,
        ]}
      >
        {viewModalData && (
          <Descriptions column={1} bordered style={{ marginTop: '16px' }}>
            <Descriptions.Item label="Sản phẩm">{viewModalData.product?.name}</Descriptions.Item>
            <Descriptions.Item label="Tài khoản (Username)">
              <span style={{ color: '#06b6d4', fontWeight: 800, fontFamily: 'monospace', fontSize: '15px' }}>
                {viewModalData.credentials?.username}
              </span>
            </Descriptions.Item>
            <Descriptions.Item label="Mật khẩu (Password)">
              <span style={{ color: '#fbbf24', fontWeight: 800, fontFamily: 'monospace', fontSize: '15px' }}>
                {viewModalData.credentials?.password}
              </span>
            </Descriptions.Item>
            <Descriptions.Item label="Email">{viewModalData.credentials?.extra?.sec_email || 'Trắng email'}</Descriptions.Item>
            <Descriptions.Item label="Số điện thoại">{viewModalData.credentials?.extra?.sec_phone || 'Trắng SĐT'}</Descriptions.Item>
            <Descriptions.Item label="Thông tin thêm">
              {viewModalData.credentials?.extra?.additional_info || 'Không có'}
            </Descriptions.Item>
          </Descriptions>
        )}
      </Modal>
    </div>
  );
};

export default InventoryPage;
