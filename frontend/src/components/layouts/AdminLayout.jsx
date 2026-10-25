import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React from 'react';
import { Outlet, Link, useNavigate, useLocation } from 'react-router-dom';
import { Layout, Menu, Button, Space, Tag } from 'antd';
import { DashboardOutlined, AppstoreOutlined, ShoppingOutlined, KeyOutlined, OrderedListOutlined, CustomerServiceOutlined, SafetyCertificateOutlined, TagOutlined, UserOutlined, TeamOutlined, FileTextOutlined, BarChartOutlined, SettingOutlined, LogoutOutlined, HomeOutlined, } from '@ant-design/icons';
import api from '../../services/api';
const { Header, Sider, Content } = Layout;
const AdminLayout = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const handleLogout = async () => {
        try {
            await api.post('/admin/auth/logout');
        } catch (error) {
            // Clear the local session even if the server token is already invalid.
        } finally {
            localStorage.removeItem('admin_token');
            localStorage.removeItem('admin_access_token');
            localStorage.removeItem('admin_info');
            navigate('/login');
        }
    };
    const menuItems = [
        { key: '/admin', icon: _jsx(DashboardOutlined, {}), label: _jsx(Link, { to: "/admin", children: "T\u1ED5ng quan Dashboard" }) },
        { key: '/admin/categories', icon: _jsx(AppstoreOutlined, {}), label: _jsx(Link, { to: "/admin/categories", children: "Danh m\u1EE5c" }) },
        { key: '/admin/games', icon: _jsx(AppstoreOutlined, {}), label: _jsx(Link, { to: "/admin/games", children: "Qu\u1EA3n l\u00FD Game" }) },
        { key: '/admin/products', icon: _jsx(ShoppingOutlined, {}), label: _jsx(Link, { to: "/admin/products", children: "S\u1EA3n ph\u1EA9m & Gi\u00E1" }) },
        { key: '/admin/inventory', icon: _jsx(KeyOutlined, {}), label: _jsx(Link, { to: "/admin/inventory", children: "Kho Acc (M\u00E3 ho\u00E1 AES-256)" }) },
        { key: '/admin/orders', icon: _jsx(OrderedListOutlined, {}), label: _jsx(Link, { to: "/admin/orders", children: "\u0110\u01A1n h\u00E0ng & Giao d\u1ECBch" }) },
        { key: '/admin/service-requests', icon: _jsx(CustomerServiceOutlined, {}), label: _jsx(Link, { to: "/admin/service-requests", children: "Y\u00EAu c\u1EA7u C\u00E0y thu\u00EA" }) },
        { key: '/admin/warranty', icon: _jsx(SafetyCertificateOutlined, {}), label: _jsx(Link, { to: "/admin/warranty", children: "Khi\u1EBFu n\u1EA1i B\u1EA3o h\u00E0nh" }) },
        { key: '/admin/coupons', icon: _jsx(TagOutlined, {}), label: _jsx(Link, { to: "/admin/coupons", children: "M\u00E3 gi\u1EA3m gi\u00E1 (Coupon)" }) },
        { key: '/admin/users', icon: _jsx(UserOutlined, {}), label: _jsx(Link, { to: "/admin/users", children: "Kh\u00E1ch h\u00E0ng" }) },
        { key: '/admin/staff', icon: _jsx(TeamOutlined, {}), label: _jsx(Link, { to: "/admin/staff", children: "Nh\u00E2n s\u1EF1 & Ph\u00E2n quy\u1EC1n" }) },
        { key: '/admin/audit-logs', icon: _jsx(FileTextOutlined, {}), label: _jsx(Link, { to: "/admin/audit-logs", children: "Audit Logs (B\u1EA3o m\u1EADt)" }) },
        { key: '/admin/reports', icon: _jsx(BarChartOutlined, {}), label: _jsx(Link, { to: "/admin/reports", children: "B\u00E1o c\u00E1o & Doanh thu" }) },
        { key: '/admin/settings', icon: _jsx(SettingOutlined, {}), label: _jsx(Link, { to: "/admin/settings", children: "C\u1EA5u h\u00ECnh H\u1EC7 th\u1ED1ng" }) },
    ];
    return (_jsxs(Layout, { style: { minHeight: '100vh', background: '#0a0a14' }, children: [_jsxs(Sider, { width: 260, style: {
                    background: '#121324',
                    borderRight: '1px solid rgba(255, 255, 255, 0.08)',
                    position: 'sticky',
                    top: 0,
                    height: '100vh',
                    overflowY: 'auto',
                }, children: [_jsxs("div", { style: { padding: '20px 24px', borderBottom: '1px solid rgba(255, 255, 255, 0.08)' }, children: [_jsxs("div", { style: { fontFamily: 'Rajdhani', fontSize: '18px', fontWeight: 800, color: '#fff' }, children: ["SHOPACCLIENQUAN ", _jsx(Tag, { color: "purple", children: "ADMIN" })] }), _jsx("div", { style: { fontSize: '11px', color: '#94a3b8', marginTop: '4px' }, children: "H\u1EC7 Th\u1ED1ng Qu\u1EA3n Tr\u1ECB Trung T\u00E2m" })] }), _jsx(Menu, { mode: "inline", selectedKeys: [location.pathname], items: menuItems, style: { background: '#121324', marginTop: '8px' } })] }), _jsxs(Layout, { children: [_jsxs(Header, { style: {
                            background: 'rgba(18, 19, 36, 0.9)',
                            backdropFilter: 'blur(10px)',
                            borderBottom: '1px solid rgba(255, 255, 255, 0.08)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            padding: '0 32px',
                            height: '64px',
                            position: 'sticky',
                            top: 0,
                            zIndex: 999,
                        }, children: [_jsxs("div", { style: { color: '#cbd5e1', fontSize: '14px', fontWeight: 600 }, children: ["Qu\u1EA3n tr\u1ECB vi\u00EAn: ", _jsx(Tag, { color: "blue", children: "Super Administrator" })] }), _jsxs(Space, { size: "middle", children: [_jsx(Link, { to: "/", children: _jsx(Button, { icon: _jsx(HomeOutlined, {}), type: "default", style: { borderRadius: '6px' }, children: "Xem Trang Kh\u00E1ch" }) }), _jsx(Button, { icon: _jsx(LogoutOutlined, {}), danger: true, onClick: handleLogout, style: { borderRadius: '6px' }, children: "\u0110\u0103ng xu\u1EA5t" })] })] }), _jsx(Content, { style: { padding: '24px 32px', minHeight: 'calc(100vh - 64px)' }, children: _jsx(Outlet, {}) })] })] }));
};
export default AdminLayout;
