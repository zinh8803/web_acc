import React, { useEffect, useState } from 'react';
import { Table, Button, Space, Modal, Form, Input, Select, InputNumber, Tag, message, Popconfirm } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons';
import api from '../../services/api';

const CategoriesPage = () => {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const [saving, setSaving] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [form] = Form.useForm();

    const fetchData = async () => {
        setLoading(true);
        try {
            const res = await api.get('/admin/categories');
            setCategories(res.data?.data || res.data || []);
        } catch {
            message.error('Không tải được danh mục.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    const openCreate = () => {
        setEditingCategory(null);
        form.resetFields();
        form.setFieldsValue({ type: 'ACCOUNT', sort_order: 0 });
        setOpen(true);
    };

    const openEdit = (category) => {
        setEditingCategory(category);
        form.setFieldsValue({
            name: category.name || category.ten_danh_muc,
            type: category.type || category.loai_danh_muc,
            description: category.description || category.mo_ta,
            sort_order: category.sort_order ?? category.thu_tu_hien_thi ?? 0
        });
        setOpen(true);
    };

    const saveCategory = async (values) => {
        setSaving(true);
        try {
            const payload = {
                name: values.name,
                type: values.type,
                description: values.description,
                sort_order: values.sort_order ?? 0,
            };
            if (editingCategory) {
                await api.put(`/admin/categories/${editingCategory.id}`, payload);
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
        { title: 'Tên danh mục', dataIndex: 'name', key: 'name', render: (name, record) => <strong style={{ color: '#fff' }}>{name || record.ten_danh_muc}</strong> },
        { title: 'Loại', dataIndex: 'type', key: 'type', render: (type, record) => {
            const val = type || record.loai_danh_muc;
            return <Tag color={val === 'SERVICE' ? 'magenta' : 'geekblue'}>{val === 'SERVICE' ? 'Dịch vụ' : 'Tài khoản'}</Tag>;
        }},
        { title: 'Slug', dataIndex: 'slug', key: 'slug', render: (slug, record) => slug || record.duong_dan_tinh },
        { title: 'Mô tả', dataIndex: 'description', key: 'description', ellipsis: true, render: (d, record) => d || record.mo_ta },
        { title: 'Thao tác', key: 'actions', render: (_, category) => (
            <Space>
                <Button icon={<EditOutlined />} onClick={() => openEdit(category)}>Sửa</Button>
                <Popconfirm title="Xóa danh mục này?" description="Danh mục còn sản phẩm hoặc danh mục con sẽ không thể xóa." onConfirm={() => deleteCategory(category)} okText="Xóa" cancelText="Hủy">
                    <Button danger icon={<DeleteOutlined />}>Xóa</Button>
                </Popconfirm>
            </Space>
        )},
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
                <div>
                    <h1 style={{ color: '#fff', fontSize: 24, fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>QUẢN LÝ DANH MỤC</h1>
                    <p style={{ color: '#94a3b8', margin: 0 }}>Danh mục sản phẩm & dịch vụ Liên Quân Mobile.</p>
                </div>
                <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm danh mục</Button>
            </div>
            <div style={{ background: '#121324', borderRadius: 14, padding: 20, border: '1px solid rgba(255,255,255,0.08)' }}>
                <Table columns={columns} dataSource={categories} rowKey="id" loading={loading} />
            </div>
            <Modal title={editingCategory ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới'} open={open} onCancel={() => setOpen(false)} footer={null} destroyOnClose>
                <Form form={form} layout="vertical" onFinish={saveCategory}>
                    <Form.Item name="name" label="Tên danh mục" rules={[{ required: true, message: 'Vui lòng nhập tên danh mục.' }]}>
                        <Input maxLength={150} />
                    </Form.Item>
                    <Form.Item name="type" label="Loại" rules={[{ required: true }]}>
                        <Select options={[{ value: 'ACCOUNT', label: 'Tài khoản (Account)' }, { value: 'SERVICE', label: 'Dịch vụ (Service)' }]} />
                    </Form.Item>
                    <Form.Item name="description" label="Mô tả">
                        <Input.TextArea rows={3} />
                    </Form.Item>
                    <Form.Item name="sort_order" label="Thứ tự hiển thị">
                        <InputNumber min={0} style={{ width: '100%' }} />
                    </Form.Item>
                    <Button type="primary" htmlType="submit" loading={saving} block>
                        {editingCategory ? 'Lưu thay đổi' : 'Lưu danh mục'}
                    </Button>
                </Form>
            </Modal>
        </div>
    );
};

export default CategoriesPage;
