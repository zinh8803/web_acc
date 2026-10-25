import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button, Tag, Spin, Descriptions } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import api from '../../services/api';
const AdminOrderDetailPage = () => {
    const { code } = useParams();
    const [order, setOrder] = useState(null);
    useEffect(() => {
        if (code) {
            api.get(`/orders/${code}`).then((res) => {
                if (res.data?.data)
                    setOrder(res.data.data);
            });
        }
    }, [code]);
    return (_jsxs("div", { style: { maxWidth: '900px' }, children: [_jsx(Link, { to: "/admin/orders", children: _jsx(Button, { icon: _jsx(ArrowLeftOutlined, {}), style: { marginBottom: '16px' }, children: "Quay l\u1EA1i danh s\u00E1ch \u0111\u01A1n h\u00E0ng" }) }), _jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsxs("h2", { style: { color: '#fff', marginBottom: '16px' }, children: ["Chi Ti\u1EBFt \u0110\u01A1n H\u00E0ng ", code] }), order ? (_jsxs(Descriptions, { column: 1, bordered: true, children: [_jsx(Descriptions.Item, { label: "M\u00E3 \u0111\u01A1n", children: order.order_code }), _jsxs(Descriptions.Item, { label: "T\u1ED5ng ti\u1EC1n", children: [Number(order.total_amount || 0).toLocaleString('vi-VN'), " \u0111"] }), _jsx(Descriptions.Item, { label: "Tr\u1EA1ng th\u00E1i", children: _jsx(Tag, { color: "cyan", children: order.status }) }), _jsx(Descriptions.Item, { label: "Th\u1EDDi gian", children: new Date(order.created_at).toLocaleString('vi-VN') })] })) : (_jsx(Spin, {}))] })] }));
};
export default AdminOrderDetailPage;
