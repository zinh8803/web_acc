import React, { useEffect, useState } from 'react';
import { Button, message, Popconfirm, Space, Table, Tag } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import { Link, useNavigate } from 'react-router-dom';
import api from '../../services/api';

const ProductsPage = () => {
    const navigate = useNavigate();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState({ current: 1, pageSize: 50, total: 0 });

    const fetchProducts = async (page = pagination.current, pageSize = pagination.pageSize) => {
        setLoading(true);
        try {
            const response = await api.get('/admin/products', { params: { page, per_page: pageSize } });
            const result = response.data?.data;
            setProducts(Array.isArray(result?.data) ? result.data : []);
            setPagination({ current: result?.current_page || page, pageSize: result?.per_page || pageSize, total: result?.total || 0 });
        } catch (error) {
            message.error(error.response?.data?.message || 'Không tải được sản phẩm.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchProducts(1, 50); }, []);

    const handleDelete = async (id) => {
        try {
            await api.delete(`/admin/products/${id}`);
            message.success('Đã xóa sản phẩm.');
            fetchProducts(products.length === 1 && pagination.current > 1 ? pagination.current - 1 : pagination.current);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể xóa sản phẩm.');
        }
    };

    const columns = [
        { title: 'Mã SKU', dataIndex: 'sku', key: 'sku', render: (sku) => <Tag color="cyan">{sku}</Tag> },
        { title: 'Tên sản phẩm', dataIndex: 'name', key: 'name', render: (name, product) => <Space>{product.thumbnail_url && <img src={product.thumbnail_url} alt={name} style={{ width: 42, height: 42, borderRadius: 6, objectFit: 'cover' }} />}<span style={{ color: '#fff', fontWeight: 600 }}>{name}</span></Space> },
        { title: 'Danh mục', dataIndex: ['category', 'name'], key: 'category', render: (name) => name || '—' },
        { title: 'Loại', dataIndex: 'type', key: 'type', render: (type) => <Tag color={type === 'ACCOUNT' ? 'purple' : 'blue'}>{type === 'ACCOUNT' ? 'Acc Game' : 'Dịch vụ'}</Tag> },
        { title: 'Giá bán', dataIndex: 'min_price', key: 'min_price', render: (price) => <span style={{ color: '#06b6d4', fontWeight: 700 }}>{Number(price || 0).toLocaleString('vi-VN')} đ</span> },
        { title: 'Còn trong kho', dataIndex: 'available_accounts_count', key: 'available_accounts_count', render: (count) => count ?? 0 },
        { title: 'Đang giữ', dataIndex: 'reserved_accounts_count', key: 'reserved_accounts_count', render: (count) => count ?? 0 },
        { title: 'Đã bán', dataIndex: 'sold_accounts_count', key: 'sold_accounts_count', render: (count) => count ?? 0 },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (status) => <Tag color={status === 'ACTIVE' ? 'success' : 'default'}>{status}</Tag> },
        { title: 'Thao tác', key: 'action', render: (_, product) => <Space><Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/admin/products/${product.id}/edit`)} /><Popconfirm title="Xóa sản phẩm này?" onConfirm={() => handleDelete(product.id)} okText="Xóa" cancelText="Hủy"><Button size="small" danger icon={<DeleteOutlined />} /></Popconfirm></Space> },
    ];

    return <div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
            <div><h1 style={{ color: '#fff', fontSize: 24, fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>QUẢN LÝ SẢN PHẨM & TÀI KHOẢN</h1><p style={{ color: '#94a3b8', fontSize: 13, margin: 0 }}>Mỗi sản phẩm có thể chứa nhiều tài khoản trong kho; số lượng cập nhật theo trạng thái từng tài khoản.</p></div>
            <Link to="/admin/products/create"><Button type="primary" icon={<PlusOutlined />}>Thêm sản phẩm mới</Button></Link>
        </div>
        <div style={{ background: '#121324', borderRadius: 14, padding: 20, border: '1px solid rgba(255,255,255,0.08)' }}>
            <Table columns={columns} dataSource={products} rowKey="id" loading={loading} scroll={{ x: 1200 }} pagination={{ current: pagination.current, pageSize: pagination.pageSize, total: pagination.total, showSizeChanger: true, pageSizeOptions: [20, 50, 100], showTotal: (total) => `Tổng ${total} sản phẩm` }} onChange={(next) => fetchProducts(next.current, next.pageSize)} />
        </div>
    </div>;
};

export default ProductsPage;
