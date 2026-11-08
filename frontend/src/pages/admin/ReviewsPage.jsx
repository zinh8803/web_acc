import React, { useEffect, useState, useCallback } from 'react';
import {
    Table,
    Card,
    Button,
    Input,
    Select,
    Tag,
    Rate,
    Space,
    Popconfirm,
    message,
    Row,
    Col,
    Avatar,
    Typography,
} from 'antd';
import {
    SearchOutlined,
    ReloadOutlined,
    CheckCircleOutlined,
    EyeInvisibleOutlined,
    DeleteOutlined,
    StarOutlined,
    UserOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const { Option } = Select;
const { Text } = Typography;

const AdminReviewsPage = () => {
    const [reviews, setReviews] = useState([]);
    const [loading, setLoading] = useState(false);
    const [pagination, setPagination] = useState({
        current: 1,
        pageSize: 20,
        total: 0,
    });
    const [searchText, setSearchText] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [ratingFilter, setRatingFilter] = useState('');

    const fetchReviews = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = {
                page,
                per_page: pagination.pageSize,
            };
            if (searchText) params.search = searchText;
            if (statusFilter) params.status = statusFilter;
            if (ratingFilter) params.rating = ratingFilter;

            const res = await api.get('/admin/reviews', { params });
            const data = res.data?.data || res.data?.du_lieu;
            if (data) {
                setReviews(data.data || []);
                setPagination((prev) => ({
                    ...prev,
                    current: data.current_page || 1,
                    total: data.total || 0,
                }));
            }
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể tải danh sách đánh giá.');
        } finally {
            setLoading(false);
        }
    }, [pagination.pageSize, searchText, statusFilter, ratingFilter]);

    useEffect(() => {
        fetchReviews(1);
    }, [fetchReviews]);

    const handleUpdateStatus = async (id, newStatus) => {
        try {
            await api.put(`/admin/reviews/${id}`, { status: newStatus });
            message.success(
                newStatus === 'APPROVED'
                    ? 'Đã duyệt hiển thị đánh giá!'
                    : 'Đã ẩn đánh giá khỏi sản phẩm!'
            );
            fetchReviews(pagination.current);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể cập nhật trạng thái.');
        }
    };

    const handleDeleteReview = async (id) => {
        try {
            await api.delete(`/admin/reviews/${id}`);
            message.success('Đã xóa đánh giá thành công!');
            fetchReviews(pagination.current);
        } catch (error) {
            message.error(error.response?.data?.message || 'Không thể xóa đánh giá.');
        }
    };

    const columns = [
        {
            title: 'ID',
            dataIndex: 'id',
            key: 'id',
            width: 70,
            render: (id) => <span style={{ color: '#94a3b8', fontWeight: 600 }}>#{id}</span>,
        },
        {
            title: 'Khách hàng',
            key: 'user',
            width: 180,
            render: (_, record) => (
                <Space>
                    <Avatar icon={<UserOutlined />} style={{ backgroundColor: '#6366f1' }} />
                    <div>
                        <div style={{ color: '#fff', fontWeight: 600 }}>
                            {record.user?.name || 'Khách hàng'}
                        </div>
                        <div style={{ color: '#94a3b8', fontSize: '12px' }}>
                            {record.user?.email || 'N/A'}
                        </div>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Sản phẩm',
            key: 'product',
            width: 240,
            render: (_, record) => {
                const prod = record.product;
                if (!prod) return <span style={{ color: '#94a3b8' }}>Sản phẩm đã xóa</span>;
                return (
                    <Space orientation="horizontal" align="center">
                        {prod.thumbnail_url && (
                            <img
                                src={prod.thumbnail_url}
                                alt={prod.name}
                                style={{
                                    width: 44,
                                    height: 44,
                                    borderRadius: 6,
                                    objectFit: 'cover',
                                    border: '1px solid rgba(255,255,255,0.1)',
                                }}
                            />
                        )}
                        <div>
                            <div style={{ color: '#f8fafc', fontWeight: 600, fontSize: '13px' }}>
                                {prod.name}
                            </div>
                            <Tag color="cyan" style={{ fontSize: '11px', marginTop: '2px' }}>
                                {prod.sku || `SP #${prod.id}`}
                            </Tag>
                        </div>
                    </Space>
                );
            },
        },
        {
            title: 'Đánh giá & Bình luận',
            key: 'rating_content',
            render: (_, record) => (
                <div>
                    <Rate
                        disabled
                        value={record.rating || record.so_sao || 5}
                        style={{ fontSize: 14, color: '#fbbf24', marginBottom: 4 }}
                    />
                    <div
                        style={{
                            color: '#cbd5e1',
                            fontSize: '13px',
                            background: 'rgba(255,255,255,0.03)',
                            padding: '8px 12px',
                            borderRadius: '8px',
                            border: '1px solid rgba(255,255,255,0.05)',
                        }}
                    >
                        {record.content || record.noi_dung_danh_gia || 'Không có bình luận'}
                    </div>
                </div>
            ),
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 140,
            render: (d) => (
                <span style={{ color: '#94a3b8', fontSize: '12px' }}>
                    {d ? dayjs(d).format('DD/MM/YYYY HH:mm') : '-'}
                </span>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            width: 120,
            render: (st) => {
                const isApproved = st === 'APPROVED';
                return (
                    <Tag
                        color={isApproved ? 'success' : 'error'}
                        style={{ fontWeight: 600, padding: '2px 8px' }}
                    >
                        {isApproved ? 'ĐÃ DUYỆT' : 'ĐÃ ẨN'}
                    </Tag>
                );
            },
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 150,
            render: (_, record) => {
                const isApproved = record.status === 'APPROVED';
                return (
                    <Space size="small">
                        {isApproved ? (
                            <Button
                                size="small"
                                danger
                                icon={<EyeInvisibleOutlined />}
                                onClick={() => handleUpdateStatus(record.id, 'HIDDEN')}
                                title="Ẩn đánh giá này"
                            >
                                Ẩn
                            </Button>
                        ) : (
                            <Button
                                size="small"
                                type="primary"
                                icon={<CheckCircleOutlined />}
                                onClick={() => handleUpdateStatus(record.id, 'APPROVED')}
                                style={{ background: '#10b981', borderColor: '#10b981' }}
                                title="Duyệt đánh giá này"
                            >
                                Duyệt
                            </Button>
                        )}
                        <Popconfirm
                            title="Xóa đánh giá này vĩnh viễn?"
                            okText="Xóa"
                            cancelText="Hủy"
                            okButtonProps={{ danger: true }}
                            onConfirm={() => handleDeleteReview(record.id)}
                        >
                            <Button size="small" danger icon={<DeleteOutlined />} />
                        </Popconfirm>
                    </Space>
                );
            },
        },
    ];

    return (
        <div>
            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    marginBottom: '24px',
                }}
            >
                <div>
                    <h1
                        style={{
                            color: '#fff',
                            fontSize: '24px',
                            fontWeight: 800,
                            fontFamily: 'Rajdhani',
                            margin: 0,
                        }}
                    >
                        QUẢN LÝ ĐÁNH GIÁ SẢN PHẨM
                    </h1>
                    <p style={{ color: '#94a3b8', margin: '4px 0 0', fontSize: '13px' }}>
                        Duyệt, ẩn hoặc kiểm duyệt ý kiến phản hồi của khách hàng sau khi mua hàng.
                    </p>
                </div>
                <Button
                    icon={<ReloadOutlined />}
                    onClick={() => fetchReviews(pagination.current)}
                    loading={loading}
                >
                    Làm mới
                </Button>
            </div>

            {/* BỘ LỌC TÌM KIẾM */}
            <Card
                style={{
                    background: '#121324',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderRadius: '12px',
                    marginBottom: '20px',
                }}
                bodyStyle={{ padding: '16px 20px' }}
            >
                <Row gutter={[16, 12]} align="middle">
                    <Col xs={24} md={10}>
                        <Input
                            placeholder="Tìm kiếm theo khách hàng, sản phẩm, nội dung..."
                            prefix={<SearchOutlined style={{ color: '#94a3b8' }} />}
                            value={searchText}
                            onChange={(e) => setSearchText(e.target.value)}
                            onPressEnter={() => fetchReviews(1)}
                            allowClear
                        />
                    </Col>
                    <Col xs={12} md={5}>
                        <Select
                            placeholder="Lọc trạng thái"
                            value={statusFilter || undefined}
                            onChange={(val) => setStatusFilter(val || '')}
                            allowClear
                            style={{ width: '100%' }}
                        >
                            <Option value="APPROVED">Đã duyệt (Hiện)</Option>
                            <Option value="HIDDEN">Đã ẩn</Option>
                        </Select>
                    </Col>
                    <Col xs={12} md={5}>
                        <Select
                            placeholder="Lọc số sao"
                            value={ratingFilter || undefined}
                            onChange={(val) => setRatingFilter(val || '')}
                            allowClear
                            style={{ width: '100%' }}
                        >
                            <Option value="5">⭐⭐⭐⭐⭐ (5 sao)</Option>
                            <Option value="4">⭐⭐⭐⭐ (4 sao)</Option>
                            <Option value="3">⭐⭐⭐ (3 sao)</Option>
                            <Option value="2">⭐⭐ (2 sao)</Option>
                            <Option value="1">⭐ (1 sao)</Option>
                        </Select>
                    </Col>
                    <Col xs={24} md={4} style={{ textAlign: 'right' }}>
                        <Button
                            type="primary"
                            icon={<SearchOutlined />}
                            onClick={() => fetchReviews(1)}
                            style={{ fontWeight: 600, width: '100%' }}
                        >
                            Tìm Kiếm
                        </Button>
                    </Col>
                </Row>
            </Card>

            {/* BẢNG DANH SÁCH ĐÁNH GIÁ */}
            <Card
                style={{
                    background: '#121324',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderRadius: '12px',
                }}
                bodyStyle={{ padding: 0 }}
            >
                <Table
                    columns={columns}
                    dataSource={reviews}
                    rowKey="id"
                    loading={loading}
                    pagination={{
                        current: pagination.current,
                        pageSize: pagination.pageSize,
                        total: pagination.total,
                        onChange: (page) => fetchReviews(page),
                        showTotal: (total) => `Tổng cộng ${total} đánh giá`,
                    }}
                    locale={{
                        emptyText: (
                            <div style={{ padding: '30px 0', color: '#94a3b8' }}>
                                <StarOutlined style={{ fontSize: '32px', marginBottom: '8px' }} />
                                <div>Chưa có đánh giá nào phù hợp.</div>
                            </div>
                        ),
                    }}
                />
            </Card>
        </div>
    );
};

export default AdminReviewsPage;

