import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Alert, Button, Card, Col, Empty, Form, Image, Input, message, Pagination, Radio, Row, Skeleton, Space, Tag, Typography } from 'antd';
import { ClockCircleOutlined, LockOutlined, SafetyCertificateOutlined, ShoppingCartOutlined, ThunderboltOutlined, UserOutlined } from '@ant-design/icons';
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

    // Dành cho dịch vụ cày thuê (SERVICE)
    const [selectedVariantId, setSelectedVariantId] = useState(null);
    const [serviceForm] = Form.useForm();
    const [serviceSubmitting, setServiceSubmitting] = useState(false);

    const handleSelectVariant = (variantId, currentProduct = product) => {
        setSelectedVariantId(variantId);
        const v = currentProduct?.variants?.find((item) => item.id === variantId);
        if (v) {
            if (v.name.includes('Đồng -> Cao Thủ')) {
                serviceForm.setFieldsValue({ current_rank: 'Đồng', target_rank: 'Cao Thủ' });
            } else if (v.name.includes('Cao Thủ -> Chiến Tướng')) {
                serviceForm.setFieldsValue({ current_rank: 'Cao Thủ', target_rank: 'Chiến Tướng' });
            } else if (v.name.includes('Chiến Tướng -> Chiến Thần')) {
                serviceForm.setFieldsValue({ current_rank: 'Chiến Tướng', target_rank: 'Chiến Thần' });
            } else if (v.name.includes('Chiến Thần -> Thách Đấu')) {
                serviceForm.setFieldsValue({ current_rank: 'Chiến Thần', target_rank: 'Thách Đấu' });
            } else if (v.name.includes('Top Tỉnh')) {
                serviceForm.setFieldsValue({ target_rank: 'Top Tỉnh (Chiến lực 4.000+)' });
            } else if (v.name.includes('Top Miền')) {
                serviceForm.setFieldsValue({ target_rank: 'Top Miền (Chiến lực 6.000+)' });
            } else if (v.name.includes('Top Việt Nam')) {
                serviceForm.setFieldsValue({ target_rank: 'Top Việt Nam (Chiến lực 8.000+)' });
            } else if (v.name.includes('Top Server')) {
                serviceForm.setFieldsValue({ target_rank: 'Top 1-10 Toàn Server' });
            }
        }
    };

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
                } else if (detail?.type === 'SERVICE') {
                    if (detail.variants && detail.variants.length > 0) {
                        handleSelectVariant(detail.variants[0].id, detail);
                    }
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
            message.success('Đã thêm tài khoản này vào giỏ hàng.');
            if (buyNow) navigate('/checkout');
        } catch (error) {
            message.error(error.response?.data?.error?.message || error.response?.data?.message || 'Tài khoản vừa hết hàng, vui lòng tải lại danh sách.');
        } finally {
            setAddingId(null);
        }
    };

    const handleOrderService = async (buyNow = true) => {
        if (!isAuthenticated) {
            message.warning('Vui lòng đăng nhập tài khoản trước khi đặt dịch vụ game.');
            navigate('/login');
            return;
        }
        try {
            const values = await serviceForm.validateFields();
            const variantId = selectedVariantId || product.variants?.[0]?.id;
            const selectedVariant = product.variants?.find((v) => v.id === variantId) || product.variants?.[0];
            if (!selectedVariant) {
                message.error('Vui lòng chọn một gói dịch vụ.');
                return;
            }

            setServiceSubmitting(true);
            const serviceData = {
                game_username: values.game_username,
                game_password: values.game_password,
                ingame_name: values.ingame_name || '',
                current_rank: values.current_rank || '',
                target_rank: values.target_rank || selectedVariant.name,
                target_hero: values.target_hero || '',
                notes: values.notes || '',
            };

            let cartItemId = `srv-${selectedVariant.id}-${Date.now()}`;
            try {
                const response = await api.post('/cart/items', {
                    variant_id: selectedVariant.id,
                    quantity: 1,
                });
                if (response.data?.data?.id) {
                    cartItemId = response.data.data.id;
                }
            } catch (cartApiErr) {
                console.warn('Backend cart item sync warning:', cartApiErr);
            }

            addItem({
                id: cartItemId,
                variant_id: selectedVariant.id,
                game_account_id: null,
                productId: product.id,
                name: `${product.name} - Gói: ${selectedVariant.name}`,
                price: Number(selectedVariant.sale_price || selectedVariant.price || 0),
                quantity: 1,
                thumbnailUrl: product.thumbnail_url,
                service_data: serviceData,
            });

            message.success('Đã lưu thông tin dịch vụ thành công!');
            if (buyNow) {
                navigate('/checkout');
            }
        } catch (err) {
            if (err?.errorFields && err.errorFields.length > 0) {
                message.error('Vui lòng nhập đầy đủ Tài khoản và Mật khẩu game cần cày!');
            } else {
                message.error(err.response?.data?.message || err.message || 'Có lỗi xảy ra khi đặt dịch vụ.');
            }
        } finally {
            setServiceSubmitting(false);
        }
    };

    if (loading) return <div style={{ maxWidth: 1100, margin: '40px auto', padding: 20 }}><Skeleton active /></div>;
    if (!product) return <Empty description="Không tìm thấy sản phẩm." style={{ padding: 80 }} />;

    const selectedVariant = product.variants?.find((v) => v.id === selectedVariantId) || product.variants?.[0];

    return (
        <div style={{ maxWidth: 1200, margin: '0 auto', padding: '32px 20px 60px' }}>
            <Row gutter={[28, 28]} align="middle">
                <Col xs={24} md={10}>
                    {product.thumbnail_url && (
                        <Image
                            src={product.thumbnail_url}
                            alt={product.name}
                            style={{ width: '100%', maxHeight: 360, objectFit: 'cover', borderRadius: 14 }}
                        />
                    )}
                </Col>
                <Col xs={24} md={14}>
                    <Space wrap>
                        <Tag color="cyan">{product.sku}</Tag>
                        {product.game && <Tag color="purple">{product.game.name}</Tag>}
                        <Tag color="blue">{product.category?.name}</Tag>
                        <Tag color={product.type === 'SERVICE' ? 'orange' : 'green'}>
                            {product.type === 'SERVICE' ? 'DỊCH VỤ CÀY GAME' : 'TÀI KHOẢN GAME'}
                        </Tag>
                    </Space>
                    <Typography.Title level={2} style={{ color: '#fff', marginTop: 12 }}>{product.name}</Typography.Title>
                    <Typography.Paragraph style={{ color: '#cbd5e1' }}>{product.short_description}</Typography.Paragraph>
                    {product.type === 'ACCOUNT' ? (
                        <Typography.Text style={{ color: '#94a3b8' }}>
                            Trong kho hiện có {product.available_count || 0} tài khoản để chọn.
                        </Typography.Text>
                    ) : (
                        <Typography.Text style={{ color: '#38bdf8', fontWeight: 600 }}>
                            ⚡ Dịch vụ uy tín • Kỹ thuật viên chuyên nghiệp • Xử lý tự động sau khi phê duyệt
                        </Typography.Text>
                    )}
                </Col>
            </Row>

            {/* PHẦN 1: DÀNH CHO DỊCH VỤ CÀY THUÊ / LEO RANK / TOP TƯỚNG (SERVICE) */}
            {product.type === 'SERVICE' && (
                <section style={{ marginTop: 36 }}>
                    <Row gutter={[28, 28]}>
                        <Col xs={24} lg={12}>
                            <Card
                                title={<span style={{ color: '#fff', fontSize: '18px', fontWeight: 700 }}>1. Chọn Gói Cày / Hạng Mục Dịch Vụ</span>}
                                style={{ background: '#121324', borderColor: 'rgba(255,255,255,.1)' }}
                            >
                                {product.variants && product.variants.length > 0 ? (
                                    <Radio.Group
                                        value={selectedVariantId}
                                        onChange={(e) => handleSelectVariant(e.target.value)}
                                        style={{ width: '100%', display: 'flex', flexDirection: 'column', gap: '12px' }}
                                    >
                                        {product.variants.map((v) => {
                                            const isSelected = selectedVariantId === v.id;
                                            const price = Number(v.sale_price || v.price || 0);
                                            return (
                                                <div
                                                    key={v.id}
                                                    onClick={() => handleSelectVariant(v.id)}
                                                    style={{
                                                        padding: '16px 20px',
                                                        borderRadius: '10px',
                                                        border: isSelected ? '2px solid #6366f1' : '1px solid rgba(255,255,255,0.08)',
                                                        background: isSelected ? 'rgba(99, 102, 241, 0.12)' : 'rgba(255,255,255,0.02)',
                                                        cursor: 'pointer',
                                                        display: 'flex',
                                                        justifyContent: 'space-between',
                                                        alignItems: 'center',
                                                        transition: 'all 0.2s',
                                                    }}
                                                >
                                                    <div>
                                                        <Radio value={v.id} style={{ color: '#fff', fontWeight: 700, fontSize: '15px' }}>
                                                            {v.name}
                                                        </Radio>
                                                        {v.estimated_hours && (
                                                            <div style={{ paddingLeft: '28px', color: '#94a3b8', fontSize: '13px', marginTop: '4px' }}>
                                                                <ClockCircleOutlined style={{ marginRight: '6px' }} />
                                                                Thời gian cày dự kiến: <strong>{v.estimated_hours} giờ</strong>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div style={{ color: '#06b6d4', fontSize: '18px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                                                        {price.toLocaleString('vi-VN')} đ
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </Radio.Group>
                                ) : (
                                    <Empty description="Chưa có gói dịch vụ nào." />
                                )}

                                {selectedVariant && (
                                    <div style={{ marginTop: '20px', padding: '16px', background: 'rgba(99,102,241,0.08)', borderRadius: '10px', border: '1px solid rgba(99,102,241,0.2)' }}>
                                        <div style={{ color: '#94a3b8', fontSize: '13px' }}>Gói bạn đang chọn:</div>
                                        <div style={{ color: '#fff', fontSize: '17px', fontWeight: 800, marginTop: '4px' }}>
                                            {selectedVariant.name}
                                        </div>
                                        <div style={{ color: '#06b6d4', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginTop: '4px' }}>
                                            {Number(selectedVariant.sale_price || selectedVariant.price || 0).toLocaleString('vi-VN')} đ
                                        </div>
                                    </div>
                                )}
                            </Card>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Card
                                title={<span style={{ color: '#fff', fontSize: '18px', fontWeight: 700 }}>2. Nhập Thông Tin Tài Khoản Game Cần Cày</span>}
                                style={{ background: '#121324', borderColor: 'rgba(255,255,255,.1)' }}
                            >
                                <Alert
                                    type="info"
                                    showIcon
                                    icon={<SafetyCertificateOutlined />}
                                    message="Bảo Mật Tuyệt Đối & Quy Trình Xử Lý"
                                    description="Tài khoản & mật khẩu của bạn được bảo mật tuyệt đối. Sau khi thanh toán, đơn hàng sẽ chuyển sang trạng thái 'Chờ Admin phê duyệt'. Admin sẽ kiểm tra nick và phân công kỹ thuật viên tiến hành cày có hẹn giờ hoàn thành."
                                    style={{ marginBottom: '20px', background: 'rgba(15,23,42,0.8)', border: '1px solid rgba(255,255,255,0.1)' }}
                                />

                                <Form form={serviceForm} layout="vertical">
                                    <Row gutter={16}>
                                        <Col xs={24} sm={12}>
                                            <Form.Item
                                                name="game_username"
                                                label={<span style={{ color: '#cbd5e1' }}>Tài khoản / Tên đăng nhập game *</span>}
                                                rules={[{ required: true, message: 'Vui lòng nhập tài khoản game.' }]}
                                            >
                                                <Input prefix={<UserOutlined style={{ color: '#6366f1' }} />} placeholder="VD: taikhoan123 hoặc email..." size="large" />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} sm={12}>
                                            <Form.Item
                                                name="game_password"
                                                label={<span style={{ color: '#cbd5e1' }}>Mật khẩu game *</span>}
                                                rules={[{ required: true, message: 'Vui lòng nhập mật khẩu game.' }]}
                                            >
                                                <Input.Password prefix={<LockOutlined style={{ color: '#6366f1' }} />} placeholder="Mật khẩu đăng nhập..." size="large" />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Row gutter={16}>
                                        <Col xs={24}>
                                            <Form.Item name="ingame_name" label={<span style={{ color: '#cbd5e1' }}>Tên nhân vật trong game (Ingame)</span>}>
                                                <Input placeholder="VD: ProPlayer99..." size="large" />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Row gutter={16}>
                                        <Col xs={24} sm={12}>
                                            <Form.Item name="current_rank" label={<span style={{ color: '#cbd5e1', whiteSpace: 'nowrap' }}>Rank hiện tại</span>}>
                                                <Input placeholder="VD: Đồng, Kim Cương..." size="large" />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} sm={12}>
                                            <Form.Item name="target_rank" label={<span style={{ color: '#cbd5e1', whiteSpace: 'nowrap' }}>Rank muốn lên *</span>} rules={[{ required: true, message: 'Vui lòng nhập rank muốn lên' }]}>
                                                <Input placeholder="VD: Cao Thủ, Chiến Tướng..." size="large" />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Row gutter={16}>
                                        <Col xs={24}>
                                            <Form.Item name="target_hero" label={<span style={{ color: '#cbd5e1' }}>Tướng muốn cày (Nếu cày top tướng)</span>}>
                                                <Input placeholder="VD: Florentino, Điêu Thuyền..." size="large" />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Form.Item name="notes" label={<span style={{ color: '#cbd5e1' }}>Ghi chú đặc biệt cho kỹ thuật viên</span>}>
                                        <Input.TextArea rows={2} placeholder="VD: Chỉ cày vào buổi sáng từ 8h-12h, không được tiêu vàng/quân huy..." />
                                    </Form.Item>

                                    <Space direction="vertical" style={{ width: '100%', marginTop: '10px' }}>
                                        <Button
                                            type="primary"
                                            size="large"
                                            block
                                            icon={<ThunderboltOutlined />}
                                            loading={serviceSubmitting}
                                            onClick={() => handleOrderService(true)}
                                            style={{ height: '48px', fontSize: '16px', fontWeight: 800, borderRadius: '8px' }}
                                        >
                                            ĐẶT DỊCH VỤ & THANH TOÁN NGAY
                                        </Button>
                                        <Button
                                            block
                                            size="large"
                                            icon={<ShoppingCartOutlined />}
                                            loading={serviceSubmitting}
                                            onClick={() => handleOrderService(false)}
                                            style={{ height: '44px', fontWeight: 700, borderRadius: '8px' }}
                                        >
                                            Thêm Vào Giỏ Hàng
                                        </Button>
                                    </Space>
                                </Form>
                            </Card>
                        </Col>
                    </Row>
                </section>
            )}

            {/* PHẦN 2: DÀNH CHO BÁN TÀI KHOẢN (ACCOUNT) */}
            {product.type === 'ACCOUNT' && (
                <section style={{ marginTop: 36 }}>
                    <Typography.Title level={3} style={{ color: '#fff' }}>Chọn tài khoản muốn mua</Typography.Title>
                    {accountsLoading ? (
                        <Skeleton active />
                    ) : accounts.length === 0 ? (
                        <Empty description="Hiện chưa có tài khoản nào sẵn sàng bán." />
                    ) : (
                        <>
                            <Row gutter={[16, 16]}>
                                {accounts.map((account) => (
                                    <Col xs={24} sm={12} lg={8} key={account.id}>
                                        <Card
                                            hoverable
                                            style={{ height: '100%', background: '#121324', borderColor: 'rgba(255,255,255,.1)' }}
                                            cover={
                                                account.image_url ? (
                                                    <Image
                                                        src={account.image_url}
                                                        alt={`Tài khoản ${account.id}`}
                                                        preview={false}
                                                        style={{ height: 190, objectFit: 'cover' }}
                                                    />
                                                ) : null
                                            }
                                        >
                                            <Card.Meta
                                                title={<span style={{ color: '#fff' }}>Tài khoản #{account.id}</span>}
                                                description={
                                                    <div style={{ color: '#cbd5e1' }}>
                                                        {(account.public_details || []).length ? (
                                                            account.public_details.map((detail, index) => (
                                                                <div key={`${detail.label}-${index}`}>
                                                                    <strong>{detail.label}:</strong> {detail.value}
                                                                </div>
                                                            ))
                                                        ) : (
                                                            <span>Thông tin chi tiết do shop cập nhật.</span>
                                                        )}
                                                        <div style={{ color: '#06b6d4', fontSize: 20, fontWeight: 800, marginTop: 12 }}>
                                                            {Number(account.price || 0).toLocaleString('vi-VN')} đ
                                                        </div>
                                                    </div>
                                                }
                                            />
                                            <Space direction="vertical" style={{ width: '100%', marginTop: 16 }}>
                                                <Button
                                                    type="primary"
                                                    block
                                                    icon={<ThunderboltOutlined />}
                                                    loading={addingId === account.id}
                                                    onClick={() => addAccountToCart(account, true)}
                                                >
                                                    Mua tài khoản này
                                                </Button>
                                                <Button
                                                    block
                                                    icon={<ShoppingCartOutlined />}
                                                    loading={addingId === account.id}
                                                    onClick={() => addAccountToCart(account)}
                                                >
                                                    Thêm vào giỏ
                                                </Button>
                                            </Space>
                                        </Card>
                                    </Col>
                                ))}
                            </Row>
                            <div style={{ display: 'flex', justifyContent: 'center', marginTop: 24 }}>
                                <Pagination current={accountPage} pageSize={24} total={accountTotal} onChange={fetchAccounts} />
                            </div>
                        </>
                    )}
                </section>
            )}

            {product.description && (
                <Card style={{ marginTop: 32, background: '#121324', color: '#fff', borderColor: 'rgba(255,255,255,.1)' }}>
                    <Typography.Title level={4} style={{ color: '#fff' }}>Mô tả sản phẩm / Dịch vụ</Typography.Title>
                    <Typography.Paragraph style={{ color: '#cbd5e1', whiteSpace: 'pre-line' }}>
                        {product.description}
                    </Typography.Paragraph>
                </Card>
            )}
        </div>
    );
};

export default ProductDetailPage;
