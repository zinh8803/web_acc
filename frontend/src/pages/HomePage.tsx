import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Button, Card, Row, Col, Tag, Spin } from 'antd';
import {
  ThunderboltOutlined,
  SafetyCertificateOutlined,
  ClockCircleOutlined,
  FireOutlined,
  RightOutlined,
} from '@ant-design/icons';
import api from '../services/api';

const HomePage: React.FC = () => {
  const navigate = useNavigate();
  const [games, setGames] = useState<any[]>([]);
  const [categories, setCategories] = useState<any[]>([]);
  const [banners, setBanners] = useState<any[]>([]);
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [gamesRes, catsRes, prodsRes, bannersRes] = await Promise.all([
          api.get('/games'),
          api.get('/categories'),
          api.get('/products?per_page=8'),
          api.get('/banners'),
        ]);
        if (gamesRes.data?.data) setGames(gamesRes.data.data);
        if (catsRes.data?.data) setCategories(catsRes.data.data);
        if (bannersRes.data?.data && Array.isArray(bannersRes.data.data)) {
          setBanners(bannersRes.data.data);
        }
        if (prodsRes.data?.data) {
          // handles both array or paginated data
          const list = prodsRes.data.data.data || prodsRes.data.data;
          setProducts(Array.isArray(list) ? list : []);
        }
      } catch (err) {
        console.error('Failed to fetch home data', err);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  return (
    <div style={{ maxWidth: '1280px', margin: '0 auto', padding: '24px 20px 60px' }}>
      {/* Hero Banner Section */}
      <div
        style={{
          borderRadius: '16px',
          overflow: 'hidden',
          marginBottom: '40px',
          boxShadow: '0 20px 40px rgba(0, 0, 0, 0.6)',
          border: '1px solid rgba(255, 255, 255, 0.08)',
          background: 'linear-gradient(135deg, #1e1b4b 0%, #0f172a 50%, #172554 100%)',
          position: 'relative',
          padding: '60px 48px',
        }}
      >
        <div style={{ maxWidth: '640px', position: 'relative', zIndex: 2 }}>
          <Tag color="cyan" style={{ fontSize: '13px', padding: '4px 12px', borderRadius: '20px', marginBottom: '16px', fontWeight: 600 }}>
            ⚡ {banners.length > 0 ? banners[0].title : 'BÀN GIAO TỰ ĐỘNG SAU 3 GIÂY • BẢO HÀNH TRỌN ĐỜI 100%'}
          </Tag>
          <h1
            style={{
              fontSize: '44px',
              fontWeight: 800,
              lineHeight: 1.15,
              color: '#ffffff',
              marginBottom: '16px',
              fontFamily: 'Rajdhani, sans-serif',
            }}
          >
            SHOP ACC <span style={{ color: '#06b6d4' }}>LIÊN QUÂN</span> & <span style={{ color: '#a855f7' }}>CÀY THUÊ RANK</span> UY TÍN
          </h1>
          <p style={{ fontSize: '16px', color: '#94a3b8', lineHeight: 1.6, marginBottom: '28px' }}>
            {banners.length > 1 ? banners[1].title : 'Hàng ngàn nick Liên Quân VIP Thách Đấu, skin SSS, Thứ Nguyên Vệ Thần, Anime Kimetsu No Yaiba, trắng thông tin 100%, bàn giao tự động nhận tài khoản ngay sau khi thanh toán.'}
          </p>
          <div style={{ display: 'flex', gap: '16px' }}>
            <Button
              type="primary"
              size="large"
              icon={<ThunderboltOutlined />}
              onClick={() => navigate('/catalog')}
              style={{
                height: '48px',
                padding: '0 28px',
                fontSize: '16px',
                fontWeight: 700,
                borderRadius: '8px',
              }}
            >
              Khám Phá Kho Acc Liên Quân
            </Button>
            <Button
              size="large"
              onClick={() => navigate('/catalog?type=service')}
              style={{
                height: '48px',
                padding: '0 24px',
                fontSize: '16px',
                color: '#fff',
                borderColor: 'rgba(255, 255, 255, 0.2)',
                background: 'rgba(255, 255, 255, 0.05)',
                borderRadius: '8px',
              }}
            >
              Cày Thuê Rank Liên Quân
            </Button>
          </div>
        </div>
      </div>

      {/* Trust Badges / Stats Bar */}
      <Row gutter={[24, 24]} style={{ marginBottom: '50px' }}>
        {[
          {
            icon: <ThunderboltOutlined style={{ fontSize: '28px', color: '#06b6d4' }} />,
            title: 'Giao dịch Tự Động 24/7',
            desc: 'Nhận tài khoản ngay lập tức qua màn hình và email sau khi thanh toán',
          },
          {
            icon: <SafetyCertificateOutlined style={{ fontSize: '28px', color: '#10b981' }} />,
            title: 'Bảo Hành 100% Không Back',
            desc: 'Đền bù tài khoản tương đương hoặc hoàn tiền 100% nếu phát sinh lỗi',
          },
          {
            icon: <ClockCircleOutlined style={{ fontSize: '28px', color: '#f59e0b' }} />,
            title: 'Hỗ Trợ Nhanh Chóng 24/7',
            desc: 'Đội ngũ tư vấn viên túc trực giải quyết mọi thắc mắc của bạn',
          },
          {
            icon: <FireOutlined style={{ fontSize: '28px', color: '#f43f5e' }} />,
            title: 'Mức Giá Tốt Nhất Thị Trường',
            desc: 'Hàng ngàn ưu đãi flash-sale và mã coupon giảm giá mỗi ngày',
          },
        ].map((item, idx) => (
          <Col xs={24} sm={12} md={6} key={idx}>
            <div
              style={{
                background: '#121324',
                padding: '24px',
                borderRadius: '12px',
                border: '1px solid rgba(255, 255, 255, 0.06)',
                height: '100%',
              }}
            >
              <div style={{ marginBottom: '14px' }}>{item.icon}</div>
              <h4 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '6px' }}>{item.title}</h4>
              <p style={{ color: '#94a3b8', fontSize: '13px', lineHeight: 1.5, margin: 0 }}>{item.desc}</p>
            </div>
          </Col>
        ))}
      </Row>

      {/* Game Selector */}
      <div style={{ marginBottom: '50px' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
          <div>
            <h2 style={{ fontSize: '26px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', margin: 0 }}>
              TRÒ CHƠI PHỔ BIẾN (LIÊN QUÂN MOBILE) ⚔️
            </h2>
            <p style={{ color: '#94a3b8', fontSize: '14px', margin: 0 }}>Hệ sinh thái Đấu Trường Danh Vọng & Máy Chủ Thử Nghiệm Liên Quân</p>
          </div>
          <Link to="/catalog" style={{ color: '#6366f1', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '4px' }}>
            Xem tất cả <RightOutlined />
          </Link>
        </div>

        <Row gutter={[16, 16]}>
          {games.map((g) => (
            <Col xs={12} sm={8} md={4} key={g.id}>
              <div
                onClick={() => navigate(`/catalog?game=${g.slug}`)}
                style={{
                  background: '#121324',
                  borderRadius: '12px',
                  padding: '16px 12px',
                  textAlign: 'center',
                  cursor: 'pointer',
                  border: '1px solid rgba(255, 255, 255, 0.08)',
                  transition: 'all 0.2s',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.borderColor = '#6366f1';
                  e.currentTarget.style.transform = 'translateY(-4px)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.borderColor = 'rgba(255, 255, 255, 0.08)';
                  e.currentTarget.style.transform = 'translateY(0)';
                }}
              >
                <img
                  src={g.logo_url || 'https://via.placeholder.com/80'}
                  alt={g.name}
                  style={{ width: '60px', height: '60px', borderRadius: '12px', objectFit: 'cover', marginBottom: '10px' }}
                />
                <div style={{ color: '#fff', fontSize: '14px', fontWeight: 700, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                  {g.name}
                </div>
              </div>
            </Col>
          ))}
        </Row>
      </div>

      {/* Dynamic Categories Section */}
      <div style={{ marginBottom: '50px' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
          <div>
            <h2 style={{ fontSize: '26px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', margin: 0 }}>
              DANH MỤC ACC & DỊCH VỤ LIÊN QUÂN 🗂️
            </h2>
            <p style={{ color: '#94a3b8', fontSize: '14px', margin: 0 }}>
              Phân loại nick Liên Quân VIP, giá rẻ, skin anime độc quyền và cày thuê siêu tốc
            </p>
          </div>
          <Link to="/catalog" style={{ color: '#6366f1', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '4px' }}>
            Khám phá tất cả <RightOutlined />
          </Link>
        </div>

        <Row gutter={[16, 16]}>
          {categories.map((cat) => (
            <Col xs={24} sm={12} md={8} lg={6} key={cat.id}>
              <div
                onClick={() => navigate(`/catalog?category=${cat.slug}`)}
                style={{
                  background: 'linear-gradient(145deg, #121324 0%, #17182f 100%)',
                  borderRadius: '12px',
                  padding: '18px 20px',
                  cursor: 'pointer',
                  border: '1px solid rgba(255, 255, 255, 0.08)',
                  transition: 'all 0.25s ease',
                  height: '100%',
                  display: 'flex',
                  flexDirection: 'column',
                  justifyContent: 'space-between',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.borderColor = '#6366f1';
                  e.currentTarget.style.transform = 'translateY(-4px)';
                  e.currentTarget.style.boxShadow = '0 10px 25px rgba(99, 102, 241, 0.2)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.borderColor = 'rgba(255, 255, 255, 0.08)';
                  e.currentTarget.style.transform = 'translateY(0)';
                  e.currentTarget.style.boxShadow = 'none';
                }}
              >
                <div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '10px' }}>
                    <Tag color={cat.type === 'SERVICE' ? 'magenta' : 'geekblue'} style={{ fontWeight: 600 }}>
                      {cat.type === 'SERVICE' ? 'DỊCH VỤ' : 'TÀI KHOẢN'}
                    </Tag>
                    {cat.game && (
                      <span style={{ fontSize: '12px', color: '#06b6d4', fontWeight: 600 }}>
                        {cat.game.name}
                      </span>
                    )}
                  </div>
                  <h3 style={{ color: '#fff', fontSize: '15px', fontWeight: 700, marginBottom: '8px', lineHeight: 1.4 }}>
                    {cat.name}
                  </h3>
                  <p style={{ color: '#94a3b8', fontSize: '12px', margin: 0, lineHeight: 1.5, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                    {cat.description || 'Khám phá ngay các tài khoản và gói dịch vụ chất lượng cao.'}
                  </p>
                </div>
                <div style={{ marginTop: '14px', display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: '4px', color: '#6366f1', fontSize: '13px', fontWeight: 600 }}>
                  Xem danh mục <RightOutlined style={{ fontSize: '10px' }} />
                </div>
              </div>
            </Col>
          ))}
        </Row>
      </div>

      {/* Featured Accounts Grid */}
      <div style={{ marginBottom: '50px' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
          <div>
            <h2 style={{ fontSize: '26px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', margin: 0 }}>
              NICK LIÊN QUÂN NỔI BẬT ĐANG BÁN 🔥
            </h2>
            <p style={{ color: '#94a3b8', fontSize: '14px', margin: 0 }}>Tài khoản Liên Quân tuyển chọn chất lượng cao - Trắng TT 100%</p>
          </div>
          <Link to="/catalog" style={{ color: '#6366f1', fontWeight: 600 }}>
            Xem toàn bộ kho hàng <RightOutlined />
          </Link>
        </div>

        {loading ? (
          <div style={{ textAlign: 'center', padding: '60px 0' }}><Spin size="large" /></div>
        ) : (
          <Row gutter={[20, 24]}>
            {products.map((prod) => (
              <Col xs={24} sm={12} md={6} key={prod.id}>
                <Card
                  hoverable
                  onClick={() => navigate(`/products/${prod.slug}`)}
                  style={{
                    background: '#121324',
                    borderRadius: '12px',
                    border: '1px solid rgba(255, 255, 255, 0.08)',
                    overflow: 'hidden',
                    height: '100%',
                    display: 'flex',
                    flexDirection: 'column',
                  }}
                  bodyStyle={{ padding: '16px', flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}
                  cover={
                    <div style={{ height: '170px', overflow: 'hidden', position: 'relative' }}>
                      <img
                        alt={prod.name}
                        src={prod.thumbnail_url || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600'}
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                      <Tag
                        color="indigo"
                        style={{ position: 'absolute', top: '10px', left: '10px', fontWeight: 600 }}
                      >
                        {prod.sku}
                      </Tag>
                    </div>
                  }
                >
                  <div>
                    <h3
                      style={{
                        color: '#f8fafc',
                        fontSize: '15px',
                        fontWeight: 700,
                        lineHeight: 1.4,
                        marginBottom: '8px',
                        display: '-webkit-box',
                        WebkitLineClamp: 2,
                        WebkitBoxOrient: 'vertical',
                        overflow: 'hidden',
                        height: '42px',
                      }}
                    >
                      {prod.name}
                    </h3>
                    <p style={{ color: '#94a3b8', fontSize: '12px', marginBottom: '14px', height: '36px', overflow: 'hidden' }}>
                      {prod.short_description}
                    </p>
                  </div>

                  <div>
                    <div style={{ display: 'flex', alignItems: 'baseline', gap: '8px', marginBottom: '14px' }}>
                      <span style={{ color: '#06b6d4', fontSize: '20px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                        {Number(prod.min_price || 0).toLocaleString('vi-VN')} đ
                      </span>
                    </div>

                    <Button
                      type="primary"
                      block
                      style={{ borderRadius: '6px', fontWeight: 700 }}
                    >
                      Xem & Mua Ngay
                    </Button>
                  </div>
                </Card>
              </Col>
            ))}
          </Row>
        )}
      </div>
    </div>
  );
};

export default HomePage;
