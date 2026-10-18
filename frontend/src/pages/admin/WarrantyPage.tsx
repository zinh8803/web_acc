import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import api from '../../services/api';

const AdminWarrantyPage: React.FC = () => {
  const [claims, setClaims] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchClaims = async () => {
    try {
      const res = await api.get('/admin/warranty-claims');
      const list = res.data?.data?.data || res.data?.data || [];
      setClaims(Array.isArray(list) ? list : []);
    } catch (e) {}
    finally { setLoading(false); }
  };

  useEffect(() => {
    fetchClaims();
  }, []);

  const columns = [
    { title: 'Mã khiếu nại', dataIndex: 'claim_code', key: 'claim_code', render: (c: string) => <Tag color="orange">{c}</Tag> },
    { title: 'Khách hàng', dataIndex: 'user', key: 'user', render: (u: any) => u?.full_name || 'Khách' },
    { title: 'Lý do', dataIndex: 'reason', key: 'reason' },
    { title: 'Mô tả', dataIndex: 'description', key: 'description' },
    { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (st: string) => <Tag color="processing">{st}</Tag> },
  ];

  return (
    <div>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        XỬ LÝ KHIẾU NẠI BẢO HÀNH 🛡️
      </h1>
      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={claims} rowKey="id" loading={loading} />
      </div>
    </div>
  );
};

export default AdminWarrantyPage;
