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
        form.setFieldsValue({ type: 'TAI_KHOAN', sort_order: 0 });
        setOpen(true);
    };

    const openEdit = (category) => {
        setEditingCategory(category);
        const rawType = (category.loai_danh_muc || category.type || '').toUpperCase();
        const type = (rawType === 'SERVICE' || rawType === 'DICH_VU') ? 'DICH_VU' : 'TAI_KHOAN';
        form.setFieldsValue({
            name: category.ten_danh_muc || category.name,
            type: type,
            description: category.mo_ta || category.description,
            sort_order: category.thu_tu_hien_thi ?? category.sort_order ?? 0
        });
        setOpen(true);
    };

    const saveCategory = async (values) => {
        setSaving(true);
        try {
            const payload = {
                name: values.name,
                ten_danh_muc: values.name,
                type: values.type,
                loai_danh_muc: values.type,
                description: values.description,
                mo_ta: values.description,
                sort_order: values.sort_order ?? 0,
                thu_tu_hien_thi: values.sort_order ?? 0,
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
            message.error(error.response?.data?.message || 'Không thể xóa danh mục đang có sản phẩm.');
        }
    };

    const columns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
        { title: 'Tên danh mục', dataIndex: 'name', key: 'name', render: (name, record) => <strong style={{ color: '#fff' }}>{record.ten_danh_muc || name}</strong> },
        { title: 'Loại danh mục', dataIndex: 'type', key: 'type', render: (type, record) => {
            const val = (record.loai_danh_muc || type || '').toUpperCase();
            const isService = val === 'SERVICE' || val === 'DICH_VU';
            return <Tag color={isService ? 'magenta' : 'geekblue'}>{isService ? 'Dịch vụ' : 'Tài khoản'}</Tag>;
        }},
        { title: 'Slug', dataIndex: 'slug', key: 'slug', render: (slug, record) => record.duong_dan_tinh || slug },
        { title: 'Mô tả', dataIndex: 'description', key: 'description', ellipsis: true, render: (d, record) => record.mo_ta || d },
        { title: 'Thao tác', key: 'actions', render: (_, category) => (
            <Space>
                <Button icon={<EditOutlined />} onClick={() => openEdit(category)}>Sửa</Button>
                <Popconfirm title="Xóa danh mục này?" description="Danh mục còn sản phẩm sẽ không thể xóa." onConfirm={() => deleteCategory(category)} okText="Xóa" cancelText="Hủy">
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
                    <Form.Item name="type" label="Loại danh mục" rules={[{ required: true, message: 'Vui lòng chọn loại danh mục.' }]}>
                        <Select options={[
                            { value: 'TAI_KHOAN', label: 'Tài khoản' },
                            { value: 'DICH_VU', label: 'Dịch vụ' }
                        ]} />
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
