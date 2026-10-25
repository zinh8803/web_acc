import React, { useEffect, useState } from 'react';
import { Button, Form, Input, InputNumber, message, Modal, Popconfirm, Select, Space, Table, Tag, Upload } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import api from '../../services/api';

const GamesPage = () => {
    const [games, setGames] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [open, setOpen] = useState(false);
    const [editingGame, setEditingGame] = useState(null);
    const [form] = Form.useForm();

    const loadGames = async () => {
        setLoading(true);
        try {
            const response = await api.get('/admin/games');
            setGames(Array.isArray(response.data?.data) ? response.data.data : []);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không tải được danh sách game.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { loadGames(); }, []);

    const openCreate = () => {
        setEditingGame(null);
        form.resetFields();
        form.setFieldsValue({ status: 'ACTIVE', sort_order: 0 });
        setOpen(true);
    };

    const openEdit = (game) => {
        setEditingGame(game);
        form.setFieldsValue(game);
        setOpen(true);
    };

    const saveGame = async (values) => {
        setSaving(true);
        try {
            const payload = new FormData();
            Object.entries(values).forEach(([key, value]) => {
                if (key === 'logo_file') {
                    const file = value?.[0]?.originFileObj;
                    if (file) payload.append('logo_file', file);
                } else if (value !== undefined && value !== null) {
                    payload.append(key, String(value));
                }
            });
            if (!payload.has('sort_order')) payload.append('sort_order', '0');
            if (editingGame) {
                payload.append('_method', 'PUT');
                await api.post(`/admin/games/${editingGame.id}`, payload);
                message.success('Đã cập nhật game.');
            } else {
                await api.post('/admin/games', payload);
                message.success('Đã thêm game.');
            }
            setOpen(false);
            await loadGames();
        } catch (error) {
            message.error(error.response?.data?.message || 'Không lưu được game.');
        } finally {
            setSaving(false);
        }
    };

    const deleteGame = async (game) => {
        try {
            await api.delete(`/admin/games/${game.id}`);
            message.success('Đã xóa game.');
            await loadGames();
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể xóa game đang được danh mục hoặc sản phẩm sử dụng.');
        }
    };

    const columns = [
        { title: 'Tên game', dataIndex: 'name', key: 'name', render: (name, game) => <Space>{game.logo_url && <img src={game.logo_url} alt="" style={{ width: 36, height: 36, borderRadius: 6, objectFit: 'cover' }} />}<strong style={{ color: '#fff' }}>{name}</strong></Space> },
        { title: 'Slug', dataIndex: 'slug', key: 'slug' },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (status) => <Tag color={status === 'ACTIVE' ? 'green' : 'default'}>{status}</Tag> },
        { title: 'Thứ tự', dataIndex: 'sort_order', key: 'sort_order' },
        { title: 'Thao tác', key: 'actions', render: (_, game) => <Space><Button icon={<EditOutlined />} onClick={() => openEdit(game)}>Sửa</Button><Popconfirm title="Xóa game này?" description="Game đang được danh mục hoặc sản phẩm sử dụng sẽ không thể xóa." onConfirm={() => deleteGame(game)} okText="Xóa" cancelText="Hủy"><Button danger icon={<DeleteOutlined />}>Xóa</Button></Popconfirm></Space> },
    ];

    return <div>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
            <div><h1 style={{ color: '#fff', margin: 0, fontFamily: 'Rajdhani' }}>QUẢN LÝ GAME</h1><p style={{ color: '#94a3b8', margin: 0 }}>Danh sách game từ backend; game được dùng trong danh mục và sản phẩm.</p></div>
            <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm game</Button>
        </div>
        <div style={{ background: '#121324', borderRadius: 14, padding: 20, border: '1px solid rgba(255,255,255,0.08)' }}><Table columns={columns} dataSource={games} rowKey="id" loading={loading} /></div>
        <Modal title={editingGame ? 'Chỉnh sửa game' : 'Thêm game mới'} open={open} onCancel={() => setOpen(false)} footer={null} destroyOnClose>
            <Form form={form} layout="vertical" onFinish={saveGame}>
                <Form.Item name="name" label="Tên game" rules={[{ required: true, message: 'Nhập tên game.' }]}><Input maxLength={120} /></Form.Item>
                {editingGame?.logo_url && <img src={editingGame.logo_url} alt="Logo hiện tại" style={{ width: 72, height: 72, objectFit: 'cover', marginBottom: 12 }} />}
                <Form.Item name="logo_file" label="Ảnh logo" valuePropName="fileList" getValueFromEvent={(event) => event?.fileList}><Upload accept="image/*" maxCount={1} beforeUpload={() => false} listType="picture"><Button>Chọn ảnh</Button></Upload></Form.Item>
                <Form.Item name="description" label="Mô tả"><Input.TextArea rows={3} /></Form.Item>
                <Form.Item name="status" label="Trạng thái" rules={[{ required: true }]}><Select options={[{ value: 'ACTIVE', label: 'Đang hoạt động' }, { value: 'HIDDEN', label: 'Ẩn' }]} /></Form.Item>
                <Form.Item name="sort_order" label="Thứ tự hiển thị"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                <Button type="primary" htmlType="submit" loading={saving} block>{editingGame ? 'Lưu thay đổi' : 'Lưu game'}</Button>
            </Form>
        </Modal>
    </div>;
};

export default GamesPage;
