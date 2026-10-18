import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Row, Col, Button, Tag, Space, Spin, message, Divider, Tabs } from 'antd';
import {
  ShoppingCartOutlined,
  ThunderboltOutlined,
  CheckCircleOutlined,
  SafetyOutlined,
  CustomerServiceOutlined,
} from '@ant-design/icons';
import api from '../../services/api';
import { useCartStore } from '../../stores/cartStore';

const ProductDetailPage: React.FC = () => {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const { addItem } = useCartStore();

  const [product, setProduct] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDetail = async () => {
      try {
        const res = await api.get(`/products/${slug}`);
        if (res.data?.data) {
          setProduct(res.data.data);
        }
      } catch (err) {
        message.error('Không tìm thấy thông tin sản phẩm.');
      } finally {
        setLoading(false);
      }
    };
    if (slug) fetchDetail();
  }, [slug]);

  const handleAddToCart = () => {
    if (!product) return;
    const defaultVariant = product.variants?.[0];
    addItem({
      id: product.id,
      variant_id: defaultVariant?.id || 1,
      productId: product.id,
      name: product.name,
      price: Number(product.min_price || 0),
      quantity: 1,
      thumbnailUrl: product.thumbnail_url,
    });
    message.success('Đã thêm sản phẩm vào giỏ hàng!');
  };

  const handleBuyNow = () => {
    handleAddToCart();
    navigate('/checkout');
  };

  if (loading) {
    return (
      <div style={{ textAlign: 'center', padding: '120px 0' }}>
        <Spin size="large" />
      </div>
    );
  }

  if (!product) {
    return (
      <div style={{ textAlign: 'center', padding: '100px 20px', color: '#fff' }}>
        <h2>Sản phẩm không tồn tại hoặc đã được gỡ xuống.</h2>
        <Button type="primary" onClick={() => navigate('/catalog')} style={{ marginTop: '16px' }}>
          Quay lại danh mục
        </Button>
      </div>
    );
  }

  const effectivePrice = Number(product.min_price || 0);

  return (
    <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '32px 20px 60px' }}>
      <Row gutter={[36, 36]}>
        {/* Product Image & Badges */}
        <Col xs={24} md={12}>
          <div
            style={{
              borderRadius: '16px',
              overflow: 'hidden',
              border: '1px solid rgba(255, 255, 255, 0.08)',
              background: '#121324',
              position: 'relative',
              boxShadow: '0 10px 30px rgba(0,0,0,0.5)',
            }}
          >
            <img
              src={product.thumbnail_url || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800'}
              alt={product.name}
              style={{ width: '100%', height: '420px', objectFit: 'cover' }}
            />
            <div style={{ position: 'absolute', top: '16px', left: '16px', display: 'flex', gap: '8px' }}>
              <Tag color="cyan" style={{ fontWeight: 700, padding: '4px 10px' }}>
                MÃ SỐ: {product.sku}
              </Tag>
              {product.game && (
                <Tag color="purple" style={{ fontWeight: 600, padding: '4px 10px' }}>
                  {product.game.name}
                </Tag>
              )}
            </div>
          </div>
        </Col>

        {/* Product Info & Actions */}
        <Col xs={24} md={12}>
          <div style={{ background: '#121324', borderRadius: '16px', padding: '32px', border: '1px solid rgba(255, 255, 255, 0.08)' }}>
            <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, lineHeight: 1.4, marginBottom: '12px' }}>
              {product.name}
            </h1>

            <div style={{ display: 'flex', alignItems: 'center', gap: '16px', marginBottom: '20px' }}>
              <span className="badge-verified">
                <CheckCircleOutlined /> Đã Xác Minh Trắng Thông Tin
              </span>
              <span style={{ color: '#10b981', fontSize: '13px', fontWeight: 600 }}>
                ⚡ Sẵn sàng bàn giao tự động
              </span>
            </div>

            <div
              style={{
                background: 'rgba(255, 255, 255, 0.03)',
                padding: '20px',
                borderRadius: '12px',
                marginBottom: '24px',
                border: '1px solid rgba(255, 255, 255, 0.05)',
              }}
            >
              <div style={{ color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Giá thanh toán:</div>
              <div style={{ display: 'flex', alignItems: 'baseline', gap: '12px' }}>
                <span style={{ color: '#06b6d4', fontSize: '32px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                  {effectivePrice.toLocaleString('vi-VN')} đ
                </span>
              </div>
            </div>

            <p style={{ color: '#cbd5e1', fontSize: '14px', lineHeight: 1.6, marginBottom: '28px' }}>
              {product.short_description}
            </p>

            {/* CTAs */}
            <Space direction="vertical" style={{ width: '100%' }} size="middle">
              <Button
                type="primary"
                size="large"
                block
                icon={<ThunderboltOutlined />}
                onClick={handleBuyNow}
                style={{
                  height: '50px',
                  fontSize: '17px',
                  fontWeight: 800,
                  borderRadius: '10px',
                }}
              >
                MUA NGAY (BÀN GIAO TỨC THÌ)
              </Button>

              <Button
                size="large"
                block
                icon={<ShoppingCartOutlined />}
                onClick={handleAddToCart}
                style={{
                  height: '46px',
                  fontSize: '15px',
                  fontWeight: 600,
                  borderRadius: '10px',
                  color: '#fff',
                  borderColor: 'rgba(255, 255, 255, 0.2)',
                  background: 'rgba(255, 255, 255, 0.05)',
                }}
              >
                Thêm Vào Giỏ Hàng
              </Button>
            </Space>

            <Divider style={{ borderColor: 'rgba(255, 255, 255, 0.08)', margin: '24px 0' }} />

            {/* Guarantees */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', fontSize: '13px', color: '#94a3b8' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <SafetyOutlined style={{ color: '#10b981', fontSize: '16px' }} />
                <span>Bảo hành 7 ngày 1 đổi 1 hoặc hoàn tiền 100% nếu có tranh chấp</span>
              </div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <ThunderboltOutlined style={{ color: '#06b6d4', fontSize: '16px' }} />
                <span>Bàn giao thông tin tài khoản (Tài khoản, Mật khẩu) tự động sau khi thanh toán</span>
              </div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <CustomerServiceOutlined style={{ color: '#a855f7', fontSize: '16px' }} />
                <span>Hỗ trợ kỹ thuật và đổi pass tận tình qua Zalo / Hotline 24/7</span>
              </div>
            </div>
          </div>
        </Col>
      </Row>

      {/* Detail Tabs */}
      <div style={{ marginTop: '40px', background: '#121324', borderRadius: '16px', padding: '32px', border: '1px solid rgba(255, 255, 255, 0.08)' }}>
        <Tabs
          defaultActiveKey="desc"
          items={[
            {
              key: 'desc',
              label: 'Mô Tả Chi Tiết & Thông Tin Acc',
              children: (
                <div style={{ color: '#cbd5e1', fontSize: '15px', lineHeight: 1.8 }}>
                  <p>{product.description || 'Tài khoản nguyên bản chính chủ, full quyền sở hữu.'}</p>
                </div>
              ),
            },
            {
              key: 'warranty',
              label: 'Chính Sách Bảo Hành & Cam Kết',
              children: (
                <div style={{ color: '#cbd5e1', fontSize: '15px', lineHeight: 1.8 }}>
                  <h4 style={{ color: '#fff', marginBottom: '8px' }}>Cam kết từ ShopAccLienQuan.vn:</h4>
                  <ul>
                    <li>100% tài khoản đã được kiểm tra tính hợp lệ trước khi đăng bán.</li>
                    <li>Khách hàng được quyền kiểm tra tài khoản và đổi mật khẩu ngay lập tức.</li>
                    <li>Hỗ trợ xử lý sự cố trong vòng tối đa 24 giờ kể từ khi tiếp nhận khiếu nại.</li>
                  </ul>
                </div>
              ),
            },
          ]}
        />
      </div>
    </div>
  );
};

export default ProductDetailPage;
