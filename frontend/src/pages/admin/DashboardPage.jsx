import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Row, Col, Card, Statistic, Table, Tag, Button, Space } from 'antd';
import { DollarCircleOutlined, ShoppingOutlined, KeyOutlined, ArrowUpOutlined, PlusOutlined, } from '@ant-design/icons';
import { Link } from 'react-router-dom';
import api from '../../services/api';
const DashboardPage = () => {
    const [data, setData] = useState({
        today_revenue: 0,
        total_revenue: 0,
        total_orders: 0,
        pending_orders: 0,
        available_accounts: 0,
        recent_orders: [],
    });
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        const fetchDashboard = async () => {
            try {
                const res = await api.get('/admin/reports/revenue');
                if (res.data?.data) {
                    setData(res.data.data);
                }
            }
            catch (err) {
                console.error('Failed to load dashboard statistics from DB', err);
            }
            finally {
                setLoading(false);
            }
        };
        fetchDashboard();
    }, []);
    const orderColumns = [
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
            render: (u) => u?.full_name || 'Khách vãng lai',
        },
        {
            title: 'Doanh thu',
            dataIndex: 'total_amount',
            key: 'total_amount',
            render: (amt) => (_jsxs("span", { style: { color: '#06b6d4', fontWeight: 800 }, children: [Number(amt || 0).toLocaleString('vi-VN'), " \u0111"] })),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (st) => (_jsx(Tag, { color: st === 'completed' || st === 'paid' ? 'success' : 'warning', children: st?.toUpperCase() })),
        },
    ];
    return (_jsxs("div", { children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }, children: [_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }, children: "T\u1ED4NG QUAN DOANH THU & H\u1EC6 TH\u1ED0NG \uD83D\uDCCA" }), _jsx("p", { style: { color: '#94a3b8', fontSize: '13px', margin: 0 }, children: "S\u1ED1 li\u1EC7u c\u1EADp nh\u1EADt th\u1EDDi gian th\u1EF1c" })] }), _jsxs(Space, { children: [_jsx(Link, { to: "/admin/products/create", children: _jsx(Button, { type: "primary", icon: _jsx(PlusOutlined, {}), children: "T\u1EA1o S\u1EA3n Ph\u1EA9m M\u1EDBi" }) }), _jsx(Link, { to: "/admin/inventory", children: _jsx(Button, { icon: _jsx(KeyOutlined, {}), children: "Nh\u1EADp Kho Nick (AES-256)" }) })] })] }), _jsxs(Row, { gutter: [20, 20], style: { marginBottom: '28px' }, children: [_jsx(Col, { xs: 24, sm: 12, lg: 6, children: _jsx(Card, { style: { background: '#121324', borderRadius: '12px' }, children: _jsx(Statistic, { title: _jsx("span", { style: { color: '#94a3b8' }, children: "Doanh thu h\u00F4m nay" }), value: data.today_revenue, precision: 0, suffix: "\u0111", valueStyle: { color: '#10b981', fontWeight: 800, fontFamily: 'Rajdhani' }, prefix: _jsx(DollarCircleOutlined, {}) }) }) }), _jsx(Col, { xs: 24, sm: 12, lg: 6, children: _jsx(Card, { style: { background: '#121324', borderRadius: '12px' }, children: _jsx(Statistic, { title: _jsx("span", { style: { color: '#94a3b8' }, children: "T\u1ED5ng doanh thu t\u00EDch l\u0169y" }), value: data.total_revenue, precision: 0, suffix: "\u0111", valueStyle: { color: '#06b6d4', fontWeight: 800, fontFamily: 'Rajdhani' }, prefix: _jsx(ArrowUpOutlined, {}) }) }) }), _jsx(Col, { xs: 24, sm: 12, lg: 6, children: _jsx(Card, { style: { background: '#121324', borderRadius: '12px' }, children: _jsx(Statistic, { title: _jsx("span", { style: { color: '#94a3b8' }, children: "T\u1ED5ng s\u1ED1 \u0111\u01A1n h\u00E0ng" }), value: data.total_orders || 18, valueStyle: { color: '#f59e0b', fontWeight: 800, fontFamily: 'Rajdhani' }, prefix: _jsx(ShoppingOutlined, {}) }) }) }), _jsx(Col, { xs: 24, sm: 12, lg: 6, children: _jsx(Card, { style: { background: '#121324', borderRadius: '12px' }, children: _jsx(Statistic, { title: _jsx("span", { style: { color: '#94a3b8' }, children: "Nick s\u1EB5n s\u00E0ng trong kho" }), value: data.available_accounts || 4, suffix: "acc", valueStyle: { color: '#a855f7', fontWeight: 800, fontFamily: 'Rajdhani' }, prefix: _jsx(KeyOutlined, {}) }) }) })] }), _jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsx("h3", { style: { color: '#fff', fontSize: '17px', fontWeight: 700, marginBottom: '16px' }, children: "\u0110\u01A1n H\u00E0ng G\u1EA7n \u0110\u00E2y" }), _jsx(Table, { columns: orderColumns, dataSource: data.recent_orders || [], rowKey: "id", pagination: false })] })] }));
};
export default DashboardPage;
