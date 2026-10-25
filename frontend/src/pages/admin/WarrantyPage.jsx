import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import api from '../../services/api';
const AdminWarrantyPage = () => {
    const [claims, setClaims] = useState([]);
    const [loading, setLoading] = useState(true);
    const fetchClaims = async () => {
        try {
            const res = await api.get('/admin/warranty-claims');
            const list = res.data?.data?.data || res.data?.data || [];
            setClaims(Array.isArray(list) ? list : []);
        }
        catch (e) { }
        finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        fetchClaims();
    }, []);
    const columns = [
        { title: 'Mã khiếu nại', dataIndex: 'claim_code', key: 'claim_code', render: (c) => _jsx(Tag, { color: "orange", children: c }) },
        { title: 'Khách hàng', dataIndex: 'user', key: 'user', render: (u) => u?.full_name || 'Khách' },
        { title: 'Lý do', dataIndex: 'reason', key: 'reason' },
        { title: 'Mô tả', dataIndex: 'description', key: 'description' },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (st) => _jsx(Tag, { color: "processing", children: st }) },
    ];
    return (_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "X\u1EEC L\u00DD KHI\u1EBEU N\u1EA0I B\u1EA2O H\u00C0NH \uD83D\uDEE1\uFE0F" }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: claims, rowKey: "id", loading: loading }) })] }));
};
export default AdminWarrantyPage;
