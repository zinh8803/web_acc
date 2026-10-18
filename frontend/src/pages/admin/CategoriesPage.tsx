import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Space, Modal, Form, Input, Select, message } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import api from '../../services/api';

const CategoriesPage: React.FC = () => {
  const [categories, setCategories] = useState<any[]>([]);
  const [games, setGames] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [form] = Form.useForm();

  const fetchData = async () => {
    try {
      const [cRes, gRes] = await Promise.all([
        api.get('/admin/categories'),
        api.get('/games'),
      ]);
      if (cRes.data?.data) setCategories(cRes.data.data);
      if (gRes.data?.data) setGames(gRes.data.data);
    } catch (e) {}
    finally { setLoading(false); }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const handleCreate = async (values: any) => {
    try {
      await api.post('/admin/categories', values);
      message.success('Tạo danh mục thành công!');
      setIsModalOpen(false);
      form.resetFields();
      fetchData();
    } catch (e) {
      message.error('Không thể tạo danh mục.');
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await api.delete(`/admin/categories/${id}`);
      message.success('Đã xóa danh mục!');
      fetchData();
    } catch (e) {
      message.error('Không thể xóa danh mục này.');
    }
  };

  const columns = [
    { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
    { title: 'Tên danh mục', dataIndex: 'name', key: 'name', render: (t: string) => <strong style={{ color: '#fff' }}>{t}</strong> },
    {
      title: 'Loại',
      dataIndex: 'type',
      key: 'type',
      render: (t: string) => (
        <Tag color={t === 'SERVICE' ? 'magenta' : 'geekblue'}>
          {t === 'SERVICE' ? 'Dịch vụ' : 'Tài khoản'}
        </Tag>
      ),
    },
    { title: 'Trò chơi', dataIndex: 'game', key: 'game', render: (g: any) => <Tag color="purple">{g?.name || 'Chung'}</Tag> },
    { title: 'Slug', dataIndex: 'slug', key: 'slug', render: (s: string) => <span style={{ color: '#94a3b8' }}>{s}</span> },
    { title: 'Mô tả', dataIndex: 'description', key: 'description', ellipsis: true },
    {
      title: 'Hành động',
      key: 'actions',
      render: (_: any, record: any) => (
        <Space>
          <Button
            danger
            size="small"
            icon={<DeleteOutlined />}
            onClick={() => handleDelete(record.id)}
          >
            Xóa
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
        <div>
          <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
            QUẢN LÝ DANH MỤC GAME (DB) 🗂️
          </h1>
          <p style={{ color: '#94a3b8', margin: 0, fontSize: '13px' }}>
            Quản lý trực tiếp các danh mục lưu trong cơ sở dữ liệu hệ thống
          </p>
        </div>
        <Button type="primary" icon={<PlusOutlined />} onClick={() => setIsModalOpen(true)}>
          Thêm Danh Mục
        </Button>
      </div>

      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={categories} rowKey="id" loading={loading} />
      </div>

      <Modal title={<span style={{ color: '#fff' }}>Thêm Danh Mục Mới Vào DB</span>} open={isModalOpen} onCancel={() => setIsModalOpen(false)} footer={null}>
        <Form form={form} layout="vertical" onFinish={handleCreate} initialValues={{ type: 'ACCOUNT' }}>
          <Form.Item name="name" label="Tên danh mục" rules={[{ required: true, message: 'Vui lòng nhập tên' }]}>
            <Input placeholder="Ví dụ: Nick VIP Thách Đấu, Acc Khởi Đầu..." />
          </Form.Item>
          <Form.Item name="game_id" label="Trò chơi" rules={[{ required: true, message: 'Vui lòng chọn game' }]}>
            <Select placeholder="Chọn game">
              {games.map((g) => <Select.Option key={g.id} value={g.id}>{g.name}</Select.Option>)}
            </Select>
          </Form.Item>
          <Form.Item name="type" label="Loại sản phẩm">
            <Select>
              <Select.Option value="ACCOUNT">Tài khoản (Account)</Select.Option>
              <Select.Option value="SERVICE">Dịch vụ cày thuê (Service)</Select.Option>
            </Select>
          </Form.Item>
          <Form.Item name="description" label="Mô tả">
            <Input.TextArea rows={2} placeholder="Mô tả chi tiết danh mục..." />
          </Form.Item>
          <Button type="primary" block htmlType="submit">Lưu Vào Database</Button>
        </Form>
      </Modal>
    </div>
  );
};

export default CategoriesPage;
