import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Row, Col, Tag, Button, Spin, message, Alert, Divider, Card, Steps, Modal, Form, Input, Space } from 'antd';
import {
    CopyOutlined,
    KeyOutlined,
    SafetyCertificateOutlined,
    ThunderboltOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    SyncOutlined,
    ExclamationCircleOutlined,
    LockOutlined,
    UserOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const OrderDetailPage = () => {
    const { code } = useParams();
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);

    // Modal nhập bổ sung thông tin cày nick
    const [modalVisible, setModalVisible] = useState(false);
    const [activeOrderItem, setActiveOrderItem] = useState(null);
    const [submitLoading, setSubmitLoading] = useState(false);
    const [form] = Form.useForm();

    const [errorMessage, setErrorMessage] = useState(null);

    const fetchDetail = async () => {
        try {
            setLoading(true);
            setErrorMessage(null);
            const res = await api.get(`/orders/${code}`);
            if (res.data?.success) {
                setOrder(res.data.data);
            }
        } catch (err) {
            console.error('Fetch order detail error:', err);
            const msg = err.response?.data?.message || err.response?.data?.error?.message || 'Không tìm thấy thông tin đơn hàng hoặc bạn không có quyền xem.';
            message.error(msg);
            setErrorMessage(msg);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (code) fetchDetail();
    }, [code]);

    const copyToClipboard = (text, label) => {
        navigator.clipboard.writeText(text);
        message.success(`Đã sao chép ${label}!`);
    };

    const handleOpenServiceModal = (item) => {
        setActiveOrderItem(item);
        form.resetFields();
        setModalVisible(true);
    };

    const handleSubmitServiceInfo = async () => {
        try {
            const values = await form.validateFields();
            setSubmitLoading(true);
            const loginInfo = {
                username: values.username,
                password: values.password,
            };
            const payload = {
                order_item_id: activeOrderItem.id,
                game_login: JSON.stringify(loginInfo),
                customer_note: values.notes || null,
                form_data: {
                    server: values.server || 'Mặc định',
                    ingame_name: values.ingame_name || '',
                    current_rank: values.current_rank || '',
                    target_rank: values.target_rank || activeOrderItem.variant_name,
                },
            };
            const res = await api.post('/service-requests', payload);
            if (res.data?.success) {
                message.success('Đã gửi thông tin tài khoản thành công! Đang chờ Admin phê duyệt.');
                setModalVisible(false);
                fetchDetail();
            }
        } catch (err) {
            message.error(err.response?.data?.message || 'Có lỗi xảy ra khi gửi thông tin.');
        } finally {
            setSubmitLoading(false);
        }
    };

    if (loading) {
        return <div style={{ textAlign: 'center', padding: '120px 0' }}><Spin size="large" /></div>;
    }

    if (!order) {
        return (
            <div style={{ textAlign: 'center', padding: '100px 20px', color: '#fff' }}>
                <h2 style={{ color: '#f87171' }}>{errorMessage || 'Không tìm thấy đơn hàng.'}</h2>
                <div style={{ marginTop: '20px', display: 'flex', justifyContent: 'center', gap: '12px' }}>
                    <Button onClick={() => fetchDetail()}>Tải lại</Button>
                    <Link to="/orders"><Button type="primary">Quay lại danh sách</Button></Link>
                </div>
            </div>
        );
    }

    const isPaid = ['completed', 'paid', 'COMPLETED', 'PAID', 'processing', 'PROCESSING'].includes(order.status);
    const serviceItems = order.items?.filter((i) => i.item_type === 'SERVICE') || [];
    const accountItems = order.items?.filter((i) => i.item_type === 'ACCOUNT') || [];

    return (
        <div style={{ maxWidth: '1050px', margin: '0 auto', padding: '36px 20px 60px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                <div>
                    <span style={{ color: '#94a3b8', fontSize: '13px' }}>Chi tiết đơn hàng:</span>
                    <h1 style={{ color: '#06b6d4', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
                        {order.order_code}
                    </h1>
                </div>
                <div>
                    {isPaid ? (
                        <Tag color="success" style={{ fontSize: '14px', padding: '6px 14px', fontWeight: 700 }}>
                            ✓ ĐÃ THANH TOÁN
                        </Tag>
                    ) : (
                        <Link to={`/payment/${order.order_code}`}>
                            <Button type="primary" danger style={{ fontWeight: 700 }}>Thanh Toán Ngay</Button>
                        </Link>
                    )}
                </div>
            </div>

            {/* PHẦN 1: TIẾN ĐỘ DỊCH VỤ CÀY GAME (NẾU CÓ MẶT HÀNG SERVICE) */}
            {serviceItems.length > 0 && isPaid && (
                <div style={{ marginBottom: '32px' }}>
                    {serviceItems.map((item) => {
                        const srv = item.service_request || null;
                        const status = srv?.status || 'NOT_SUBMITTED';

                        // Xác định bước của tiến trình
                        let currentStep = 0;
                        let stepStatus = 'process';
                        if (status === 'PENDING') {
                            currentStep = 0;
                        } else if (status === 'IN_PROGRESS' || status === 'ASSIGNED') {
                            currentStep = 1;
                        } else if (status === 'DONE') {
                            currentStep = 2;
                            stepStatus = 'finish';
                        } else if (status === 'CANCELLED') {
                            stepStatus = 'error';
                        }

                        return (
                            <Card
                                key={item.id}
                                style={{
                                    background: '#121324',
                                    borderRadius: '16px',
                                    border: '2px solid rgba(99, 102, 241, 0.4)',
                                    marginBottom: '20px',
                                    boxShadow: '0 8px 30px rgba(99, 102, 241, 0.15)',
                                }}
                            >
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                        <ThunderboltOutlined style={{ fontSize: '22px', color: '#6366f1' }} />
                                        <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 800, margin: 0 }}>
                                            TIẾN ĐỘ DỊCH VỤ: {item.product_name} ({item.variant_name})
                                        </h3>
                                    </div>
                                    {status === 'PENDING' && (
                                        <Tag color="orange" icon={<ClockCircleOutlined />} style={{ fontSize: '13px', padding: '4px 10px', fontWeight: 700 }}>
                                            Chờ Admin Phê Duyệt
                                        </Tag>
                                    )}
                                    {(status === 'IN_PROGRESS' || status === 'ASSIGNED') && (
                                        <Tag color="processing" icon={<SyncOutlined spin />} style={{ fontSize: '13px', padding: '4px 10px', fontWeight: 700 }}>
                                            Đang Tiến Hành Cày
                                        </Tag>
                                    )}
                                    {status === 'DONE' && (
                                        <Tag color="success" icon={<CheckCircleOutlined />} style={{ fontSize: '13px', padding: '4px 10px', fontWeight: 700 }}>
                                            Đã Hoàn Thành Dịch Vụ
                                        </Tag>
                                    )}
                                    {status === 'CANCELLED' && (
                                        <Tag color="error" icon={<ExclamationCircleOutlined />} style={{ fontSize: '13px', padding: '4px 10px', fontWeight: 700 }}>
                                            Đã Huỷ / Từ Chối
                                        </Tag>
                                    )}
                                </div>

                                {srv ? (
                                    <>
                                        {/* Thanh tiến trình */}
                                        <div style={{ padding: '20px 10px 10px' }}>
                                            <Steps
                                                current={currentStep}
                                                status={stepStatus}
                                                items={[
                                                    {
                                                        title: <span style={{ color: '#fff', fontWeight: 700 }}>1. Đã Gửi Yêu Cầu</span>,
                                                        description: <span style={{ color: '#94a3b8' }}>Admin đang duyệt nick</span>,
                                                    },
                                                    {
                                                        title: <span style={{ color: '#fff', fontWeight: 700 }}>2. Đang Tiến Hành</span>,
                                                        description: (
                                                            <div>
                                                                <span style={{ color: '#38bdf8' }}>Kỹ thuật viên đang cày</span>
                                                                {srv.due_at && (
                                                                    <div style={{ color: '#fbbf24', fontWeight: 700, fontSize: '12px', marginTop: '2px' }}>
                                                                        ⏰ Dự kiến: {dayjs(srv.due_at).format('DD/MM/YYYY HH:mm')}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ),
                                                    },
                                                    {
                                                        title: <span style={{ color: '#fff', fontWeight: 700 }}>3. Hoàn Thành</span>,
                                                        description: srv.completed_at ? (
                                                            <span style={{ color: '#10b981' }}>Xong: {dayjs(srv.completed_at).format('DD/MM HH:mm')}</span>
                                                        ) : (
                                                            <span style={{ color: '#64748b' }}>Bàn giao tài khoản</span>
                                                        ),
                                                    },
                                                ]}
                                            />
                                        </div>

                                        {/* Chi tiết yêu cầu */}
                                        <div style={{ marginTop: '20px', padding: '16px', background: 'rgba(255,255,255,0.02)', borderRadius: '10px', border: '1px solid rgba(255,255,255,0.06)' }}>
                                            <Row gutter={[16, 12]}>
                                                <Col xs={24} sm={12} md={6}>
                                                    <span style={{ color: '#94a3b8', fontSize: '12px' }}>Mã yêu cầu:</span>
                                                    <div style={{ color: '#38bdf8', fontWeight: 700 }}>{srv.request_code}</div>
                                                </Col>
                                                <Col xs={24} sm={12} md={6}>
                                                    <span style={{ color: '#94a3b8', fontSize: '12px' }}>Thời gian dự kiến:</span>
                                                    <div style={{ color: srv.due_at ? '#fbbf24' : '#94a3b8', fontWeight: 700 }}>
                                                        {srv.due_at ? dayjs(srv.due_at).format('DD/MM/YYYY HH:mm') : 'Đang sắp xếp...'}
                                                    </div>
                                                </Col>
                                                <Col xs={24} sm={12} md={6}>
                                                    <span style={{ color: '#94a3b8', fontSize: '12px' }}>Bắt đầu từ:</span>
                                                    <div style={{ color: '#cbd5e1' }}>
                                                        {srv.started_at ? dayjs(srv.started_at).format('DD/MM/YYYY HH:mm') : 'Chờ phân công'}
                                                    </div>
                                                </Col>
                                                <Col xs={24} sm={12} md={6}>
                                                    <span style={{ color: '#94a3b8', fontSize: '12px' }}>Nhân viên phụ trách:</span>
                                                    <div style={{ color: '#cbd5e1' }}>{srv.staff?.full_name || 'Hệ thống'}</div>
                                                </Col>
                                            </Row>

                                            {srv.form_data && (() => {
                                                const fd = typeof srv.form_data === 'string' ? (JSON.parse(srv.form_data) || {}) : srv.form_data;
                                                return (
                                                    <div style={{ marginTop: '12px', paddingTop: '12px', borderTop: '1px dashed rgba(255,255,255,0.08)' }}>
                                                        <span style={{ color: '#94a3b8', fontSize: '12px' }}>Thông tin cày đã đăng ký: </span>
                                                        <Space wrap style={{ marginTop: '4px' }}>
                                                            {fd.server && <Tag color="blue">Server: {fd.server}</Tag>}
                                                            {fd.ingame_name && <Tag color="purple">Ingame: {fd.ingame_name}</Tag>}
                                                            {fd.current_rank && <Tag color="cyan">Rank đầu: {fd.current_rank}</Tag>}
                                                            {fd.target_rank && <Tag color="gold">Mục tiêu: {fd.target_rank}</Tag>}
                                                            {fd.target_hero && <Tag color="magenta">Tướng: {fd.target_hero}</Tag>}
                                                        </Space>
                                                    </div>
                                                );
                                            })()}

                                            {srv.cancel_reason && (
                                                <Alert
                                                    type="error"
                                                    showIcon
                                                    message="Lý do huỷ / từ chối:"
                                                    description={srv.cancel_reason}
                                                    style={{ marginTop: '14px' }}
                                                />
                                            )}
                                        </div>
                                    </>
                                ) : (
                                    <div style={{ padding: '16px', textAlign: 'center' }}>
                                        <Alert
                                            type="warning"
                                            showIcon
                                            message="Chưa Cung Cấp Thông Tin Tài Khoản Game"
                                            description="Bạn đã thanh toán đơn dịch vụ này nhưng chưa nhập tài khoản/mật khẩu game để kỹ thuật viên cày. Vui lòng bấm nút bên dưới để gửi thông tin."
                                            style={{ marginBottom: '16px' }}
                                        />
                                        <Button type="primary" icon={<KeyOutlined />} onClick={() => handleOpenServiceModal(item)}>
                                            Nhập Thông Tin Tài Khoản Cày Thuê Ngay
                                        </Button>
                                    </div>
                                )}
                            </Card>
                        );
                    })}
                </div>
            )}

            {/* PHẦN 2: THÔNG TIN TÀI KHOẢN MUA (ACCOUNT) */}
            {accountItems.length > 0 && isPaid && (
                <div
                    style={{
                        background: 'linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(6, 182, 212, 0.1))',
                        borderRadius: '16px',
                        padding: '28px',
                        border: '2px solid rgba(16, 185, 129, 0.4)',
                        marginBottom: '32px',
                        boxShadow: '0 8px 30px rgba(16, 185, 129, 0.15)',
                    }}
                >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '16px' }}>
                        <KeyOutlined style={{ fontSize: '24px', color: '#10b981' }} />
                        <h2 style={{ color: '#fff', fontSize: '20px', fontWeight: 800, margin: 0 }}>
                            THÔNG TIN TÀI KHOẢN ĐÃ BÀN GIAO (MÃ HOÁ AES-256)
                        </h2>
                    </div>
                    <Alert
                        type="info"
                        showIcon
                        message="LƯU Ý BẢO MẬT QUAN TRỌNG"
                        description="Vui lòng đăng nhập và tiến hành đổi mật khẩu, cập nhật số điện thoại / email bảo mật ngay lập tức để bảo đảm an toàn 100% cho tài khoản của bạn."
                        style={{ marginBottom: '20px', background: 'rgba(15, 23, 42, 0.7)', border: '1px solid rgba(255,255,255,0.1)' }}
                    />
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                        {accountItems.map((item) => {
                            const creds = item.credentials || null;
                            if (!creds) return null;
                            return (
                                <div
                                    key={item.id}
                                    style={{
                                        background: '#121324',
                                        borderRadius: '12px',
                                        padding: '20px',
                                        border: '1px solid rgba(255,255,255,0.08)',
                                    }}
                                >
                                    <div style={{ color: '#06b6d4', fontWeight: 700, fontSize: '15px', marginBottom: '14px' }}>
                                        Sản phẩm: {item.product_name || item.product?.name}
                                    </div>
                                    <Row gutter={[16, 16]}>
                                        <Col xs={24} sm={12}>
                                            <div style={{ background: 'rgba(255,255,255,0.03)', padding: '12px', borderRadius: '8px', border: '1px solid rgba(255,255,255,0.05)' }}>
                                                <div style={{ color: '#94a3b8', fontSize: '12px', marginBottom: '4px' }}>Tên đăng nhập / Email:</div>
                                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                                    <span style={{ color: '#fff', fontSize: '16px', fontWeight: 700, fontFamily: 'monospace' }}>
                                                        {creds.username}
                                                    </span>
                                                    <Button size="small" icon={<CopyOutlined />} onClick={() => copyToClipboard(creds.username, 'Tài khoản')}>
                                                        Chép
                                                    </Button>
                                                </div>
                                            </div>
                                        </Col>
                                        <Col xs={24} sm={12}>
                                            <div style={{ background: 'rgba(255,255,255,0.03)', padding: '12px', borderRadius: '8px', border: '1px solid rgba(255,255,255,0.05)' }}>
                                                <div style={{ color: '#94a3b8', fontSize: '12px', marginBottom: '4px' }}>Mật khẩu đăng nhập:</div>
                                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                                    <span style={{ color: '#fbbf24', fontSize: '16px', fontWeight: 700, fontFamily: 'monospace' }}>
                                                        {creds.password}
                                                    </span>
                                                    <Button size="small" type="primary" icon={<CopyOutlined />} onClick={() => copyToClipboard(creds.password, 'Mật khẩu')}>
                                                        Chép
                                                    </Button>
                                                </div>
                                            </div>
                                        </Col>
                                    </Row>
                                    {creds.extra_info && (
                                        <div style={{ marginTop: '12px', padding: '10px 12px', background: 'rgba(99,102,241,0.08)', borderRadius: '6px', color: '#cbd5e1', fontSize: '13px' }}>
                                            📌 <strong>Thông tin bổ sung:</strong>{' '}
                                            {typeof creds.extra_info === 'object'
                                                ? (creds.extra_info.additional_info || Object.entries(creds.extra_info).map(([k, v]) => `${k}: ${v}`).join(' | '))
                                                : String(creds.extra_info)}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* DANH SÁCH MẶT HÀNG VÀ TỔNG TIỀN */}
            <div style={{ background: '#121324', borderRadius: '16px', padding: '28px', border: '1px solid rgba(255,255,255,0.08)' }}>
                <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, marginBottom: '20px' }}>Danh Sách Mặt Hàng Trong Đơn</h3>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '14px', marginBottom: '24px' }}>
                    {order.items?.map((item) => (
                        <div key={item.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <div>
                                <div style={{ color: '#fff', fontSize: '15px', fontWeight: 600 }}>
                                    {item.product_name || item.product?.name} {item.variant_name ? `(${item.variant_name})` : ''}
                                </div>
                                <div style={{ color: '#94a3b8', fontSize: '13px' }}>
                                    <Tag color={item.item_type === 'SERVICE' ? 'orange' : 'cyan'}>
                                        {item.item_type === 'SERVICE' ? 'Dịch Vụ' : 'Tài Khoản'}
                                    </Tag>
                                    Số lượng: {item.quantity} x {Number(item.unit_price).toLocaleString('vi-VN')} đ
                                </div>
                            </div>
                            <div style={{ color: '#06b6d4', fontWeight: 700, fontFamily: 'Rajdhani', fontSize: '16px' }}>
                                {Number(item.line_total || 0).toLocaleString('vi-VN')} đ
                            </div>
                        </div>
                    ))}
                </div>

                <Divider style={{ borderColor: 'rgba(255,255,255,0.08)' }} />
                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#cbd5e1', marginBottom: '10px' }}>
                    <span>Tạm tính:</span>
                    <span>{Number(order.subtotal || 0).toLocaleString('vi-VN')} đ</span>
                </div>
                {Number(order.discount_total || 0) > 0 && (
                    <div style={{ display: 'flex', justifyContent: 'space-between', color: '#10b981', marginBottom: '10px' }}>
                        <span>Giảm giá (Coupon):</span>
                        <span>-{Number(order.discount_total).toLocaleString('vi-VN')} đ</span>
                    </div>
                )}
                <div style={{ display: 'flex', justifyContent: 'space-between', color: '#fff', fontSize: '18px', fontWeight: 700, marginTop: '16px' }}>
                    <span>Tổng thanh toán:</span>
                    <span style={{ color: '#06b6d4', fontSize: '26px', fontFamily: 'Rajdhani', fontWeight: 800 }}>
                        {Number(order.total || 0).toLocaleString('vi-VN')} đ
                    </span>
                </div>

                <div style={{ marginTop: '24px', display: 'flex', gap: '12px' }}>
                    <Link to={`/warranty-claims?order_code=${order.order_code}`}><Button type="primary" ghost icon={<SafetyCertificateOutlined />}>Yêu Cầu Bảo Hành Cho Đơn Này</Button></Link>
                    <Link to="/orders"><Button type="default">Quay lại danh sách đơn hàng</Button></Link>
                </div>
            </div>

            {/* MODAL NHẬP BỔ SUNG TÀI KHOẢN CÀY THUÊ */}
            <Modal
                title="Cung cấp thông tin tài khoản game cày thuê"
                open={modalVisible}
                onCancel={() => setModalVisible(false)}
                footer={null}
            >
                <Alert
                    type="info"
                    message="Bảo mật tuyệt đối"
                    description="Thông tin đăng nhập game của bạn được mã hoá AES-256 an toàn và chỉ cung cấp cho nhân viên cày game được phân công."
                    style={{ marginBottom: 16 }}
                />
                <Form form={form} layout="vertical" onFinish={handleSubmitServiceInfo}>
                    <Form.Item name="username" label="Tài khoản / Email game *" rules={[{ required: true, message: 'Vui lòng nhập tài khoản' }]}>
                        <Input prefix={<UserOutlined />} placeholder="Nhập tài khoản game..." />
                    </Form.Item>
                    <Form.Item name="password" label="Mật khẩu game *" rules={[{ required: true, message: 'Vui lòng nhập mật khẩu' }]}>
                        <Input.Password prefix={<LockOutlined />} placeholder="Nhập mật khẩu game..." />
                    </Form.Item>
                    <Row gutter={12}>
                        <Col span={12}>
                            <Form.Item name="server" label="Máy chủ / Server">
                                <Input placeholder="VD: Mặt Trời, Asia..." />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item name="ingame_name" label="Tên nhân vật (Ingame)">
                                <Input placeholder="Tên nhân vật..." />
                            </Form.Item>
                        </Col>
                    </Row>
                    <Row gutter={12}>
                        <Col span={12}>
                            <Form.Item name="current_rank" label="Rank hiện tại">
                                <Input placeholder="VD: Kim Cương..." />
                            </Form.Item>
                        </Col>
                        <Col span={12}>
                            <Form.Item name="target_rank" label="Rank / Tướng mong muốn">
                                <Input placeholder="VD: Cao Thủ..." />
                            </Form.Item>
                        </Col>
                    </Row>
                    <Form.Item name="notes" label="Ghi chú thêm cho kỹ thuật viên">
                        <Input.TextArea rows={2} placeholder="Thời gian không được vào nick, tướng ưu tiên..." />
                    </Form.Item>
                    <Button type="primary" htmlType="submit" block loading={submitLoading} style={{ height: 40, fontWeight: 700 }}>
                        Gửi Thông Tin & Bắt Đầu Cày
                    </Button>
                </Form>
            </Modal>
        </div>
    );
};

export default OrderDetailPage;
