import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Table, Button, InputNumber, Row, Col } from 'antd';
import { DeleteOutlined, ArrowRightOutlined, ShoppingCartOutlined } from '@ant-design/icons';
import { useCartStore } from '../stores/cartStore';

const CartPage: React.FC = () => {
  const navigate = useNavigate();
  const { items, removeItem, updateQuantity } = useCartStore();

  const subtotal = items ? items.reduce((sum, item) => sum + (item.price || 0) * item.quantity, 0) : 0;

  const columns = [
    {
      title: 'Sản phẩm',
      dataIndex: 'name',
      key: 'name',
      render: (_: any, record: any) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
          <img
            src={record.thumbnailUrl || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=100'}
            alt={record.name}
            style={{ width: '60px', height: '60px', borderRadius: '8px', objectFit: 'cover' }}
          />
          <div>
            <div style={{ color: '#fff', fontWeight: 700, fontSize: '15px' }}>{record.name}</div>
            <div style={{ color: '#94a3b8', fontSize: '13px' }}>Đơn giá: {Number(record.price || 0).toLocaleString('vi-VN')} đ</div>
          </div>
        </div>
      ),
    },
    {
      title: 'Số lượng',
      dataIndex: 'quantity',
      key: 'quantity',
      width: 130,
      render: (qty: number, record: any) => (
        <InputNumber
          min={1}
          max={10}
          value={qty}
          onChange={(val) => updateQuantity(record.id, val || 1)}
        />
      ),
    },
    {
      title: 'Thành tiền',
      key: 'total',
      width: 160,
      render: (_: any, record: any) => (
        <span style={{ color: '#06b6d4', fontWeight: 800, fontSize: '16px', fontFamily: 'Rajdhani' }}>
          {((record.price || 0) * record.quantity).toLocaleString('vi-VN')} đ
        </span>
      ),
    },
    {
      title: '',
      key: 'action',
      width: 60,
      render: (_: any, record: any) => (
        <Button
          type="text"
          danger
          icon={<DeleteOutlined />}
          onClick={() => removeItem(record.id)}
        />
      ),
    },
  ];

  if (!items || items.length === 0) {
    return (
      <div style={{ maxWidth: '800px', margin: '60px auto', textAlign: 'center', padding: '0 20px' }}>
        <div style={{ background: '#121324', borderRadius: '16px', padding: '60px 24px', border: '1px solid rgba(255,255,255,0.08)' }}>
          <ShoppingCartOutlined style={{ fontSize: '64px', color: '#6366f1', marginBottom: '20px' }} />
          <h2 style={{ color: '#fff', marginBottom: '12px' }}>Giỏ hàng của bạn đang trống</h2>
          <p style={{ color: '#94a3b8', marginBottom: '24px' }}>
            Hãy chọn cho mình tài khoản game ưng ý để tiếp tục thanh toán nhé.
          </p>
          <Button type="primary" size="large" onClick={() => navigate('/catalog')}>
            Khám Phá Kho Acc
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }}>
      <h1 style={{ color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
        GIỎ HÀNG CỦA BẠN ({items.length} sản phẩm)
      </h1>

      <Row gutter={[32, 32]}>
        <Col xs={24} lg={16}>
          <div style={{ background: '#121324', borderRadius: '12px', padding: '16px', border: '1px solid rgba(255,255,255,0.08)' }}>
            <Table
              columns={columns}
              dataSource={items}
              rowKey="id"
              pagination={false}
              style={{ background: 'transparent' }}
            />
          </div>
        </Col>

        <Col xs={24} lg={8}>
          <div style={{ background: '#121324', borderRadius: '12px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)' }}>
            <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }}>
              Tóm Tắt Đơn Hàng
            </h3>

            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '16px', color: '#cbd5e1' }}>
              <span>Tạm tính:</span>
              <span style={{ fontWeight: 600 }}>{subtotal.toLocaleString('vi-VN')} đ</span>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '24px', color: '#cbd5e1' }}>
              <span>Phí giao dịch tự động:</span>
              <span style={{ color: '#10b981', fontWeight: 600 }}>Miễn phí (0đ)</span>
            </div>

            <div
              style={{
                borderTop: '1px solid rgba(255,255,255,0.08)',
                paddingTop: '16px',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'baseline',
                marginBottom: '28px',
              }}
            >
              <span style={{ color: '#fff', fontSize: '16px', fontWeight: 700 }}>Tổng thanh toán:</span>
              <span style={{ color: '#06b6d4', fontSize: '26px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                {subtotal.toLocaleString('vi-VN')} đ
              </span>
            </div>

            <Button
              type="primary"
              size="large"
              block
              icon={<ArrowRightOutlined />}
              onClick={() => navigate('/checkout')}
              style={{ height: '48px', fontSize: '16px', fontWeight: 700, borderRadius: '8px' }}
            >
              Tiến Hành Đặt Hàng
            </Button>
          </div>
        </Col>
      </Row>
    </div>
  );
};

export default CartPage;
