import React, { useEffect, useState } from 'react';
import { Button, Form, Input, InputNumber, message, Modal, Popconfirm, Select, Space, Table, Tag, Upload } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import api from '../../services/api';

const CategoriesPage = () => {
    const [categories, setCategories] = useState([]);
    const [games, setGames] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [open, setOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [form] = Form.useForm();

    const fetchData = async () => {
        setLoading(true);
        try {
            const [categoryResponse, gameResponse] = await Promise.all([api.get('/admin/categories'), api.get('/admin/games')]);
            setCategories(Array.isArray(categoryResponse.data?.data) ? categoryResponse.data.data : []);
            setGames(Array.isArray(gameResponse.data?.data) ? gameResponse.data.data : []);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không tải được danh mục và game.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchData(); }, []);

    const openCreate = () => {
        setEditingCategory(null);
        form.resetFields();
        form.setFieldsValue({ type: 'ACCOUNT', sort_order: 0 });
        setOpen(true);
    };

    const openEdit = (category) => {
        setEditingCategory(category);
        form.setFieldsValue({ name: category.name, game_id: category.game_id, type: category.type, description: category.description, sort_order: category.sort_order });
        setOpen(true);
    };

    const saveCategory = async (values) => {
        setSaving(true);
        try {
            const payload = new FormData();
            Object.entries(values).forEach(([key, value]) => {
                if (key === 'image_file') {
                    const file = value?.[0]?.originFileObj;
                    if (file) payload.append('image_file', file);
                } else if (value !== undefined && value !== null) {
                    payload.append(key, String(value));
                }
            });
            if (editingCategory) {
                payload.append('_method', 'PUT');
                await api.post(`/admin/categories/${editingCategory.id}`, payload);
                message.success('Đã cập nhật danh mục.');
            } else {
                await api.post('/admin/categories', payload);
                message.success('Đã thêm danh mục.');
            }
            setOpen(false);
            await fetchData();
        } catch (error) {
            message.error(error.response?.data?.message || 'Không lưu được danh mục.');
        } finally {
            setSaving(false);
        }
    };

    const deleteCategory = async (category) => {
        try {
            await api.delete(`/admin/categories/${category.id}`);
            message.success('Đã xóa danh mục.');
            await fetchData();
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể xóa danh mục đang có sản phẩm hoặc danh mục con.');
        }
    };

    const columns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
        { title: 'Tên danh mục', dataIndex: 'name', key: 'name', render: (name) => <strong style={{ color: '#fff' }}>{name}</strong> },
        { title: 'Loại', dataIndex: 'type', key: 'type', render: (type) => <Tag color={type === 'SERVICE' ? 'magenta' : 'geekblue'}>{type === 'SERVICE' ? 'Dịch vụ' : 'Tài khoản'}</Tag> },
        { title: 'Game', dataIndex: 'game', key: 'game', render: (game) => <Tag color="purple">{game?.name || 'Chung'}</Tag> },
        { title: 'Slug', dataIndex: 'slug', key: 'slug' },
        { title: 'Mô tả', dataIndex: 'description', key: 'description', ellipsis: true },
        { title: 'Thao tác', key: 'actions', render: (_, category) => <Space><Button icon={<EditOutlined />} onClick={() => openEdit(category)}>Sửa</Button><Popconfirm title="Xóa danh mục này?" description="Danh mục còn sản phẩm hoặc danh mục con sẽ không thể xóa." onConfirm={() => deleteCategory(category)} okText="Xóa" cancelText="Hủy"><Button danger icon={<DeleteOutlined />}>Xóa</Button></Popconfirm></Space> },
    ];

    return <div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
            <div><h1 style={{ color: '#fff', fontSize: 24, fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>QUẢN LÝ DANH MỤC</h1><p style={{ color: '#94a3b8', margin: 0 }}>Danh mục gắn với game được quản lý riêng.</p></div>
            <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm danh mục</Button>
        </div>
        <div style={{ background: '#121324', borderRadius: 14, padding: 20, border: '1px solid rgba(255,255,255,0.08)' }}><Table columns={columns} dataSource={categories} rowKey="id" loading={loading} /></div>
        <Modal title={editingCategory ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới'} open={open} onCancel={() => setOpen(false)} footer={null} destroyOnClose>
            <Form form={form} layout="vertical" onFinish={saveCategory}>
                <Form.Item name="name" label="Tên danh mục" rules={[{ required: true, message: 'Vui lòng nhập tên danh mục.' }]}><Input maxLength={150} /></Form.Item>
                <Form.Item name="game_id" label="Game" rules={[{ required: true, message: 'Vui lòng chọn game.' }]}><Select placeholder="Chọn game" options={games.map((game) => ({ value: game.id, label: game.name }))} /></Form.Item>
                <Form.Item name="type" label="Loại" rules={[{ required: true }]}><Select options={[{ value: 'ACCOUNT', label: 'Tài khoản (Account)' }, { value: 'SERVICE', label: 'Dịch vụ (Service)' }]} /></Form.Item>
                <Form.Item name="description" label="Mô tả"><Input.TextArea rows={3} /></Form.Item>
                {editingCategory?.image_url && <img src={editingCategory.image_url} alt="Ảnh hiện tại" style={{ width: 96, height: 64, objectFit: 'cover', marginBottom: 12 }} />}
                <Form.Item name="image_file" label="Ảnh danh mục" valuePropName="fileList" getValueFromEvent={(event) => event?.fileList}><Upload accept="image/*" maxCount={1} beforeUpload={() => false} listType="picture"><Button>Chọn ảnh</Button></Upload></Form.Item>
                <Form.Item name="sort_order" label="Thứ tự hiển thị"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                <Button type="primary" htmlType="submit" loading={saving} block>{editingCategory ? 'Lưu thay đổi' : 'Lưu danh mục'}</Button>
            </Form>
        </Modal>
    </div>;
};

export default CategoriesPage;
