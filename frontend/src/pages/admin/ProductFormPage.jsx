import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button, Form, Input, InputNumber, message, Select, Upload, Card, Table, Space, Tag, Divider } from 'antd';
import { PlusOutlined, DeleteOutlined } from '@ant-design/icons';
import api from '../../services/api';

const ProductFormPage = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const [categories, setCategories] = useState([]);
    const [currentImage, setCurrentImage] = useState(null);
    const [loading, setLoading] = useState(false);
    const [variants, setVariants] = useState([]);
    const [productType, setProductType] = useState('ACCOUNT');

    useEffect(() => {
        const loadFormData = async () => {
            try {
                const categoriesResponse = await api.get('/admin/categories');
                setCategories(categoriesResponse.data?.data || []);

                if (id) {
                    const productResponse = await api.get(`/admin/products/${id}`);
                    const product = productResponse.data?.data;
                    if (product) {
                        form.setFieldsValue({
                            ...product,
                            price: product.variants?.[0]?.price ?? product.min_price,
                        });
                        setCurrentImage(product.thumbnail_url);
                        setProductType(product.type || 'ACCOUNT');
                        if (product.variants && Array.isArray(product.variants)) {
                            setVariants(product.variants);
                        }
                    }
                } else {
                    form.setFieldsValue({ type: 'ACCOUNT', status: 'ACTIVE' });
                }
            } catch (error) {
                message.error(error.response?.data?.message || 'Không tải được dữ liệu sản phẩm.');
            }
        };
        loadFormData();
    }, [id, form]);

    const handleVariantChange = (index, field, value) => {
        const updated = [...variants];
        updated[index] = { ...updated[index], [field]: value };
        setVariants(updated);
    };

    const handleAddVariant = () => {
        setVariants([
            ...variants,
            {
                id: null,
                name: 'Gói dịch vụ mới',
                price: 100000,
                sale_price: 80000,
                stock_quantity: 999,
                status: 'ACTIVE',
            }
        ]);
    };

    const handleDeleteVariant = (index) => {
        const updated = variants.filter((_, i) => i !== index);
        setVariants(updated);
    };

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

            // Gửi mảng variants đã chỉnh sửa giá
            if (variants && variants.length > 0) {
                payload.append('variants', JSON.stringify(variants));
            }

            if (id) {
                payload.append('_method', 'PUT');
                await api.post(`/admin/products/${id}`, payload);
                message.success('Cập nhật sản phẩm & bảng giá dịch vụ thành công!');
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

    const variantColumns = [
        {
            title: 'Tên Gói / Hạng Mục Cày',
            dataIndex: 'name',
            key: 'name',
            render: (text, record, index) => (
                <Input
                    value={text}
                    onChange={(e) => handleVariantChange(index, 'name', e.target.value)}
                    style={{ fontWeight: 600 }}
                />
            ),
        },
        {
            title: 'Giá Gốc (VNĐ)',
            dataIndex: 'price',
            key: 'price',
            width: 170,
            render: (val, record, index) => (
                <InputNumber
                    min={0}
                    step={10000}
                    value={val}
                    formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    parser={(v) => v.replace(/\$\s?|(,*)/g, '')}
                    onChange={(v) => handleVariantChange(index, 'price', v)}
                    style={{ width: '100%' }}
                />
            ),
        },
        {
            title: 'Giá Khuyến Mãi (VNĐ)',
            dataIndex: 'sale_price',
            key: 'sale_price',
            width: 170,
            render: (val, record, index) => (
                <InputNumber
                    min={0}
                    step={10000}
                    value={val}
                    formatter={(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                    parser={(v) => v.replace(/\$\s?|(,*)/g, '')}
                    onChange={(v) => handleVariantChange(index, 'sale_price', v)}
                    style={{ width: '100%' }}
                />
            ),
        },
        {
            title: 'Xóa',
            key: 'actions',
            width: 70,
            render: (_, __, index) => (
                <Button
                    danger
                    icon={<DeleteOutlined />}
                    size="small"
                    onClick={() => handleDeleteVariant(index)}
                />
            ),
        },
    ];

    return (
        <div style={{ maxWidth: 900, margin: '0 auto', paddingBottom: '60px' }}>
            <h1 style={{ color: '#fff', fontSize: 24, fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: 24 }}>
                {id ? 'CẬP NHẬT SẢN PHẨM & BẢNG GIÁ' : 'THÊM SẢN PHẨM MỚI'}
            </h1>
            <div style={{ background: '#121324', borderRadius: 14, padding: 32, border: '1px solid rgba(255,255,255,0.08)' }}>
                <Form
                    form={form}
                    layout="vertical"
                    onFinish={saveProduct}
                    initialValues={{ type: 'ACCOUNT', status: 'ACTIVE' }}
                >
                    <Form.Item name="name" label="Tên sản phẩm / dịch vụ" rules={[{ required: true }]}>
                        <Input size="large" />
                    </Form.Item>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px' }}>
                        <Form.Item name="category_id" label="Danh mục Liên Quân" rules={[{ required: true }]}>
                            <Select options={categories.map((c) => ({ value: c.id, label: c.name }))} />
                        </Form.Item>
                        <Form.Item name="type" label="Loại sản phẩm" rules={[{ required: true }]}>
                            <Select
                                onChange={(val) => setProductType(val)}
                                options={[
                                    { value: 'ACCOUNT', label: 'Tài khoản Game (Acc)' },
                                    { value: 'SERVICE', label: 'Dịch vụ Game (Cày thuê / Top tướng)' },
                                ]}
                            />
                        </Form.Item>
                    </div>

                    <Form.Item name="price" label="Giá bán tham chiếu (VNĐ)" rules={[{ required: true }]}>
                        <InputNumber min={0} style={{ width: '100%' }} size="large" />
                    </Form.Item>

                    {/* BẢNG CHỈNH GIÁ CÁC GÓI DỊCH VỤ / VARIANTS */}
                    {(productType === 'SERVICE' || variants.length > 0) && (
                        <Card
                            title={
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <span style={{ color: '#fff', fontSize: '16px', fontWeight: 700 }}>
                                        Bảng Giá Các Gói Dịch Vụ (Chỉnh Giá Trực Tiếp)
                                    </span>
                                    <Button
                                        type="dashed"
                                        size="small"
                                        icon={<PlusOutlined />}
                                        onClick={handleAddVariant}
                                        style={{ color: '#6366f1', borderColor: '#6366f1' }}
                                    >
                                        Thêm Gói Mới
                                    </Button>
                                </div>
                            }
                            style={{
                                background: 'rgba(99, 102, 241, 0.05)',
                                borderColor: 'rgba(99, 102, 241, 0.2)',
                                marginBottom: '24px',
                            }}
                        >
                            <div style={{ color: '#94a3b8', fontSize: '13px', marginBottom: '14px' }}>
                                Admin có thể trực tiếp sửa giá gốc, giá khuyến mãi của từng gói cày rank (Đồng &rarr; Cao Thủ, Cao Thủ &rarr; Chiến Tướng,...) hoặc gói cày top tướng (Top Tỉnh, Miền, Việt Nam, Server) tại đây:
                            </div>
                            <Table
                                columns={variantColumns}
                                dataSource={variants}
                                rowKey={(r, idx) => r.id || `idx-${idx}`}
                                pagination={false}
                                size="small"
                            />
                        </Card>
                    )}

                    {currentImage && (
                        <div style={{ marginBottom: 12 }}>
                            <div style={{ color: '#94a3b8', fontSize: '12px', marginBottom: '4px' }}>Ảnh hiện tại:</div>
                            <img src={currentImage} alt="Ảnh đại diện" style={{ width: 140, height: 90, objectFit: 'cover', borderRadius: '8px' }} />
                        </div>
                    )}

                    <Form.Item
                        name="thumbnail_file"
                        label="Ảnh đại diện"
                        valuePropName="fileList"
                        getValueFromEvent={(e) => e?.fileList}
                    >
                        <Upload accept="image/*" maxCount={1} beforeUpload={() => false} listType="picture">
                            <Button>Chọn ảnh mới</Button>
                        </Upload>
                    </Form.Item>

                    <Form.Item name="short_description" label="Mô tả ngắn">
                        <Input.TextArea rows={2} />
                    </Form.Item>

                    <Form.Item name="description" label="Mô tả chi tiết">
                        <Input.TextArea rows={4} />
                    </Form.Item>

                    <Form.Item name="status" label="Trạng thái" rules={[{ required: true }]}>
                        <Select
                            options={[
                                { value: 'ACTIVE', label: 'Đang mở bán' },
                                { value: 'DRAFT', label: 'Nháp' },
                                { value: 'HIDDEN', label: 'Tạm ẩn' },
                                { value: 'SOLD_OUT', label: 'Tạm dừng dịch vụ' },
                            ]}
                        />
                    </Form.Item>

                    <Button
                        type="primary"
                        size="large"
                        block
                        htmlType="submit"
                        loading={loading}
                        style={{ marginTop: 16, height: '48px', fontWeight: 800, fontSize: '16px' }}
                    >
                        LƯU SẢN PHẨM & CẬP NHẬT GIÁ
                    </Button>
                </Form>
            </div>
        </div>
    );
};

export default ProductFormPage;
