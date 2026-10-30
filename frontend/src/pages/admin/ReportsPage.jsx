import React, { useEffect, useState } from 'react';
import { Row, Col, Spin, Empty, Tag, Card, Statistic, Table } from 'antd';
import {
    BarChartOutlined,
    PieChartOutlined,
    TrophyOutlined,
    SafetyCertificateOutlined,
    DollarCircleOutlined,
    ShoppingOutlined
} from '@ant-design/icons';
import api from '../../services/api';

const AdminReportsPage = () => {
    const [revenueData, setRevenueData] = useState(null);
    const [topProducts, setTopProducts] = useState([]);
    const [warrantyStats, setWarrantyStats] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchAllReports = async () => {
            try {
                setLoading(true);
                const [revRes, prodRes, warRes] = await Promise.all([
                    api.get('/admin/reports/revenue'),
                    api.get('/admin/reports/products'),
                    api.get('/admin/reports/warranty'),
                ]);

                if (revRes.data?.data) setRevenueData(revRes.data.data);
                if (prodRes.data?.data) setTopProducts(prodRes.data.data);
                if (warRes.data?.data) setWarrantyStats(warRes.data.data);
            } catch (e) {
                console.error('Failed to load reports', e);
            } finally {
                setLoading(false);
            }
        };
        fetchAllReports();
    }, []);

    const totalGameRevenue = revenueData?.game_revenue?.reduce((acc, g) => acc + Number(g.total_amount || 0), 0) || 0;
    const totalTxCount = revenueData?.payment_stats?.reduce((acc, p) => acc + Number(p.tx_count || 0), 0) || 0;

    const productColumns = [
        {
            title: 'Tên Sản Phẩm',
            dataIndex: 'name',
            key: 'name',
            render: (text, r) => (
                <div>
                    <span style={{ color: '#fff', fontWeight: 600 }}>{text}</span>
                    <div style={{ color: '#94a3b8', fontSize: '12px' }}>{r.game?.name} • {r.category?.name}</div>
                </div>
            ),
        },
        {
            title: 'Loại',
            dataIndex: 'type',
            key: 'type',
            render: (t) => <Tag color={t === 'ACCOUNT' ? 'cyan' : 'orange'}>{t === 'ACCOUNT' ? 'Tài Khoản' : 'Dịch Vụ'}</Tag>,
        },
        {
            title: 'Đã Bán',
            dataIndex: 'sold_count',
            key: 'sold_count',
            render: (cnt) => <Tag color="green" style={{ fontWeight: 700 }}>{cnt || 0} đơn</Tag>,
        },
    ];

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: '100px 0' }}>
                <Spin size="large" />
            </div>
        );
    }

    return (
        <div>
            <div style={{ marginBottom: '24px' }}>
                <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }}>
                    BÁO CÁO DOANH THU & PHÂN TÍCH 📈
                </h1>
                <p style={{ color: '#94a3b8', fontSize: '13px', margin: 0 }}>
                    Tổng hợp số liệu kinh doanh, tỷ trọng doanh thu và hiệu suất bán hàng
                </p>
            </div>

            {/* THỐNG KÊ TỔNG QUAN */}
            <Row gutter={[16, 16]} style={{ marginBottom: '24px' }}>
                <Col xs={24} sm={12} lg={6}>
                    <Card style={{ background: '#121324', borderRadius: '12px' }}>
                        <Statistic
                            title={<span style={{ color: '#94a3b8' }}>Doanh thu hôm nay</span>}
                            value={revenueData?.today_revenue || 0}
                            precision={0}
                            suffix="đ"
                            valueStyle={{ color: '#10b981', fontWeight: 800, fontFamily: 'Rajdhani' }}
                            prefix={<DollarCircleOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card style={{ background: '#121324', borderRadius: '12px' }}>
                        <Statistic
                            title={<span style={{ color: '#94a3b8' }}>Tổng doanh thu lũy kế</span>}
                            value={revenueData?.total_revenue || 0}
                            precision={0}
                            suffix="đ"
                            valueStyle={{ color: '#06b6d4', fontWeight: 800, fontFamily: 'Rajdhani' }}
                            prefix={<DollarCircleOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card style={{ background: '#121324', borderRadius: '12px' }}>
                        <Statistic
                            title={<span style={{ color: '#94a3b8' }}>Đơn hàng hoàn tất</span>}
                            value={revenueData?.completed_orders || 0}
                            valueStyle={{ color: '#f59e0b', fontWeight: 800, fontFamily: 'Rajdhani' }}
                            prefix={<ShoppingOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card style={{ background: '#121324', borderRadius: '12px' }}>
                        <Statistic
                            title={<span style={{ color: '#94a3b8' }}>Khiếu nại bảo hành đang xử lý</span>}
                            value={warrantyStats?.pending_claims || 0}
                            valueStyle={{ color: '#ec4899', fontWeight: 800, fontFamily: 'Rajdhani' }}
                            prefix={<SafetyCertificateOutlined />}
                        />
                    </Card>
                </Col>
            </Row>

            {/* BIỂU ĐỒ & BẢNG PHÂN BỐ */}
            <Row gutter={[20, 20]} style={{ marginBottom: '24px' }}>
                <Col xs={24} md={12}>
                    <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)', height: '100%' }}>
                        <h3 style={{ color: '#fff', marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <BarChartOutlined style={{ color: '#06b6d4' }} /> Doanh Thu Theo Tựa Game
                        </h3>
                        {revenueData?.game_revenue && revenueData.game_revenue.length > 0 ? (
                            <div style={{ color: '#cbd5e1', display: 'flex', flexDirection: 'column', gap: '12px' }}>
                                {revenueData.game_revenue.map((g) => {
                                    const amt = Number(g.total_amount || 0);
                                    const pct = totalGameRevenue > 0 ? Math.round((amt / totalGameRevenue) * 100) : 0;
                                    return (
                                        <div key={g.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                            <span>• <strong>{g.name}</strong>:</span>
                                            <span>
                                                <strong style={{ color: '#06b6d4' }}>{amt.toLocaleString('vi-VN')} đ</strong>{' '}
                                                <Tag color="cyan">{pct}%</Tag>
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <Empty description={<span style={{ color: '#94a3b8' }}>Chưa có phát sinh doanh thu</span>} />
                        )}
                    </div>
                </Col>

                <Col xs={24} md={12}>
                    <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)', height: '100%' }}>
                        <h3 style={{ color: '#fff', marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <PieChartOutlined style={{ color: '#10b981' }} /> Phương Thức Thanh Toán
                        </h3>
                        {revenueData?.payment_stats && revenueData.payment_stats.length > 0 ? (
                            <div style={{ color: '#cbd5e1', display: 'flex', flexDirection: 'column', gap: '12px' }}>
                                {revenueData.payment_stats.map((p) => {
                                    const count = Number(p.tx_count || 0);
                                    const pct = totalTxCount > 0 ? Math.round((count / totalTxCount) * 100) : 0;
                                    return (
                                        <div key={p.code} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                            <span>• <strong>{p.name}</strong>:</span>
                                            <span>
                                                <strong style={{ color: '#10b981' }}>{count} giao dịch</strong>{' '}
                                                <Tag color="green">{pct}%</Tag>
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <Empty description={<span style={{ color: '#94a3b8' }}>Chưa có giao dịch thanh toán</span>} />
                        )}
                    </div>
                </Col>
            </Row>

            {/* TOP SẢN PHẨM BÁN CHẠY */}
            <div style={{ background: '#121324', borderRadius: '14px', padding: '24px', border: '1px solid rgba(255,255,255,0.08)' }}>
                <h3 style={{ color: '#fff', fontSize: '17px', fontWeight: 700, marginBottom: '16px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <TrophyOutlined style={{ color: '#fbbf24' }} /> Top Sản Phẩm Bán Chạy Nhất
                </h3>
                <Table
                    columns={productColumns}
                    dataSource={topProducts}
                    rowKey="id"
                    pagination={false}
                    locale={{ emptyText: 'Chưa có sản phẩm phát sinh đơn hàng.' }}
                />
            </div>
        </div>
    );
};

export default AdminReportsPage;
