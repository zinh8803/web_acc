import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button, Empty, InputNumber, message, Table } from 'antd';
import { ArrowRightOutlined, DeleteOutlined, ShoppingCartOutlined } from '@ant-design/icons';
import api from '../services/api';
import { useCartStore } from '../stores/cartStore';

const CartPage = () => {
    const navigate = useNavigate();
    const { items, removeItem, updateQuantity, setItems } = useCartStore();

    useEffect(() => {
        api.get('/cart').then((response) => {
            const cartItems = response.data?.data?.items || [];
            setItems(cartItems.map((item) => {
                const product = item.variant?.product || {};
                const account = item.game_account;
                const image = product.primary_image || product.primaryImage;
                return {
                    id: item.id,
                    variant_id: item.variant_id,
                    game_account_id: item.game_account_id,
                    productId: product.id || item.variant?.product_id,
                    name: product.name ? `${product.name}${account ? ` - Tài khoản #${account.id}` : ''}` : item.product_name,
                    price: Number(item.variant?.sale_price || item.variant?.price || product.min_price || 0),
                    quantity: item.quantity,
                    thumbnailUrl: account?.image_url || product.thumbnail_url || image?.image_url || image?.url,
                    accountDetails: account?.public_details || [],
                };
            }));
        }).catch((error) => message.error(error.response?.data?.error?.message || 'Không tải được giỏ hàng từ máy chủ.'));
    }, [setItems]);

    const remove = async (id) => {
        try {
            await api.delete(`/cart/items/${id}`);
            removeItem(id);
        } catch (error) {
            message.error(error.response?.data?.error?.message || 'Không thể xóa sản phẩm khỏi giỏ hàng.');
        }
    };

    const update = async (id, quantity) => {
        try {
            await api.patch(`/cart/items/${id}`, { quantity });
            updateQuantity(id, quantity);
        } catch (error) {
            message.error(error.response?.data?.error?.message || 'Không thể cập nhật số lượng.');
        }
    };

    const subtotal = items.reduce((total, item) => total + Number(item.price || 0) * item.quantity, 0);
    const columns = [
        { title: 'Tài khoản đã chọn', dataIndex: 'name', key: 'name', render: (_, item) => <div style={{ display: 'flex', gap: 14, alignItems: 'center' }}>
            {item.thumbnailUrl && <img src={item.thumbnailUrl} alt="" style={{ width: 72, height: 60, objectFit: 'cover', borderRadius: 8 }} />}
            <div><strong style={{ color: '#fff' }}>{item.name}</strong>{item.accountDetails?.map((detail, index) => <div key={`${detail.label}-${index}`} style={{ color: '#94a3b8', fontSize: 12 }}>{detail.label}: {detail.value}</div>)}<div style={{ color: '#06b6d4', marginTop: 4 }}>{Number(item.price || 0).toLocaleString('vi-VN')} đ</div></div>
        </div> },
        { title: 'Số lượng', dataIndex: 'quantity', key: 'quantity', width: 140, render: (quantity, item) => item.game_account_id ? '1 tài khoản' : <InputNumber min={1} max={10} value={quantity} onChange={(value) => update(item.id, value || 1)} /> },
        { title: 'Thành tiền', key: 'total', render: (_, item) => <strong style={{ color: '#06b6d4' }}>{(Number(item.price || 0) * item.quantity).toLocaleString('vi-VN')} đ</strong> },
        { title: '', key: 'action', width: 60, render: (_, item) => <Button type="text" danger icon={<DeleteOutlined />} onClick={() => remove(item.id)} /> },
    ];

    if (!items.length) return <div style={{ maxWidth: 800, margin: '60px auto', padding: 20, textAlign: 'center' }}><Empty image={<ShoppingCartOutlined style={{ fontSize: 60, color: '#6366f1' }} />} description={<span style={{ color: '#cbd5e1' }}>Giỏ hàng đang trống</span>}><Button type="primary" onClick={() => navigate('/catalog')}>Khám phá kho acc</Button></Empty></div>;

    return <div style={{ maxWidth: 1100, margin: '0 auto', padding: '36px 20px 60px' }}>
        <h1 style={{ color: '#fff', fontFamily: 'Rajdhani' }}>GIỎ HÀNG ({items.length} tài khoản/sản phẩm)</h1>
        <div style={{ background: '#121324', borderRadius: 12, padding: 16 }}><Table columns={columns} dataSource={items} rowKey="id" pagination={false} scroll={{ x: 700 }} /></div>
        <div style={{ display: 'flex', justifyContent: 'flex-end', alignItems: 'center', gap: 20, marginTop: 24 }}><strong style={{ color: '#fff', fontSize: 20 }}>Tổng: {subtotal.toLocaleString('vi-VN')} đ</strong><Button type="primary" size="large" icon={<ArrowRightOutlined />} onClick={() => navigate('/checkout')}>Tiến hành đặt hàng</Button></div>
    </div>;
};

export default CartPage;
