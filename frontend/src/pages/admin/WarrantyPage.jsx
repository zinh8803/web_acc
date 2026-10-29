import React, { useEffect, useState } from 'react';
import {
    Table,
    Tag,
    Button,
    Modal,
    Form,
    Input,
    Select,
    Tabs,
    Card,
    Row,
    Col,
    message,
    Space,
    Popconfirm,
    Switch,
    InputNumber
} from 'antd';
import {
    SafetyCertificateOutlined,
    EditOutlined,
    PlusOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    SyncOutlined,
    CloseCircleOutlined,
    ExclamationCircleOutlined,
    DeleteOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const AdminWarrantyPage = () => {
    const [activeTab, setActiveTab] = useState('claims');

    // State Claims
    const [claims, setClaims] = useState([]);
    const [claimsLoading, setClaimsLoading] = useState(true);
    const [claimsPagination, setClaimsPagination] = useState({ current: 1, pageSize: 15, total: 0 });
    const [filterStatus, setFilterStatus] = useState(undefined);
    const [searchKeyword, setSearchKeyword] = useState('');

    // Modal Xử lý Claim
    const [decideModalOpen, setDecideModalOpen] = useState(false);
    const [activeClaim, setActiveClaim] = useState(null);
    const [decideLoading, setDecideLoading] = useState(false);
    const [decideForm] = Form.useForm();

    // State Policies
    const [policies, setPolicies] = useState([]);
    const [policiesLoading, setPoliciesLoading] = useState(true);
    const [policyModalOpen, setPolicyModalOpen] = useState(false);
    const [editingPolicy, setEditingPolicy] = useState(null);
    const [policySubmitting, setPolicySubmitting] = useState(false);
    const [policyForm] = Form.useForm();

    // Fetch Claims
    const fetchClaims = async (page = 1, status = filterStatus, search = searchKeyword) => {
        try {
            setClaimsLoading(true);
            const params = { page, per_page: 15 };
            if (status) params.status = status;
            if (search) params.search = search;

            const res = await api.get('/admin/warranty-claims', { params });
            if (res.data?.success) {
                const data = res.data.data;
                setClaims(data.data || []);
                setClaimsPagination({
                    current: data.current_page || 1,
                    pageSize: data.per_page || 15,
                    total: data.total || 0,
                });
            }
        } catch (e) {
            console.error('Fetch admin claims error:', e);
            message.error('Không thể tải danh sách khiếu nại.');
        } finally {
            setClaimsLoading(false);
        }
    };

    // Fetch Policies
    const fetchPolicies = async () => {
        try {
            setPoliciesLoading(true);
            const res = await api.get('/admin/warranty-policies');
            if (res.data?.success) {
                setPolicies(res.data.data || []);
            }
        } catch (e) {
            console.error('Fetch policies error:', e);
        } finally {
            setPoliciesLoading(false);
        }
    };

    useEffect(() => {
        fetchClaims(1);
        fetchPolicies();
    }, []);

    // Xử lý mở Modal giải quyết Claim
    const handleOpenDecideModal = (claim) => {
        setActiveClaim(claim);
        decideForm.setFieldsValue({
            status: claim.status,
            resolution_type: claim.resolution_type || 'REPLACE_ACCOUNT',
            resolution_note: claim.resolution_note || '',
            reject_reason: claim.reject_reason || '',
        });
        setDecideModalOpen(true);
    };

    const handleSaveDecide = async (values) => {
        try {
            setDecideLoading(true);
            const res = await api.post(`/admin/warranty-claims/${activeClaim.id}/decide`, values);
            if (res.data?.success) {
                message.success('Đã cập nhật xử lý khiếu nại thành công!');
                setDecideModalOpen(false);
                fetchClaims(claimsPagination.current);
            }
        } catch (err) {
            message.error(err.response?.data?.message || 'Có lỗi khi cập nhật xử lý.');
        } finally {
            setDecideLoading(false);
        }
    };

    // Policy Form Handlers
    const handleOpenPolicyModal = (policy = null) => {
        setEditingPolicy(policy);
        if (policy) {
            policyForm.setFieldsValue({
                name: policy.name,
                product_type: policy.product_type,
                is_lifetime: policy.is_lifetime,
                duration_days: policy.duration_days,
                claim_sla_hours: policy.claim_sla_hours || 24,
                description: policy.description,
                exclusions: policy.exclusions,
                status: policy.status || 'ACTIVE',
            });
        } else {
            policyForm.resetFields();
            policyForm.setFieldsValue({
                product_type: 'ACCOUNT',
                is_lifetime: true,
                claim_sla_hours: 24,
                status: 'ACTIVE',
            });
        }
        setPolicyModalOpen(true);
    };

    const handleSavePolicy = async (values) => {
        try {
            setPolicySubmitting(true);
            if (editingPolicy) {
                await api.put(`/admin/warranty-policies/${editingPolicy.id}`, values);
                message.success('Đã cập nhật chính sách bảo hành!');
            } else {
                await api.post('/admin/warranty-policies', values);
                message.success('Đã thêm chính sách bảo hành mới!');
            }
            setPolicyModalOpen(false);
            fetchPolicies();
        } catch (err) {
            message.error(err.response?.data?.message || 'Có lỗi khi lưu chính sách.');
        } finally {
            setPolicySubmitting(false);
        }
    };

    const handleDeletePolicy = async (id) => {
        try {
            await api.delete(`/admin/warranty-policies/${id}`);
            message.success('Đã xoá chính sách bảo hành.');
            fetchPolicies();
        } catch (err) {
            message.error(err.response?.data?.message || 'Không thể xoá chính sách này.');
        }
    };

    const renderStatusTag = (status) => {
        const s = (status || '').toUpperCase();
        switch (s) {
            case 'SUBMITTED':
                return <Tag color="orange" icon={<ClockCircleOutlined />}>Chờ Xử Lý</Tag>;
            case 'IN_REVIEW':
                return <Tag color="blue" icon={<SyncOutlined spin />}>Đang Kiểm Tra</Tag>;
            case 'NEED_INFO':
                return <Tag color="warning" icon={<ExclamationCircleOutlined />}>Cần Bổ Sung</Tag>;
            case 'APPROVED':
            case 'RESOLVED':
                return <Tag color="success" icon={<CheckCircleOutlined />}>Đã Xử Lý / Đổi Acc</Tag>;
            case 'REJECTED':
                return <Tag color="error" icon={<CloseCircleOutlined />}>Từ Chối</Tag>;
            case 'CANCELLED':
                return <Tag color="default">Đã Huỷ</Tag>;
            default:
                return <Tag color="processing">{status}</Tag>;
        }
    };

    const claimColumns = [
        {
            title: 'Mã khiếu nại',
            dataIndex: 'claim_code',
            key: 'claim_code',
            render: (code) => <Tag color="cyan" style={{ fontWeight: 700 }}>{code}</Tag>,
        },
        {
            title: 'Khách hàng',
            key: 'user',
            render: (_, record) => {
                const u = record.user;
                return (
                    <div>
                        <div style={{ color: '#fff', fontWeight: 600 }}>{u?.full_name || 'Khách hàng'}</div>
                        <div style={{ color: '#94a3b8', fontSize: '12px' }}>{u?.email || u?.phone}</div>
                    </div>
                );
            },
        },
        {
            title: 'Sản phẩm & Đơn hàng',
            key: 'product',
            render: (_, record) => {
                const item = record.order_item || record.orderItem;
                const order = record.order || item?.order;
                return (
                    <div>
                        <div style={{ color: '#fff', fontWeight: 600 }}>
                            {item?.product_name || item?.product?.name || 'Tài khoản'}
                        </div>
                        {order?.order_code && (
                            <div style={{ color: '#06b6d4', fontSize: '12px' }}>
                                Đơn: {order.order_code}
                            </div>
                        )}
                    </div>
                );
            },
        },
        {
            title: 'Loại sự cố',
            key: 'reason',
            render: (_, record) => (
                <Tag color="gold" style={{ fontWeight: 600 }}>
                    {record.reason || record.claim_type}
                </Tag>
            ),
        },
        {
            title: 'Mô tả từ khách',
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
            title: 'Hạn SLA',
            dataIndex: 'due_at',
            key: 'due_at',
            render: (d) =>
                d ? (
                    <span style={{ color: dayjs(d).isBefore(dayjs()) ? '#f87171' : '#fbbf24', fontSize: '12px', fontWeight: 700 }}>
                        {dayjs(d).format('DD/MM HH:mm')}
                    </span>
                ) : (
                    '-'
                ),
        },
        {
            title: 'Thao tác',
            key: 'action',
            render: (_, record) => (
                <Button
                    type="primary"
                    size="small"
                    icon={<EditOutlined />}
                    onClick={() => handleOpenDecideModal(record)}
                >
                    Xử lý
                </Button>
            ),
        },
    ];

    const policyColumns = [
        {
            title: 'Mã Policy',
            dataIndex: 'code',
            key: 'code',
            render: (code) => <Tag color="blue" style={{ fontWeight: 700 }}>{code}</Tag>,
        },
        {
            title: 'Tên chính sách',
            dataIndex: 'name',
            key: 'name',
            render: (t) => <span style={{ color: '#fff', fontWeight: 600 }}>{t}</span>,
        },
        {
            title: 'Áp dụng cho',
            dataIndex: 'product_type',
            key: 'product_type',
            render: (t) => (
                <Tag color={t === 'ACCOUNT' ? 'cyan' : 'orange'}>
                    {t === 'ACCOUNT' ? 'Tài Khoản' : 'Dịch Vụ Cày'}
                </Tag>
            ),
        },
        {
            title: 'Thời hạn',
            key: 'duration',
            render: (_, r) =>
                r.is_lifetime ? (
                    <Tag color="success">Trọn Đời</Tag>
                ) : (
                    <span style={{ color: '#cbd5e1' }}>{r.duration_days} ngày</span>
                ),
        },
        {
            title: 'SLA giải quyết',
            dataIndex: 'claim_sla_hours',
            key: 'claim_sla_hours',
            render: (h) => `${h || 24} giờ`,
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (st) => (
                <Tag color={st === 'ACTIVE' ? 'success' : 'default'}>
                    {st === 'ACTIVE' ? 'Kích Hoạt' : 'Vô Hiệu'}
                </Tag>
            ),
        },
        {
            title: 'Thao tác',
            key: 'action',
            render: (_, record) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => handleOpenPolicyModal(record)}>
                        Sửa
                    </Button>
                    <Popconfirm
                        title="Bạn có chắc chắn muốn xoá chính sách này?"
                        onConfirm={() => handleDeletePolicy(record.id)}
                    >
                        <Button size="small" danger icon={<DeleteOutlined />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div style={{ padding: '24px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                <div>
                    <h1 style={{ color: '#fff', fontSize: '26px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0, display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <SafetyCertificateOutlined style={{ color: '#10b981' }} /> QUẢN LÝ BẢO HÀNH & KHIẾU NẠI
                    </h1>
                    <p style={{ color: '#94a3b8', fontSize: '13px', marginTop: '4px', marginBottom: 0 }}>
                        Tiếp nhận, xử lý khiếu nại tài khoản lỗi, đổi acc mới, hoàn tiền và cấu hình chính sách bảo hành.
                    </p>
                </div>
            </div>

            <Tabs
                activeKey={activeTab}
                onChange={setActiveTab}
                items={[
                    {
                        key: 'claims',
                        label: `📋 Danh Sách Khiếu Nại (${claimsPagination.total})`,
                        children: (
                            <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
                                <Row gutter={[12, 12]} style={{ marginBottom: '16px' }}>
                                    <Col xs={24} sm={8}>
                                        <Input.Search
                                            placeholder="Tìm theo mã khiếu nại, email khách..."
                                            allowClear
                                            onSearch={(val) => {
                                                setSearchKeyword(val);
                                                fetchClaims(1, filterStatus, val);
                                            }}
                                        />
                                    </Col>
                                    <Col xs={24} sm={6}>
                                        <Select
                                            placeholder="Lọc trạng thái"
                                            style={{ width: '100%' }}
                                            allowClear
                                            value={filterStatus}
                                            onChange={(val) => {
                                                setFilterStatus(val);
                                                fetchClaims(1, val, searchKeyword);
                                            }}
                                        >
                                            <Select.Option value="SUBMITTED">Chờ Xử Lý</Select.Option>
                                            <Select.Option value="IN_REVIEW">Đang Kiểm Tra</Select.Option>
                                            <Select.Option value="APPROVED">Đã Phê Duyệt</Select.Option>
                                            <Select.Option value="RESOLVED">Đã Hoàn Tất</Select.Option>
                                            <Select.Option value="REJECTED">Đã Từ Chối</Select.Option>
                                        </Select>
                                    </Col>
                                    <Col xs={24} sm={4}>
                                        <Button onClick={() => fetchClaims(1)}>Làm Mới</Button>
                                    </Col>
                                </Row>

                                <Table
                                    columns={claimColumns}
                                    dataSource={claims}
                                    rowKey="id"
                                    loading={claimsLoading}
                                    pagination={{
                                        ...claimsPagination,
                                        onChange: (page) => fetchClaims(page),
                                    }}
                                />
                            </div>
                        ),
                    },
                    {
                        key: 'policies',
                        label: '⚙️ Cấu Hình Chính Sách Bảo Hành',
                        children: (
                            <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '16px' }}>
                                    <h3 style={{ color: '#fff', margin: 0 }}>Danh Sách Chính Sách Bảo Hành</h3>
                                    <Button
                                        type="primary"
                                        icon={<PlusOutlined />}
                                        onClick={() => handleOpenPolicyModal()}
                                    >
                                        Thêm Chính Sách
                                    </Button>
                                </div>
                                <Table
                                    columns={policyColumns}
                                    dataSource={policies}
                                    rowKey="id"
                                    loading={policiesLoading}
                                    pagination={false}
                                />
                            </div>
                        ),
                    },
                ]}
            />

            {/* MODAL XỬ LÝ KHIẾU NẠI */}
            <Modal
                title={
                    <span style={{ color: '#fff' }}>
                        Xử Lý Khiếu Nại: {activeClaim?.claim_code}
                    </span>
                }
                open={decideModalOpen}
                onCancel={() => setDecideModalOpen(false)}
                footer={null}
                width={650}
            >
                {activeClaim && (
                    <div style={{ marginBottom: '16px', background: 'rgba(255,255,255,0.02)', padding: '12px', borderRadius: '8px' }}>
                        <div style={{ color: '#94a3b8', fontSize: '12px' }}>Sản phẩm: <span style={{ color: '#fff' }}>{activeClaim.order_item?.product_name || activeClaim.order_item?.product?.name}</span></div>
                        <div style={{ color: '#94a3b8', fontSize: '12px', marginTop: '4px' }}>Khách mô tả: <span style={{ color: '#fbbf24' }}>{activeClaim.description}</span></div>
                    </div>
                )}
                <Form form={decideForm} layout="vertical" onFinish={handleSaveDecide}>
                    <Form.Item name="status" label="Trạng thái quyết định *" rules={[{ required: true }]}>
                        <Select size="large">
                            <Select.Option value="IN_REVIEW">🔄 Đang Kiểm Tra & Xác Minh Thông Tin</Select.Option>
                            <Select.Option value="APPROVED">✅ Phê Duyệt Bảo Hành (Đổi Acc / Hoàn Tiền)</Select.Option>
                            <Select.Option value="RESOLVED">🎉 Đã Hoàn Tất Xử Lý Bảo Hành</Select.Option>
                            <Select.Option value="REJECTED">❌ Từ Chối Bảo Hành (Không Hợp Lệ / Vi Phạm)</Select.Option>
                        </Select>
                    </Form.Item>

                    <Form.Item name="resolution_type" label="Phương án giải quyết">
                        <Select size="large">
                            <Select.Option value="REPLACE_ACCOUNT">Đổi Tài Khoản Mới Tương Đương (1 Đổi 1)</Select.Option>
                            <Select.Option value="REFUND">Hoàn Tiền 100% Cho Khách Hàng</Select.Option>
                            <Select.Option value="PARTIAL_REFUND">Hoàn Một Phần Tiền</Select.Option>
                            <Select.Option value="RECOVER_ACCOUNT">Hỗ Trợ Lấy Lại Quyền Truy Cập Thành Công</Select.Option>
                            <Select.Option value="OTHER">Phương Án Khác</Select.Option>
                        </Select>
                    </Form.Item>

                    <Form.Item name="resolution_note" label="Ghi chú phản hồi cho khách hàng (Hiển thị cho khách)">
                        <Input.TextArea
                            rows={3}
                            placeholder="VD: Đã tiến hành đổi mật khẩu mới / Bàn giao nick thay thế trong phần chat hỗ trợ..."
                        />
                    </Form.Item>

                    <Form.Item name="reject_reason" label="Lý do từ chối (Nếu chọn Từ chối)">
                        <Input.TextArea
                            rows={2}
                            placeholder="VD: Khách hàng tự ý đổi thông tin bảo mật vi phạm chính sách bảo hành..."
                        />
                    </Form.Item>

                    <Button type="primary" htmlType="submit" block size="large" loading={decideLoading} style={{ fontWeight: 700 }}>
                        Lưu Quyết Định Xử Lý
                    </Button>
                </Form>
            </Modal>

            {/* MODAL THÊM / SỬA POLICY */}
            <Modal
                title={<span style={{ color: '#fff' }}>{editingPolicy ? 'Sửa Chính Sách' : 'Thêm Chính Sách Mới'}</span>}
                open={policyModalOpen}
                onCancel={() => setPolicyModalOpen(false)}
                footer={null}
                width={600}
            >
                <Form form={policyForm} layout="vertical" onFinish={handleSavePolicy}>
                    <Form.Item name="name" label="Tên chính sách *" rules={[{ required: true }]}>
                        <Input placeholder="VD: Bảo Hành Tài Khoản Trọn Đời" />
                    </Form.Item>

                    <Row gutter={12}>
                        <Col span={12}>
                            <Form.Item name="product_type" label="Loại sản phẩm *" rules={[{ required: true }]}>
                                <Select>
                                    <Select.Option value="ACCOUNT">Tài Khoản Game</Select.Option>
                                    <Select.Option value="SERVICE">Dịch Vụ Cày Game</Select.Option>
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item name="claim_sla_hours" label="Hạn SLA xử lý (giờ)" rules={[{ required: true }]}>
                                <InputNumber style={{ width: '100%' }} min={1} max={72} />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={12}>
                        <Col span={12}>
                            <Form.Item name="is_lifetime" label="Bảo hành trọn đời?" valuePropName="checked">
                                <Switch checkedChildren="Có" unCheckedChildren="Không" />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item name="duration_days" label="Số ngày bảo hành (Nếu không trọn đời)">
                                <InputNumber style={{ width: '100%' }} min={1} placeholder="VD: 30" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item name="description" label="Mô tả chính sách">
                        <Input.TextArea rows={2} placeholder="Quyền lợi của khách..." />
                    </Form.Item>

                    <Form.Item name="exclusions" label="Trường hợp loại trừ (Từ chối bảo hành)">
                        <Input.TextArea rows={2} placeholder="Hack cheat, chia sẻ acc cho bên thứ 3..." />
                    </Form.Item>

                    <Button type="primary" htmlType="submit" block size="large" loading={policySubmitting} style={{ fontWeight: 700 }}>
                        Lưu Chính Sách
                    </Button>
                </Form>
            </Modal>
        </div>
    );
};

export default AdminWarrantyPage;
