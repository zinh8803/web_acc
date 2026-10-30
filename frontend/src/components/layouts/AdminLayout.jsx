import React from 'react';
import { Outlet, Link, useNavigate, useLocation } from 'react-router-dom';
import { Layout, Menu, Button, Space, Tag } from 'antd';
import {
    DashboardOutlined,
    AppstoreOutlined,
    ShoppingOutlined,
    KeyOutlined,
    OrderedListOutlined,
    CustomerServiceOutlined,
    SafetyCertificateOutlined,
    TagOutlined,
    UserOutlined,
    TeamOutlined,
    FileTextOutlined,
    BarChartOutlined,
    SettingOutlined,
    LogoutOutlined,
    HomeOutlined,
} from '@ant-design/icons';
import api from '../../services/api';

const { Header, Sider, Content } = Layout;

const AdminLayout = () => {
    const navigate = useNavigate();
    const location = useLocation();

    const handleLogout = async () => {
        try {
            await api.post('/admin/auth/logout');
        } catch (error) {
            // Clear local session regardless of server status
        } finally {
            localStorage.removeItem('admin_token');
            localStorage.removeItem('admin_access_token');
            localStorage.removeItem('admin_info');
            navigate('/login');
        }
    };

    const menuItems = [
        { key: '/admin', icon: <DashboardOutlined />, label: <Link to="/admin">Tổng quan Dashboard</Link> },
        { key: '/admin/categories', icon: <AppstoreOutlined />, label: <Link to="/admin/categories">Danh mục</Link> },
        { key: '/admin/games', icon: <AppstoreOutlined />, label: <Link to="/admin/games">Quản lý Game</Link> },
        { key: '/admin/products', icon: <ShoppingOutlined />, label: <Link to="/admin/products">Sản phẩm & Giá</Link> },
        { key: '/admin/inventory', icon: <KeyOutlined />, label: <Link to="/admin/inventory">Kho Tài Khoản Game</Link> },
        { key: '/admin/orders', icon: <OrderedListOutlined />, label: <Link to="/admin/orders">Đơn hàng & Giao dịch</Link> },
        { key: '/admin/service-requests', icon: <CustomerServiceOutlined />, label: <Link to="/admin/service-requests">Yêu cầu Cày thuê</Link> },
        { key: '/admin/warranty', icon: <SafetyCertificateOutlined />, label: <Link to="/admin/warranty">Khiếu nại Bảo hành</Link> },
        { key: '/admin/coupons', icon: <TagOutlined />, label: <Link to="/admin/coupons">Mã giảm giá (Coupon)</Link> },
        { key: '/admin/users', icon: <UserOutlined />, label: <Link to="/admin/users">Khách hàng</Link> },
        { key: '/admin/staff', icon: <TeamOutlined />, label: <Link to="/admin/staff">Nhân sự & Phân quyền</Link> },
        { key: '/admin/audit-logs', icon: <FileTextOutlined />, label: <Link to="/admin/audit-logs">Audit Logs</Link> },
        { key: '/admin/reports', icon: <BarChartOutlined />, label: <Link to="/admin/reports">Báo cáo Doanh thu</Link> },
        { key: '/admin/settings', icon: <SettingOutlined />, label: <Link to="/admin/settings">Cấu hình Hệ thống</Link> },
    ];

    return (
        <Layout style={{ minHeight: '100vh', background: '#0a0a14' }}>
            <Sider
                width={260}
                style={{
                    background: '#121324',
                    borderRight: '1px solid rgba(255, 255, 255, 0.08)',
                    position: 'sticky',
                    top: 0,
                    height: '100vh',
                    overflowY: 'auto',
                }}
            >
                <div style={{ padding: '20px 24px', borderBottom: '1px solid rgba(255, 255, 255, 0.08)' }}>
                    <div style={{ fontFamily: 'Rajdhani', fontSize: '18px', fontWeight: 800, color: '#fff' }}>
                        GAMEHUB <Tag color="purple">ADMIN</Tag>
                    </div>
                    <div style={{ fontSize: '11px', color: '#94a3b8', marginTop: '4px' }}>
                        Hệ Thống Quản Trị Trung Tâm
                    </div>
                </div>
                <Menu
                    mode="inline"
                    selectedKeys={[location.pathname]}
                    items={menuItems}
                    style={{ background: '#121324', marginTop: '8px' }}
                />
            </Sider>

            <Layout>
                <Header
                    style={{
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
                    }}
                >
                    <div style={{ color: '#cbd5e1', fontSize: '14px', fontWeight: 600 }}>
                        Quản trị viên: <Tag color="blue">Super Administrator</Tag>
                    </div>
                    <Space size="middle">
                        <Link to="/">
                            <Button icon={<HomeOutlined />} type="default" style={{ borderRadius: '6px' }}>
                                Xem Trang Khách
                            </Button>
                        </Link>
                        <Button icon={<LogoutOutlined />} danger onClick={handleLogout} style={{ borderRadius: '6px' }}>
                            Đăng xuất
                        </Button>
                    </Space>
                </Header>

                <Content style={{ padding: '24px 32px', minHeight: 'calc(100vh - 64px)' }}>
                    <Outlet />
                </Content>
            </Layout>
        </Layout>
    );
};

export default AdminLayout;
