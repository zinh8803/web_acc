import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Modal, Form, Input, InputNumber, Select, DatePicker, Popconfirm, Space, Row, Col, message } from 'antd';
import { PlusOutlined, EditOutlined, DeleteOutlined, ClockCircleOutlined, TagOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const AdminCouponsPage = () => {
    const [coupons, setCoupons] = useState([]);
    const [loading, setLoading] = useState(true);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingCoupon, setEditingCoupon] = useState(null);
    const [form] = Form.useForm();

    const fetchCoupons = async () => {
        try {
            setLoading(true);
            const res = await api.get('/admin/coupons');
            if (res.data?.data) {
                setCoupons(res.data.data);
            }
        } catch (e) {
            message.error('Không thể tải danh sách mã giảm giá.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCoupons();
    }, []);

    const handleOpenCreateModal = () => {
        setEditingCoupon(null);
        form.resetFields();
        form.setFieldsValue({
            discount_type: 'PERCENT',
            usage_per_user: 1,
            status: 'ACTIVE',
        });
        setIsModalOpen(true);
    };

    const handleOpenEditModal = (coupon) => {
        setEditingCoupon(coupon);
        form.setFieldsValue({
            code: coupon.code,
            name: coupon.name,
            description: coupon.description,
            discount_type: coupon.discount_type,
            discount_value: coupon.discount_value,
            max_discount_amount: coupon.max_discount_amount,
            min_order_amount: coupon.min_order_amount,
            usage_limit: coupon.usage_limit,
            usage_per_user: coupon.usage_per_user || 1,
            starts_at: coupon.starts_at ? dayjs(coupon.starts_at) : null,
            ends_at: coupon.ends_at ? dayjs(coupon.ends_at) : null,
            status: coupon.status || 'ACTIVE',
        });
        setIsModalOpen(true);
    };

    const handleSubmit = async (values) => {
        try {
            const payload = {
                ...values,
                code: values.code?.toUpperCase().trim(),
                starts_at: values.starts_at ? values.starts_at.format('YYYY-MM-DD HH:mm:ss') : null,
                ends_at: values.ends_at ? values.ends_at.format('YYYY-MM-DD HH:mm:ss') : null,
            };

            if (editingCoupon) {
                await api.put(`/admin/coupons/${editingCoupon.id}`, payload);
                message.success('Cập nhật mã giảm giá thành công!');
            } else {
                await api.post('/admin/coupons', payload);
                message.success('Tạo mã giảm giá thành công!');
            }

            setIsModalOpen(false);
            form.resetFields();
            fetchCoupons();
        } catch (e) {
            message.error(e.response?.data?.message || 'Có lỗi xảy ra khi lưu mã giảm giá.');
        }
    };

    const handleDelete = async (id) => {
        try {
            const res = await api.delete(`/admin/coupons/${id}`);
            message.success(res.data?.message || 'Đã xử lý mã giảm giá thành công.');
            fetchCoupons();
        } catch (e) {
            message.error(e.response?.data?.message || 'Không thể xóa mã giảm giá.');
        }
    };

    const getTimeStatusTag = (startsAt, endsAt) => {
        const now = dayjs();
        if (startsAt && now.isBefore(dayjs(startsAt))) {
            return <Tag color="warning" icon={<ClockCircleOutlined />}>Chưa bắt đầu</Tag>;
        }
        if (endsAt && now.isAfter(dayjs(endsAt))) {
            return <Tag color="error" icon={<ClockCircleOutlined />}>Đã hết hạn</Tag>;
        }
        if (startsAt || endsAt) {
            return <Tag color="success" icon={<ClockCircleOutlined />}>Đang hiệu lực</Tag>;
        }
        return <Tag color="blue">Vô thời hạn</Tag>;
    };

    const columns = [
        {
            title: 'Mã Code',
            dataIndex: 'code',
            key: 'code',
            render: (c) => (
                <Tag color="cyan" style={{ fontWeight: 800, fontSize: '13px', padding: '4px 10px', letterSpacing: '0.5px' }}>
                    {c}
                </Tag>
            ),
        },
        {
            title: 'Mô tả & Điều kiện',
            key: 'description',
            render: (_, r) => (
                <div>
                    <div style={{ color: '#fff', fontWeight: 600 }}>{r.name || r.code}</div>
                    <div style={{ color: '#94a3b8', fontSize: '12px' }}>{r.description || 'Không có mô tả'}</div>
                    {r.min_order_amount > 0 && (
                        <div style={{ color: '#fbbf24', fontSize: '11px', marginTop: '2px' }}>
                            Đơn tối thiểu: {Number(r.min_order_amount).toLocaleString('vi-VN')} đ
                        </div>
                    )}
                </div>
            ),
        },
        {
            title: 'Mức giảm',
            key: 'discount',
            render: (_, r) => (
                <div>
                    <div style={{ color: '#10b981', fontWeight: 700, fontSize: '14px' }}>
                        {r.discount_type === 'PERCENT' ? `${r.discount_value}%` : `${Number(r.discount_value).toLocaleString('vi-VN')} đ`}
                    </div>
                    {r.discount_type === 'PERCENT' && r.max_discount_amount && (
                        <div style={{ color: '#94a3b8', fontSize: '11px' }}>
                            Tối đa: {Number(r.max_discount_amount).toLocaleString('vi-VN')} đ
                        </div>
                    )}
                </div>
            ),
        },
        {
            title: 'Lượt sử dụng',
            key: 'usage',
            render: (_, r) => (
                <div>
                    <div style={{ color: '#f8fafc', fontSize: '13px' }}>
                        Đã dùng: <strong style={{ color: '#38bdf8' }}>{r.used_count || 0}</strong> / {r.usage_limit ? r.usage_limit : '∞'}
                    </div>
                    <div style={{ color: '#94a3b8', fontSize: '12px' }}>
                        Giới hạn: <strong>{r.usage_per_user || 1} lần</strong> / tài khoản
                    </div>
                </div>
            ),
        },
        {
            title: 'Thời gian áp dụng',
            key: 'duration',
            render: (_, r) => (
                <div>
                    <div style={{ marginBottom: '4px' }}>
                        {getTimeStatusTag(r.starts_at, r.ends_at)}
                    </div>
                    <div style={{ color: '#cbd5e1', fontSize: '12px' }}>
                        Từ: {r.starts_at ? dayjs(r.starts_at).format('DD/MM/YYYY HH:mm') : 'Ngay lập tức'}
                    </div>
                    <div style={{ color: '#cbd5e1', fontSize: '12px' }}>
                        Đến: {r.ends_at ? dayjs(r.ends_at).format('DD/MM/YYYY HH:mm') : 'Không giới hạn'}
                    </div>
                </div>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (s) => (
                <Tag color={s === 'ACTIVE' ? 'green' : 'default'} style={{ fontWeight: 600 }}>
                    {s === 'ACTIVE' ? 'KÍCH HOẠT' : 'TẠM TẮT'}
                </Tag>
            ),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            render: (_, r) => (
                <Space>
                    <Button
                        type="primary"
                        ghost
                        size="small"
                        icon={<EditOutlined />}
                        onClick={() => handleOpenEditModal(r)}
                    >
                        Sửa
                    </Button>
                    <Popconfirm
                        title="Xác nhận xoá mã giảm giá"
                        description="Nếu mã đã được sử dụng trong đơn hàng, hệ thống sẽ tự động chuyển sang trạng thái Vô hiệu hóa để bảo toàn lịch sử giao dịch."
                        onConfirm={() => handleDelete(r.id)}
                        okText="Đồng ý"
                        cancelText="Huỷ"
                    >
                        <Button danger size="small" icon={<DeleteOutlined />}>
                            Xóa
                        </Button>
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                <div>
                    <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
                        QUẢN LÝ MÃ GIẢM GIÁ (COUPONS) 🏷️
                    </h1>
                    <div style={{ color: '#94a3b8', fontSize: '13px', marginTop: '4px' }}>
                        Quản lý mã khuyến mãi, giới hạn lượt dùng và cài đặt thời hạn bắt đầu / kết thúc
                    </div>
                </div>
                <Button type="primary" icon={<PlusOutlined />} onClick={handleOpenCreateModal} size="large">
                    Tạo Mã Mới
                </Button>
            </div>

            <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
                <Table
                    columns={columns}
                    dataSource={coupons}
                    rowKey="id"
                    loading={loading}
                    pagination={{ pageSize: 10 }}
                />
            </div>

            <Modal
                title={<span style={{ color: '#fff', fontSize: '17px', fontWeight: 700 }}>{editingCoupon ? 'Chỉnh Sửa Mã Giảm Giá' : 'Thêm Mã Giảm Giá Mới'}</span>}
                open={isModalOpen}
                onCancel={() => setIsModalOpen(false)}
                footer={null}
                width={650}
            >
                <Form form={form} layout="vertical" onFinish={handleSubmit} style={{ marginTop: '16px' }}>
                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="code"
                                label="Mã Voucher (Code)"
                                rules={[{ required: true, message: 'Vui lòng nhập mã giảm giá' }]}
                            >
                                <Input placeholder="VÍ DỤ: CHAOBANMOI" style={{ textTransform: 'uppercase' }} />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item name="name" label="Tên chương trình">
                                <Input placeholder="Ưu đãi chào mừng khách hàng mới" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item name="description" label="Mô tả ưu đãi">
                        <Input.TextArea rows={2} placeholder="Giảm 10% cho khách hàng mới gia nhập hệ thống" />
                    </Form.Item>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="discount_type"
                                label="Loại giảm giá"
                                rules={[{ required: true }]}
                            >
                                <Select>
                                    <Select.Option value="PERCENT">Phần trăm (%)</Select.Option>
                                    <Select.Option value="FIXED">Số tiền cố định (đ)</Select.Option>
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item
                                name="discount_value"
                                label="Giá trị giảm"
                                rules={[{ required: true, message: 'Vui lòng nhập mức giảm' }]}
                            >
                                <InputNumber style={{ width: '100%' }} min={0} placeholder="Ví dụ: 10 hoặc 50000" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item name="min_order_amount" label="Đơn hàng tối thiểu (đ)">
                                <InputNumber style={{ width: '100%' }} min={0} placeholder="0 (Áp dụng mọi đơn)" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item name="max_discount_amount" label="Giảm tối đa (đ) (Khi giảm %)">
                                <InputNumber style={{ width: '100%' }} min={0} placeholder="Không giới hạn" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item
                                name="usage_per_user"
                                label="Số lần sử dụng / mỗi tài khoản"
                                rules={[{ required: true, message: 'Nhập số lần dùng mỗi user' }]}
                            >
                                <InputNumber style={{ width: '100%' }} min={1} placeholder="Mặc định: 1 lần" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item name="usage_limit" label="Tổng lượt dùng toàn hệ thống">
                                <InputNumber style={{ width: '100%' }} min={1} placeholder="Để trống nếu không giới hạn" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={16}>
                        <Col span={12}>
                            <Form.Item name="starts_at" label="Thời gian bắt đầu">
                                <DatePicker showTime format="YYYY-MM-DD HH:mm" style={{ width: '100%' }} placeholder="Bắt đầu ngay" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item name="ends_at" label="Thời gian kết thúc">
                                <DatePicker showTime format="YYYY-MM-DD HH:mm" style={{ width: '100%' }} placeholder="Vô thời hạn" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item name="status" label="Trạng thái">
                        <Select>
                            <Select.Option value="ACTIVE">KÍCH HOẠT (ACTIVE)</Select.Option>
                            <Select.Option value="DISABLED">TẠM TẮT (DISABLED)</Select.Option>
                        </Select>
                    </Form.Item>

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '24px' }}>
                        <Button onClick={() => setIsModalOpen(false)}>Huỷ</Button>
                        <Button type="primary" htmlType="submit">
                            {editingCoupon ? 'Cập Nhật Mã' : 'Tạo Mã Giảm Giá'}
                        </Button>
                    </div>
                </Form>
            </Modal>
        </div>
    );
};

export default AdminCouponsPage;
