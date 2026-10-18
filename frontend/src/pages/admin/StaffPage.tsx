import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import api from '../../services/api';

const AdminStaffPage: React.FC = () => {
  const [staff, setStaff] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchStaff = async () => {
    try {
      const res = await api.get('/admin/admin-users');
      if (res.data?.data) setStaff(res.data.data);
    } catch (e) {}
    finally { setLoading(false); }
  };

  useEffect(() => {
    fetchStaff();
  }, []);

  const columns = [
    { title: 'Tên nhân sự', dataIndex: 'full_name', key: 'full_name', render: (n: string) => <span style={{ color: '#fff', fontWeight: 600 }}>{n}</span> },
    { title: 'Email', dataIndex: 'email', key: 'email' },
    { title: 'Vai trò (Role)', dataIndex: 'role', key: 'role', render: (r: any) => <Tag color="blue">{r?.name || 'Super Admin'}</Tag> },
    { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (s: string) => <Tag color="green">{s || 'ACTIVE'}</Tag> },
  ];

  return (
    <div>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        NHÂN SỰ & PHÂN QUYỀN (RBAC) 🛡️
      </h1>
      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={staff} rowKey="id" loading={loading} />
      </div>
    </div>
  );
};

export default AdminStaffPage;
