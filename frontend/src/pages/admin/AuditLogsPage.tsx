import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import { SafetyOutlined } from '@ant-design/icons';
import api from '../../services/api';

const AdminAuditLogsPage: React.FC = () => {
  const [logs, setLogs] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchLogs = async () => {
      try {
        const res = await api.get('/admin/audit-logs');
        const list = res.data?.data?.data || res.data?.data || [];
        setLogs(Array.isArray(list) ? list : []);
      } catch (e) {}
      finally { setLoading(false); }
    };
    fetchLogs();
  }, []);

  const columns = [
    { title: 'Thời gian', dataIndex: 'created_at', key: 'created_at', render: (d: string) => new Date(d).toLocaleString('vi-VN') },
    { title: 'Nhân viên thực hiện', dataIndex: 'admin_user', key: 'admin_user', render: (u: any) => u?.full_name || 'System' },
    { title: 'Hành động', dataIndex: 'action', key: 'action', render: (a: string) => <Tag color="red" icon={<SafetyOutlined />}>{a}</Tag> },
    { title: 'Đối tượng', dataIndex: 'entity_type', key: 'entity_type' },
    { title: 'IP Address', dataIndex: 'ip_address', key: 'ip_address' },
  ];

  return (
    <div>
      <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        NHẬT KÝ BẢO MẬT & AUDIT LOGS 🔍
      </h1>
      <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
        <Table columns={columns} dataSource={logs} rowKey="id" loading={loading} />
      </div>
    </div>
  );
};

export default AdminAuditLogsPage;
