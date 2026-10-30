import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Row, Col, Card, Select, Input, Slider, Button, Tag, Spin, Empty } from 'antd';
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
    const requestedType = searchParams.get('type') || '';
    const [priceRange, setPriceRange] = useState([0, 5000000]);

    // Kiểm tra xem có phải đang chủ động xem dịch vụ cày thuê hay đang ở Kho Nick Game
    const isServiceView = requestedType === 'service' || selectedCategory === 'dich-vu-cay-thue-lq';

    // Fetch games and categories from Database
    useEffect(() => {
        const fetchMetadata = async () => {
            try {
                const [gRes, cRes] = await Promise.all([
                    api.get('/games'),
                    api.get('/categories'),
                ]);
                if (gRes.data?.data) setGames(gRes.data.data);
                if (cRes.data?.data) setCategories(cRes.data.data);
            } catch (e) {
                console.error(e);
            }
        };
        fetchMetadata();
    }, []);

    const fetchProducts = async () => {
        setLoading(true);
        try {
            const params = {};
            if (selectedGame) params.game = selectedGame;
            if (selectedCategory) params.category = selectedCategory;
            if (searchKeyword) params.search = searchKeyword;
            if (priceRange[0] > 0) params.min_price = priceRange[0];
            if (priceRange[1] < 5000000) params.max_price = priceRange[1];

            // Trong Kho Nick Game: KHÔNG HIỂN THỊ DỊCH VỤ CÀY THUÊ, chỉ lấy sản phẩm ACCOUNT
            if (isServiceView) {
                params.type = 'SERVICE';
            } else {
                params.type = 'ACCOUNT';
            }

            const res = await api.get('/products', { params });
            const list = res.data?.data?.data || res.data?.data || [];
            setProducts(Array.isArray(list) ? list : []);
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchProducts();
    }, [selectedGame, selectedCategory, searchKeyword, requestedType]);

    // Trong Kho Nick Game, lọc bỏ danh mục cày thuê khỏi dropdown danh mục
    const relevantCategories = isServiceView
        ? categories.filter((c) => c.slug === 'dich-vu-cay-thue-lq')
        : categories.filter((c) => c.slug !== 'dich-vu-cay-thue-lq');

    const filteredCategories = selectedGame
        ? relevantCategories.filter((c) => c.game?.slug === selectedGame || !c.game)
        : relevantCategories;

    return (
        <div style={{ maxWidth: '1280px', margin: '0 auto', padding: '32px 20px 60px' }}>
            <div style={{ marginBottom: '28px' }}>
                <h1 style={{ fontSize: '32px', fontWeight: 800, color: '#fff', fontFamily: 'Rajdhani', marginBottom: '8px' }}>
                    {isServiceView ? 'DỊCH VỤ CÀY THUÊ GAME LIÊN QUÂN MOBILE ⚡' : 'KHO NICK GAME LIÊN QUÂN MOBILE & ESPORTS ⚔️'}
                </h1>
                <p style={{ color: '#94a3b8', fontSize: '15px' }}>
                    {isServiceView
                        ? 'Dịch vụ cày thuê rank, cày điểm chiến lực top tướng chuyên nghiệp, bảo mật nick tuyệt đối 24/7.'
                        : 'Kho tài khoản Liên Quân tuyển chọn chính chủ, trắng thông tin 100%, bảo hành uy tín và giao dịch tự động 24/7.'}
                </p>
            </div>

            <Row gutter={[24, 24]}>
                <Col xs={24} md={6}>
                    <div style={{
                        background: '#121324',
                        borderRadius: '12px',
                        padding: '24px',
                        border: '1px solid rgba(255, 255, 255, 0.08)',
                    }}>
                        <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <FilterOutlined style={{ color: '#6366f1' }} /> Bộ Lọc Tìm Kiếm
                        </h3>

                        <div style={{ marginBottom: '24px' }}>
                            <label style={{ display: 'block', color: '#cbd5e1', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>
                                Chọn Trò Chơi
                            </label>
                            <Select
                                value={selectedGame || undefined}
                                placeholder="Tất cả game"
                                style={{ width: '100%' }}
                                allowClear
                                onChange={(val) => {
                                    const next = new URLSearchParams(searchParams);
                                    if (val) next.set('game', val);
                                    else next.delete('game');
                                    next.delete('category'); // reset category when game changes
                                    setSearchParams(next);
                                }}
                            >
                                {games.map((g) => (
                                    <Option key={g.slug} value={g.slug}>{g.name}</Option>
                                ))}
                            </Select>
                        </div>

                        <div style={{ marginBottom: '24px' }}>
                            <label style={{ display: 'block', color: '#cbd5e1', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>
                                Chọn Danh Mục
                            </label>
                            <Select
                                value={selectedCategory || undefined}
                                placeholder="Tất cả danh mục"
                                style={{ width: '100%' }}
                                allowClear
                                onChange={(val) => {
                                    const next = new URLSearchParams(searchParams);
                                    if (val) next.set('category', val);
                                    else next.delete('category');
                                    setSearchParams(next);
                                }}
                            >
                                {filteredCategories.map((c) => (
                                    <Option key={c.slug} value={c.slug}>{c.name}</Option>
                                ))}
                            </Select>
                        </div>

                        <div style={{ marginBottom: '24px' }}>
                            <label style={{ display: 'block', color: '#cbd5e1', fontSize: '14px', fontWeight: 600, marginBottom: '8px' }}>
                                Khoảng Giá: {priceRange[0].toLocaleString('vi-VN')}đ - {priceRange[1].toLocaleString('vi-VN')}đ
                            </label>
                            <Slider
                                range
                                min={0}
                                max={5000000}
                                step={100000}
                                value={priceRange}
                                onChange={(val) => setPriceRange(val)}
                            />
                            <Button block onClick={fetchProducts} style={{ marginTop: '10px' }}>
                                Áp Dụng Lọc Giá
                            </Button>
                        </div>
                    </div>
                </Col>

                <Col xs={24} md={18}>
                    <div style={{ marginBottom: '20px' }}>
                        <Search
                            placeholder="Tìm kiếm theo mã số, tên tướng, skin, rank..."
                            allowClear
                            enterButton="Tìm Kiếm"
                            size="large"
                            defaultValue={searchKeyword}
                            onSearch={(val) => {
                                const next = new URLSearchParams(searchParams);
                                if (val) next.set('q', val);
                                else next.delete('q');
                                setSearchParams(next);
                            }}
                        />
                    </div>

                    {loading ? (
                        <div style={{ textAlign: 'center', padding: '80px 0' }}>
                            <Spin size="large" />
                        </div>
                    ) : products.length === 0 ? (
                        <div style={{ background: '#121324', padding: '60px 20px', borderRadius: '12px', textAlign: 'center' }}>
                            <Empty description={<span style={{ color: '#94a3b8' }}>Không tìm thấy tài khoản nào phù hợp</span>} />
                        </div>
                    ) : (
                        <Row gutter={[20, 24]}>
                            {products.map((prod) => (
                                <Col xs={24} sm={12} lg={8} key={prod.id}>
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
                                            <div style={{ height: '180px', overflow: 'hidden', position: 'relative' }}>
                                                <img
                                                    alt={prod.name}
                                                    src={prod.thumbnail_url}
                                                    style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                                />
                                                <Tag color="indigo" style={{ position: 'absolute', top: '10px', left: '10px', fontWeight: 600 }}>
                                                    {prod.sku}
                                                </Tag>
                                                {prod.type === 'SERVICE' ? (
                                                    <Tag color="cyan" style={{ position: 'absolute', top: '10px', right: '10px', fontWeight: 700 }}>
                                                        DỊCH VỤ CÀY THUÊ
                                                    </Tag>
                                                ) : (
                                                    <Tag color="gold" style={{ position: 'absolute', top: '10px', right: '10px', fontWeight: 700 }}>
                                                        TÀI KHOẢN GAME
                                                    </Tag>
                                                )}
                                                {prod.category && (
                                                    <Tag color="purple" style={{ position: 'absolute', bottom: '10px', left: '10px', fontWeight: 600, maxWidth: '80%', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                                        {prod.category.name}
                                                    </Tag>
                                                )}
                                            </div>
                                        }
                                    >
                                        <div>
                                            <h3 style={{
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
                                            }}>
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
                                                {prod.type === 'SERVICE' ? 'Xem Dịch Vụ & Đặt' : 'Xem Chi Tiết & Mua'}
                                            </Button>
                                        </div>
                                    </Card>
                                </Col>
                            ))}
                        </Row>
                    )}
                </Col>
            </Row>
        </div>
    );
};

export default CatalogPage;
