import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Space, Select, message } from 'antd';
import { EyeOutlined } from '@ant-design/icons';
import { Link } from 'react-router-dom';
import api from '../../services/api';

const AdminOrdersPage: React.FC = () => {
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchOrders = async () => {
    try {
      const res = await api.get('/admin/orders');
      const list = res.data?.data?.data || res.data?.data || [];
      setOrders(Array.isArray(list) ? list : []);
    } catch (e) {}
    finally { setLoading(false); }
  };

  useEffect(() => {
    fetchOrders();
  }, []);

  const handleUpdateStatus = async (id: number, status: string) => {
    try {
      await api.patch(`/admin/orders/${id}/status`, { status });
      message.success('Đã cập nhật trạng thái đơn hàng!');
      fetchOrders();
    } catch (e) {
      message.error('Không thể cập nhật trạng thái.');
    }
  };

  const columns = [
    {
      title: 'Mã đơn',
      dataIndex: 'order_code',
      key: 'order_code',
      render: (c: string) => <Tag color="cyan" style={{ fontWeight: 700 }}>{c}</Tag>,
    },
    {
      title: 'Khách hàng',
      dataIndex: 'user',
      key: 'user',
      render: (u: any) => u?.full_name || u?.email || 'Khách vãng lai',
    },
    {
      title: 'Tổng tiền',
      dataIndex: 'total_amount',
      key: 'total_amount',
      render: (amt: number) => (
        <span style={{ color: '#06b6d4', fontWeight: 800 }}>
          {Number(amt || 0).toLocaleString('vi-VN')} đ
        </span>
      ),
    },
    {
      title: 'Trạng thái',
      dataIndex: 'status',
      key: 'status',
      render: (st: string, record: any) => (
        <Select
          value={st}
          size="small"
          style={{ width: 150 }}
          onChange={(val) => handleUpdateStatus(record.id, val)}
        >
          <Select.Option value="pending_payment">Chờ thanh toán</Select.Option>
          <Select.Option value="paid">Đã thanh toán</Select.Option>
          <Select.Option value="processing">Đang xử lý</Select.Option>
          <Select.Option value="completed">Hoàn tất</Select.Option>
          <Select.Option value="cancelled">Huỷ đơn</Select.Option>
        </Select>
      ),
    },
    {
      title: 'Ngày đặt',
      dataIndex: 'created_at',
      key: 'created_at',
      render: (d: string) => new Date(d).toLocaleString('vi-VN'),
    },
  ];

  return (
    <div>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        QUẢN LÝ ĐƠN HÀNG & GIAO DỊCH 🧾
      </h1>

      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={orders} rowKey="id" loading={loading} />
      </div>
    </div>
  );
};

export default AdminOrdersPage;
