import React, { useEffect, useState } from 'react';
import { Outlet, Link, useNavigate, useLocation } from 'react-router-dom';
import { Layout, Button, Badge, Dropdown, Avatar, Space, Tag } from 'antd';
import {
    ShoppingCartOutlined,
    UserOutlined,
    LogoutOutlined,
    SafetyCertificateOutlined,
    ThunderboltOutlined,
    FireOutlined,
    HistoryOutlined,
    AppstoreOutlined,
    DownOutlined,
} from '@ant-design/icons';
import { useAuthStore } from '../../stores/authStore';
import { useCartStore } from '../../stores/cartStore';
import { useSettingsStore } from '../../stores/settingsStore';
import api from '../../services/api';

const { Header, Content, Footer } = Layout;

const UserLayout = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const { user, isAuthenticated, logout } = useAuthStore();
    const { items } = useCartStore();
    const { siteName, settings: siteSettings, fetchSettings } = useSettingsStore();
    const [dbCategories, setDbCategories] = useState([]);
    const [dbServices, setDbServices] = useState([]);

    // Tự động cuộn lên đầu trang khi người dùng chuyển trang hoặc đổi điều hướng
    useEffect(() => {
        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
    }, [location.pathname, location.search]);

    useEffect(() => {
        const fetchNavData = async () => {
            try {
                const [catsRes, servicesRes] = await Promise.all([
                    api.get('/categories'),
                    api.get('/products', { params: { type: 'SERVICE', limit: 20 } }),
                    fetchSettings(),
                ]);
                if (catsRes.data?.data) {
                    setDbCategories(Array.isArray(catsRes.data.data) ? catsRes.data.data : []);
                }
                if (servicesRes.data?.data) {
                    setDbServices(Array.isArray(servicesRes.data.data) ? servicesRes.data.data : []);
                }
            } catch (err) {
                console.error('Failed to load navigation categories from DB:', err);
            }
        };
        fetchNavData();
    }, [fetchSettings]);

    useEffect(() => {
        if (siteName) {
            document.title = `${siteName} - Hệ Thống Tài Khoản & Dịch Vụ Game Uy Tín`;
        }
    }, [siteName]);

    const cartItemCount = items ? items.reduce((acc, i) => acc + i.quantity, 0) : 0;

    const handleLogout = async () => {
        try {
            await api.post('/auth/logout');
        } catch (error) {
            // Clear local session regardless of server status
        } finally {
            logout();
            navigate('/login');
        }
    };

    const categoryDropdownItems = [
        {
            key: 'all-catalog',
            label: (
                <Link to="/catalog" style={{ fontWeight: 700, color: '#6366f1' }}>
                    Xem Toàn Bộ Danh Mục & Nick
                </Link>
            ),
        },
        {
            type: 'divider',
        },
        ...dbCategories.map((cat) => ({
            key: `cat-${cat.id}`,
            label: (
                <Link to={`/catalog?category=${cat.slug}`} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '16px' }}>
                    <span>{cat.name}</span>
                </Link>
            ),
        })),
    ];

    const serviceDropdownItems = dbServices.length > 0 ? (
        dbServices.map((srv) => {
            return {
                key: `service-${srv.id}`,
                label: (
                    <Link to={`/products/${srv.slug}`} style={{ display: 'block', padding: '6px 4px' }}>
                        <div>
                            <div style={{ fontWeight: 700, color: '#f8fafc', fontSize: '14px' }}>
                                {srv.name}
                            </div>
                            <div style={{ fontSize: '12px', color: '#94a3b8', maxWidth: '320px', whiteSpace: 'normal', lineHeight: 1.4 }}>
                                {srv.short_description || `Bảng giá từ ${Number(srv.min_price || 0).toLocaleString('vi-VN')} đ`}
                            </div>
                        </div>
                    </Link>
                ),
            };
        })
    ) : [
        {
            key: 'service-empty',
            label: <span style={{ color: '#94a3b8', padding: '6px' }}>Đang tải danh sách dịch vụ...</span>,
        },
    ];

    const userMenuItems = [
        {
            key: 'profile',
            label: <Link to="/profile">Thông tin tài khoản</Link>,
            icon: <UserOutlined />,
        },
        {
            key: 'orders',
            label: <Link to="/orders">Lịch sử đơn hàng</Link>,
            icon: <HistoryOutlined />,
        },
        {
            key: 'warranty',
            label: <Link to="/warranty-claims">Yêu cầu bảo hành</Link>,
            icon: <SafetyCertificateOutlined />,
        },
        {
            type: 'divider',
        },
        {
            key: 'logout',
            label: 'Đăng xuất',
            icon: <LogoutOutlined />,
            danger: true,
            onClick: handleLogout,
        },
    ];

    return (
        <Layout style={{ minHeight: '100vh', background: '#0a0a14' }}>
            <div style={{
                background: 'linear-gradient(90deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%)',
                color: '#ffffff',
                textAlign: 'center',
                padding: '6px 16px',
                fontSize: '13px',
                fontWeight: '600',
                letterSpacing: '0.3px',
            }}>
                <Space size="middle">
                    <span>Chào mừng đến với hệ thống {siteName} - Mua bán tài khoản & dịch vụ game số 1</span>
                    {siteSettings?.general_settings?.hotline && (
                        <>
                            <span>•</span>
                            <span>Hotline CSKH: <strong>{siteSettings.general_settings.hotline}</strong></span>
                        </>
                    )}
                </Space>
            </div>

            <Header style={{
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
                lineHeight: 'normal',
            }}>
                <Link to="/" style={{ display: 'flex', alignItems: 'center', gap: '12px', textDecoration: 'none', lineHeight: 'normal' }}>
                    <div style={{
                        width: '42px',
                        height: '42px',
                        borderRadius: '10px',
                        background: 'linear-gradient(135deg, #6366f1, #06b6d4)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        boxShadow: '0 0 15px rgba(99, 102, 241, 0.5)',
                        flexShrink: 0,
                    }}>
                        <ThunderboltOutlined style={{ fontSize: '22px', color: '#fff' }} />
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'center', lineHeight: 'normal' }}>
                        <div style={{ fontFamily: 'Rajdhani', fontSize: '22px', fontWeight: 800, color: '#fff', lineHeight: 1.1, letterSpacing: '0.5px' }}>
                            {siteName}
                        </div>
                        <div style={{ fontSize: '10px', color: '#06b6d4', letterSpacing: '1px', textTransform: 'uppercase', fontWeight: 700, lineHeight: 1.2, marginTop: '2px' }}>
                            Shop Game Uy Tín
                        </div>
                    </div>
                </Link>

                <div style={{ display: 'flex', alignItems: 'center', gap: '24px' }}>
                    <Link to="/" style={{
                        color: location.pathname === '/' ? '#6366f1' : '#cbd5e1',
                        fontWeight: 600,
                        fontSize: '15px',
                        transition: 'color 0.2s',
                    }}>
                        Trang Chủ
                    </Link>

                    <Dropdown menu={{ items: categoryDropdownItems }} placement="bottomLeft">
                        <span style={{
                            color: location.pathname.startsWith('/catalog') && !location.search.includes('cay-thue') ? '#6366f1' : '#cbd5e1',
                            fontWeight: 600,
                            fontSize: '15px',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px',
                        }}>
                            <AppstoreOutlined /> Danh Mục <DownOutlined style={{ fontSize: '11px' }} />
                        </span>
                    </Dropdown>

                    <Link to="/catalog" style={{
                        color: location.pathname === '/catalog' && !location.search ? '#6366f1' : '#cbd5e1',
                        fontWeight: 600,
                        fontSize: '15px',
                    }}>
                        Kho Nick Game
                    </Link>

                    <Dropdown menu={{ items: serviceDropdownItems }} placement="bottomLeft" trigger={['click', 'hover']}>
                        <span style={{
                            color: location.pathname.includes('cay-thue') || location.search.includes('cay-thue') || dbServices.some((s) => location.pathname === `/products/${s.slug}`) ? '#f43f5e' : '#cbd5e1',
                            fontWeight: 600,
                            fontSize: '15px',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px',
                            transition: 'color 0.2s',
                        }}>
                            <FireOutlined style={{ color: '#f43f5e' }} /> Dịch Vụ Cày Thuê <DownOutlined style={{ fontSize: '11px' }} />
                        </span>
                    </Dropdown>

                    <Link to="/warranty-claims" style={{
                        color: location.pathname.startsWith('/warranty') ? '#10b981' : '#cbd5e1',
                        fontWeight: 600,
                        fontSize: '15px',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                    }}>
                        <SafetyCertificateOutlined style={{ color: '#10b981' }} /> Chính Sách Bảo Hành
                    </Link>
                </div>

                <div style={{ display: 'flex', alignItems: 'center', gap: '18px' }}>
                    <Link to="/cart">
                        <Badge count={cartItemCount} overflowCount={99} color="#6366f1">
                            <Button
                                type="text"
                                shape="circle"
                                icon={<ShoppingCartOutlined style={{ fontSize: '20px', color: '#f8fafc' }} />}
                                style={{ width: '42px', height: '42px', background: 'rgba(255, 255, 255, 0.05)' }}
                            />
                        </Badge>
                    </Link>

                    {isAuthenticated && user ? (
                        <Dropdown menu={{ items: userMenuItems }} placement="bottomRight" arrow>
                            <div style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: '10px',
                                cursor: 'pointer',
                                padding: '6px 12px',
                                borderRadius: '20px',
                                background: 'rgba(255, 255, 255, 0.05)',
                                border: '1px solid rgba(255, 255, 255, 0.1)',
                            }}>
                                <Avatar style={{ backgroundColor: '#6366f1' }} icon={<UserOutlined />} src={user.avatar_url} />
                                <div style={{ textAlign: 'left', lineHeight: 1.2 }}>
                                    <div style={{ color: '#fff', fontSize: '13px', fontWeight: 600 }}>{user.full_name || user.email}</div>
                                    <div style={{ color: '#10b981', fontSize: '11px' }}>Thành viên</div>
                                </div>
                            </div>
                        </Dropdown>
                    ) : (
                        <Space>
                            <Link to="/login">
                                <Button type="text" style={{ color: '#cbd5e1', fontWeight: 600 }}>Đăng Nhập</Button>
                            </Link>
                            <Link to="/register">
                                <Button type="primary" style={{ fontWeight: 600, borderRadius: '8px' }}>Đăng Ký</Button>
                            </Link>
                        </Space>
                    )}
                </div>
            </Header>

            <Content style={{ minHeight: 'calc(100vh - 70px - 260px)' }}>
                <Outlet />
            </Content>

            <Footer style={{
                background: '#0d0e1c',
                color: '#94a3b8',
                borderTop: '1px solid rgba(255, 255, 255, 0.06)',
                padding: '48px 32px 24px',
            }}>
                <div style={{
                    maxWidth: '1200px',
                    margin: '0 auto',
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
                    gap: '32px',
                    marginBottom: '40px',
                }}>
                    <div>
                        <div style={{ fontFamily: 'Rajdhani', fontSize: '24px', fontWeight: 800, color: '#fff', marginBottom: '12px' }}>
                            {siteName}
                        </div>
                        <p style={{ fontSize: '14px', lineHeight: 1.6, color: '#94a3b8' }}>
                            Chuyên trang mua bán tài khoản game & dịch vụ cày thuê uy tín hàng đầu Việt Nam. Bàn giao tài khoản tự động 24/7 tức thì, bảo mật tuyệt đối, bảo hành 100% an toàn.
                        </p>
                    </div>

                    <div>
                        <h4 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }}>
                            Dịch Vụ Cày Thuê
                        </h4>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }}>
                            {dbServices.length > 0 ? (
                                dbServices.map((s) => (
                                    <Link key={s.id} to={`/products/${s.slug}`} style={{ color: '#94a3b8', transition: 'color 0.2s' }}>
                                        {s.name}
                                    </Link>
                                ))
                            ) : (
                                <span style={{ color: '#64748b' }}>Đang tải dịch vụ...</span>
                            )}
                        </div>
                    </div>

                    <div>
                        <h4 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }}>
                            Danh Mục Sản Phẩm
                        </h4>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }}>
                            {dbCategories.length > 0 ? (
                                dbCategories.slice(0, 5).map((c) => (
                                    <Link key={c.id} to={`/catalog?category=${c.slug}`} style={{ color: '#94a3b8', transition: 'color 0.2s' }}>
                                        {c.name}
                                    </Link>
                                ))
                            ) : (
                                <span style={{ color: '#64748b' }}>Đang tải danh mục...</span>
                            )}
                        </div>
                    </div>

                    <div>
                        <h4 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }}>
                            Chính Sách & Hỗ Trợ
                        </h4>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }}>
                            <Link to="/warranty-claims" style={{ color: '#94a3b8' }}>Chính sách bảo hành & Đổi trả</Link>
                            <span style={{ color: '#94a3b8' }}>Hướng dẫn mua hàng & Thanh toán</span>
                            <span style={{ color: '#94a3b8' }}>Điều khoản sử dụng dịch vụ</span>
                            <span style={{ color: '#94a3b8' }}>Bảo mật thông tin khách hàng</span>
                        </div>
                    </div>

                    <div>
                        <h4 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }}>
                            Liên Hệ Hỗ Trợ 24/7
                        </h4>
                        <div style={{ fontSize: '14px', lineHeight: 1.8, color: '#94a3b8' }}>
                            <div>Hotline: <strong style={{ color: '#fff' }}>{siteSettings?.general_settings?.hotline || '1900 xxxx'}</strong></div>
                            <div>Zalo CSKH: <strong style={{ color: '#fff' }}>{siteSettings?.general_settings?.zalo || '09xxxxxxxx'}</strong></div>
                            <div>Telegram: <span style={{ color: '#06b6d4' }}>{siteSettings?.general_settings?.telegram || '@cskh'}</span></div>
                            <div>Thời gian hỗ trợ: 24/7 kể cả ngày lễ Tết</div>
                        </div>
                    </div>
                </div>

                <div style={{
                    borderTop: '1px solid rgba(255, 255, 255, 0.06)',
                    paddingTop: '20px',
                    textAlign: 'center',
                    fontSize: '13px',
                    color: '#64748b',
                }}>
                    © 2026 {siteName}. All rights reserved. Hệ thống thương mại điện tử chuyên nghiệp cho cộng đồng game thủ.
                </div>
            </Footer>
        </Layout>
    );
};

export default UserLayout;
