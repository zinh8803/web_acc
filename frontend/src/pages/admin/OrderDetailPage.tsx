import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button, Tag, Spin, Descriptions } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import api from '../../services/api';

const AdminOrderDetailPage: React.FC = () => {
  const { code } = useParams<{ code: string }>();
  const [order, setOrder] = useState<any>(null);

  useEffect(() => {
    if (code) {
      api.get(`/orders/${code}`).then((res) => {
        if (res.data?.data) setOrder(res.data.data);
      });
    }
  }, [code]);

  return (
    <div style={{ maxWidth: '900px' }}>
      <Link to="/admin/orders">
        <Button icon={<ArrowLeftOutlined />} style={{ marginBottom: '16px' }}>
          Quay lại danh sách đơn hàng
        </Button>
      </Link>
      <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <h2 style={{ color: '#fff', marginBottom: '16px' }}>Chi Tiết Đơn Hàng {code}</h2>
        {order ? (
          <Descriptions column={1} bordered>
            <Descriptions.Item label="Mã đơn">{order.order_code}</Descriptions.Item>
            <Descriptions.Item label="Tổng tiền">{Number(order.total_amount || 0).toLocaleString('vi-VN')} đ</Descriptions.Item>
            <Descriptions.Item label="Trạng thái"><Tag color="cyan">{order.status}</Tag></Descriptions.Item>
            <Descriptions.Item label="Thời gian">{new Date(order.created_at).toLocaleString('vi-VN')}</Descriptions.Item>
          </Descriptions>
        ) : (
          <Spin />
        )}
      </div>
    </div>
  );
};

export default AdminOrderDetailPage;
