import React, { useEffect, useState } from 'react';
import { Row, Col, Spin, Empty, Tag, Card, Statistic, Table, Progress } from 'antd';
import {
    BarChartOutlined,
    TrophyOutlined,
    SafetyCertificateOutlined,
    DollarCircleOutlined,
    ShoppingOutlined
} from '@ant-design/icons';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip as ChartTooltip,
    Legend,
} from 'chart.js';
import { Bar } from 'react-chartjs-2';
import api from '../../services/api';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    ChartTooltip,
    Legend
);

const progressColors = [
    '#6366f1',
    '#06b6d4',
    '#10b981',
    '#f59e0b',
    '#ec4899',
    '#8b5cf6',
];

const chartColors = [
    'rgba(99, 102, 241, 0.85)',
    'rgba(6, 182, 212, 0.85)',
    'rgba(16, 185, 129, 0.85)',
    'rgba(245, 158, 11, 0.85)',
    'rgba(236, 72, 153, 0.85)',
    'rgba(139, 92, 246, 0.85)',
];

const chartBorderColors = [
    '#6366f1',
    '#06b6d4',
    '#10b981',
    '#f59e0b',
    '#ec4899',
    '#7c3aed',
];

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

    const categoriesList = revenueData?.game_revenue || [];

    // Chia tên danh mục thành nhiều dòng nếu quá dài để không bao giờ bị xoay nghiêng (nằm chéo)
    const splitLabel = (str, maxChars = 16) => {
        if (!str) return '';
        const words = str.split(' ');
        const lines = [];
        let current = '';
        for (const w of words) {
            if ((current + ' ' + w).trim().length <= maxChars) {
                current = (current + ' ' + w).trim();
            } else {
                if (current) lines.push(current);
                current = w;
            }
        }
        if (current) lines.push(current);
        return lines.length > 1 ? lines : lines[0];
    };

    const chartLabels = categoriesList.map((g) => splitLabel(g.name));
    const chartAmounts = categoriesList.map((g) => Number(g.total_amount || 0));

    const chartData = {
        labels: chartLabels,
        datasets: [
            {
                label: 'Doanh Thu (VNĐ)',
                data: chartAmounts,
                backgroundColor: chartLabels.map((_, i) => chartColors[i % chartColors.length]),
                borderColor: chartLabels.map((_, i) => chartBorderColors[i % chartBorderColors.length]),
                borderWidth: 1.5,
                borderRadius: 8,
                barPercentage: 0.65,
                categoryPercentage: 0.85,
            },
        ],
    };

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: {
                left: 8,
                right: 8,
                top: 15,
                bottom: 10,
            },
        },
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#f8fafc',
                bodyColor: '#cbd5e1',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                padding: 12,
                boxPadding: 6,
                callbacks: {
                    title: (tooltipItems) => {
                        const idx = tooltipItems[0]?.dataIndex;
                        return categoriesList[idx]?.name || '';
                    },
                    label: (context) => {
                        const val = context.parsed.y || 0;
                        const pct = totalGameRevenue > 0 ? Math.round((val / totalGameRevenue) * 100) : 0;
                        return ` Doanh thu: ${val.toLocaleString('vi-VN')} đ (${pct}%)`;
                    },
                },
            },
        },
        scales: {
            x: {
                grid: {
                    color: 'rgba(255, 255, 255, 0.04)',
                    drawBorder: false,
                },
                ticks: {
                    color: '#cbd5e1',
                    font: {
                        size: 12,
                        weight: 600,
                    },
                    maxRotation: 0,
                    minRotation: 0,
                    autoSkip: false,
                },
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(255, 255, 255, 0.06)',
                    drawBorder: false,
                },
                ticks: {
                    color: '#94a3b8',
                    font: {
                        size: 12,
                        weight: 600,
                        family: 'Rajdhani',
                    },
                    callback: (value) => {
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M đ';
                        if (value >= 1000) return (value / 1000).toFixed(0) + 'k đ';
                        return value + ' đ';
                    },
                },
            },
        },
    };

    const productColumns = [
        {
            title: 'Tên Sản Phẩm',
            dataIndex: 'name',
            key: 'name',
            render: (text, r) => (
                <div>
                    <span style={{ color: '#fff', fontWeight: 600 }}>{text}</span>
                    <div style={{ color: '#94a3b8', fontSize: '12px' }}>{r.category?.name}</div>
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
                    BÁO CÁO DOANH THU & PHÂN TÍCH
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

            {/* BIỂU ĐỒ DOANH THU THEO DANH MỤC (THANH BIỂU ĐỒ CỘT) */}
            <div style={{
                background: '#121324',
                borderRadius: '14px',
                padding: '24px',
                border: '1px solid rgba(255,255,255,0.08)',
                marginBottom: '24px',
            }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px', flexWrap: 'wrap', gap: '12px' }}>
                    <div>
                        <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <BarChartOutlined style={{ color: '#06b6d4' }} /> Biểu Đồ Cột Doanh Thu Theo Danh Mục
                        </h3>
                        <p style={{ color: '#94a3b8', fontSize: '13px', margin: '4px 0 0' }}>
                            Phân bổ doanh thu thực tế theo từng danh mục tài khoản & dịch vụ Liên Quân Mobile
                        </p>
                    </div>
                    {totalGameRevenue > 0 && (
                        <div style={{ textAlign: 'right' }}>
                            <span style={{ color: '#94a3b8', fontSize: '13px' }}>Tổng doanh thu danh mục: </span>
                            <span style={{ color: '#10b981', fontSize: '18px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                                {totalGameRevenue.toLocaleString('vi-VN')} đ
                            </span>
                        </div>
                    )}
                </div>

                {revenueData?.game_revenue && revenueData.game_revenue.length > 0 ? (
                    <div>
                        {/* Biểu đồ cột Chart.js */}
                        <div style={{
                            width: '100%',
                            height: '380px',
                            position: 'relative',
                        }}>
                            <Bar data={chartData} options={chartOptions} />
                        </div>

                        {/* Thanh tiến trình chi tiết bên dưới */}
                        <div style={{ marginTop: '24px', borderTop: '1px solid rgba(255, 255, 255, 0.06)', paddingTop: '20px' }}>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '16px' }}>
                                {revenueData.game_revenue.map((g, idx) => {
                                    const amt = Number(g.total_amount || 0);
                                    const pct = totalGameRevenue > 0 ? Math.round((amt / totalGameRevenue) * 100) : 0;
                                    return (
                                        <div
                                            key={g.id}
                                            style={{
                                                background: 'rgba(255, 255, 255, 0.02)',
                                                padding: '14px 18px',
                                                borderRadius: '10px',
                                                border: '1px solid rgba(255, 255, 255, 0.06)',
                                            }}
                                        >
                                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                                                <span style={{ color: '#fff', fontWeight: 600, fontSize: '13px' }}>{g.name}</span>
                                                <span style={{ color: '#06b6d4', fontWeight: 700, fontSize: '13px', fontFamily: 'Rajdhani' }}>
                                                    {amt.toLocaleString('vi-VN')} đ <Tag color="cyan" style={{ marginLeft: 6, fontWeight: 700 }}>{pct}%</Tag>
                                                </span>
                                            </div>
                                            <Progress
                                                percent={pct}
                                                showInfo={false}
                                                strokeColor={progressColors[idx % progressColors.length]}
                                                trailColor="rgba(255, 255, 255, 0.08)"
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                ) : (
                    <Empty description={<span style={{ color: '#94a3b8' }}>Chưa có phát sinh doanh thu</span>} />
                )}
            </div>

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
