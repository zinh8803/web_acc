import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import api from '../../services/api';

const AdminUsersPage: React.FC = () => {
  const [users, setUsers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchUsers = async () => {
    try {
      const res = await api.get('/admin/users');
      const list = res.data?.data?.data || res.data?.data || [];
      setUsers(Array.isArray(list) ? list : []);
    } catch (e) {}
    finally { setLoading(false); }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  const columns = [
    { title: 'Họ tên', dataIndex: 'full_name', key: 'full_name', render: (n: string) => <span style={{ color: '#fff', fontWeight: 600 }}>{n}</span> },
    { title: 'Email', dataIndex: 'email', key: 'email' },
    { title: 'Số điện thoại', dataIndex: 'phone', key: 'phone' },
    { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (s: string) => <Tag color={s === 'ACTIVE' ? 'success' : 'error'}>{s || 'ACTIVE'}</Tag> },
    { title: 'Ngày tham gia', dataIndex: 'created_at', key: 'created_at', render: (d: string) => new Date(d).toLocaleDateString('vi-VN') },
  ];

  return (
    <div>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        QUẢN LÝ KHÁCH HÀNG 👥
      </h1>
      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={users} rowKey="id" loading={loading} />
      </div>
    </div>
  );
};

export default AdminUsersPage;
