import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import { SafetyOutlined } from '@ant-design/icons';
import api from '../../services/api';
const AdminAuditLogsPage = () => {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        const fetchLogs = async () => {
            try {
                const res = await api.get('/admin/audit-logs');
                const list = res.data?.data?.data || res.data?.data || [];
                setLogs(Array.isArray(list) ? list : []);
            }
            catch (e) { }
            finally {
                setLoading(false);
            }
        };
        fetchLogs();
    }, []);
    const columns = [
        { title: 'Thời gian', dataIndex: 'created_at', key: 'created_at', render: (d) => new Date(d).toLocaleString('vi-VN') },
        { title: 'Nhân viên thực hiện', dataIndex: 'admin_user', key: 'admin_user', render: (u) => u?.full_name || 'System' },
        { title: 'Hành động', dataIndex: 'action', key: 'action', render: (a) => _jsx(Tag, { color: "red", icon: _jsx(SafetyOutlined, {}), children: a }) },
        { title: 'Đối tượng', dataIndex: 'entity_type', key: 'entity_type' },
        { title: 'IP Address', dataIndex: 'ip_address', key: 'ip_address' },
    ];
    return (_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "NH\u1EACT K\u00DD B\u1EA2O M\u1EACT & AUDIT LOGS \uD83D\uDD0D" }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: logs, rowKey: "id", loading: loading }) })] }));
};
export default AdminAuditLogsPage;
