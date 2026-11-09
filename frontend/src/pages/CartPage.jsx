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
            const cartItems = response.data?.data?.items || response.data?.data?.chi_tiets || [];
            setItems(cartItems.map((item) => {
                const product = item.variant?.product || item.bien_the?.san_pham || {};
                const account = item.game_account || item.tai_khoan_game;
                const image = product.primary_image || product.primaryImage;
                const variantId = item.variant_id ?? item.bien_the_id;
                const gameAccountId = item.game_account_id ?? item.tai_khoan_game_id;
                return {
                    id: item.id,
                    variant_id: variantId,
                    bien_the_id: variantId,
                    game_account_id: gameAccountId,
                    tai_khoan_game_id: gameAccountId,
                    productId: product.id || item.variant?.product_id || item.bien_the?.san_pham_id,
                    name: product.name || product.ten_san_pham ? `${product.name || product.ten_san_pham}${account ? ` - Tài khoản #${account.id}` : ''}` : (item.product_name || item.name || 'Sản phẩm'),
                    price: Number(item.variant?.sale_price || item.variant?.price || item.bien_the?.gia_khuyen_mai || item.bien_the?.gia_goc || product.min_price || product.gia_thap_nhat || 0),
                    quantity: gameAccountId ? 1 : (item.quantity || item.so_luong || 1),
                    thumbnailUrl: account?.image_url || account?.anh_minh_hoa || product.thumbnail_url || product.anh_dai_dien || image?.image_url || image?.url,
                    accountDetails: account?.public_details || account?.thong_tin_cong_khai || [],
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
        { title: 'Số lượng', dataIndex: 'quantity', key: 'quantity', width: 140, render: (quantity, item) => (item.game_account_id || item.tai_khoan_game_id) ? '1 tài khoản' : <InputNumber min={1} max={10} value={quantity} onChange={(value) => update(item.id, value || 1)} /> },
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
