import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Card, Row, Col, Tag, Button, Spin, Alert, Steps, Divider, message } from 'antd';
import {
    ArrowLeftOutlined,
    SafetyCertificateOutlined,
    ClockCircleOutlined,
    CheckCircleOutlined,
    SyncOutlined,
    CloseCircleOutlined,
    ExclamationCircleOutlined,
    FileTextOutlined
} from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const WarrantyClaimDetailPage = () => {
    const { code } = useParams();
    const [claim, setClaim] = useState(null);
    const [loading, setLoading] = useState(true);

    const fetchDetail = async () => {
        try {
            setLoading(true);
            const res = await api.get(`/warranty-claims/${code}`);
            if (res.data?.success) {
                setClaim(res.data.data);
            }
        } catch (err) {
            console.error('Fetch claim detail error:', err);
            message.error('Không tìm thấy thông tin khiếu nại bảo hành.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (code) fetchDetail();
    }, [code]);

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: '120px 0' }}>
                <Spin size="large" />
            </div>
        );
    }

    if (!claim) {
        return (
            <div style={{ textAlign: 'center', padding: '100px 20px', color: '#fff' }}>
                <h2>Không tìm thấy khiếu nại bảo hành.</h2>
                <Link to="/warranty-claims">
                    <Button type="primary" style={{ marginTop: '16px' }}>Quay lại danh sách</Button>
                </Link>
            </div>
        );
    }

    const item = claim.order_item || claim.orderItem;
    const order = claim.order || item?.order;
    const policy = claim.policy;

    let stepCurrent = 0;
    let stepStatus = 'process';
    const st = (claim.status || '').toUpperCase();
    if (st === 'SUBMITTED') {
        stepCurrent = 0;
    } else if (st === 'IN_REVIEW' || st === 'NEED_INFO') {
        stepCurrent = 1;
    } else if (st === 'APPROVED' || st === 'RESOLVED') {
        stepCurrent = 2;
        stepStatus = 'finish';
    } else if (st === 'REJECTED' || st === 'CANCELLED') {
        stepCurrent = 1;
        stepStatus = 'error';
    }

    return (
        <div style={{ maxWidth: '900px', margin: '36px auto 60px', padding: '0 20px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                <div>
                    <span style={{ color: '#94a3b8', fontSize: '13px' }}>Chi tiết khiếu nại bảo hành:</span>
                    <h1 style={{ color: '#10b981', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
                        {claim.claim_code}
                    </h1>
                </div>
                <Link to="/warranty-claims">
                    <Button icon={<ArrowLeftOutlined />}>Quay lại danh sách</Button>
                </Link>
            </div>

            <Card
                style={{
                    background: '#121324',
                    borderRadius: '16px',
                    border: '1px solid rgba(255,255,255,0.08)',
                    boxShadow: '0 8px 30px rgba(0,0,0,0.3)',
                    marginBottom: '24px',
                }}
            >
                {/* TIẾN ĐỘ XỬ LÝ */}
                <div style={{ padding: '10px 0 20px' }}>
                    <Steps
                        current={stepCurrent}
                        status={stepStatus}
                        items={[
                            {
                                title: <span style={{ color: '#fff', fontWeight: 700 }}>1. Đã Gửi Yêu Cầu</span>,
                                description: (
                                    <span style={{ color: '#94a3b8' }}>
                                        {claim.submitted_at ? dayjs(claim.submitted_at).format('DD/MM HH:mm') : 'Hệ thống tiếp nhận'}
                                    </span>
                                ),
                            },
                            {
                                title: <span style={{ color: '#fff', fontWeight: 700 }}>2. Đang Kiểm Tra</span>,
                                description: (
                                    <div>
                                        <span style={{ color: '#38bdf8' }}>Kỹ thuật xác minh</span>
                                        {claim.due_at && (
                                            <div style={{ color: '#fbbf24', fontSize: '12px' }}>
                                                ⏰ Hạn SLA: {dayjs(claim.due_at).format('DD/MM HH:mm')}
                                            </div>
                                        )}
                                    </div>
                                ),
                            },
                            {
                                title: <span style={{ color: '#fff', fontWeight: 700 }}>3. Hoàn Tất</span>,
                                description: (
                                    <span style={{ color: '#10b981' }}>
                                        {claim.resolved_at ? dayjs(claim.resolved_at).format('DD/MM HH:mm') : 'Đổi acc / Hoàn tiền'}
                                    </span>
                                ),
                            },
                        ]}
                    />
                </div>

                <Divider style={{ borderColor: 'rgba(255,255,255,0.08)' }} />

                {/* THÔNG TIN SẢN PHẨM & ĐƠN HÀNG */}
                <div style={{ marginBottom: '24px' }}>
                    <h3 style={{ color: '#fff', fontSize: '17px', fontWeight: 700, marginBottom: '14px' }}>
                        Thông Tin Sản Phẩm Khiếu Nại
                    </h3>
                    <div style={{ background: 'rgba(255,255,255,0.02)', padding: '16px', borderRadius: '10px', border: '1px solid rgba(255,255,255,0.06)' }}>
                        <Row gutter={[16, 12]}>
                            <Col xs={24} sm={12}>
                                <span style={{ color: '#94a3b8', fontSize: '12px' }}>Sản phẩm:</span>
                                <div style={{ color: '#fff', fontWeight: 700, fontSize: '15px' }}>
                                    {item?.product_name || item?.product?.name || 'Tài khoản'}
                                </div>
                            </Col>
                            <Col xs={24} sm={12}>
                                <span style={{ color: '#94a3b8', fontSize: '12px' }}>Đơn hàng liên quan:</span>
                                <div>
                                    {order?.order_code ? (
                                        <Link to={`/orders/${order.order_code}`}>
                                            <span style={{ color: '#06b6d4', fontWeight: 700 }}>{order.order_code}</span>
                                        </Link>
                                    ) : (
                                        <span style={{ color: '#cbd5e1' }}>Không xác định</span>
                                    )}
                                </div>
                            </Col>
                            <Col xs={24} sm={12}>
                                <span style={{ color: '#94a3b8', fontSize: '12px' }}>Chính sách áp dụng:</span>
                                <div style={{ color: '#10b981', fontWeight: 600 }}>
                                    {policy?.name || 'Bảo hành 1 đổi 1 tiêu chuẩn'}
                                </div>
                            </Col>
                            <Col xs={24} sm={12}>
                                <span style={{ color: '#94a3b8', fontSize: '12px' }}>Thời gian gửi:</span>
                                <div style={{ color: '#cbd5e1' }}>
                                    {claim.created_at ? dayjs(claim.created_at).format('DD/MM/YYYY HH:mm') : 'Mới gửi'}
                                </div>
                            </Col>
                        </Row>
                    </div>
                </div>

                {/* LÝ DO VÀ MÔ TẢ CỦA KHÁCH */}
                <div style={{ marginBottom: '24px' }}>
                    <h3 style={{ color: '#fff', fontSize: '17px', fontWeight: 700, marginBottom: '12px' }}>
                        Chi Tiết Sự Cố Của Bạn
                    </h3>
                    <div style={{ background: 'rgba(255,255,255,0.02)', padding: '16px', borderRadius: '10px', border: '1px solid rgba(255,255,255,0.06)' }}>
                        <div style={{ marginBottom: '10px' }}>
                            <span style={{ color: '#94a3b8', fontSize: '13px' }}>Phân loại: </span>
                            <Tag color="orange" style={{ fontWeight: 700, fontSize: '13px' }}>
                                {claim.reason || claim.claim_type}
                            </Tag>
                        </div>
                        <div style={{ color: '#94a3b8', fontSize: '13px', marginBottom: '6px' }}>Nội dung mô tả:</div>
                        <div style={{ color: '#0f172a', whiteSpace: 'pre-wrap', lineHeight: 1.6, background: '#ffffff', padding: '14px 16px', borderRadius: '8px', border: '1px solid #cbd5e1', fontWeight: 500 }}>
                            {claim.description}
                        </div>
                    </div>
                </div>

                {/* KẾT QUẢ XỬ LÝ TỪ KỸ THUẬT VIÊN / ADMIN */}
                {(claim.resolution_note || claim.reject_reason || claim.resolution_type) && (
                    <div>
                        <h3 style={{ color: '#fff', fontSize: '17px', fontWeight: 700, marginBottom: '12px' }}>
                            Phản Hồi & Kết Quả Từ Đội Kỹ Thuật
                        </h3>
                        {claim.reject_reason ? (
                            <Alert
                                type="error"
                                showIcon
                                message="Yêu Cầu Bị Từ Chối"
                                description={claim.reject_reason}
                            />
                        ) : (
                            <Alert
                                type="success"
                                showIcon
                                message={`Phương Án Xử Lý: ${claim.resolution_type || 'Đã giải quyết'}`}
                                description={claim.resolution_note || 'Đội ngũ kỹ thuật đã xử lý thành công khiếu nại của bạn.'}
                            />
                        )}
                    </div>
                )}
            </Card>
        </div>
    );
};

export default WarrantyClaimDetailPage;
