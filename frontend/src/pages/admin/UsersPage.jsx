import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import api from '../../services/api';
const AdminUsersPage = () => {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const fetchUsers = async () => {
        try {
            const res = await api.get('/admin/users');
            const list = res.data?.data?.data || res.data?.data || [];
            setUsers(Array.isArray(list) ? list : []);
        }
        catch (e) { }
        finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        fetchUsers();
    }, []);
    const columns = [
        { title: 'Họ tên', dataIndex: 'full_name', key: 'full_name', render: (n) => _jsx("span", { style: { color: '#fff', fontWeight: 600 }, children: n }) },
        { title: 'Email', dataIndex: 'email', key: 'email' },
        { title: 'Số điện thoại', dataIndex: 'phone', key: 'phone' },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (s) => _jsx(Tag, { color: s === 'ACTIVE' ? 'success' : 'error', children: s || 'ACTIVE' }) },
        { title: 'Ngày tham gia', dataIndex: 'created_at', key: 'created_at', render: (d) => new Date(d).toLocaleDateString('vi-VN') },
    ];
    return (_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "QU\u1EA2N L\u00DD KH\u00C1CH H\u00C0NG" }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: users, rowKey: "id", loading: loading }) })] }));
};
export default AdminUsersPage;
