import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Row, Col, Spin, Empty, Tag } from 'antd';
import { BarChartOutlined, PieChartOutlined } from '@ant-design/icons';
import api from '../../services/api';
const AdminReportsPage = () => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        const fetchReport = async () => {
            try {
                const res = await api.get('/admin/reports/revenue');
                if (res.data?.data) {
                    setData(res.data.data);
                }
            }
            catch (e) {
                console.error('Failed to load reports from DB', e);
            }
            finally {
                setLoading(false);
            }
        };
        fetchReport();
    }, []);
    const totalGameRevenue = data?.game_revenue?.reduce((acc, g) => acc + Number(g.total_amount || 0), 0) || 0;
    const totalTxCount = data?.payment_stats?.reduce((acc, p) => acc + Number(p.tx_count || 0), 0) || 0;
    return (_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "B\u00C1O C\u00C1O DOANH THU & PH\u00C2N T\u00CDCH (DB) \uD83D\uDCC8" }), loading ? (_jsx("div", { style: { textAlign: 'center', padding: '60px 0' }, children: _jsx(Spin, { size: "large" }) })) : (_jsxs(Row, { gutter: [20, 20], children: [_jsx(Col, { xs: 24, md: 12, children: _jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)', height: '100%' }, children: [_jsxs("h3", { style: { color: '#fff', marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }, children: [_jsx(BarChartOutlined, { style: { color: '#06b6d4' } }), " Doanh Thu Theo T\u1EF1a Game (T\u1EEB Database)"] }), data?.game_revenue && data.game_revenue.length > 0 ? (_jsx("div", { style: { color: '#cbd5e1', display: 'flex', flexDirection: 'column', gap: '12px' }, children: data.game_revenue.map((g) => {
                                        const amt = Number(g.total_amount || 0);
                                        const pct = totalGameRevenue > 0 ? Math.round((amt / totalGameRevenue) * 100) : 0;
                                        return (_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' }, children: [_jsxs("span", { children: ["\u2022 ", _jsx("strong", { children: g.name }), ":"] }), _jsxs("span", { children: [_jsxs("strong", { style: { color: '#06b6d4' }, children: [amt.toLocaleString('vi-VN'), " \u0111"] }), ' ', _jsxs(Tag, { color: "cyan", children: [pct, "%"] })] })] }, g.id));
                                    }) })) : (_jsx(Empty, { description: _jsx("span", { style: { color: '#94a3b8' }, children: "Ch\u01B0a c\u00F3 ph\u00E1t sinh doanh thu" }) }))] }) }), _jsx(Col, { xs: 24, md: 12, children: _jsxs("div", { style: { background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)', height: '100%' }, children: [_jsxs("h3", { style: { color: '#fff', marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }, children: [_jsx(PieChartOutlined, { style: { color: '#10b981' } }), " Ph\u01B0\u01A1ng Th\u1EE9c Thanh To\u00E1n (T\u1EEB Database)"] }), data?.payment_stats && data.payment_stats.length > 0 ? (_jsx("div", { style: { color: '#cbd5e1', display: 'flex', flexDirection: 'column', gap: '12px' }, children: data.payment_stats.map((p) => {
                                        const count = Number(p.tx_count || 0);
                                        const pct = totalTxCount > 0 ? Math.round((count / totalTxCount) * 100) : 0;
                                        return (_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' }, children: [_jsxs("span", { children: ["\u2022 ", _jsx("strong", { children: p.name }), ":"] }), _jsxs("span", { children: [_jsxs("strong", { style: { color: '#10b981' }, children: [count, " giao d\u1ECBch"] }), ' ', _jsxs(Tag, { color: "green", children: [pct, "%"] })] })] }, p.code));
                                    }) })) : (_jsx(Empty, { description: _jsx("span", { style: { color: '#94a3b8' }, children: "Ch\u01B0a c\u00F3 giao d\u1ECBch thanh to\u00E1n" }) }))] }) })] }))] }));
};
export default AdminReportsPage;
