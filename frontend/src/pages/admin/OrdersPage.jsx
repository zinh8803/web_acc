import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Table, Tag, Select, Input, message, Button, Space } from 'antd';
import { EyeOutlined, ReloadOutlined } from '@ant-design/icons';
import dayjs from 'dayjs';
import api from '../../services/api';

const AdminOrdersPage = () => {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState(undefined);

    const fetchOrders = async (s = search, st = statusFilter) => {
        setLoading(true);
        try {
            const params = {};
            if (s) params.search = s;
            if (st) params.status = st;
            const res = await api.get('/admin/orders', { params });
            const list = res.data?.data?.data || res.data?.data || [];
            setOrders(Array.isArray(list) ? list : []);
        } catch (e) {
            console.error('Fetch orders error:', e);
            message.error('Không thể tải danh sách đơn hàng.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchOrders();
    }, []);

    const handleUpdateStatus = async (code, status) => {
        try {
            await api.patch(`/admin/orders/${code}/status`, { status });
            message.success('Đã cập nhật trạng thái đơn hàng!');
            fetchOrders();
        } catch (e) {
            message.error('Không thể cập nhật trạng thái.');
        }
    };

    const columns = [
        {
            title: 'Mã đơn',
            dataIndex: 'order_code',
            key: 'order_code',
            render: (c) => <Tag color="cyan" style={{ fontWeight: 700, fontFamily: 'monospace' }}>{c}</Tag>,
        },
        {
            title: 'Khách hàng',
            dataIndex: 'user',
            key: 'user',
            render: (u) => u?.full_name || u?.email || 'Khách vãng lai',
        },
        {
            title: 'Tổng tiền',
            dataIndex: 'total',
            key: 'total',
            render: (amt) => (
                <span style={{ color: '#06b6d4', fontWeight: 800, fontFamily: 'Rajdhani', fontSize: 16 }}>
                    {Number(amt || 0).toLocaleString('vi-VN')} đ
                </span>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (st, record) => (
                <Select
                    value={st?.toUpperCase()}
                    size="small"
                    style={{ width: 160 }}
                    onChange={(val) => handleUpdateStatus(record.order_code, val)}
                >
                    <Select.Option value="PENDING_PAYMENT">Chờ thanh toán</Select.Option>
                    <Select.Option value="PAID">Đã thanh toán</Select.Option>
                    <Select.Option value="PROCESSING">Đang xử lý</Select.Option>
                    <Select.Option value="COMPLETED">Hoàn tất / Bàn giao</Select.Option>
                    <Select.Option value="CANCELLED">Huỷ đơn</Select.Option>
                </Select>
            ),
        },
        {
            title: 'Ngày đặt',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (d) => dayjs(d).format('DD/MM/YYYY HH:mm'),
        },
        {
            title: 'Hành động',
            key: 'action',
            render: (_, record) => (
                <Link to={`/admin/orders/${record.order_code}`}>
                    <Button type="primary" size="small" icon={<EyeOutlined />}>
                        Xem Chi Tiết
                    </Button>
                </Link>
            ),
        },
    ];

    return (
        <div>
            <h1 style={{ color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }}>
                QUẢN LÝ ĐƠN HÀNG & GIAO DỊCH 🧾
            </h1>
            <div style={{ background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }}>
                <div style={{ display: 'flex', gap: '12px', marginBottom: '16px', flexWrap: 'wrap' }}>
                    <Input.Search
                        placeholder="Tìm theo mã đơn (ORD-...)..."
                        style={{ width: 280 }}
                        allowClear
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onSearch={(val) => {
                            setSearch(val);
                            fetchOrders(val, statusFilter);
                        }}
                    />
                    <Select
                        placeholder="Lọc theo trạng thái"
                        style={{ width: 180 }}
                        allowClear
                        value={statusFilter}
                        onChange={(val) => {
                            setStatusFilter(val);
                            fetchOrders(search, val);
                        }}
                    >
                        <Select.Option value="PENDING_PAYMENT">Chờ thanh toán</Select.Option>
                        <Select.Option value="PAID">Đã thanh toán</Select.Option>
                        <Select.Option value="PROCESSING">Đang xử lý</Select.Option>
                        <Select.Option value="COMPLETED">Hoàn tất / Bàn giao</Select.Option>
                        <Select.Option value="CANCELLED">Huỷ đơn</Select.Option>
                    </Select>
                    <Button
                        icon={<ReloadOutlined />}
                        onClick={() => {
                            setSearch('');
                            setStatusFilter(undefined);
                            fetchOrders('', undefined);
                        }}
                    >
                        Làm mới
                    </Button>
                </div>
                <Table columns={columns} dataSource={orders} rowKey="id" loading={loading} />
            </div>
        </div>
    );
};

export default AdminOrdersPage;
