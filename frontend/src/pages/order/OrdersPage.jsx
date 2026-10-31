import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Table, Tag, Button, Spin } from 'antd';
import { EyeOutlined, ShoppingOutlined } from '@ant-design/icons';
import api from '../../services/api';
const OrdersPage = () => {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        const fetchOrders = async () => {
            try {
                const res = await api.get('/orders');
                const list = res.data?.data?.data || res.data?.data || [];
                setOrders(Array.isArray(list) ? list : []);
            }
            catch (err) {
                console.error(err);
            }
            finally {
                setLoading(false);
            }
        };
        fetchOrders();
    }, []);
    const getStatusTag = (status) => {
        switch (status?.toLowerCase()) {
            case 'completed':
            case 'paid':
                return _jsx(Tag, { color: "success", children: "\u0110\u00C3 HO\u00C0N T\u1EA4T / \u0110\u00C3 B\u00C0N GIAO" });
            case 'pending_payment':
            case 'pending':
                return _jsx(Tag, { color: "warning", children: "CH\u1EDC THANH TO\u00C1N" });
            case 'processing':
                return _jsx(Tag, { color: "processing", children: "\u0110ANG X\u1EEC L\u00DD" });
            case 'cancelled':
                return _jsx(Tag, { color: "error", children: "\u0110\u00C3 HU\u1EF6" });
            default:
                return _jsx(Tag, { color: "default", children: status });
        }
    };
    const columns = [
        {
            title: 'Mã đơn hàng',
            dataIndex: 'order_code',
            key: 'order_code',
            render: (code) => (_jsx("span", { style: { color: '#06b6d4', fontWeight: 800, fontFamily: 'Rajdhani', fontSize: '15px' }, children: code })),
        },
        {
            title: 'Thời gian đặt',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (date) => new Date(date).toLocaleString('vi-VN'),
        },
        {
            title: 'Tổng tiền',
            dataIndex: 'total_amount',
            key: 'total_amount',
            render: (amt, r) => (_jsxs("span", { style: { color: '#f8fafc', fontWeight: 700 }, children: [Number(amt || r.total || 0).toLocaleString('vi-VN'), " \u0111"] })),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (st) => getStatusTag(st),
        },
        {
            title: 'Hành động',
            key: 'action',
            render: (_, record) => (_jsx(Link, { to: `/orders/${record.order_code}`, children: _jsx(Button, { type: "primary", size: "small", icon: _jsx(EyeOutlined, {}), children: "Xem Chi Ti\u1EBFt" }) })),
        },
    ];
    return (_jsxs("div", { style: { maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }, children: [_jsx("h1", { style: { color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "L\u1ECACH S\u1EEC \u0110\u01A0N H\u00C0NG C\u1EE6A B\u1EA0N" }), loading ? (_jsx("div", { style: { textAlign: 'center', padding: '80px 0' }, children: _jsx(Spin, { size: "large" }) })) : orders.length === 0 ? (_jsxs("div", { style: { background: '#121324', padding: '60px 20px', borderRadius: '12px', textAlign: 'center' }, children: [_jsx(ShoppingOutlined, { style: { fontSize: '48px', color: '#6366f1', marginBottom: '16px' } }), _jsx("h3", { style: { color: '#fff', marginBottom: '8px' }, children: "B\u1EA1n ch\u01B0a c\u00F3 \u0111\u01A1n h\u00E0ng n\u00E0o" }), _jsx("p", { style: { color: '#94a3b8', marginBottom: '20px' }, children: "H\u00E3y tr\u1EA3i nghi\u1EC7m mua s\u1EAFm t\u00E0i kho\u1EA3n game uy t\u00EDn ngay!" }), _jsx(Link, { to: "/catalog", children: _jsx(Button, { type: "primary", children: "Kh\u00E1m Ph\u00E1 C\u1EEDa H\u00E0ng" }) })] })) : (_jsx("div", { style: { background: '#121324', borderRadius: '12px', padding: '16px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: orders, rowKey: "id", pagination: { pageSize: 10 }, style: { background: 'transparent' } }) }))] }));
};
export default OrdersPage;
