import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Row, Col, Card, Select, Input, Slider, Button, Tag, Spin, Empty, Radio } from 'antd';
import { FilterOutlined } from '@ant-design/icons';
import api from '../../services/api';
const { Search } = Input;
const { Option } = Select;
const CatalogPage = () => {
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const [games, setGames] = useState([]);
    const [categories, setCategories] = useState([]);
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const selectedGame = searchParams.get('game') || '';
    const selectedCategory = searchParams.get('category') || '';
    const searchKeyword = searchParams.get('q') || '';
    const selectedType = searchParams.get('type') || '';
    const [priceRange, setPriceRange] = useState([0, 5000000]);
    // Fetch games and categories from Database
    useEffect(() => {
        const fetchMetadata = async () => {
            try {
                const [gRes, cRes] = await Promise.all([
                    api.get('/games'),
                    api.get('/categories'),
                ]);
                if (gRes.data?.data)
                    setGames(gRes.data.data);
                if (cRes.data?.data)
                    setCategories(cRes.data.data);
            }
            catch (e) {
                console.error(e);
            }
        };
        fetchMetadata();
    }, []);
    const fetchProducts = async () => {
        setLoading(true);
        try {
            const params = {};
            if (selectedGame)
                params.game = selectedGame;
            if (selectedCategory)
                params.category = selectedCategory;
            if (searchKeyword)
                params.search = searchKeyword;
            if (selectedType)
                params.type = selectedType;
            if (priceRange[0] > 0)
                params.min_price = priceRange[0];
            if (priceRange[1] < 5000000)
                params.max_price = priceRange[1];
            const res = await api.get('/products', { params });
            const list = res.data?.data?.data || res.data?.data || [];
            setProducts(Array.isArray(list) ? list : []);
        }
        catch (e) {
            console.error(e);
        }
        finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        fetchProducts();
    }, [selectedGame, selectedCategory, searchKeyword, selectedType]);
    // Filter categories shown in dropdown if a game is selected
    const filteredCategories = selectedGame
        ? categories.filter((c) => c.game?.slug === selectedGame || !c.game)
        : categories;
    return (_jsxs("div", { style: { maxWidth: '1280px', margin: '0 auto', padding: '32px 20px 60px' }, children: [_jsxs("div", { style: { marginBottom: '28px' }, children: [_jsx("h1", { style: { fontSize: '32px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', marginBottom: '8px' }, children: "KHO ACC LI\u00CAN QU\u00C2N MOBILE & D\u1ECACH V\u1EE4 \u2694\uFE0F" }), _jsx("p", { style: { color: '#94a3b8', fontSize: '15px' }, children: "Kho t\u00E0i kho\u1EA3n Li\u00EAn Qu\u00E2n ch\u00EDnh ch\u1EE7, tr\u1EAFng th\u00F4ng tin 100%, b\u1EA3o h\u00E0nh kh\u00F4ng back v\u00E0 giao d\u1ECBch t\u1EF1 \u0111\u1ED9ng 24/7." })] }), _jsxs(Row, { gutter: [24, 24], children: [_jsx(Col, { xs: 24, md: 6, children: _jsxs("div", { style: {
                                background: '#121324',
                                borderRadius: '12px',
                                padding: '24px',
                                border: '1px solid rgba(255, 255, 255, 0.08)',
                            }, children: [_jsxs("h3", { style: { color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '8px' }, children: [_jsx(FilterOutlined, { style: { color: '#6366f1' } }), " B\u1ED9 L\u1ECDc T\u00ECm Ki\u1EBFm"] }), _jsxs("div", { style: { marginBottom: '24px' }, children: [_jsx("label", { style: { display: 'block', color: '#cbd5e1', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }, children: "Ch\u1ECDn Tr\u00F2 Ch\u01A1i" }), _jsx(Select, { value: selectedGame || undefined, placeholder: "T\u1EA5t c\u1EA3 game", style: { width: '100%' }, allowClear: true, onChange: (val) => {
                                                const next = new URLSearchParams(searchParams);
                                                if (val)
                                                    next.set('game', val);
                                                else
                                                    next.delete('game');
                                                next.delete('category'); // reset category when game changes
                                                setSearchParams(next);
                                            }, children: games.map((g) => (_jsx(Option, { value: g.slug, children: g.name }, g.slug))) })] }), _jsxs("div", { style: { marginBottom: '24px' }, children: [_jsx("label", { style: { display: 'block', color: '#cbd5e1', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }, children: "Ch\u1ECDn Danh M\u1EE5c" }), _jsx(Select, { value: selectedCategory || undefined, placeholder: "T\u1EA5t c\u1EA3 danh m\u1EE5c", style: { width: '100%' }, allowClear: true, onChange: (val) => {
                                                const next = new URLSearchParams(searchParams);
                                                if (val)
                                                    next.set('category', val);
                                                else
                                                    next.delete('category');
                                                setSearchParams(next);
                                            }, children: filteredCategories.map((c) => (_jsx(Option, { value: c.slug, children: c.name }, c.slug))) })] }), _jsxs("div", { style: { marginBottom: '24px' }, children: [_jsx("label", { style: { display: 'block', color: '#cbd5e1', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }, children: "Lo\u1EA1i M\u1EB7t H\u00E0ng" }), _jsxs(Radio.Group, { value: selectedType || '', onChange: (e) => {
                                                const next = new URLSearchParams(searchParams);
                                                if (e.target.value)
                                                    next.set('type', e.target.value);
                                                else
                                                    next.delete('type');
                                                setSearchParams(next);
                                            }, children: [_jsx(Radio, { value: "", style: { color: '#94a3b8', display: 'block', marginBottom: '6px' }, children: "T\u1EA5t c\u1EA3" }), _jsx(Radio, { value: "account", style: { color: '#94a3b8', display: 'block', marginBottom: '6px' }, children: "T\u00E0i kho\u1EA3n Game (Acc)" }), _jsx(Radio, { value: "service", style: { color: '#94a3b8', display: 'block' }, children: "D\u1ECBch v\u1EE5 C\u00E0y thu\u00EA" })] })] }), _jsxs("div", { style: { marginBottom: '24px' }, children: [_jsxs("label", { style: { display: 'block', color: '#cbd5e1', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }, children: ["Kho\u1EA3ng Gi\u00E1: ", priceRange[0].toLocaleString('vi-VN'), "\u0111 - ", priceRange[1].toLocaleString('vi-VN'), "\u0111"] }), _jsx(Slider, { range: true, min: 0, max: 5000000, step: 100000, value: priceRange, onChange: (val) => setPriceRange(val) }), _jsx(Button, { block: true, onClick: fetchProducts, style: { marginTop: '10px' }, children: "\u00C1p D\u1EE5ng L\u1ECDc Gi\u00E1" })] })] }) }), _jsxs(Col, { xs: 24, md: 18, children: [_jsx("div", { style: { marginBottom: '20px' }, children: _jsx(Search, { placeholder: "T\u00ECm ki\u1EBFm theo m\u00E3 s\u1ED1, t\u00EAn t\u01B0\u1EDBng, skin, rank...", allowClear: true, enterButton: "T\u00ECm Ki\u1EBFm", size: "large", defaultValue: searchKeyword, onSearch: (val) => {
                                        const next = new URLSearchParams(searchParams);
                                        if (val)
                                            next.set('q', val);
                                        else
                                            next.delete('q');
                                        setSearchParams(next);
                                    } }) }), loading ? (_jsx("div", { style: { textAlign: 'center', padding: '80px 0' }, children: _jsx(Spin, { size: "large" }) })) : products.length === 0 ? (_jsx("div", { style: { background: '#121324', padding: '60px 20px', borderRadius: '12px', textAlign: 'center' }, children: _jsx(Empty, { description: _jsx("span", { style: { color: '#94a3b8' }, children: "Kh\u00F4ng t\u00ECm th\u1EA5y s\u1EA3n ph\u1EA9m n\u00E0o trong danh m\u1EE5c n\u00E0y" }) }) })) : (_jsx(Row, { gutter: [20, 24], children: products.map((prod) => (_jsx(Col, { xs: 24, sm: 12, lg: 8, children: _jsxs(Card, { hoverable: true, onClick: () => navigate(`/products/${prod.slug}`), style: {
                                            background: '#121324',
                                            borderRadius: '12px',
                                            border: '1px solid rgba(255, 255, 255, 0.08)',
                                            overflow: 'hidden',
                                            height: '100%',
                                            display: 'flex',
                                            flexDirection: 'column',
                                        }, bodyStyle: { padding: '16px', flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }, cover: _jsxs("div", { style: { height: '180px', overflow: 'hidden', position: 'relative' }, children: [_jsx("img", { alt: prod.name, src: prod.thumbnail_url, style: { width: '100%', height: '100%', objectFit: 'cover' } }), _jsx(Tag, { color: "indigo", style: { position: 'absolute', top: '10px', left: '10px', fontWeight: 600 }, children: prod.sku }), prod.category && (_jsx(Tag, { color: "purple", style: { position: 'absolute', bottom: '10px', left: '10px', fontWeight: 600, maxWidth: '80%', overflow: 'hidden', textOverflow: 'ellipsis' }, children: prod.category.name }))] }), children: [_jsxs("div", { children: [_jsx("h3", { style: {
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
                                                        }, children: prod.name }), _jsx("p", { style: { color: '#94a3b8', fontSize: '12px', marginBottom: '14px', height: '36px', overflow: 'hidden' }, children: prod.short_description })] }), _jsxs("div", { children: [_jsx("div", { style: { display: 'flex', alignItems: 'baseline', gap: '8px', marginBottom: '14px' }, children: _jsxs("span", { style: { color: '#06b6d4', fontSize: '20px', fontWeight: 800, fontFamily: 'Rajdhani' }, children: [Number(prod.min_price || 0).toLocaleString('vi-VN'), " \u0111"] }) }), _jsx(Button, { type: "primary", block: true, style: { borderRadius: '6px', fontWeight: 700 }, children: "Xem Chi Ti\u1EBFt & Mua" })] })] }) }, prod.id))) }))] })] })] }));
};
export default CatalogPage;
