import React, { useEffect, useState } from 'react';
import { Button, Form, Image, Input, InputNumber, message, Modal, Popconfirm, Select, Space, Table, Tag, Upload } from 'antd';
import { DeleteOutlined, EditOutlined, PictureOutlined, PlusOutlined } from '@ant-design/icons';
import api from '../../services/api';

const BannersPage = () => {
    const [banners, setBanners] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [open, setOpen] = useState(false);
    const [editingBanner, setEditingBanner] = useState(null);
    const [form] = Form.useForm();

    const fetchBanners = async () => {
        setLoading(true);
        try {
            const res = await api.get('/admin/banners');
            setBanners(Array.isArray(res.data?.data) ? res.data.data : []);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể tải danh sách banner.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchBanners();
    }, []);

    const openCreate = () => {
        setEditingBanner(null);
        form.resetFields();
        form.setFieldsValue({
            sort_order: 0,
            status: 'ACTIVE',
        });
        setOpen(true);
    };

    const openEdit = (banner) => {
        setEditingBanner(banner);
        form.setFieldsValue({
            title: banner.title || '',
            image_url: banner.image_url || '',
            link_url: banner.link_url || '',
            sort_order: banner.sort_order ?? 0,
            status: banner.status || 'ACTIVE',
        });
        setOpen(true);
    };

    const saveBanner = async (values) => {
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

            if (editingBanner) {
                payload.append('_method', 'PUT');
                await api.post(`/admin/banners/${editingBanner.id}`, payload);
                message.success('Đã cập nhật banner thành công.');
            } else {
                await api.post('/admin/banners', payload);
                message.success('Đã tạo banner mới thành công.');
            }
            setOpen(false);
            await fetchBanners();
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể lưu banner.');
        } finally {
            setSaving(false);
        }
    };

    const deleteBanner = async (banner) => {
        try {
            await api.delete(`/admin/banners/${banner.id}`);
            message.success('Đã xóa banner.');
            await fetchBanners();
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể xóa banner.');
        }
    };

    const toggleStatus = async (banner) => {
        try {
            const newStatus = banner.status === 'ACTIVE' ? 'HIDDEN' : 'ACTIVE';
            await api.put(`/admin/banners/${banner.id}`, { status: newStatus });
            message.success(`Đã đổi trạng thái sang ${newStatus === 'ACTIVE' ? 'Hiển thị' : 'Ẩn'}.`);
            await fetchBanners();
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể cập nhật trạng thái.');
        }
    };

    const columns = [
        {
            title: 'ID',
            dataIndex: 'id',
            key: 'id',
            width: 60,
        },
        {
            title: 'Hình ảnh',
            dataIndex: 'image_url',
            key: 'image_url',
            width: 160,
            render: (url) => (
                <Image
                    src={url}
                    alt="Banner"
                    width={130}
                    height={60}
                    style={{ objectFit: 'cover', borderRadius: 6, border: '1px solid #334155' }}
                    fallback="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='50'><rect width='100%' height='100%' fill='%23334155'/><text x='50%' y='50%' fill='%2394a3b8' font-size='12' text-anchor='middle' alignment-baseline='middle'>Lỗi ảnh</text></svg>"
                />
            ),
        },
        {
            title: 'Tiêu đề / Chú thích',
            dataIndex: 'title',
            key: 'title',
            render: (title) => <strong style={{ color: '#fff' }}>{title || '(Không có tiêu đề)'}</strong>,
        },
        {
            title: 'Liên kết (Link URL)',
            dataIndex: 'link_url',
            key: 'link_url',
            ellipsis: true,
            render: (link) => link ? (
                <a href={link} target="_blank" rel="noopener noreferrer" style={{ color: '#818cf8' }}>
                    {link}
                </a>
            ) : <span style={{ color: '#64748b' }}>Không có link</span>,
        },
        {
            title: 'Thứ tự',
            dataIndex: 'sort_order',
            key: 'sort_order',
            width: 90,
            align: 'center',
            render: (order) => <Tag color="blue">{order ?? 0}</Tag>,
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            width: 120,
            render: (status, record) => (
                <Tag
                    color={status === 'ACTIVE' ? 'green' : 'default'}
                    style={{ cursor: 'pointer' }}
                    onClick={() => toggleStatus(record)}
                >
                    {status === 'ACTIVE' ? 'Hiển thị' : 'Đang ẩn'}
                </Tag>
            ),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 170,
            render: (_, banner) => (
                <Space>
                    <Button icon={<EditOutlined />} onClick={() => openEdit(banner)}>
                        Sửa
                    </Button>
                    <Popconfirm
                        title="Xóa banner này?"
                        description="Hành động này không thể hoàn tác."
                        onConfirm={() => deleteBanner(banner)}
                        okText="Xóa"
                        cancelText="Hủy"
                    >
                        <Button danger icon={<DeleteOutlined />}>
                            Xóa
                        </Button>
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
                <div>
                    <h1 style={{ color: '#fff', fontSize: 24, fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
                        QUẢN LÝ BANNER & HÌNH ẢNH HERO
                    </h1>
                    <p style={{ color: '#94a3b8', margin: 0 }}>
                        Quản lý các hình ảnh banner hiển thị ở trang chủ (lớp nền background Hero).
                    </p>
                </div>
                <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>
                    Thêm Banner mới
                </Button>
            </div>

            <div style={{ background: '#121324', borderRadius: 14, padding: 20, border: '1px solid rgba(255,255,255,0.08)' }}>
                <Table
                    columns={columns}
                    dataSource={banners}
                    rowKey="id"
                    loading={loading}
                    pagination={{ pageSize: 10 }}
                />
            </div>

            <Modal
                title={editingBanner ? 'Chỉnh sửa Banner' : 'Thêm Banner mới'}
                open={open}
                onCancel={() => setOpen(false)}
                footer={null}
                destroyOnClose
                width={560}
            >
                <Form form={form} layout="vertical" onFinish={saveBanner}>
                    <Form.Item name="title" label="Tiêu đề Banner">
                        <Input placeholder="Nhập tiêu đề hoặc mô tả vắn tắt" maxLength={255} />
                    </Form.Item>

                    {editingBanner?.image_url && (
                        <div style={{ marginBottom: 12 }}>
                            <div style={{ color: '#64748b', fontSize: 13, marginBottom: 4 }}>Hình ảnh hiện tại:</div>
                            <img
                                src={editingBanner.image_url}
                                alt="Banner hiện tại"
                                style={{ width: '100%', maxHeight: 160, objectFit: 'cover', borderRadius: 8, border: '1px solid #e2e8f0' }}
                            />
                        </div>
                    )}

                    <Form.Item
                        name="image_file"
                        label="Tải ảnh mới từ máy tính"
                        valuePropName="fileList"
                        getValueFromEvent={(event) => event?.fileList}
                        extra="Định dạng jpg, png, webp (khuyến nghị tỷ lệ 16:9 hoặc ảnh ngang độ phân giải cao)"
                    >
                        <Upload accept="image/*" maxCount={1} beforeUpload={() => false} listType="picture">
                            <Button icon={<PictureOutlined />}>Chọn file ảnh từ máy</Button>
                        </Upload>
                    </Form.Item>

                    <Form.Item
                        name="image_url"
                        label="Hoặc dán Link URL ảnh online"
                        extra="Nếu không tải file ảnh, bạn có thể dán link ảnh trực tiếp tại đây"
                    >
                        <Input placeholder="https://example.com/banner.jpg" />
                    </Form.Item>

                    <Form.Item name="link_url" label="Đường dẫn khi click (tùy chọn)">
                        <Input placeholder="Ví dụ: /catalog hoặc https://..." />
                    </Form.Item>

                    <Form.Item name="sort_order" label="Thứ tự hiển thị (nhỏ hơn ưu tiên trước)">
                        <InputNumber min={0} style={{ width: '100%' }} />
                    </Form.Item>

                    <Form.Item name="status" label="Trạng thái hiển thị" rules={[{ required: true }]}>
                        <Select
                            options={[
                                { value: 'ACTIVE', label: 'Hiển thị (ACTIVE)' },
                                { value: 'HIDDEN', label: 'Ẩn (HIDDEN)' },
                            ]}
                        />
                    </Form.Item>

                    <Button type="primary" htmlType="submit" loading={saving} block size="large">
                        {editingBanner ? 'Lưu thay đổi' : 'Tạo Banner'}
                    </Button>
                </Form>
            </Modal>
        </div>
    );
};

export default BannersPage;

