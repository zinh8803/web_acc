import { jsxs as _jsxs, jsx as _jsx } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Button, Card, Row, Col, Tag, Spin } from 'antd';
import { ThunderboltOutlined, SafetyCertificateOutlined, ClockCircleOutlined, FireOutlined, RightOutlined, } from '@ant-design/icons';
import api from '../services/api';
const HomePage = () => {
    const navigate = useNavigate();
    const [games, setGames] = useState([]);
    const [categories, setCategories] = useState([]);
    const [banners, setBanners] = useState([]);
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        const fetchData = async () => {
            try {
                const [gamesRes, catsRes, prodsRes, bannersRes] = await Promise.all([
                    api.get('/games'),
                    api.get('/categories'),
                    api.get('/products', { params: { limit: 8 } }),
                    api.get('/banners'),
                ]);
                if (gamesRes.data?.data)
                    setGames(gamesRes.data.data);
                if (catsRes.data?.data)
                    setCategories(catsRes.data.data);
                if (bannersRes.data?.data && Array.isArray(bannersRes.data.data)) {
                    setBanners(bannersRes.data.data);
                }
                if (prodsRes.data?.data) {
                    // handles both array or paginated data
                    const list = prodsRes.data.data.data || prodsRes.data.data;
                    setProducts(Array.isArray(list) ? list : []);
                }
            }
            catch (err) {
                console.error('Failed to fetch home data', err);
            }
            finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);
    return (_jsxs("div", { style: { maxWidth: '1280px', margin: '0 auto', padding: '24px 20px 60px' }, children: [_jsx("div", { style: {
                    borderRadius: '16px',
                    overflow: 'hidden',
                    marginBottom: '40px',
                    boxShadow: '0 16px 36px rgba(30, 41, 59, 0.12)',
                    border: '1px solid #e0e7ff',
                    background: 'linear-gradient(135deg, #eef2ff 0%, #f8faff 55%, #ecfeff 100%)',
                    position: 'relative',
                    padding: '60px 48px',
                }, children: _jsxs("div", { style: { maxWidth: '640px', position: 'relative', zIndex: 2 }, children: [_jsxs(Tag, { color: "cyan", style: { fontSize: '13px', padding: '4px 12px', borderRadius: '20px', marginBottom: '16px', fontWeight: 600 }, children: ["\u26A1 ", banners.length > 0 ? banners[0].title : 'BÀN GIAO TỰ ĐỘNG SAU 3 GIÂY • BẢO HÀNH TRỌN ĐỜI 100%'] }), _jsxs("h1", { style: {
                                fontSize: '44px',
                                fontWeight: 800,
                                lineHeight: 1.15,
                                color: '#1e293b',
                                marginBottom: '16px',
                                fontFamily: 'Rajdhani, sans-serif',
                            }, children: ["SHOP ACC ", _jsx("span", { style: { color: '#06b6d4' }, children: "LI\u00CAN QU\u00C2N" }), " & ", _jsx("span", { style: { color: '#a855f7' }, children: "C\u00C0Y THU\u00CA RANK" }), " UY T\u00CDN"] }), _jsx("p", { style: { fontSize: '16px', color: '#64748b', lineHeight: 1.6, marginBottom: '28px' }, children: banners.length > 1 ? banners[1].title : 'Hàng ngàn nick Liên Quân VIP Thách Đấu, skin SSS, Thứ Nguyên Vệ Thần, Anime Kimetsu No Yaiba, trắng thông tin 100%, bàn giao tự động nhận tài khoản ngay sau khi thanh toán.' }), _jsxs("div", { style: { display: 'flex', gap: '16px' }, children: [_jsx(Button, { type: "primary", size: "large", icon: _jsx(ThunderboltOutlined, {}), onClick: () => navigate('/catalog'), style: {
                                        height: '48px',
                                        padding: '0 28px',
                                        fontSize: '16px',
                                        fontWeight: 700,
                                        borderRadius: '8px',
                                    }, children: "Kh\u00E1m Ph\u00E1 Kho Acc Li\u00EAn Qu\u00E2n" }), _jsx(Button, { size: "large", onClick: () => navigate('/catalog?type=service'), style: {
                                        height: '48px',
                                        padding: '0 24px',
                                        fontSize: '16px',
                                        color: '#4f46e5',
                                        borderColor: '#c7d2fe',
                                        background: '#fff',
                                        borderRadius: '8px',
                                    }, children: "C\u00E0y Thu\u00EA Rank Li\u00EAn Qu\u00E2n" })] })] }) }), _jsx(Row, { gutter: [24, 24], style: { marginBottom: '50px' }, children: [
                    {
                        icon: _jsx(ThunderboltOutlined, { style: { fontSize: '28px', color: '#06b6d4' } }),
                        title: 'Giao dịch Tự Động 24/7',
                        desc: 'Nhận tài khoản ngay lập tức qua màn hình và email sau khi thanh toán',
                    },
                    {
                        icon: _jsx(SafetyCertificateOutlined, { style: { fontSize: '28px', color: '#10b981' } }),
                        title: 'Bảo Hành 100% Không Back',
                        desc: 'Đền bù tài khoản tương đương hoặc hoàn tiền 100% nếu phát sinh lỗi',
                    },
                    {
                        icon: _jsx(ClockCircleOutlined, { style: { fontSize: '28px', color: '#f59e0b' } }),
                        title: 'Hỗ Trợ Nhanh Chóng 24/7',
                        desc: 'Đội ngũ tư vấn viên túc trực giải quyết mọi thắc mắc của bạn',
                    },
                    {
                        icon: _jsx(FireOutlined, { style: { fontSize: '28px', color: '#f43f5e' } }),
                        title: 'Mức Giá Tốt Nhất Thị Trường',
                        desc: 'Hàng ngàn ưu đãi flash-sale và mã coupon giảm giá mỗi ngày',
                    },
                ].map((item, idx) => (_jsx(Col, { xs: 24, sm: 12, md: 6, children: _jsxs("div", { style: {
                            background: '#121324',
                            padding: '24px',
                            borderRadius: '12px',
                            border: '1px solid rgba(255, 255, 255, 0.06)',
                            height: '100%',
                        }, children: [_jsx("div", { style: { marginBottom: '14px' }, children: item.icon }), _jsx("h4", { style: { color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '6px' }, children: item.title }), _jsx("p", { style: { color: '#94a3b8', fontSize: '13px', lineHeight: 1.5, margin: 0 }, children: item.desc })] }) }, idx))) }), _jsxs("div", { style: { marginBottom: '50px' }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }, children: [_jsxs("div", { children: [_jsx("h2", { style: { fontSize: '26px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', margin: 0 }, children: "TR\u00D2 CH\u01A0I PH\u1ED4 BI\u1EBEN (LI\u00CAN QU\u00C2N MOBILE) \u2694\uFE0F" }), _jsx("p", { style: { color: '#94a3b8', fontSize: '14px', margin: 0 }, children: "H\u1EC7 sinh th\u00E1i \u0110\u1EA5u Tr\u01B0\u1EDDng Danh V\u1ECDng & M\u00E1y Ch\u1EE7 Th\u1EED Nghi\u1EC7m Li\u00EAn Qu\u00E2n" })] }), _jsxs(Link, { to: "/catalog", style: { color: '#6366f1', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '4px' }, children: ["Xem t\u1EA5t c\u1EA3 ", _jsx(RightOutlined, {})] })] }), _jsx(Row, { gutter: [16, 16], children: games.map((g) => (_jsx(Col, { xs: 12, sm: 8, md: 4, children: _jsxs("div", { onClick: () => navigate(`/catalog?game=${g.slug}`), style: {
                                    background: '#121324',
                                    borderRadius: '12px',
                                    padding: '16px 12px',
                                    textAlign: 'center',
                                    cursor: 'pointer',
                                    border: '1px solid rgba(255, 255, 255, 0.08)',
                                    transition: 'all 0.2s',
                                }, onMouseEnter: (e) => {
                                    e.currentTarget.style.borderColor = '#6366f1';
                                    e.currentTarget.style.transform = 'translateY(-4px)';
                                }, onMouseLeave: (e) => {
                                    e.currentTarget.style.borderColor = 'rgba(255, 255, 255, 0.08)';
                                    e.currentTarget.style.transform = 'translateY(0)';
                                }, children: [_jsx("img", { src: g.logo_url, alt: g.name, style: { width: '60px', height: '60px', borderRadius: '12px', objectFit: 'cover', marginBottom: '10px' } }), _jsx("div", { style: { color: '#fff', fontSize: '14px', fontWeight: 700, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }, children: g.name })] }) }, g.id))) })] }), _jsxs("div", { style: { marginBottom: '50px' }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }, children: [_jsxs("div", { children: [_jsx("h2", { style: { fontSize: '26px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', margin: 0 }, children: "DANH M\u1EE4C ACC & D\u1ECACH V\u1EE4 LI\u00CAN QU\u00C2N \uD83D\uDDC2\uFE0F" }), _jsx("p", { style: { color: '#94a3b8', fontSize: '14px', margin: 0 }, children: "Ph\u00E2n lo\u1EA1i nick Li\u00EAn Qu\u00E2n VIP, gi\u00E1 r\u1EBB, skin anime \u0111\u1ED9c quy\u1EC1n v\u00E0 c\u00E0y thu\u00EA si\u00EAu t\u1ED1c" })] }), _jsxs(Link, { to: "/catalog", style: { color: '#6366f1', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '4px' }, children: ["Kh\u00E1m ph\u00E1 t\u1EA5t c\u1EA3 ", _jsx(RightOutlined, {})] })] }), _jsx(Row, { gutter: [16, 16], children: categories.map((cat) => (_jsx(Col, { xs: 24, sm: 12, md: 8, lg: 6, children: _jsxs("div", { onClick: () => navigate(`/catalog?category=${cat.slug}`), style: {
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
                                }, onMouseEnter: (e) => {
                                    e.currentTarget.style.borderColor = '#6366f1';
                                    e.currentTarget.style.transform = 'translateY(-4px)';
                                    e.currentTarget.style.boxShadow = '0 10px 25px rgba(99, 102, 241, 0.2)';
                                }, onMouseLeave: (e) => {
                                    e.currentTarget.style.borderColor = 'rgba(255, 255, 255, 0.08)';
                                    e.currentTarget.style.transform = 'translateY(0)';
                                    e.currentTarget.style.boxShadow = 'none';
                                }, children: [_jsxs("div", { children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '10px' }, children: [_jsx(Tag, { color: cat.type === 'SERVICE' ? 'magenta' : 'geekblue', style: { fontWeight: 600 }, children: cat.type === 'SERVICE' ? 'DỊCH VỤ' : 'TÀI KHOẢN' }), cat.game && (_jsx("span", { style: { fontSize: '12px', color: '#06b6d4', fontWeight: 600 }, children: cat.game.name }))] }), _jsx("h3", { style: { color: '#fff', fontSize: '15px', fontWeight: 700, marginBottom: '8px', lineHeight: 1.4 }, children: cat.name }), _jsx("p", { style: { color: '#94a3b8', fontSize: '12px', margin: 0, lineHeight: 1.5, display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }, children: cat.description || 'Khám phá ngay các tài khoản và gói dịch vụ chất lượng cao.' })] }), _jsxs("div", { style: { marginTop: '14px', display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: '4px', color: '#6366f1', fontSize: '13px', fontWeight: 600 }, children: ["Xem danh m\u1EE5c ", _jsx(RightOutlined, { style: { fontSize: '10px' } })] })] }) }, cat.id))) })] }), _jsxs("div", { style: { marginBottom: '50px' }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }, children: [_jsxs("div", { children: [_jsx("h2", { style: { fontSize: '26px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', margin: 0 }, children: "NICK LI\u00CAN QU\u00C2N N\u1ED4I B\u1EACT \u0110ANG B\u00C1N \uD83D\uDD25" }), _jsx("p", { style: { color: '#94a3b8', fontSize: '14px', margin: 0 }, children: "T\u00E0i kho\u1EA3n Li\u00EAn Qu\u00E2n tuy\u1EC3n ch\u1ECDn ch\u1EA5t l\u01B0\u1EE3ng cao - Tr\u1EAFng TT 100%" })] }), _jsxs(Link, { to: "/catalog", style: { color: '#6366f1', fontWeight: 600 }, children: ["Xem to\u00E0n b\u1ED9 kho h\u00E0ng ", _jsx(RightOutlined, {})] })] }), loading ? (_jsx("div", { style: { textAlign: 'center', padding: '60px 0' }, children: _jsx(Spin, { size: "large" }) })) : (_jsx(Row, { gutter: [20, 24], children: products.map((prod) => (_jsx(Col, { xs: 24, sm: 12, md: 6, children: _jsxs(Card, { hoverable: true, onClick: () => navigate(`/products/${prod.slug}`), style: {
                                    background: '#121324',
                                    borderRadius: '12px',
                                    border: '1px solid rgba(255, 255, 255, 0.08)',
                                    overflow: 'hidden',
                                    height: '100%',
                                    display: 'flex',
                                    flexDirection: 'column',
                                }, bodyStyle: { padding: '16px', flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }, cover: _jsxs("div", { style: { height: '170px', overflow: 'hidden', position: 'relative' }, children: [_jsx("img", { alt: prod.name, src: prod.thumbnail_url, style: { width: '100%', height: '100%', objectFit: 'cover' } }), _jsx(Tag, { color: "indigo", style: { position: 'absolute', top: '10px', left: '10px', fontWeight: 600 }, children: prod.sku })] }), children: [_jsxs("div", { children: [_jsx("h3", { style: {
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
                                                }, children: prod.name }), _jsx("p", { style: { color: '#94a3b8', fontSize: '12px', marginBottom: '14px', height: '36px', overflow: 'hidden' }, children: prod.short_description })] }), _jsxs("div", { children: [_jsx("div", { style: { display: 'flex', alignItems: 'baseline', gap: '8px', marginBottom: '14px' }, children: _jsxs("span", { style: { color: '#06b6d4', fontSize: '20px', fontWeight: 800, fontFamily: 'Rajdhani' }, children: [Number(prod.min_price || 0).toLocaleString('vi-VN'), " \u0111"] }) }), _jsx(Button, { type: "primary", block: true, style: { borderRadius: '6px', fontWeight: 700 }, children: "Xem & Mua Ngay" })] })] }) }, prod.id))) }))] })] }));
};
export default HomePage;
