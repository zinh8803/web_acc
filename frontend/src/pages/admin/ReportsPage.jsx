import React, { useEffect, useState } from 'react';
import { Row, Col, Spin, Empty, Tag, Card, Statistic, Table, Progress, Segmented, Select, DatePicker, Space } from 'antd';
import {
    BarChartOutlined,
    TrophyOutlined,
    SafetyCertificateOutlined,
    DollarCircleOutlined,
    ShoppingOutlined,
    CalendarOutlined,
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
import dayjs from 'dayjs';
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

const AdminReportsPage = () => {
    const [revenueData, setRevenueData] = useState(null);
    const [topProducts, setTopProducts] = useState([]);
    const [warrantyStats, setWarrantyStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [chartLoading, setChartLoading] = useState(false);

    // Bộ lọc chu kỳ biểu đồ: 'year' (Năm: Tháng 1 - 12), 'month' (Tháng: Ngày 1 - 31), 'week' (Tuần: Thứ 2 - CN)
    const [period, setPeriod] = useState('year');
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
    const [selectedDate, setSelectedDate] = useState(dayjs());

    // Tải dữ liệu toàn bộ khi khởi động trang
    useEffect(() => {
        const fetchInitialReports = async () => {
            try {
                setLoading(true);
                const [prodRes, warRes] = await Promise.all([
                    api.get('/admin/reports/products'),
                    api.get('/admin/reports/warranty'),
                ]);

                if (prodRes.data?.data) setTopProducts(prodRes.data.data);
                if (warRes.data?.data) setWarrantyStats(warRes.data.data);
            } catch (e) {
                console.error('Failed to load initial reports', e);
            } finally {
                setLoading(false);
            }
        };
        fetchInitialReports();
    }, []);

    // Tải dữ liệu doanh thu & biểu đồ theo bộ lọc thời gian
    useEffect(() => {
        const fetchRevenue = async () => {
            setChartLoading(true);
            try {
                const params = {
                    period,
                    year: selectedYear,
                    month: selectedMonth,
                    date: selectedDate ? selectedDate.format('YYYY-MM-DD') : undefined,
                };
                const res = await api.get('/admin/reports/revenue', { params });
                if (res.data?.data) {
                    setRevenueData(res.data.data);
                }
            } catch (e) {
                console.error('Failed to load revenue chart data', e);
            } finally {
                setChartLoading(false);
            }
        };
        fetchRevenue();
    }, [period, selectedYear, selectedMonth, selectedDate]);

    // Tạo danh sách năm phong phú (từ năm hiện tại - 6 đến năm hiện tại + 3) kết hợp các năm từ DB
    const currentSystemYear = new Date().getFullYear();
    const fallbackYears = Array.from({ length: 11 }, (_, i) => currentSystemYear + 3 - i); // 2029, 2028, ..., 2019
    const availableYears = Array.from(
        new Set([
            ...(revenueData?.available_years || []),
            ...fallbackYears,
        ])
    ).sort((a, b) => b - a);

    // Dữ liệu biểu đồ từ API
    const chartInfo = revenueData?.revenue_chart;
    const chartLabels = chartInfo?.labels || [
        'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
        'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
    ];
    const chartAmounts = chartInfo?.data || new Array(12).fill(0);
    const chartPeriodTotal = chartInfo?.total || 0;
    const chartTitle = chartInfo?.period_title || `Năm ${selectedYear}`;

    // Cấu hình dữ liệu Bar Chart
    const barChartData = {
        labels: chartLabels,
        datasets: [
            {
                label: 'Doanh Thu (VNĐ)',
                data: chartAmounts,
                backgroundColor: 'rgba(6, 182, 212, 0.75)',
                hoverBackgroundColor: 'rgba(6, 182, 212, 0.95)',
                borderColor: '#06b6d4',
                borderWidth: 1.5,
                borderRadius: 6,
                barPercentage: period === 'month' ? 0.8 : 0.6,
                categoryPercentage: 0.85,
            },
        ],
    };

    const barChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: {
                left: 6,
                right: 6,
                top: 15,
                bottom: 6,
            },
        },
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#f8fafc',
                bodyColor: '#38bdf8',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                padding: 12,
                boxPadding: 6,
                callbacks: {
                    title: (tooltipItems) => {
                        return tooltipItems[0]?.label || '';
                    },
                    label: (context) => {
                        const val = context.parsed.y || 0;
                        return ` Doanh thu: ${val.toLocaleString('vi-VN')} đ`;
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
                        size: period === 'month' ? 11 : 12,
                        weight: 600,
                    },
                    maxRotation: period === 'month' ? 45 : 0,
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
                        if (value >= 1000000000) return (value / 1000000000).toFixed(1) + 'B đ';
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M đ';
                        if (value >= 1000) return (value / 1000).toFixed(0) + 'k đ';
                        return value + ' đ';
                    },
                },
            },
        },
    };

    const totalGameRevenue = revenueData?.game_revenue?.reduce((acc, g) => acc + Number(g.total_amount || 0), 0) || 0;

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
                    Thống kê doanh thu theo năm (tháng 1 - tháng 12), theo tháng và theo tuần
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

            {/* BIỂU ĐỒ CỘT DOANH THU THEO THỜI GIAN (NĂM: T1 -> T12, THÁNG, TUẦN) */}
            <div style={{
                background: '#121324',
                borderRadius: '14px',
                padding: '24px',
                border: '1px solid rgba(255,255,255,0.08)',
                marginBottom: '24px',
            }}>
                {/* Header thanh điều khiển bộ lọc thời gian */}
                <div style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    marginBottom: '24px',
                    flexWrap: 'wrap',
                    gap: '16px',
                }}>
                    <div>
                        <h3 style={{ color: '#fff', fontSize: '18px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <BarChartOutlined style={{ color: '#06b6d4' }} />
                            BIỂU ĐỒ CỘT DOANH THU - {chartTitle.toUpperCase()}
                        </h3>
                        <p style={{ color: '#94a3b8', fontSize: '13px', margin: '4px 0 0' }}>
                            {period === 'year' && `Doanh thu các tháng từ Tháng 1 đến Tháng 12 trong năm ${selectedYear}`}
                            {period === 'month' && `Doanh thu các ngày trong Tháng ${selectedMonth}/${selectedYear}`}
                            {period === 'week' && `Doanh thu 7 ngày trong tuần (${chartTitle})`}
                        </p>
                    </div>

                    {/* Bộ điều khiển chọn: Năm, Tháng, Tuần */}
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flexWrap: 'wrap' }}>
                        <Segmented
                            value={period}
                            onChange={(val) => setPeriod(val)}
                            options={[
                                { label: 'Theo Năm (T1 - T12)', value: 'year' },
                                { label: 'Theo Tháng', value: 'month' },
                                { label: 'Theo Tuần', value: 'week' },
                            ]}
                            style={{ background: 'rgba(255, 255, 255, 0.08)' }}
                        />

                        {/* Bộ lọc tùy theo chế độ */}
                        {period === 'year' && (
                            <Select
                                showSearch
                                value={selectedYear}
                                onChange={(y) => setSelectedYear(y)}
                                options={availableYears.map((y) => ({
                                    value: y,
                                    label: y === currentSystemYear ? `Năm ${y} (Hiện tại)` : `Năm ${y}`
                                }))}
                                style={{ width: 160 }}
                                placeholder="Chọn năm"
                            />
                        )}

                        {period === 'month' && (
                            <Space>
                                <Select
                                    value={selectedMonth}
                                    onChange={(m) => setSelectedMonth(m)}
                                    options={Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: `Tháng ${i + 1}` }))}
                                    style={{ width: 110 }}
                                />
                                <Select
                                    showSearch
                                    value={selectedYear}
                                    onChange={(y) => setSelectedYear(y)}
                                    options={availableYears.map((y) => ({
                                        value: y,
                                        label: y === currentSystemYear ? `Năm ${y} (Hiện tại)` : `Năm ${y}`
                                    }))}
                                    style={{ width: 160 }}
                                    placeholder="Chọn năm"
                                />
                            </Space>
                        )}

                        {period === 'week' && (
                            <DatePicker
                                value={selectedDate}
                                onChange={(d) => setSelectedDate(d || dayjs())}
                                allowClear={false}
                                placeholder="Chọn ngày trong tuần"
                                format="DD/MM/YYYY"
                                style={{ width: 150 }}
                            />
                        )}

                        {/* Tổng doanh thu của kỳ */}
                        <div style={{
                            background: 'rgba(6, 182, 212, 0.1)',
                            border: '1px solid rgba(6, 182, 212, 0.3)',
                            padding: '6px 14px',
                            borderRadius: '8px',
                        }}>
                            <span style={{ color: '#94a3b8', fontSize: '12px' }}>Doanh thu kỳ này: </span>
                            <span style={{ color: '#22d3ee', fontSize: '16px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                                {Number(chartPeriodTotal).toLocaleString('vi-VN')} đ
                            </span>
                        </div>
                    </div>
                </div>

                {/* Vùng hiển thị biểu đồ Bar Chart */}
                <div style={{ width: '100%', height: '390px', position: 'relative' }}>
                    {chartLoading ? (
                        <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100%' }}>
                            <Spin size="large" />
                        </div>
                    ) : (
                        <Bar data={barChartData} options={barChartOptions} />
                    )}
                </div>
            </div>

            {/* PHÂN BỔ DOANH THU THEO DANH MỤC */}
            {revenueData?.game_revenue && revenueData.game_revenue.length > 0 && (
                <div style={{
                    background: '#121324',
                    borderRadius: '14px',
                    padding: '24px',
                    border: '1px solid rgba(255,255,255,0.08)',
                    marginBottom: '24px',
                }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                        <div>
                            <h3 style={{ color: '#fff', fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <CalendarOutlined style={{ color: '#10b981' }} /> Phân Bổ Doanh Thu Theo Danh Mục
                            </h3>
                            <p style={{ color: '#94a3b8', fontSize: '13px', margin: '4px 0 0' }}>
                                Tỷ trọng đóng góp doanh thu của từng thể loại nick & dịch vụ Liên Quân
                            </p>
                        </div>
                        <div style={{ textAlign: 'right' }}>
                            <span style={{ color: '#94a3b8', fontSize: '13px' }}>Tổng danh mục: </span>
                            <span style={{ color: '#10b981', fontSize: '17px', fontWeight: 800, fontFamily: 'Rajdhani' }}>
                                {totalGameRevenue.toLocaleString('vi-VN')} đ
                            </span>
                        </div>
                    </div>

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
            )}

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
