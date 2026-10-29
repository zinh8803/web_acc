import React, { useEffect, useState } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { Table, Tag, Button, Modal, Form, Input, Select, message, Card, Row, Col, Space, Alert } from 'antd';
import {
    PlusOutlined,
    SafetyCertificateOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    SyncOutlined,
    CloseCircleOutlined,
    EyeOutlined,
    ExclamationCircleOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const WarrantyClaimsPage = () => {
    const [searchParams] = useSearchParams();
    const prefillOrderCode = searchParams.get('order_code');
    const prefillItemId = searchParams.get('order_item_id');

    const [claims, setClaims] = useState([]);
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [form] = Form.useForm();

    const fetchClaims = async () => {
        try {
            setLoading(true);
            const res = await api.get('/warranty-claims');
            const list = res.data?.data?.data || res.data?.data || [];
            setClaims(Array.isArray(list) ? list : []);
        } catch (e) {
            console.error('Fetch claims error:', e);
        } finally {
            setLoading(false);
        }
    };

    const fetchPaidOrders = async () => {
        try {
            const res = await api.get('/orders?per_page=50');
            const list = res.data?.data?.data || res.data?.data || [];
            const paid = list.filter((o) =>
                ['completed', 'paid', 'COMPLETED', 'PAID', 'processing', 'PROCESSING'].includes(o.status)
            );
            setOrders(paid);

            // Tự động mở modal nếu có prefill từ URL
            if (prefillItemId || prefillOrderCode) {
                let targetItemId = prefillItemId ? Number(prefillItemId) : null;
                if (!targetItemId && prefillOrderCode) {
                    const matchOrder = paid.find((o) => o.order_code === prefillOrderCode);
                    if (matchOrder && matchOrder.items?.length > 0) {
                        targetItemId = matchOrder.items[0].id;
                    }
                }
                if (targetItemId) {
                    form.setFieldsValue({ order_item_id: targetItemId });
                    setIsModalOpen(true);
                }
            }
        } catch (e) {
            console.error('Fetch orders error:', e);
        }
    };

    useEffect(() => {
        fetchClaims();
        fetchPaidOrders();
    }, []);

    const handleCreateClaim = async (values) => {
        try {
            setSubmitting(true);
            const payload = {
                order_item_id: values.order_item_id,
                claim_type: values.claim_type,
                description: values.description,
            };
            const res = await api.post('/warranty-claims', payload);
            if (res.data?.success) {
                message.success(res.data.message || 'Đã gửi yêu cầu bảo hành thành công!');
                setIsModalOpen(false);
                form.resetFields();
                fetchClaims();
            }
        } catch (err) {
            message.error(err.response?.data?.message || 'Không thể gửi khiếu nại bảo hành.');
        } finally {
            setSubmitting(false);
        }
    };

    const renderStatusTag = (status) => {
        const s = (status || '').toUpperCase();
        switch (s) {
            case 'SUBMITTED':
                return <Tag color="orange" icon={<ClockCircleOutlined />}>Chờ Tiếp Nhận</Tag>;
            case 'IN_REVIEW':
                return <Tag color="blue" icon={<SyncOutlined spin />}>Đang Kiểm Tra</Tag>;
            case 'NEED_INFO':
                return <Tag color="warning" icon={<ExclamationCircleOutlined />}>Cần Bổ Sung TT</Tag>;
            case 'APPROVED':
            case 'RESOLVED':
                return <Tag color="success" icon={<CheckCircleOutlined />}>Đã Xử Lý / Đổi Acc</Tag>;
            case 'REJECTED':
                return <Tag color="error" icon={<CloseCircleOutlined />}>Từ Chối Bảo Hành</Tag>;
            case 'CANCELLED':
                return <Tag color="default">Đã Huỷ</Tag>;
            default:
                return <Tag color="processing">{status}</Tag>;
        }
    };

    const columns = [
        {
            title: 'Mã khiếu nại',
            dataIndex: 'claim_code',
            key: 'claim_code',
            render: (code, record) => (
                <Link to={`/warranty-claims/${code}`}>
                    <Tag color="cyan" style={{ fontWeight: 700, cursor: 'pointer' }}>
                        {code}
                    </Tag>
                </Link>
            ),
        },
        {
            title: 'Sản phẩm bảo hành',
            key: 'product',
            render: (_, record) => {
                const item = record.order_item || record.orderItem;
                const orderCode = item?.order?.order_code || record.order?.order_code;
                return (
                    <div>
                        <div style={{ color: '#fff', fontWeight: 600 }}>
                            {item?.product_name || item?.product?.name || 'Tài khoản / Gói dịch vụ'}
                        </div>
                        {orderCode && (
                            <div style={{ color: '#94a3b8', fontSize: '12px' }}>
                                Đơn hàng: <span style={{ color: '#06b6d4' }}>{orderCode}</span>
                            </div>
                        )}
                    </div>
                );
            },
        },
        {
            title: 'Loại sự cố',
            key: 'claim_type',
            render: (_, record) => (
                <span style={{ color: '#fbbf24', fontWeight: 600 }}>
                    {record.reason || record.claim_type}
                </span>
            ),
        },
        {
            title: 'Mô tả chi tiết',
            dataIndex: 'description',
            key: 'description',
            ellipsis: true,
            render: (text) => <span style={{ color: '#cbd5e1' }}>{text}</span>,
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (st) => renderStatusTag(st),
        },
        {
            title: 'Hạn SLA giải quyết',
            dataIndex: 'due_at',
            key: 'due_at',
            render: (d) =>
                d ? (
                    <span style={{ color: '#94a3b8', fontSize: '13px' }}>
                        {dayjs(d).format('DD/MM/YYYY HH:mm')}
                    </span>
                ) : (
                    <span style={{ color: '#64748b' }}>24h</span>
                ),
        },
        {
            title: 'Thao tác',
            key: 'action',
            render: (_, record) => (
                <Link to={`/warranty-claims/${record.claim_code || record.id}`}>
                    <Button size="small" type="primary" ghost icon={<EyeOutlined />}>
                        Chi tiết
                    </Button>
                </Link>
            ),
        },
    ];

    return (
        <div style={{ maxWidth: '1150px', margin: '0 auto', padding: '36px 20px 60px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '28px', flexWrap: 'wrap', gap: '16px' }}>
                <div>
                    <h1 style={{ color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0, display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <SafetyCertificateOutlined style={{ color: '#10b981' }} /> TRUNG TÂM BẢO HÀNH & KHIẾU NẠI
                    </h1>
                    <p style={{ color: '#94a3b8', fontSize: '14px', marginTop: '6px', marginBottom: 0 }}>
                        Cam kết giải quyết trong 24h. Bảo hành 1 đổi 1 hoặc hoàn tiền nếu tài khoản gặp sự cố, sai mật khẩu, back nick.
                    </p>
                </div>
                <Button
                    type="primary"
                    icon={<PlusOutlined />}
                    onClick={() => {
                        form.resetFields();
                        setIsModalOpen(true);
                    }}
                    style={{ height: '44px', fontWeight: 700, borderRadius: '8px', padding: '0 24px', background: 'linear-gradient(135deg, #10b981, #059669)', border: 'none' }}
                >
                    Gửi Yêu Cầu Bảo Hành
                </Button>
            </div>

            <Row gutter={[16, 16]} style={{ marginBottom: '28px' }}>
                <Col xs={24} sm={8}>
                    <Card style={{ background: '#121324', border: '1px solid rgba(255,255,255,0.08)', borderRadius: '12px' }}>
                        <div style={{ color: '#94a3b8', fontSize: '13px' }}>Chính Sách Cam Kết</div>
                        <div style={{ color: '#10b981', fontSize: '18px', fontWeight: 800, marginTop: '4px' }}>1 ĐỔI 1 HOẶC HOÀN TIỀN</div>
                    </Card>
                </Col>
                <Col xs={24} sm={8}>
                    <Card style={{ background: '#121324', border: '1px solid rgba(255,255,255,0.08)', borderRadius: '12px' }}>
                        <div style={{ color: '#94a3b8', fontSize: '13px' }}>Thời Gian Xử Lý SLA</div>
                        <div style={{ color: '#06b6d4', fontSize: '18px', fontWeight: 800, marginTop: '4px' }}>TỐI ĐA 24 GIỜ</div>
                    </Card>
                </Col>
                <Col xs={24} sm={8}>
                    <Card style={{ background: '#121324', border: '1px solid rgba(255,255,255,0.08)', borderRadius: '12px' }}>
                        <div style={{ color: '#94a3b8', fontSize: '13px' }}>Hỗ Trợ Kỹ Thuật</div>
                        <div style={{ color: '#fbbf24', fontSize: '18px', fontWeight: 800, marginTop: '4px' }}>24/7 TẬN TÂM</div>
                    </Card>
                </Col>
            </Row>

            <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)' }}>
                <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '16px' }}>
                    Danh Sách Yêu Cầu Bảo Hành Của Bạn
                </h3>
                <Table
                    columns={columns}
                    dataSource={claims}
                    rowKey="id"
                    loading={loading}
                    pagination={{ pageSize: 8 }}
                    locale={{ emptyText: 'Bạn chưa có yêu cầu bảo hành nào.' }}
                />
            </div>

            {/* MODAL GỬI KHIẾU NẠI BẢO HÀNH */}
            <Modal
                title={<span style={{ color: '#fff', fontSize: '18px', fontWeight: 700 }}>Gửi Yêu Cầu Khiếu Nại Bảo Hành</span>}
                open={isModalOpen}
                onCancel={() => setIsModalOpen(false)}
                footer={null}
                width={650}
            >
                <Alert
                    type="info"
                    showIcon
                    message="Quy trình bảo hành siêu tốc"
                    description="Chúng tôi cam kết xác minh và đổi nick mới hoặc hoàn tiền trong vòng 24h làm việc. Vui lòng cung cấp chi tiết lỗi để kỹ thuật viên hỗ trợ nhanh nhất."
                    style={{ marginBottom: 20 }}
                />

                <Form form={form} layout="vertical" onFinish={handleCreateClaim}>
                    <Form.Item
                        name="order_item_id"
                        label="Chọn sản phẩm trong đơn đã thanh toán *"
                        rules={[{ required: true, message: 'Vui lòng chọn sản phẩm cần bảo hành!' }]}
                    >
                        <Select placeholder="Chọn sản phẩm đã mua để bảo hành..." size="large">
                            {orders.flatMap((o) =>
                                (o.items || []).map((i) => (
                                    <Select.Option key={i.id} value={i.id}>
                                        [{o.order_code}] {i.product_name || i.product?.name || 'Tài khoản'} {i.variant_name ? `(${i.variant_name})` : ''}
                                    </Select.Option>
                                ))
                            )}
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="claim_type"
                        label="Loại sự cố / Lý do bảo hành *"
                        rules={[{ required: true, message: 'Vui lòng chọn loại sự cố!' }]}
                    >
                        <Select placeholder="Chọn loại sự cố gặp phải..." size="large">
                            <Select.Option value="WRONG_CREDENTIALS">
                                🔑 Sai mật khẩu hoặc tài khoản không đăng nhập được
                            </Select.Option>
                            <Select.Option value="RECOVERED_BY_OLD_OWNER">
                                ⚠️ Bị chủ cũ đổi thông tin (Back nick)
                            </Select.Option>
                            <Select.Option value="LOSS_ACCESS">
                                🚫 Mất quyền truy cập / Tài khoản bị khóa bất thường
                            </Select.Option>
                            <Select.Option value="SERVICE_NOT_DONE">
                                ⚡ Dịch vụ cày thuê không hoàn thành đúng cam kết
                            </Select.Option>
                            <Select.Option value="OTHER">
                                📝 Lý do khác (Mô tả chi tiết bên dưới)
                            </Select.Option>
                        </Select>
                    </Form.Item>

                    <Form.Item
                        name="description"
                        label="Mô tả chi tiết sự cố *"
                        rules={[{ required: true, message: 'Vui lòng mô tả cụ thể tình trạng gặp phải!' }]}
                    >
                        <Input.TextArea
                            rows={4}
                            placeholder="Mô tả cụ thể thời gian xảy ra lỗi, thông báo lỗi hiển thị trên game và yêu cầu hỗ trợ (đổi acc mới hoặc hoàn tiền)..."
                        />
                    </Form.Item>

                    <Button
                        type="primary"
                        block
                        htmlType="submit"
                        size="large"
                        loading={submitting}
                        style={{ height: 44, fontWeight: 700, borderRadius: 8, background: '#10b981', borderColor: '#10b981' }}
                    >
                        Gửi Yêu Cầu Cho Đội Kỹ Thuật
                    </Button>
                </Form>
            </Modal>
        </div>
    );
};

export default WarrantyClaimsPage;
