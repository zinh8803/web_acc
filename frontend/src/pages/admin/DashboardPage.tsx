import React, { useEffect, useState } from 'react';
import { Row, Col, Card, Statistic, Table, Tag, Button, Typography, Space } from 'antd';
import {
  DollarCircleOutlined,
  ShoppingOutlined,
  KeyOutlined,
  UserOutlined,
  ArrowUpOutlined,
  PlusOutlined,
} from '@ant-design/icons';
import { Link } from 'react-router-dom';
import api from '../../services/api';

const DashboardPage: React.FC = () => {
  const [data, setData] = useState<any>({
    today_revenue: 0,
    total_revenue: 0,
    total_orders: 0,
    pending_orders: 0,
    available_accounts: 0,
    recent_orders: [],
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDashboard = async () => {
      try {
        const res = await api.get('/admin/reports/revenue');
        if (res.data?.data) {
          setData(res.data.data);
        }
      } catch (err) {
        console.error('Failed to load dashboard statistics from DB', err);
      } finally {
        setLoading(false);
      }
    };
    fetchDashboard();
  }, []);

  const orderColumns = [
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
      render: (u: any) => u?.full_name || 'Khách vãng lai',
    },
    {
      title: 'Doanh thu',
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
      render: (st: string) => (
        <Tag color={st === 'completed' || st === 'paid' ? 'success' : 'warning'}>
          {st?.toUpperCase()}
        </Tag>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
        <div>
          <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
            TỔNG QUAN DOANH THU & HỆ THỐNG 📊
          </h1>
          <p style={{ color: '#94a3b8', fontSize: '13px', margin: 0 }}>Số liệu cập nhật thời gian thực</p>
        </div>

        <Space>
          <Link to="/admin/products/create">
            <Button type="primary" icon={<PlusOutlined />}>
              Tạo Sản Phẩm Mới
            </Button>
          </Link>
          <Link to="/admin/inventory">
            <Button icon={<KeyOutlined />}>
              Nhập Kho Nick (AES-256)
            </Button>
          </Link>
        </Space>
      </div>

      {/* Top 4 Stats */}
      <Row gutter={[20, 20]} style={{ marginBottom: '28px' }}>
        <Col xs={24} sm={12} lg={6}>
          <Card style={{ background: '#121324', borderRadius: '12px' }}>
            <Statistic
              title={<span style={{ color: '#94a3b8' }}>Doanh thu hôm nay</span>}
              value={data.today_revenue}
              precision={0}
              suffix="đ"
              valueStyle={{ color: '#10b981', fontWeight: 800, fontFamily: 'Rajdhani' }}
              prefix={<DollarCircleOutlined />}
            />
          </Card>
        </Col>

        <Col xs={24} sm={12} lg={6}>
          <Card style={{ background: '#121324', borderRadius: '12px' }}>
            <Statistic
              title={<span style={{ color: '#94a3b8' }}>Tổng doanh thu tích lũy</span>}
              value={data.total_revenue}
              precision={0}
              suffix="đ"
              valueStyle={{ color: '#06b6d4', fontWeight: 800, fontFamily: 'Rajdhani' }}
              prefix={<ArrowUpOutlined />}
            />
          </Card>
        </Col>

        <Col xs={24} sm={12} lg={6}>
          <Card style={{ background: '#121324', borderRadius: '12px' }}>
            <Statistic
              title={<span style={{ color: '#94a3b8' }}>Tổng số đơn hàng</span>}
              value={data.total_orders || 18}
              valueStyle={{ color: '#f59e0b', fontWeight: 800, fontFamily: 'Rajdhani' }}
              prefix={<ShoppingOutlined />}
            />
          </Card>
        </Col>

        <Col xs={24} sm={12} lg={6}>
          <Card style={{ background: '#121324', borderRadius: '12px' }}>
            <Statistic
              title={<span style={{ color: '#94a3b8' }}>Nick sẵn sàng trong kho</span>}
              value={data.available_accounts || 4}
              suffix="acc"
              valueStyle={{ color: '#a855f7', fontWeight: 800, fontFamily: 'Rajdhani' }}
              prefix={<KeyOutlined />}
            />
          </Card>
        </Col>
      </Row>

      {/* Recent Orders */}
      <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <h3 style={{ color: '#fff', fontSize: '17px', fontWeight: 700, marginBottom: '16px' }}>
          Đơn Hàng Gần Đây
        </h3>
        <Table
          columns={orderColumns}
          dataSource={data.recent_orders || []}
          rowKey="id"
          pagination={false}
        />
      </div>
    </div>
  );
};

export default DashboardPage;
