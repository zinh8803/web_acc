import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Table, Tag } from 'antd';
import api from '../../services/api';
const AdminServiceRequestsPage = () => {
    const [services, setServices] = useState([]);
    const [loading, setLoading] = useState(true);
    const fetchServices = async () => {
        try {
            const res = await api.get('/admin/service-requests');
            const list = res.data?.data?.data || res.data?.data || [];
            setServices(Array.isArray(list) ? list : []);
        }
        catch (e) { }
        finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        fetchServices();
    }, []);
    const columns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
        { title: 'Khách hàng', dataIndex: 'user', key: 'user', render: (u) => u?.full_name || 'Khách' },
        { title: 'Dịch vụ', key: 'service', render: (_, row) => row.order_item?.product?.name || row.request_code },
        { title: 'Ưu tiên', dataIndex: 'priority', key: 'priority' },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (s) => _jsx(Tag, { color: "orange", children: s }) },
    ];
    return (_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "Y\u00CAU C\u1EA6U D\u1ECACH V\u1EE4 C\u00C0Y THU\u00CA \uD83C\uDFAE" }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: services, rowKey: "id", loading: loading }) })] }));
};
export default AdminServiceRequestsPage;
