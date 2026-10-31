import React, { useEffect, useState } from 'react';
import {
    Table, Tag, Button, Space, Modal, Form, Input, DatePicker, Select,
    Card, Descriptions, Alert, message, Radio, Row, Col, Typography, Divider
} from 'antd';
import {
    ClockCircleOutlined,
    CheckCircleOutlined,
    SyncOutlined,
    ExclamationCircleOutlined,
    EyeOutlined,
    KeyOutlined,
    CopyOutlined,
    ThunderboltOutlined,
    UserOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const AdminServiceRequestsPage = () => {
    const [services, setServices] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filterStatus, setFilterStatus] = useState('ALL');
    const [pagination, setPagination] = useState({ current: 1, pageSize: 15, total: 0 });

    // Modal xem & xử lý
    const [modalVisible, setModalVisible] = useState(false);
    const [selectedService, setSelectedService] = useState(null);
    const [credentials, setCredentials] = useState(null);
    const [credsLoading, setCredsLoading] = useState(false);
    const [updateLoading, setUpdateLoading] = useState(false);
    const [staffList, setStaffList] = useState([]);
    const [form] = Form.useForm();

    const fetchServices = async (page = 1, status = filterStatus) => {
        setLoading(true);
        try {
            const params = { page, per_page: 15 };
            if (status && status !== 'ALL') params.status = status;
            const res = await api.get('/admin/service-requests', { params });
            const result = res.data?.data;
            setServices(result?.data || []);
            setPagination({
                current: result?.current_page || 1,
                pageSize: result?.per_page || 15,
                total: result?.total || 0,
            });
        } catch (e) {
            message.error('Không thể tải danh sách yêu cầu dịch vụ.');
        } finally {
            setLoading(false);
        }
    };

    const fetchStaffList = async () => {
        try {
            const res = await api.get('/admin/admin-users');
            const list = res.data?.data?.data || res.data?.data || [];
            setStaffList(Array.isArray(list) ? list : []);
        } catch (e) {
            // ignore
        }
    };

    useEffect(() => {
        fetchServices(1, filterStatus);
        fetchStaffList();
    }, [filterStatus]);

    const handleOpenModal = (record) => {
        setSelectedService(record);
        setCredentials(null);
        form.setFieldsValue({
            status: record.status || 'PENDING',
            assigned_to: record.assigned_to || undefined,
            due_at: record.due_at ? dayjs(record.due_at) : null,
            cancel_reason: record.cancel_reason || '',
        });
        setModalVisible(true);
    };

    const fetchCredentials = async () => {
        if (!selectedService) return;
        setCredsLoading(true);
        try {
            const res = await api.get(`/admin/service-requests/${selectedService.id}/credentials`);
            if (res.data?.success) {
                setCredentials(res.data.data);
                message.success('Đã giải mã thông tin tài khoản thành công!');
            }
        } catch (e) {
            message.error('Không thể giải mã thông tin đăng nhập.');
        } finally {
            setCredsLoading(false);
        }
    };

    const handleSaveStatus = async () => {
        try {
            const values = await form.validateFields();
            setUpdateLoading(true);
            const payload = {
                status: values.status,
                assigned_to: values.assigned_to || null,
                due_at: values.due_at ? values.due_at.format('YYYY-MM-DD HH:mm:ss') : null,
                cancel_reason: values.status === 'CANCELLED' ? values.cancel_reason : null,
            };

            const res = await api.post(`/admin/service-requests/${selectedService.id}/status`, payload);
            if (res.data?.success) {
                message.success('Cập nhật tiến độ dịch vụ thành công!');
                setModalVisible(false);
                fetchServices(pagination.current, filterStatus);
            }
        } catch (err) {
            message.error(err.response?.data?.message || 'Có lỗi xảy ra khi cập nhật.');
        } finally {
            setUpdateLoading(false);
        }
    };

    const setQuickDueTime = (hours) => {
        form.setFieldValue('due_at', dayjs().add(hours, 'hour'));
    };

    const copyText = (txt, label) => {
        navigator.clipboard.writeText(txt);
        message.success(`Đã sao chép ${label}!`);
    };

    const renderStatusTag = (status) => {
        switch (status) {
            case 'PENDING':
                return <Tag color="orange" icon={<ClockCircleOutlined />}>Chờ Admin Duyệt</Tag>;
            case 'ASSIGNED':
                return <Tag color="blue" icon={<UserOutlined />}>Đã Phân Công</Tag>;
            case 'IN_PROGRESS':
                return <Tag color="processing" icon={<SyncOutlined spin />}>Đang Cày Thuê</Tag>;
            case 'DONE':
                return <Tag color="success" icon={<CheckCircleOutlined />}>Đã Hoàn Thành</Tag>;
            case 'CANCELLED':
                return <Tag color="error" icon={<ExclamationCircleOutlined />}>Đã Huỷ / Từ Chối</Tag>;
            default:
                return <Tag>{status}</Tag>;
        }
    };

    const columns = [
        {
            title: 'Mã Yêu Cầu',
            dataIndex: 'request_code',
            key: 'request_code',
            render: (text) => <span style={{ color: '#38bdf8', fontWeight: 700, fontFamily: 'monospace' }}>{text}</span>,
        },
        {
            title: 'Khách Hàng',
            dataIndex: 'user',
            key: 'user',
            render: (u) => (
                <div>
                    <div style={{ color: '#fff', fontWeight: 600 }}>{u?.full_name || 'Khách'}</div>
                    <div style={{ color: '#94a3b8', fontSize: '12px' }}>{u?.email}</div>
                </div>
            ),
        },
        {
            title: 'Dịch Vụ & Gói Cày',
            key: 'service',
            render: (_, row) => {
                const item = row.order_item;
                return (
                    <div>
                        <div style={{ color: '#fff', fontWeight: 600 }}>{item?.product_name || item?.product?.name || 'Dịch vụ'}</div>
                        <Tag color="cyan" style={{ marginTop: '4px' }}>{item?.variant_name || row.form_data?.variant_name || 'Gói cày'}</Tag>
                    </div>
                );
            },
        },
        {
            title: 'Trạng Thái',
            dataIndex: 'status',
            key: 'status',
            render: (s) => renderStatusTag(s),
        },
        {
            title: 'Thời Gian Dự Kiến Xong',
            dataIndex: 'due_at',
            key: 'due_at',
            render: (due) => (
                due ? (
                    <span style={{ color: '#fbbf24', fontWeight: 600 }}>
                        {dayjs(due).format('DD/MM/YYYY HH:mm')}
                    </span>
                ) : <span style={{ color: '#64748b' }}>Chưa hẹn giờ</span>
            ),
        },
        {
            title: 'Kỹ Thuật Viên',
            dataIndex: 'staff',
            key: 'staff',
            render: (st) => st?.full_name ? <Tag color="geekblue">{st.full_name}</Tag> : <span style={{ color: '#64748b' }}>Chưa gán</span>,
        },
        {
            title: 'Ngày Tạo',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (d) => <span style={{ color: '#94a3b8', fontSize: '13px' }}>{dayjs(d).format('DD/MM/YYYY HH:mm')}</span>,
        },
        {
            title: 'Hành Động',
            key: 'action',
            render: (_, row) => (
                <Button
                    type="primary"
                    size="small"
                    icon={<EyeOutlined />}
                    onClick={() => handleOpenModal(row)}
                >
                    Xem & Xử Lý
                </Button>
            ),
        },
    ];

    const currentStatusWatch = Form.useWatch('status', form);

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
                    QUẢN LÝ DỊCH VỤ CÀY GAME & LEO RANK
                </h1>
                <Radio.Group
                    value={filterStatus}
                    onChange={(e) => setFilterStatus(e.target.value)}
                    buttonStyle="solid"
                >
                    <Radio.Button value="ALL">Tất Cả</Radio.Button>
                    <Radio.Button value="PENDING">Chờ Duyệt</Radio.Button>
                    <Radio.Button value="IN_PROGRESS">Đang Cày</Radio.Button>
                    <Radio.Button value="DONE">Hoàn Thành</Radio.Button>
                    <Radio.Button value="CANCELLED">Đã Huỷ</Radio.Button>
                </Radio.Group>
            </div>

            <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
                <Table
                    columns={columns}
                    dataSource={services}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        current: pagination.current,
                        pageSize: pagination.pageSize,
                        total: pagination.total,
                        onChange: (page) => fetchServices(page, filterStatus),
                    }}
                />
            </div>

            {/* MODAL PHÊ DUYỆT & XỬ LÝ DỊCH VỤ */}
            <Modal
                title={<span style={{ fontSize: '18px', fontWeight: 800 }}>CHI TIẾT & PHÊ DUYỆT YÊU CẦU DỊCH VỤ #{selectedService?.request_code}</span>}
                open={modalVisible}
                onCancel={() => setModalVisible(false)}
                width={800}
                footer={[
                    <Button key="back" onClick={() => setModalVisible(false)}>Đóng</Button>,
                    <Button key="submit" type="primary" loading={updateLoading} onClick={handleSaveStatus}>
                        Lưu Cập Nhật Tiến Độ
                    </Button>,
                ]}
            >
                {selectedService && (
                    <div style={{ maxHeight: '70vh', overflowY: 'auto', paddingRight: '8px' }}>
                        {/* THÔNG TIN KHÁCH HÀNG & DỊCH VỤ */}
                        <Descriptions size="small" bordered column={2} style={{ marginBottom: '16px' }}>
                            <Descriptions.Item label="Khách hàng">{selectedService.user?.full_name || 'Khách'}</Descriptions.Item>
                            <Descriptions.Item label="Email liên hệ">{selectedService.user?.email}</Descriptions.Item>
                            <Descriptions.Item label="Sản phẩm">{selectedService.orderItem?.product_name || 'Dịch vụ'}</Descriptions.Item>
                            <Descriptions.Item label="Gói dịch vụ cày">{selectedService.orderItem?.variant_name || selectedService.form_data?.variant_name}</Descriptions.Item>
                            <Descriptions.Item label="Đơn hàng liên kết">{selectedService.orderItem?.order?.order_code || '---'}</Descriptions.Item>
                            <Descriptions.Item label="Trạng thái hiện tại">{renderStatusTag(selectedService.status)}</Descriptions.Item>
                        </Descriptions>

                        {/* CHI TIẾT YÊU CẦU CÀY THUÊ */}
                        <Card size="small" title="Thông tin chi tiết cày nick do khách gửi" style={{ marginBottom: '16px', background: '#0f172a' }}>
                            <Row gutter={[16, 8]}>
                                {selectedService.form_data?.server && (
                                    <Col span={12}>
                                        <span style={{ color: '#94a3b8' }}>Máy chủ / Server: </span>
                                        <strong style={{ color: '#fff' }}>{selectedService.form_data.server}</strong>
                                    </Col>
                                )}
                                <Col span={selectedService.form_data?.server ? 12 : 24}>
                                    <span style={{ color: '#94a3b8' }}>Tên nhân vật (Ingame): </span>
                                    <strong style={{ color: '#fff' }}>{selectedService.form_data?.ingame_name || '---'}</strong>
                                </Col>
                                <Col span={12}>
                                    <span style={{ color: '#94a3b8' }}>Rank ban đầu: </span>
                                    <strong style={{ color: '#38bdf8' }}>{selectedService.form_data?.current_rank || '---'}</strong>
                                </Col>
                                <Col span={12}>
                                    <span style={{ color: '#94a3b8' }}>Mục tiêu cày: </span>
                                    <strong style={{ color: '#fbbf24' }}>{selectedService.form_data?.target_rank || '---'}</strong>
                                </Col>
                                <Col span={24}>
                                    <span style={{ color: '#94a3b8' }}>Tướng muốn cày (nếu có): </span>
                                    <strong style={{ color: '#a855f7' }}>{selectedService.form_data?.target_hero || 'Tự do'}</strong>
                                </Col>
                                <Col span={24}>
                                    <span style={{ color: '#94a3b8' }}>Ghi chú của khách: </span>
                                    <span style={{ color: '#e2e8f0', fontStyle: 'italic' }}>{selectedService.customer_note || 'Không có ghi chú.'}</span>
                                </Col>
                            </Row>
                        </Card>

                        {/* MỤC XEM TÀI KHOẢN & MẬT KHẨU GAME */}
                        <Card
                            size="small"
                            title={
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <span><KeyOutlined style={{ marginRight: 6, color: '#f59e0b' }} /> Tài Khoản & Mật Khẩu Đăng Nhập Game</span>
                                    {!credentials && (
                                        <Button size="small" type="primary" loading={credsLoading} onClick={fetchCredentials}>
                                            Giải Mã & Xem Mật Khẩu
                                        </Button>
                                    )}
                                </div>
                            }
                            style={{ marginBottom: '20px', background: 'rgba(245, 158, 11, 0.05)', borderColor: 'rgba(245, 158, 11, 0.3)' }}
                        >
                            {credentials ? (
                                <Row gutter={[16, 12]}>
                                    <Col span={12}>
                                        <div style={{ color: '#94a3b8', fontSize: '12px' }}>Tài khoản / Tên đăng nhập:</div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 4 }}>
                                            <span style={{ color: '#fff', fontSize: '16px', fontWeight: 700, fontFamily: 'monospace' }}>
                                                {credentials.username || credentials.login_info || '---'}
                                            </span>
                                            {credentials.username && (
                                                <Button size="small" icon={<CopyOutlined />} onClick={() => copyText(credentials.username, 'Tài khoản')} />
                                            )}
                                        </div>
                                    </Col>
                                    <Col span={12}>
                                        <div style={{ color: '#94a3b8', fontSize: '12px' }}>Mật khẩu game:</div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 4 }}>
                                            <span style={{ color: '#fbbf24', fontSize: '16px', fontWeight: 700, fontFamily: 'monospace' }}>
                                                {credentials.password || '---'}
                                            </span>
                                            {credentials.password && (
                                                <Button size="small" type="primary" icon={<CopyOutlined />} onClick={() => copyText(credentials.password, 'Mật khẩu')} />
                                            )}
                                        </div>
                                    </Col>
                                </Row>
                            ) : (
                                <div style={{ textAlign: 'center', padding: '10px', color: '#94a3b8' }}>
                                    Thông tin tài khoản được mã hoá an toàn. Bấm <strong>"Giải Mã & Xem Mật Khẩu"</strong> để hiển thị thông tin đăng nhập.
                                </div>
                            )}
                        </Card>

                        {/* FORM PHÊ DUYỆT & ĐỔI TIẾN ĐỘ */}
                        <Card size="small" title="Phê Duyệt & Cập Nhật Tiến Độ Cày" style={{ background: '#121324' }}>
                            <Form form={form} layout="vertical">
                                <Row gutter={16}>
                                    <Col span={12}>
                                        <Form.Item name="status" label="Trạng thái dịch vụ *" rules={[{ required: true }]}>
                                            <Select>
                                                <Select.Option value="PENDING">⏳ Chờ Admin Phê Duyệt</Select.Option>
                                                <Select.Option value="IN_PROGRESS">Phê Duyệt & Đang Tiến Hành Cày</Select.Option>
                                                <Select.Option value="DONE">Đã Hoàn Thành Dịch Vụ</Select.Option>
                                                <Select.Option value="CANCELLED">Huỷ / Từ Chối Dịch Vụ</Select.Option>
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    <Col span={12}>
                                        <Form.Item name="assigned_to" label="Kỹ thuật viên phụ trách">
                                            <Select placeholder="Chọn nhân viên..." allowClear>
                                                {staffList.map((st) => (
                                                    <Select.Option key={st.id} value={st.id}>
                                                        {st.full_name || st.username} ({st.role})
                                                    </Select.Option>
                                                ))}
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                </Row>

                                <Form.Item
                                    name="due_at"
                                    label="Thời gian hoàn thành dự kiến (Hẹn giờ xong cho khách)"
                                    extra="Thiết lập thời gian dự kiến xong để khách theo dõi tiến độ trên giao diện của họ."
                                >
                                    <DatePicker showTime format="YYYY-MM-DD HH:mm:ss" style={{ width: '100%' }} />
                                </Form.Item>

                                <div style={{ marginBottom: 16 }}>
                                    <span style={{ color: '#94a3b8', fontSize: '13px', marginRight: 10 }}>Hẹn giờ nhanh:</span>
                                    <Space wrap>
                                        <Button size="small" onClick={() => setQuickDueTime(6)}>+6 Giờ</Button>
                                        <Button size="small" onClick={() => setQuickDueTime(12)}>+12 Giờ</Button>
                                        <Button size="small" onClick={() => setQuickDueTime(24)}>+24 Giờ (1 ngày)</Button>
                                        <Button size="small" onClick={() => setQuickDueTime(48)}>+48 Giờ (2 ngày)</Button>
                                        <Button size="small" onClick={() => setQuickDueTime(72)}>+72 Giờ (3 ngày)</Button>
                                    </Space>
                                </div>

                                {currentStatusWatch === 'CANCELLED' && (
                                    <Form.Item
                                        name="cancel_reason"
                                        label="Lý do huỷ / từ chối dịch vụ *"
                                        rules={[{ required: true, message: 'Vui lòng nhập lý do huỷ' }]}
                                    >
                                        <Input.TextArea rows={2} placeholder="VD: Mật khẩu game không đúng, tài khoản có bảo mật 2FA chưa tắt..." />
                                    </Form.Item>
                                )}
                            </Form>
                        </Card>
                    </div>
                )}
            </Modal>
        </div>
    );
};

export default AdminServiceRequestsPage;
