import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button, Tag, Spin, Descriptions, Card, Table, Row, Col, Divider, message, Select } from 'antd';
import { ArrowLeftOutlined, KeyOutlined, ThunderboltOutlined, CopyOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const AdminOrderDetailPage = () => {
    const { code } = useParams();
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);
    const [updating, setUpdating] = useState(false);

    const fetchOrder = async () => {
        setLoading(true);
        try {
            const res = await api.get(`/admin/orders/${code}`);
            if (res.data?.data) {
                setOrder(res.data.data);
            }
        } catch (e) {
            message.error('Không tìm thấy thông tin đơn hàng này.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (code) fetchOrder();
    }, [code]);

    const handleUpdateStatus = async (status) => {
        setUpdating(true);
        try {
            await api.patch(`/admin/orders/${code}/status`, { status });
            message.success('Đã cập nhật trạng thái đơn hàng!');
            fetchOrder();
        } catch (e) {
            message.error('Không thể cập nhật trạng thái.');
        } finally {
            setUpdating(false);
        }
    };

    const copyText = (txt, label) => {
        navigator.clipboard.writeText(txt);
        message.success(`Đã sao chép ${label}!`);
    };

    if (loading) {
        return <div style={{ textAlign: 'center', padding: '100px 0' }}><Spin size="large" /></div>;
    }

    if (!order) {
        return (
            <div style={{ textAlign: 'center', padding: '80px 20px', color: '#fff' }}>
                <h2>Không tìm thấy đơn hàng #{code}</h2>
                <Link to="/admin/orders"><Button type="primary" style={{ marginTop: 16 }}>Quay lại danh sách</Button></Link>
            </div>
        );
    }

    return (
        <div style={{ maxWidth: '1000px', margin: '0 auto', paddingBottom: '40px' }}>
            <Link to="/admin/orders">
                <Button icon={<ArrowLeftOutlined />} style={{ marginBottom: '16px' }}>
                    Quay lại danh sách đơn hàng
                </Button>
            </Link>

            <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                    <div>
                        <span style={{ color: '#94a3b8', fontSize: '13px' }}>Đơn hàng quản trị:</span>
                        <h2 style={{ color: '#06b6d4', fontSize: '24px', fontWeight: 800, margin: 0 }}>
                            {order.order_code}
                        </h2>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <span style={{ color: '#94a3b8' }}>Đổi trạng thái:</span>
                        <Select
                            value={order.status?.toLowerCase()}
                            style={{ width: 180 }}
                            loading={updating}
                            onChange={handleUpdateStatus}
                        >
                            <Select.Option value="pending_payment">Chờ thanh toán</Select.Option>
                            <Select.Option value="paid">Đã thanh toán</Select.Option>
                            <Select.Option value="processing">Đang xử lý</Select.Option>
                            <Select.Option value="completed">Hoàn tất / Bàn giao</Select.Option>
                            <Select.Option value="cancelled">Huỷ đơn hàng</Select.Option>
                        </Select>
                    </div>
                </div>

                <Descriptions bordered column={2} size="small" style={{ marginBottom: '24px' }}>
                    <Descriptions.Item label="Khách hàng">{order.user?.full_name || order.user?.username || 'Khách'}</Descriptions.Item>
                    <Descriptions.Item label="Email khách">{order.user?.email}</Descriptions.Item>
                    <Descriptions.Item label="Phương thức">{order.payment_method_code?.toUpperCase() || 'VIETQR'}</Descriptions.Item>
                    <Descriptions.Item label="Mã giao dịch">{order.payment_reference || 'Chưa có'}</Descriptions.Item>
                    <Descriptions.Item label="Ngày đặt">{dayjs(order.created_at).format('DD/MM/YYYY HH:mm:ss')}</Descriptions.Item>
                    <Descriptions.Item label="Trạng thái">
                        <Tag color={order.status?.toUpperCase() === 'COMPLETED' ? 'success' : 'processing'}>
                            {order.status}
                        </Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Ghi chú của khách" span={2}>
                        {order.customer_note || 'Không có ghi chú.'}
                    </Descriptions.Item>
                </Descriptions>

                <h3 style={{ color: '#fff', fontSize: '16px', fontWeight: 700, marginBottom: '14px' }}>
                    Danh Sách Sản Phẩm & Dịch Vụ Trong Đơn
                </h3>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', marginBottom: '20px' }}>
                    {order.items?.map((item) => (
                        <div
                            key={item.id}
                            style={{
                                background: 'rgba(255,255,255,0.02)',
                                border: '1px solid rgba(255,255,255,0.06)',
                                borderRadius: '10px',
                                padding: '16px',
                            }}
                        >
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <div>
                                    <div style={{ color: '#fff', fontSize: '15px', fontWeight: 700 }}>
                                        {item.product_name || item.product?.name}
                                    </div>
                                    <div style={{ color: '#94a3b8', fontSize: '13px', marginTop: 4 }}>
                                        <Tag color={item.item_type === 'SERVICE' ? 'orange' : 'cyan'}>
                                            {item.item_type === 'SERVICE' ? 'Dịch vụ' : 'Tài khoản'}
                                        </Tag>
                                        Gói: {item.variant_name || 'Mặc định'} | SL: {item.quantity}
                                    </div>
                                </div>
                                <div style={{ color: '#06b6d4', fontSize: '17px', fontWeight: 800 }}>
                                    {Number(item.line_total || 0).toLocaleString('vi-VN')} đ
                                </div>
                            </div>

                            {/* Thông tin tài khoản bàn giao (nếu là ACCOUNT) */}
                            {item.credentials && (
                                <div style={{ marginTop: 12, padding: 12, background: 'rgba(16, 185, 129, 0.08)', borderRadius: 8 }}>
                                    <span style={{ color: '#10b981', fontWeight: 700 }}>
                                        <KeyOutlined /> Thông tin tài khoản game đã giao:
                                    </span>
                                    <div style={{ marginTop: 6, display: 'flex', gap: 20 }}>
                                        <span style={{ color: '#fff' }}>TK: <strong>{item.credentials.username}</strong></span>
                                        <span style={{ color: '#fbbf24' }}>MK: <strong>{item.credentials.password}</strong></span>
                                    </div>
                                </div>
                            )}

                            {/* Thông tin cày thuê (nếu là SERVICE) */}
                            {item.service_request && (
                                <div style={{ marginTop: 12, padding: 12, background: 'rgba(99, 102, 241, 0.08)', borderRadius: 8 }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                        <span style={{ color: '#818cf8', fontWeight: 700 }}>
                                            <ThunderboltOutlined /> Yêu cầu cày nick: #{item.service_request.request_code}
                                        </span>
                                        <Tag color="orange">{item.service_request.status}</Tag>
                                    </div>
                                    <div style={{ marginTop: 6, color: '#cbd5e1', fontSize: '13px' }}>
                                        Hẹn xong: <strong>{item.service_request.due_at ? dayjs(item.service_request.due_at).format('DD/MM/YYYY HH:mm') : 'Chưa hẹn'}</strong>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                <Divider style={{ borderColor: 'rgba(255,255,255,0.08)' }} />
                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '30px', alignItems: 'baseline' }}>
                    <span style={{ color: '#94a3b8', fontSize: '16px' }}>Tổng thanh toán đơn hàng:</span>
                    <span style={{ color: '#06b6d4', fontSize: '26px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                        {Number(order.total || 0).toLocaleString('vi-VN')} đ
                    </span>
                </div>
            </div>
        </div>
    );
};

export default AdminOrderDetailPage;
