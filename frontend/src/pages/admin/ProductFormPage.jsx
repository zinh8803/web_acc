import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button, Form, Input, InputNumber, message, Select, Upload } from 'antd';
import api from '../../services/api';

const ProductFormPage = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const [games, setGames] = useState([]);
    const [categories, setCategories] = useState([]);
    const [currentImage, setCurrentImage] = useState(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const loadFormData = async () => {
            try {
                const [gamesResponse, categoriesResponse] = await Promise.all([api.get('/admin/games'), api.get('/admin/categories')]);
                setGames(gamesResponse.data?.data || []);
                setCategories(categoriesResponse.data?.data || []);
                if (id) {
                    const productResponse = await api.get(`/admin/products/${id}`);
                    const product = productResponse.data?.data;
                    if (product) {
                        form.setFieldsValue({ ...product, price: product.variants?.[0]?.price ?? product.min_price });
                        setCurrentImage(product.thumbnail_url);
                    }
                }
            } catch (error) {
                message.error(error.response?.data?.message || 'Không tải được dữ liệu sản phẩm.');
            }
        };
        loadFormData();
    }, [id, form]);

    const saveProduct = async (values) => {
        setLoading(true);
        try {
            const payload = new FormData();
            Object.entries(values).forEach(([key, value]) => {
                if (key === 'thumbnail_file') {
                    const file = value?.[0]?.originFileObj;
                    if (file) payload.append('thumbnail_file', file);
                } else if (value !== undefined && value !== null) {
                    payload.append(key, String(value));
                }
            });
            if (id) {
                payload.append('_method', 'PUT');
                await api.post(`/admin/products/${id}`, payload);
                message.success('Cập nhật sản phẩm thành công!');
            } else {
                await api.post('/admin/products', payload);
                message.success('Tạo sản phẩm thành công!');
            }
            navigate('/admin/products');
        } catch (error) {
            message.error(error.response?.data?.message || 'Có lỗi xảy ra khi lưu sản phẩm.');
        } finally {
            setLoading(false);
        }
    };

    return <div style={{ maxWidth: 800, margin: '0 auto' }}>
        <h1 style={{ color: '#fff', fontSize: 24, fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: 24 }}>{id ? 'CẬP NHẬT SẢN PHẨM' : 'THÊM SẢN PHẨM MỚI'}</h1>
        <div style={{ background: '#121324', borderRadius: 14, padding: 32, border: '1px solid rgba(255,255,255,0.08)' }}>
            <Form form={form} layout="vertical" onFinish={saveProduct} initialValues={{ type: 'ACCOUNT', status: 'ACTIVE' }}>
                <Form.Item name="name" label="Tên sản phẩm" rules={[{ required: true }]}><Input size="large" /></Form.Item>
                <Form.Item name="game_id" label="Trò chơi" rules={[{ required: true }]}><Select options={games.map((game) => ({ value: game.id, label: game.name }))} /></Form.Item>
                <Form.Item name="category_id" label="Danh mục" rules={[{ required: true }]}><Select options={categories.map((category) => ({ value: category.id, label: category.name }))} /></Form.Item>
                <Form.Item name="type" label="Loại sản phẩm" rules={[{ required: true }]}><Select options={[{ value: 'ACCOUNT', label: 'Tài khoản' }, { value: 'SERVICE', label: 'Dịch vụ' }]} /></Form.Item>
                <Form.Item name="price" label="Giá bán (VNĐ)" rules={[{ required: true }]}><InputNumber min={0} style={{ width: '100%' }} size="large" /></Form.Item>
                {currentImage && <img src={currentImage} alt="Ảnh hiện tại" style={{ width: 140, height: 90, objectFit: 'cover', marginBottom: 12 }} />}
                <Form.Item name="thumbnail_file" label="Ảnh đại diện" valuePropName="fileList" getValueFromEvent={(event) => event?.fileList}><Upload accept="image/*" maxCount={1} beforeUpload={() => false} listType="picture"><Button>Chọn ảnh</Button></Upload></Form.Item>
                <Form.Item name="short_description" label="Mô tả ngắn"><Input.TextArea rows={2} /></Form.Item>
                <Form.Item name="description" label="Mô tả chi tiết"><Input.TextArea rows={4} /></Form.Item>
                <Form.Item name="status" label="Trạng thái" rules={[{ required: true }]}><Select options={[{ value: 'ACTIVE', label: 'Đang bán' }, { value: 'DRAFT', label: 'Nháp' }, { value: 'HIDDEN', label: 'Ẩn' }, { value: 'SOLD_OUT', label: 'Hết hàng' }]} /></Form.Item>
                <Button type="primary" size="large" block htmlType="submit" loading={loading} style={{ marginTop: 16, fontWeight: 700 }}>Lưu sản phẩm</Button>
            </Form>
        </div>
    </div>;
};

export default ProductFormPage;
