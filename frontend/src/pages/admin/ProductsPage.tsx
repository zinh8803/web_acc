import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Space, message, Popconfirm } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import { Link, useNavigate } from 'react-router-dom';
import api from '../../services/api';

const ProductsPage: React.FC = () => {
  const navigate = useNavigate();
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchProducts = async () => {
    try {
      const res = await api.get('/admin/products');
      const list = res.data?.data?.data || res.data?.data || [];
      setProducts(Array.isArray(list) ? list : []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts();
  }, []);

  const handleDelete = async (id: number) => {
    try {
      await api.delete(`/admin/products/${id}`);
      message.success('Đã xóa sản phẩm thành công.');
      fetchProducts();
    } catch (e: any) {
      message.error('Không thể xóa sản phẩm.');
    }
  };

  const columns = [
    {
      title: 'Mã SKU',
      dataIndex: 'sku',
      key: 'sku',
      render: (sku: string) => <Tag color="cyan">{sku}</Tag>,
    },
    {
      title: 'Tên sản phẩm',
      dataIndex: 'name',
      key: 'name',
      render: (name: string, record: any) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
          <img
            src={record.thumbnail_url || 'https://via.placeholder.com/40'}
            alt={name}
            style={{ width: '40px', height: '40px', borderRadius: '6px', objectFit: 'cover' }}
          />
          <span style={{ color: '#fff', fontWeight: 600 }}>{name}</span>
        </div>
      ),
    },
    {
      title: 'Loại',
      dataIndex: 'type',
      key: 'type',
      render: (t: string) => (
        <Tag color={t === 'ACCOUNT' ? 'purple' : 'blue'}>
          {t === 'ACCOUNT' ? 'Acc Game' : 'Dịch Vụ'}
        </Tag>
      ),
    },
    {
      title: 'Giá bán',
      dataIndex: 'min_price',
      key: 'min_price',
      render: (p: number) => (
        <span style={{ color: '#06b6d4', fontWeight: 700, fontFamily: 'Rajdhani' }}>
          {Number(p || 0).toLocaleString('vi-VN')} đ
        </span>
      ),
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      render: (st: string) => (
        <Tag color={st === 'ACTIVE' ? 'success' : 'default'}>
          {st}
        </Tag>
      ),
    },
    {
      title: 'Thao tác',
      key: 'action',
      render: (_: any, record: any) => (
        <Space>
          <Button
            size="small"
            icon={<EditOutlined />}
            onClick={() => navigate(`/admin/products/${record.id}/edit`)}
          />
          <Popconfirm
            title="Xóa sản phẩm này?"
            onConfirm={() => handleDelete(record.id)}
            okText="Xóa"
            cancelText="Hủy"
          >
            <Button size="small" danger icon={<DeleteOutlined />} />
          </Popconfirm>
        </Space>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
        <div>
          <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
            QUẢN LÝ SẢN PHẨM & TÀI KHOẢN 🏷️
          </h1>
          <p style={{ color: '#94a3b8', fontSize: '13px', margin: 0 }}>Danh sách tất cả sản phẩm đăng bán trên hệ thống</p>
        </div>

        <Link to="/admin/products/create">
          <Button type="primary" icon={<PlusOutlined />}>
            Thêm Sản Phẩm Mới
          </Button>
        </Link>
      </div>

      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={products} rowKey="id" loading={loading} />
      </div>
    </div>
  );
};

export default ProductsPage;
