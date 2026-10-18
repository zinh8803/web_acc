import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import api from '../../services/api';

const AdminServiceRequestsPage: React.FC = () => {
  const [services, setServices] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchServices = async () => {
    try {
      const res = await api.get('/admin/service-requests');
      const list = res.data?.data?.data || res.data?.data || [];
      setServices(Array.isArray(list) ? list : []);
    } catch (e) {}
    finally { setLoading(false); }
  };

  useEffect(() => {
    fetchServices();
  }, []);

  const columns = [
    { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
    { title: 'Khách hàng', dataIndex: 'user', key: 'user', render: (u: any) => u?.full_name || 'Khách' },
    { title: 'Loại dịch vụ', dataIndex: 'service_type', key: 'service_type', render: (st: string) => <Tag color="blue">{st}</Tag> },
    { title: 'Tiến độ', dataIndex: 'progress_percentage', key: 'progress_percentage', render: (p: number) => `${p || 0}%` },
    { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (s: string) => <Tag color="orange">{s}</Tag> },
  ];

  return (
    <div>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        YÊU CẦU DỊCH VỤ CÀY THUÊ 🎮
      </h1>
      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={services} rowKey="id" loading={loading} />
      </div>
    </div>
  );
};

export default AdminServiceRequestsPage;
