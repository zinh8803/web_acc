import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Table, Tag, Select, message } from 'antd';
import api from '../../services/api';
const AdminOrdersPage = () => {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const fetchOrders = async () => {
        try {
            const res = await api.get('/admin/orders');
            const list = res.data?.data?.data || res.data?.data || [];
            setOrders(Array.isArray(list) ? list : []);
        }
        catch (e) { }
        finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        fetchOrders();
    }, []);
    const handleUpdateStatus = async (id, status) => {
        try {
            await api.patch(`/admin/orders/${id}/status`, { status });
            message.success('Đã cập nhật trạng thái đơn hàng!');
            fetchOrders();
        }
        catch (e) {
            message.error('Không thể cập nhật trạng thái.');
        }
    };
    const columns = [
        {
            title: 'Mã đơn',
            dataIndex: 'order_code',
            key: 'order_code',
            render: (c) => _jsx(Tag, { color: "cyan", style: { fontWeight: 700 }, children: c }),
        },
        {
            title: 'Khách hàng',
            dataIndex: 'user',
            key: 'user',
            render: (u) => u?.full_name || u?.email || 'Khách vãng lai',
        },
        {
            title: 'Tổng tiền',
            dataIndex: 'total_amount',
            key: 'total_amount',
            render: (amt) => (_jsxs("span", { style: { color: '#06b6d4', fontWeight: 800 }, children: [Number(amt || 0).toLocaleString('vi-VN'), " \u0111"] })),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (st, record) => (_jsxs(Select, { value: st, size: "small", style: { width: 150 }, onChange: (val) => handleUpdateStatus(record.id, val), children: [_jsx(Select.Option, { value: "pending_payment", children: "Ch\u1EDD thanh to\u00E1n" }), _jsx(Select.Option, { value: "paid", children: "\u0110\u00E3 thanh to\u00E1n" }), _jsx(Select.Option, { value: "processing", children: "\u0110ang x\u1EED l\u00FD" }), _jsx(Select.Option, { value: "completed", children: "Ho\u00E0n t\u1EA5t" }), _jsx(Select.Option, { value: "cancelled", children: "Hu\u1EF7 \u0111\u01A1n" })] })),
        },
        {
            title: 'Ngày đặt',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (d) => new Date(d).toLocaleString('vi-VN'),
        },
    ];
    return (_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "QU\u1EA2N L\u00DD \u0110\u01A0N H\u00C0NG & GIAO D\u1ECACH \uD83E\uDDFE" }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: orders, rowKey: "id", loading: loading }) })] }));
};
export default AdminOrdersPage;
