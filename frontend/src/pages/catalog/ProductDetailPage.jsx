import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button, Card, Col, Empty, Image, message, Pagination, Row, Skeleton, Space, Tag, Typography } from 'antd';
import { ShoppingCartOutlined, ThunderboltOutlined } from '@ant-design/icons';
import api from '../../services/api';
import { useCartStore } from '../../stores/cartStore';
import { useAuthStore } from '../../stores/authStore';

const ProductDetailPage = () => {
    const { slug } = useParams();
    const navigate = useNavigate();
    const addItem = useCartStore((state) => state.addItem);
    const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
    const [product, setProduct] = useState(null);
    const [accounts, setAccounts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [accountsLoading, setAccountsLoading] = useState(false);
    const [accountPage, setAccountPage] = useState(1);
    const [accountTotal, setAccountTotal] = useState(0);
    const [addingId, setAddingId] = useState(null);

    const fetchAccounts = async (page = 1) => {
        setAccountsLoading(true);
        try {
            const response = await api.get(`/products/${slug}/accounts`, { params: { page, per_page: 24 } });
            const result = response.data?.data?.accounts;
            setAccounts(result?.data || []);
            setAccountPage(result?.current_page || page);
            setAccountTotal(result?.total || 0);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không tải được danh sách tài khoản.');
        } finally {
            setAccountsLoading(false);
        }
    };

    useEffect(() => {
        let active = true;
        const loadProduct = async () => {
            setLoading(true);
            try {
                const response = await api.get(`/products/${slug}`);
                const detail = response.data?.data;
                if (!active) return;
                setProduct(detail || null);
                if (detail?.type === 'ACCOUNT') {
                    await fetchAccounts(1);
                }
            } catch (error) {
                if (active) message.error(error.response?.data?.message || 'Không tải được sản phẩm.');
            } finally {
                if (active) {
                    setLoading(false);
                    setAccountsLoading(false);
                }
            }
        };
        if (slug) loadProduct();
        return () => { active = false; };
    }, [slug]);

    const addAccountToCart = async (account, buyNow = false) => {
        if (!isAuthenticated) {
            message.info('Vui lòng đăng nhập để chọn tài khoản.');
            navigate('/login');
            return;
        }
        setAddingId(account.id);
        try {
            const response = await api.post('/cart/items', {
                variant_id: account.variant_id,
                game_account_id: account.id,
                quantity: 1,
            });
            const cartItem = response.data?.data;
            addItem({
                id: cartItem.id,
                variant_id: account.variant_id,
                game_account_id: account.id,
                productId: product.id,
                name: `${product.name} - Tài khoản #${account.id}`,
                price: Number(account.price || 0),
                quantity: 1,
                thumbnailUrl: account.image_url || product.thumbnail_url,
                accountDetails: account.public_details || [],
            });
            message.success('Đã thêm đúng tài khoản này vào giỏ hàng.');
            if (buyNow) navigate('/checkout');
        } catch (error) {
            message.error(error.response?.data?.error?.message || error.response?.data?.message || 'Tài khoản vừa hết hàng, vui lòng tải lại danh sách.');
        } finally {
            setAddingId(null);
        }
    };

    if (loading) return <div style={{ maxWidth: 1100, margin: '40px auto', padding: 20 }}><Skeleton active /></div>;
    if (!product) return <Empty description="Không tìm thấy sản phẩm." style={{ padding: 80 }} />;

    return <div style={{ maxWidth: 1200, margin: '0 auto', padding: '32px 20px 60px' }}>
        <Row gutter={[28, 28]} align="middle">
            <Col xs={24} md={10}>{product.thumbnail_url && <Image src={product.thumbnail_url} alt={product.name} style={{ width: '100%', maxHeight: 360, objectFit: 'cover', borderRadius: 14 }} />}</Col>
            <Col xs={24} md={14}>
                <Space wrap><Tag color="cyan">{product.sku}</Tag>{product.game && <Tag color="purple">{product.game.name}</Tag>}<Tag color="blue">{product.category?.name}</Tag></Space>
                <Typography.Title level={2} style={{ color: '#fff', marginTop: 12 }}>{product.name}</Typography.Title>
                <Typography.Paragraph style={{ color: '#cbd5e1' }}>{product.short_description}</Typography.Paragraph>
                <Typography.Text style={{ color: '#94a3b8' }}>Trong kho hiện có {product.available_count || 0} tài khoản để chọn.</Typography.Text>
            </Col>
        </Row>

        {product.type === 'ACCOUNT' && <section style={{ marginTop: 36 }}>
            <Typography.Title level={3} style={{ color: '#fff' }}>Chọn tài khoản muốn mua</Typography.Title>
            {accountsLoading ? <Skeleton active /> : accounts.length === 0 ? <Empty description="Hiện chưa có tài khoản nào sẵn sàng bán." /> : <>
                <Row gutter={[16, 16]}>
                {accounts.map((account) => <Col xs={24} sm={12} lg={8} key={account.id}>
                    <Card hoverable style={{ height: '100%', background: '#121324', borderColor: 'rgba(255,255,255,.1)' }} cover={account.image_url ? <Image src={account.image_url} alt={`Tài khoản ${account.id}`} preview={false} style={{ height: 190, objectFit: 'cover' }} /> : null}>
                        <Card.Meta title={<span style={{ color: '#fff' }}>Tài khoản #{account.id}</span>} description={<div style={{ color: '#cbd5e1' }}>
                            {(account.public_details || []).length ? account.public_details.map((detail, index) => <div key={`${detail.label}-${index}`}><strong>{detail.label}:</strong> {detail.value}</div>) : <span>Thông tin chi tiết do shop cập nhật.</span>}
                            <div style={{ color: '#06b6d4', fontSize: 20, fontWeight: 800, marginTop: 12 }}>{Number(account.price || 0).toLocaleString('vi-VN')} đ</div>
                        </div>} />
                        <Space direction="vertical" style={{ width: '100%', marginTop: 16 }}>
                            <Button type="primary" block icon={<ThunderboltOutlined />} loading={addingId === account.id} onClick={() => addAccountToCart(account, true)}>Mua tài khoản này</Button>
                            <Button block icon={<ShoppingCartOutlined />} loading={addingId === account.id} onClick={() => addAccountToCart(account)}>Thêm vào giỏ</Button>
                        </Space>
                    </Card>
                </Col>)}
                </Row>
                <div style={{ display: 'flex', justifyContent: 'center', marginTop: 24 }}><Pagination current={accountPage} pageSize={24} total={accountTotal} onChange={fetchAccounts} /></div>
            </>}
        </section>}

        {product.description && <Card style={{ marginTop: 32, background: '#121324', color: '#fff', borderColor: 'rgba(255,255,255,.1)' }}><Typography.Title level={4} style={{ color: '#fff' }}>Mô tả sản phẩm</Typography.Title><Typography.Paragraph style={{ color: '#cbd5e1', whiteSpace: 'pre-line' }}>{product.description}</Typography.Paragraph></Card>}
    </div>;
};

export default ProductDetailPage;
