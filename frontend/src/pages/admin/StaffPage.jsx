import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import api from '../../services/api';
const AdminStaffPage = () => {
    const [staff, setStaff] = useState([]);
    const [loading, setLoading] = useState(true);
    const fetchStaff = async () => {
        try {
            const res = await api.get('/admin/admin-users');
            if (res.data?.data)
                setStaff(res.data.data);
        }
        catch (e) { }
        finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        fetchStaff();
    }, []);
    const columns = [
        { title: 'Tên nhân sự', dataIndex: 'full_name', key: 'full_name', render: (n) => _jsx("span", { style: { color: '#fff', fontWeight: 600 }, children: n }) },
        { title: 'Email', dataIndex: 'email', key: 'email' },
        { title: 'Vai trò (Role)', dataIndex: 'role', key: 'role', render: (r) => _jsx(Tag, { color: "blue", children: r?.name || 'Super Admin' }) },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (s) => _jsx(Tag, { color: "green", children: s || 'ACTIVE' }) },
    ];
    return (_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "NH\u00C2N S\u1EF0 & PH\u00C2N QUY\u1EC0N (RBAC) \uD83D\uDEE1\uFE0F" }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: staff, rowKey: "id", loading: loading }) })] }));
};
export default AdminStaffPage;
