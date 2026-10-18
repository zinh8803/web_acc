import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Table, Tag, Button, Spin } from 'antd';
import { EyeOutlined, ShoppingOutlined } from '@ant-design/icons';
import api from '../../services/api';

const OrdersPage: React.FC = () => {
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchOrders = async () => {
      try {
        const res = await api.get('/orders');
        const list = res.data?.data?.data || res.data?.data || [];
        setOrders(Array.isArray(list) ? list : []);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };
    fetchOrders();
  }, []);

  const getStatusTag = (status: string) => {
    switch (status?.toLowerCase()) {
      case 'completed':
      case 'paid':
        return <Tag color="success">ĐÃ HOÀN TẤT / ĐÃ BÀN GIAO</Tag>;
      case 'pending_payment':
      case 'pending':
        return <Tag color="warning">CHỜ THANH TOÁN</Tag>;
      case 'processing':
        return <Tag color="processing">ĐANG XỬ LÝ</Tag>;
      case 'cancelled':
        return <Tag color="error">ĐÃ HUỶ</Tag>;
      default:
        return <Tag color="default">{status}</Tag>;
    }
  };

  const columns = [
    {
      title: 'Mã đơn hàng',
      dataIndex: 'order_code',
      key: 'order_code',
      render: (code: string) => (
        <span style={{ color: '#06b6d4', fontWeight: 800, fontFamily: 'Rajdhani', fontSize: '15px' }}>
          {code}
        </span>
      ),
    },
    {
      title: 'Thời gian đặt',
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date: string) => new Date(date).toLocaleString('vi-VN'),
    },
    {
      title: 'Tổng tiền',
      dataIndex: 'total_amount',
      key: 'total_amount',
      render: (amt: number, r: any) => (
        <span style={{ color: '#f8fafc', fontWeight: 700 }}>
          {Number(amt || r.total || 0).toLocaleString('vi-VN')} đ
        </span>
      ),
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      render: (st: string) => getStatusTag(st),
    },
    {
      title: 'Hành động',
      key: 'action',
      render: (_: any, record: any) => (
        <Link to={`/orders/${record.order_code}`}>
          <Button type="primary" size="small" icon={<EyeOutlined />}>
            Xem Chi Tiết
          </Button>
        </Link>
      ),
    },
  ];

  return (
    <div style={{ maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }}>
      <h1 style={{ color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        LỊCH SỬ ĐƠN HÀNG CỦA BẠN 📦
      </h1>

      {loading ? (
        <div style={{ textAlign: 'center', padding: '80px 0' }}><Spin size="large" /></div>
      ) : orders.length === 0 ? (
        <div style={{ background: '#121324', padding: '60px 20px', borderRadius: '12px', textAlign: 'center' }}>
          <ShoppingOutlined style={{ fontSize: '48px', color: '#6366f1', marginBottom: '16px' }} />
          <h3 style={{ color: '#fff', marginBottom: '8px' }}>Bạn chưa có đơn hàng nào</h3>
          <p style={{ color: '#94a3b8', marginBottom: '20px' }}>Hãy trải nghiệm mua sắm tài khoản game uy tín ngay!</p>
          <Link to="/catalog">
            <Button type="primary">Khám Phá Cửa Hàng</Button>
          </Link>
        </div>
      ) : (
        <div style={{ background: '#121324', borderRadius: '12px', padding: '16px', border: '1px solid rgba(255,255,255,0.08)' }}>
          <Table
            columns={columns}
            dataSource={orders}
            rowKey="id"
            pagination={{ pageSize: 10 }}
            style={{ background: 'transparent' }}
          />
        </div>
      )}
    </div>
  );
};

export default OrdersPage;
