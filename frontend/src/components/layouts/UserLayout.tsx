import React, { useEffect, useState } from 'react';
import { Outlet, Link, useNavigate, useLocation } from 'react-router-dom';
import { Layout, Menu, Button, Badge, Dropdown, Avatar, Space, Tag } from 'antd';
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
import api from '../../services/api';

const { Header, Content, Footer } = Layout;

const UserLayout: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user, isAuthenticated, logout } = useAuthStore();
  const { items } = useCartStore();

  const [dbCategories, setDbCategories] = useState<any[]>([]);
  const [dbGames, setDbGames] = useState<any[]>([]);
  const [siteSettings, setSiteSettings] = useState<any>(null);

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
      } catch (err) {
        console.error('Failed to load navigation categories/games from DB:', err);
      }
    };
    fetchNavData();
  }, []);

  const cartItemCount = items ? items.reduce((acc, i) => acc + i.quantity, 0) : 0;

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const categoryDropdownItems = [
    {
      key: 'all-catalog',
      label: (
        <Link to="/catalog" style={{ fontWeight: 700, color: '#6366f1' }}>
          ✨ Xem Toàn Bộ Danh Mục & Nick
        </Link>
      ),
    },
    {
      type: 'divider' as const,
    },
    ...dbCategories.map((cat) => ({
      key: `cat-${cat.id}`,
      label: (
        <Link
          to={`/catalog?category=${cat.slug}`}
          style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '16px' }}
        >
          <span>{cat.name}</span>
          {cat.game && (
            <Tag color="cyan" style={{ fontSize: '11px', margin: 0 }}>
              {cat.game.name}
            </Tag>
          )}
        </Link>
      ),
    })),
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
      type: 'divider' as const,
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
      {/* Top Banner Notice */}
      <div
        style={{
          background: 'linear-gradient(90deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%)',
          color: '#ffffff',
          textAlign: 'center',
          padding: '6px 16px',
          fontSize: '13px',
          fontWeight: '600',
          letterSpacing: '0.3px',
        }}
      >
        <Space size="middle">
          <span>🔥 SIÊU SALE HÈ: Nhập mã <strong>CHAOBANMOI</strong> giảm ngay 10% cho mọi đơn hàng!</span>
          <span>•</span>
          <span>⚡ Nhận tài khoản tức thì sau 3 giây thanh toán 24/7</span>
          {siteSettings?.general_settings?.hotline && (
            <>
              <span>•</span>
              <span>📞 Hotline CSKH: <strong>{siteSettings.general_settings.hotline}</strong></span>
            </>
          )}
        </Space>
      </div>

      {/* Main Navbar */}
      <Header
        style={{
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
        }}
      >
        {/* Brand Logo */}
        <Link to="/" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div
            style={{
              width: '40px',
              height: '40px',
              borderRadius: '10px',
              background: 'linear-gradient(135deg, #f59e0b, #ef4444)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              boxShadow: '0 0 15px rgba(239, 68, 68, 0.5)',
            }}
          >
            <ThunderboltOutlined style={{ fontSize: '22px', color: '#fff' }} />
          </div>
          <div>
            <div style={{ fontFamily: 'Rajdhani, sans-serif', fontSize: '20px', fontWeight: 800, color: '#fff', letterSpacing: '0.5px', lineHeight: 1.1 }}>
              SHOPACC<span style={{ color: '#06b6d4' }}>LIENQUAN</span>.VN
            </div>
            <div style={{ fontSize: '10px', color: '#f59e0b', letterSpacing: '1px', textTransform: 'uppercase', fontWeight: 700 }}>
              Chuyên Acc Liên Quân Số 1 VN
            </div>
          </div>
        </Link>

        {/* Navigation Links */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '24px' }}>
          <Link
            to="/"
            style={{
              color: location.pathname === '/' ? '#6366f1' : '#cbd5e1',
              fontWeight: 600,
              fontSize: '15px',
              transition: 'color 0.2s',
            }}
          >
            Trang Chủ
          </Link>

          {/* Dynamic Categories Dropdown from DB */}
          <Dropdown menu={{ items: categoryDropdownItems }} placement="bottomLeft">
            <span
              style={{
                color: location.pathname.startsWith('/catalog') ? '#6366f1' : '#cbd5e1',
                fontWeight: 600,
                fontSize: '15px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
              }}
            >
              <AppstoreOutlined />
              Danh Mục <DownOutlined style={{ fontSize: '11px' }} />
            </span>
          </Dropdown>

          <Link
            to="/catalog"
            style={{
              color: location.pathname === '/catalog' && !location.search ? '#6366f1' : '#cbd5e1',
              fontWeight: 600,
              fontSize: '15px',
            }}
          >
            Kho Nick Game
          </Link>
          <Link
            to="/catalog?type=service"
            style={{
              color: location.search.includes('type=service') ? '#f43f5e' : '#cbd5e1',
              fontWeight: 600,
              fontSize: '15px',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
            }}
          >
            <FireOutlined style={{ color: '#f43f5e' }} />
            Dịch Vụ Cày Thuê
          </Link>
          <Link
            to="/warranty-claims"
            style={{
              color: location.pathname.startsWith('/warranty') ? '#10b981' : '#cbd5e1',
              fontWeight: 600,
              fontSize: '15px',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
            }}
          >
            <SafetyCertificateOutlined style={{ color: '#10b981' }} />
            Chính Sách Bảo Hành
          </Link>
        </div>

        {/* User Actions */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '18px' }}>
          {/* Cart Icon */}
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
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '10px',
                  cursor: 'pointer',
                  padding: '6px 12px',
                  borderRadius: '20px',
                  background: 'rgba(255, 255, 255, 0.05)',
                  border: '1px solid rgba(255, 255, 255, 0.1)',
                }}
              >
                <Avatar
                  style={{ backgroundColor: '#6366f1' }}
                  icon={<UserOutlined />}
                  src={user.avatar_url}
                />
                <div style={{ textAlign: 'left', lineHeight: 1.2 }}>
                  <div style={{ color: '#fff', fontSize: '13px', fontWeight: 600 }}>
                    {user.full_name || user.email}
                  </div>
                  <div style={{ color: '#10b981', fontSize: '11px' }}>
                    Thành viên
                  </div>
                </div>
              </div>
            </Dropdown>
          ) : (
            <Space>
              <Link to="/login">
                <Button type="text" style={{ color: '#cbd5e1', fontWeight: 600 }}>
                  Đăng Nhập
                </Button>
              </Link>
              <Link to="/register">
                <Button type="primary" style={{ fontWeight: 600, borderRadius: '8px' }}>
                  Đăng Ký
                </Button>
              </Link>
            </Space>
          )}
        </div>
      </Header>

      {/* Main Page Body */}
      <Content style={{ minHeight: 'calc(100vh - 70px - 260px)' }}>
        <Outlet />
      </Content>

      {/* Professional Footer */}
      <Footer
        style={{
          background: '#0d0e1c',
          color: '#94a3b8',
          borderTop: '1px solid rgba(255, 255, 255, 0.06)',
          padding: '48px 32px 24px',
        }}
      >
        <div
          style={{
            maxWidth: '1200px',
            margin: '0 auto',
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
            gap: '32px',
            marginBottom: '40px',
          }}
        >
          <div>
            <div style={{ fontFamily: 'Rajdhani', fontSize: '24px', fontWeight: 800, color: '#fff', marginBottom: '12px' }}>
              SHOPACC<span style={{ color: '#06b6d4' }}>LIENQUAN</span>.VN
            </div>
            <p style={{ fontSize: '14px', lineHeight: 1.6, color: '#94a3b8' }}>
              Chuyên trang mua bán tài khoản Liên Quân Mobile & dịch vụ cày thuê uy tín số 1 Việt Nam. Bàn giao tài khoản tự động 24/7 tức thì, mã hoá bảo mật tuyệt đối, bảo hành 100% không back.
            </p>
          </div>

          {/* Dynamic Games from DB */}
          <div>
            <h4 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }}>
              Trò Chơi (DB)
            </h4>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', fontSize: '14px' }}>
              {dbGames.length > 0 ? (
                dbGames.map((g) => (
                  <Link key={g.id} to={`/catalog?game=${g.slug}`} style={{ color: '#94a3b8', transition: 'color 0.2s' }}>
                    {g.name}
                  </Link>
                ))
              ) : (
                <span style={{ color: '#64748b' }}>Đang tải trò chơi...</span>
              )}
            </div>
          </div>

          {/* Dynamic Categories from DB */}
          <div>
            <h4 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '16px' }}>
              Danh Mục Sản Phẩm (DB)
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
              Liên Hệ Hỗ Trợ 24/7 (DB)
            </h4>
            <div style={{ fontSize: '14px', lineHeight: 1.8, color: '#94a3b8' }}>
              <div>📞 Hotline: <strong style={{ color: '#fff' }}>{siteSettings?.general_settings?.hotline || '0987.654.321'}</strong></div>
              <div>💬 Zalo CSKH: <strong style={{ color: '#fff' }}>{siteSettings?.general_settings?.zalo || '0987654321'}</strong></div>
              <div>✈️ Telegram: <span style={{ color: '#06b6d4' }}>{siteSettings?.general_settings?.telegram || '@gamehub_support'}</span></div>
              <div>⏰ Thời gian hỗ trợ: 24/7 kể cả ngày lễ Tết</div>
            </div>
          </div>
        </div>

        <div
          style={{
            borderTop: '1px solid rgba(255, 255, 255, 0.06)',
            paddingTop: '20px',
            textAlign: 'center',
            fontSize: '13px',
            color: '#64748b',
          }}
        >
          © 2026 SHOPACCLIENQUAN.VN. All rights reserved. Hệ thống thương mại điện tử chuyên nghiệp cho cộng đồng game thủ Liên Quân Mobile.
        </div>
      </Footer>
    </Layout>
  );
};

export default UserLayout;
