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

const DEFAULT_BANNERS = [
    {
        id: 'default-1',
        title: 'Mừng Đại Lễ - Xả Kho Acc Game VIP Giảm Đến 50%',
        image_url: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1600&auto=format&fit=crop&q=80',
        link_url: '/catalog',
    },
    {
        id: 'default-2',
        title: 'Bảo Hành Trọn Đời - Đổi Trả Miễn Phí Trong 7 Ngày',
        image_url: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1600&auto=format&fit=crop&q=80',
        link_url: '/warranty',
    },
];

const HomePage = () => {
    const navigate = useNavigate();
    const [categories, setCategories] = useState([]);
    const [banners, setBanners] = useState([]);
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [activeBannerIdx, setActiveBannerIdx] = useState(0);

    const fetchBanners = async () => {
        try {
            const res = await api.get('/banners');
            if (res.data?.data && Array.isArray(res.data.data) && res.data.data.length > 0) {
                setBanners(res.data.data);
            } else {
                setBanners(DEFAULT_BANNERS);
            }
        } catch {
            setBanners(DEFAULT_BANNERS);
        }
    };

    useEffect(() => {
        const fetchData = async () => {
            try {
                const [catsRes, prodsRes] = await Promise.all([
                    api.get('/categories'),
                    api.get('/products', { params: { limit: 8, type: 'ACCOUNT' } }),
                ]);
                if (catsRes.data?.data) {
                    setCategories(catsRes.data.data);
                }
                if (prodsRes.data?.data) {
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
        fetchBanners();
    }, []);

    const displayBanners = banners.length > 0 ? banners : DEFAULT_BANNERS;

    // Chuyển slide banner tự động mỗi 5 giây
    useEffect(() => {
        if (displayBanners.length <= 1) return;
        const timer = setInterval(() => {
            setActiveBannerIdx((prev) => (prev + 1) % displayBanners.length);
        }, 5000);
        return () => clearInterval(timer);
    }, [displayBanners.length]);

    return (
        <div style={{ maxWidth: '1280px', margin: '0 auto', padding: '24px 20px 60px' }}>
            {/* HERO BANNER SECTION */}
            <div
                className="hero-banner-wrapper"
                style={{
                    position: 'relative',
                    borderRadius: '16px',
                    overflow: 'hidden',
                    marginBottom: '40px',
                    boxShadow: '0 20px 45px rgba(0, 0, 0, 0.55)',
                    border: '1px solid rgba(255, 255, 255, 0.15)',
                    minHeight: '430px',
                    display: 'flex',
                    alignItems: 'center',
                    background: '#090d16',
                }}
            >
                {/* 1. LỚP BANNER TRONG CÙNG (Background Images) */}
                {displayBanners.map((banner, index) => (
                    <div
                        key={banner.id || index}
                        style={{
                            position: 'absolute',
                            top: 0,
                            left: 0,
                            width: '100%',
                            height: '100%',
                            backgroundImage: `url(${banner.image_url})`,
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                            opacity: index === activeBannerIdx ? 1 : 0,
                            transition: 'opacity 0.8s ease-in-out',
                            zIndex: 0,
                        }}
                    />
                ))}

                {/* 2. LỚP GRADIENT OVERLAY LÀM RÕ NÉT CHỮ */}
                <div
                    style={{
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        background: 'linear-gradient(90deg, rgba(5, 7, 18, 0.93) 0%, rgba(8, 12, 28, 0.84) 52%, rgba(10, 16, 32, 0.48) 100%)',
                        zIndex: 1,
                        pointerEvents: 'none',
                    }}
                />

                {/* 3. LỚP NỘI DUNG ĐÈ LÊN BANNER */}
                <div
                    style={{
                        position: 'relative',
                        zIndex: 2,
                        padding: '50px 44px',
                        maxWidth: '720px',
                    }}
                >
                    {/* Tag cam kết */}
                    <Tag
                        className="hero-tag"
                        style={{
                            fontSize: '13px',
                            padding: '6px 14px',
                            borderRadius: '20px',
                            marginBottom: '16px',
                            fontWeight: 700,
                            letterSpacing: '0.5px',
                            border: '1px solid #06b6d4',
                            background: 'rgba(6, 182, 212, 0.2)',
                            color: '#22d3ee',
                            display: 'inline-block',
                        }}
                    >
                        BÀN GIAO TỰ ĐỘNG SAU 3 GIÂY • BẢO HÀNH TRỌN ĐỜI 100%
                    </Tag>

                    {/* Tiêu đề H1 sắc nét, màu trắng sáng */}
                    <h1
                        className="hero-banner-title"
                        style={{
                            fontSize: '44px',
                            fontWeight: 900,
                            lineHeight: 1.18,
                            color: '#ffffff',
                            marginBottom: '16px',
                            fontFamily: 'Rajdhani, sans-serif',
                            textTransform: 'uppercase',
                            textShadow: '0 2px 14px rgba(0, 0, 0, 0.8)',
                        }}
                    >
                        SHOP ACC <span style={{ color: '#22d3ee' }}>LIÊN QUÂN</span> &{' '}
                        <span style={{ color: '#c084fc' }}>CÀY THUÊ RANK</span> UY TÍN
                    </h1>

                    {/* Mô tả */}
                    <p
                        className="hero-banner-desc"
                        style={{
                            fontSize: '16px',
                            color: '#f8fafc',
                            lineHeight: 1.6,
                            marginBottom: '28px',
                            maxWidth: '640px',
                            textShadow: '0 2px 8px rgba(0, 0, 0, 0.9)',
                        }}
                    >
                        Hàng ngàn nick Liên Quân VIP Thách Đấu, skin SSS, Thứ Nguyên Vệ Thần, Anime Kimetsu No Yaiba,
                        trắng thông tin 100%, bàn giao tự động nhận tài khoản ngay sau khi thanh toán.
                    </p>

                    {/* Nút hành động */}
                    <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                        <Button
                            type="primary"
                            size="large"
                            icon={<ThunderboltOutlined />}
                            onClick={() => navigate('/catalog')}
                            style={{
                                height: '48px',
                                padding: '0 30px',
                                fontSize: '15px',
                                fontWeight: 700,
                                borderRadius: '8px',
                                background: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
                                boxShadow: '0 4px 16px rgba(99, 102, 241, 0.5)',
                                border: 'none',
                                color: '#ffffff',
                            }}
                        >
                            Khám Phá Kho Acc Liên Quân
                        </Button>
                        <Button
                            className="hero-btn-secondary"
                            size="large"
                            onClick={() => navigate('/catalog?type=service')}
                            style={{
                                height: '48px',
                                padding: '0 26px',
                                fontSize: '15px',
                                fontWeight: 700,
                                borderRadius: '8px',
                                color: '#ffffff',
                                backgroundColor: 'rgba(15, 23, 42, 0.85)',
                                borderColor: 'rgba(255, 255, 255, 0.4)',
                                backdropFilter: 'blur(8px)',
                            }}
                        >
                            Cày Thuê Rank Liên Quân
                        </Button>
                    </div>

                    {/* Dot indicators chuyển đổi banner */}
                    {displayBanners.length > 1 && (
                        <div style={{ display: 'flex', gap: '8px', marginTop: '24px' }}>
                            {displayBanners.map((_, idx) => (
                                <button
                                    key={idx}
                                    type="button"
                                    onClick={() => setActiveBannerIdx(idx)}
                                    style={{
                                        width: idx === activeBannerIdx ? '28px' : '10px',
                                        height: '6px',
                                        borderRadius: '3px',
                                        background: idx === activeBannerIdx ? '#22d3ee' : 'rgba(255, 255, 255, 0.3)',
                                        border: 'none',
                                        cursor: 'pointer',
                                        transition: 'all 0.3s ease',
                                        padding: 0,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* DỊCH VỤ CAM KẾT / TIÊU CHÍ */}
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
                            <h4 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '6px' }}>
                                {item.title}
                            </h4>
                            <p style={{ color: '#94a3b8', fontSize: '13px', lineHeight: 1.5, margin: 0 }}>
                                {item.desc}
                            </p>
                        </div>
                    </Col>
                ))}
            </Row>

            {/* DANH MỤC */}
            <div style={{ marginBottom: '50px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                    <div>
                        <h2 style={{ fontSize: '26px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', margin: 0 }}>
                            DANH MỤC ACC & DỊCH VỤ LIÊN QUÂN
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
                                    background: 'linear-gradient(145deg, #ffffff 0%, #f5f7ff 100%)',
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
                                    </div>
                                    <h3 style={{ color: '#1e293b', fontSize: '15px', fontWeight: 700, marginBottom: '8px', lineHeight: 1.4 }}>
                                        {cat.name}
                                    </h3>
                                    <p
                                        style={{
                                            color: '#64748b',
                                            fontSize: '12px',
                                            margin: 0,
                                            lineHeight: 1.5,
                                            display: '-webkit-box',
                                            WebkitLineClamp: 2,
                                            WebkitBoxOrient: 'vertical',
                                            overflow: 'hidden',
                                        }}
                                    >
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

            {/* SẢN PHẨM NỔI BẬT */}
            <div style={{ marginBottom: '50px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                    <div>
                        <h2 style={{ fontSize: '26px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', margin: 0 }}>
                            NICK LIÊN QUÂN NỔI BẬT ĐANG BÁN
                        </h2>
                        <p style={{ color: '#94a3b8', fontSize: '14px', margin: 0 }}>
                            Tài khoản Liên Quân tuyển chọn chất lượng cao - Trắng TT 100%
                        </p>
                    </div>
                    <Link to="/catalog" style={{ color: '#6366f1', fontWeight: 600 }}>
                        Xem toàn bộ kho hàng <RightOutlined />
                    </Link>
                </div>
                {loading ? (
                    <div style={{ textAlign: 'center', padding: '60px 0' }}>
                        <Spin size="large" />
                    </div>
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
                                                src={prod.thumbnail_url}
                                                style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                            />
                                            <Tag color="indigo" style={{ position: 'absolute', top: '10px', left: '10px', fontWeight: 600 }}>
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
                                        <Button type="primary" block style={{ borderRadius: '6px', fontWeight: 700 }}>
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
