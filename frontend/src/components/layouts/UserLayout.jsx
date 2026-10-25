import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Outlet, Link, useNavigate, useLocation } from 'react-router-dom';
import { Layout, Button, Badge, Dropdown, Avatar, Space, Tag } from 'antd';
import { ShoppingCartOutlined, UserOutlined, LogoutOutlined, SafetyCertificateOutlined, ThunderboltOutlined, FireOutlined, HistoryOutlined, AppstoreOutlined, DownOutlined, } from '@ant-design/icons';
import { useAuthStore } from '../../stores/authStore';
import { useCartStore } from '../../stores/cartStore';
import api from '../../services/api';
const { Header, Content, Footer } = Layout;
const UserLayout = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const { user, isAuthenticated, logout } = useAuthStore();
    const { items } = useCartStore();
    const [dbCategories, setDbCategories] = useState([]);
    const [dbGames, setDbGames] = useState([]);
    const [siteSettings, setSiteSettings] = useState(null);
    useEffect(() => {
        const fetchNavData = async () => {
            try {
                const [catsRes, gamesRes, settingsRes] = await Promise.all([
                    api.get('/categories'),
                    api.get('/games'),
                    api.get('/settings/public'),
                ]);
                if (catsRes.data?.data) {
                    setDbCategories(Array.isArray(catsRes.data.data) ? catsRes.data.data : []);
                }
                if (gamesRes.data?.data) {
                    setDbGames(Array.isArray(gamesRes.data.data) ? gamesRes.data.data : []);
                }
                if (settingsRes.data?.data) {
                    setSiteSettings(settingsRes.data.data);
                }
            }
            catch (err) {
                console.error('Failed to load navigation categories/games from DB:', err);
            }
        };
        fetchNavData();
    }, []);
    const cartItemCount = items ? items.reduce((acc, i) => acc + i.quantity, 0) : 0;
    const handleLogout = async () => {
        try {
            await api.post('/auth/logout');
        } catch (error) {
            // Clear the local session even if the server token is already invalid.
        } finally {
            logout();
            navigate('/login');
        }
    };
    const categoryDropdownItems = [
        {
            key: 'all-catalog',
            label: (_jsx(Link, { to: "/catalog", style: { fontWeight: 700, color: '#6366f1' }, children: "\u2728 Xem To\u00E0n B\u1ED9 Danh M\u1EE5c & Nick" })),
        },
        {
            type: 'divider',
        },
        ...dbCategories.map((cat) => ({
            key: `cat-${cat.id}`,
            label: (_jsxs(Link, { to: `/catalog?category=${cat.slug}`, style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '16px' }, children: [_jsx("span", { children: cat.name }), cat.game && (_jsx(Tag, { color: "cyan", style: { fontSize: '11px', margin: 0 }, children: cat.game.name }))] })),
        })),
    ];
    const userMenuItems = [
        {
            key: 'profile',
            label: _jsx(Link, { to: "/profile", children: "Th\u00F4ng tin t\u00E0i kho\u1EA3n" }),
            icon: _jsx(UserOutlined, {}),
        },
        {
            key: 'orders',
            label: _jsx(Link, { to: "/orders", children: "L\u1ECBch s\u1EED \u0111\u01A1n h\u00E0ng" }),
            icon: _jsx(HistoryOutlined, {}),
        },
        {
            key: 'warranty',
            label: _jsx(Link, { to: "/warranty-claims", children: "Y\u00EAu c\u1EA7u b\u1EA3o h\u00E0nh" }),
            icon: _jsx(SafetyCertificateOutlined, {}),
        },
        {
            type: 'divider',
        },
        {
            key: 'logout',
            label: 'Đăng xuất',
            icon: _jsx(LogoutOutlined, {}),
            danger: true,
            onClick: handleLogout,
        },
    ];
    return (_jsxs(Layout, { style: { minHeight: '100vh', background: '#0a0a14' }, children: [_jsx("div", { style: {
                    background: 'linear-gradient(90deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%)',
                    color: '#ffffff',
                    textAlign: 'center',
                    padding: '6px 16px',
                    fontSize: '13px',
                    fontWeight: '600',
                    letterSpacing: '0.3px',
                }, children: _jsxs(Space, { size: "middle", children: [siteSettings?.general_settings?.hotline && (_jsxs(_Fragment, { children: [_jsx("span", { children: "\u2022" }), _jsxs("span", { children: ["\uD83D\uDCDE Hotline CSKH: ", _jsx("strong", { children: siteSettings.general_settings.hotline })] })] }))] }) }), _jsxs(Header, { style: {
                    background: 'rgba(18, 19, 36, 0.95)',
                    backdropFilter: 'blur(12px)',
                    borderBottom: '1px solid rgba(255, 255, 255, 0.08)',
                    position: 'sticky',
                    top: 0,
                    zIndex: 1000,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    padding: '0 32px',
                    height: '70px',
                }, children: [_jsxs(Link, { to: "/", style: { display: 'flex', alignItems: 'center', gap: '10px' }, children: [_jsx("div", { style: {
                                    width: '40px',
                                    height: '40px',
                                    borderRadius: '10px',
                                    background: 'linear-gradient(135deg, #f59e0b, #ef4444)',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    boxShadow: '0 0 15px rgba(239, 68, 68, 0.5)',
                                }, children: _jsx(ThunderboltOutlined, { style: { fontSize: '22px', color: '#fff' } }) }), _jsx("div", { children: _jsx("div", { style: { fontSize: '10px', color: '#f59e0b', letterSpacing: '1px', textTransform: 'uppercase', fontWeight: 700 }, children: "Chuy\u00EAn Acc Li\u00EAn Qu\u00E2n S\u1ED1 1 VN" }) })] }), _jsxs("div", { style: { display: 'flex', alignItems: 'center', gap: '24px' }, children: [_jsx(Link, { to: "/", style: {
                                    color: location.pathname === '/' ? '#6366f1' : '#cbd5e1',
                                    fontWeight: 600,
                                    fontSize: '15px',
                                    transition: 'color 0.2s',
                                }, children: "Trang Ch\u1EE7" }), _jsx(Dropdown, { menu: { items: categoryDropdownItems }, placement: "bottomLeft", children: _jsxs("span", { style: {
                                        color: location.pathname.startsWith('/catalog') ? '#6366f1' : '#cbd5e1',
                                        fontWeight: 600,
                                        fontSize: '15px',
                                        cursor: 'pointer',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '6px',
                                    }, children: [_jsx(AppstoreOutlined, {}), "Danh M\u1EE5c ", _jsx(DownOutlined, { style: { fontSize: '11px' } })] }) }), _jsx(Link, { to: "/catalog", style: {
                                    color: location.pathname === '/catalog' && !location.search ? '#6366f1' : '#cbd5e1',
                                    fontWeight: 600,
                                    fontSize: '15px',
                                }, children: "Kho Nick Game" }), _jsxs(Link, { to: "/catalog?type=service", style: {
                                    color: location.search.includes('type=service') ? '#f43f5e' : '#cbd5e1',
                                    fontWeight: 600,
                                    fontSize: '15px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '6px',
                                }, children: [_jsx(FireOutlined, { style: { color: '#f43f5e' } }), "D\u1ECBch V\u1EE5 C\u00E0y Thu\u00EA"] }), _jsxs(Link, { to: "/warranty-claims", style: {
                                    color: location.pathname.startsWith('/warranty') ? '#10b981' : '#cbd5e1',
                                    fontWeight: 600,
                                    fontSize: '15px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '6px',
                                }, children: [_jsx(SafetyCertificateOutlined, { style: { color: '#10b981' } }), "Ch\u00EDnh S\u00E1ch B\u1EA3o H\u00E0nh"] })] }), _jsxs("div", { style: { display: 'flex', alignItems: 'center', gap: '18px' }, children: [_jsx(Link, { to: "/cart", children: _jsx(Badge, { count: cartItemCount, overflowCount: 99, color: "#6366f1", children: _jsx(Button, { type: "text", shape: "circle", icon: _jsx(ShoppingCartOutlined, { style: { fontSize: '20px', color: '#f8fafc' } }), style: { width: '42px', height: '42px', background: 'rgba(255, 255, 255, 0.05)' } }) }) }), isAuthenticated && user ? (_jsx(Dropdown, { menu: { items: userMenuItems }, placement: "bottomRight", arrow: true, children: _jsxs("div", { style: {
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '10px',
                                        cursor: 'pointer',
                                        padding: '6px 12px',
                                        borderRadius: '20px',
                                        background: 'rgba(255, 255, 255, 0.05)',
                                        border: '1px solid rgba(255, 255, 255, 0.1)',
                                    }, children: [_jsx(Avatar, { style: { backgroundColor: '#6366f1' }, icon: _jsx(UserOutlined, {}), src: user.avatar_url }), _jsxs("div", { style: { textAlign: 'left', lineHeight: 1.2 }, children: [_jsx("div", { style: { color: '#fff', fontSize: '13px', fontWeight: 600 }, children: user.full_name || user.email }), _jsx("div", { style: { color: '#10b981', fontSize: '11px' }, children: "Th\u00E0nh vi\u00EAn" })] })] }) })) : (_jsxs(Space, { children: [_jsx(Link, { to: "/login", children: _jsx(Button, { type: "text", style: { color: '#cbd5e1', fontWeight: 600 }, children: "\u0110\u0103ng Nh\u1EADp" }) }), _jsx(Link, { to: "/register", children: _jsx(Button, { type: "primary", style: { fontWeight: 600, borderRadius: '8px' }, children: "\u0110\u0103ng K\u00FD" }) })] }))] })] }), _jsx(Content, { style: { minHeight: 'calc(100vh - 70px - 260px)' }, children: _jsx(Outlet, {}) }), _jsxs(Footer, { style: {
                    background: '#0d0e1c',
                    color: '#94a3b8',
                    borderTop: '1px solid rgba(255, 255, 255, 0.06)',
                    padding: '48px 32px 24px',
                }, children: [_jsxs("div", { style: {
                            maxWidth: '1200px',
                            margin: '0 auto',
                            display: 'grid',
                            gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
                            gap: '32px',
                            marginBottom: '40px',
                        }, children: [_jsxs("div", { children: [_jsxs("div", { style: { fontFamily: 'Rajdhani', fontSize: '24px', fontWeight: 800, color: '#fff', marginBottom: '12px' }, children: ["SHOPACC", _jsx("span", { style: { color: '#06b6d4' }, children: "LIENQUAN" }), ".VN"] }), _jsx("p", { style: { fontSize: '14px', lineHeight: 1.6, color: '#94a3b8' }, children: "Chuy\u00EAn trang mua b\u00E1n t\u00E0i kho\u1EA3n Li\u00EAn Qu\u00E2n Mobile & d\u1ECBch v\u1EE5 c\u00E0y thu\u00EA uy t\u00EDn s\u1ED1 1 Vi\u1EC7t Nam. B\u00E0n giao t\u00E0i kho\u1EA3n t\u1EF1 \u0111\u1ED9ng 24/7 t\u1EE9c th\u00EC, m\u00E3 ho\u00E1 b\u1EA3o m\u1EADt tuy\u1EC7t \u0111\u1ED1i, b\u1EA3o h\u00E0nh 100% kh\u00F4ng back." })] }), _jsxs("div", { children: [_jsx("h4", { style: { color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }, children: "Tr\u00F2 Ch\u01A1i (DB)" }), _jsx("div", { style: { display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }, children: dbGames.length > 0 ? (dbGames.map((g) => (_jsx(Link, { to: `/catalog?game=${g.slug}`, style: { color: '#94a3b8', transition: 'color 0.2s' }, children: g.name }, g.id)))) : (_jsx("span", { style: { color: '#64748b' }, children: "\u0110ang t\u1EA3i tr\u00F2 ch\u01A1i..." })) })] }), _jsxs("div", { children: [_jsx("h4", { style: { color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }, children: "Danh M\u1EE5c S\u1EA3n Ph\u1EA9m (DB)" }), _jsx("div", { style: { display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }, children: dbCategories.length > 0 ? (dbCategories.slice(0, 5).map((c) => (_jsx(Link, { to: `/catalog?category=${c.slug}`, style: { color: '#94a3b8', transition: 'color 0.2s' }, children: c.name }, c.id)))) : (_jsx("span", { style: { color: '#64748b' }, children: "\u0110ang t\u1EA3i danh m\u1EE5c..." })) })] }), _jsxs("div", { children: [_jsx("h4", { style: { color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }, children: "Ch\u00EDnh S\u00E1ch & H\u1ED7 Tr\u1EE3" }), _jsxs("div", { style: { display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }, children: [_jsx(Link, { to: "/warranty-claims", style: { color: '#94a3b8' }, children: "Ch\u00EDnh s\u00E1ch b\u1EA3o h\u00E0nh & \u0110\u1ED5i tr\u1EA3" }), _jsx("span", { style: { color: '#94a3b8' }, children: "H\u01B0\u1EDBng d\u1EABn mua h\u00E0ng & Thanh to\u00E1n" }), _jsx("span", { style: { color: '#94a3b8' }, children: "\u0110i\u1EC1u kho\u1EA3n s\u1EED d\u1EE5ng d\u1ECBch v\u1EE5" }), _jsx("span", { style: { color: '#94a3b8' }, children: "B\u1EA3o m\u1EADt th\u00F4ng tin kh\u00E1ch h\u00E0ng" })] })] }), _jsxs("div", { children: [_jsx("h4", { style: { color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }, children: "Li\u00EAn H\u1EC7 H\u1ED7 Tr\u1EE3 24/7 (DB)" }), _jsxs("div", { style: { fontSize: '14px', lineHeight: 1.8, color: '#94a3b8' }, children: [_jsxs("div", { children: ["\uD83D\uDCDE Hotline: ", _jsx("strong", { style: { color: '#fff' }, children: siteSettings?.general_settings?.hotline })] }), _jsxs("div", { children: ["\uD83D\uDCAC Zalo CSKH: ", _jsx("strong", { style: { color: '#fff' }, children: siteSettings?.general_settings?.zalo })] }), _jsxs("div", { children: ["\u2708\uFE0F Telegram: ", _jsx("span", { style: { color: '#06b6d4' }, children: siteSettings?.general_settings?.telegram })] }), _jsx("div", { children: "\u23F0 Th\u1EDDi gian h\u1ED7 tr\u1EE3: 24/7 k\u1EC3 c\u1EA3 ng\u00E0y l\u1EC5 T\u1EBFt" })] })] })] }), _jsx("div", { style: {
                            borderTop: '1px solid rgba(255, 255, 255, 0.06)',
                            paddingTop: '20px',
                            textAlign: 'center',
                            fontSize: '13px',
                            color: '#64748b',
                        }, children: "\u00A9 2026 SHOPACCLIENQUAN.VN. All rights reserved. H\u1EC7 th\u1ED1ng th\u01B0\u01A1ng m\u1EA1i \u0111i\u1EC7n t\u1EED chuy\u00EAn nghi\u1EC7p cho c\u1ED9ng \u0111\u1ED3ng game th\u1EE7 Li\u00EAn Qu\u00E2n Mobile." })] })] }));
};
export default UserLayout;
