import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Button, Result, Spin } from 'antd';
import api from '../services/api';

const VnPayReturnPage = () => {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const [checking, setChecking] = useState(true);
    const [paid, setPaid] = useState(false);
    const [orderCode, setOrderCode] = useState(searchParams.get('order_code') || '');

    useEffect(() => {
        const checkOrder = async () => {
            if (!orderCode) {
                setChecking(false);
                return;
            }
            try {
                const response = await api.get(`/orders/${orderCode}`);
                const order = response.data?.data;
                setOrderCode(order?.order_code || orderCode);
                setPaid(['PAID', 'PROCESSING', 'COMPLETED'].includes(order?.status));
            } catch {
                setPaid(false);
            } finally {
                setChecking(false);
            }
        };
        checkOrder();
    }, [orderCode]);

    if (checking) return <div style={{ textAlign: 'center', padding: '120px 0' }}><Spin size="large" /></div>;

    return <div style={{ maxWidth: 700, margin: '60px auto', padding: '0 20px' }}>
        <Result
            status={paid ? 'success' : 'error'}
            title={paid ? 'Thanh toán VNPay thành công' : 'Thanh toán VNPay chưa thành công'}
            subTitle={paid
                ? `Đơn hàng ${orderCode} đã được xác nhận.`
                : `Đơn hàng ${orderCode || 'không xác định'} vẫn chưa được xác nhận. Mã phản hồi VNPay: ${searchParams.get('vnp_ResponseCode') || 'không có'}.`}
            extra={paid
                ? <Button type="primary" onClick={() => navigate(`/orders/${orderCode}`)}>Xem đơn hàng</Button>
                : <Button onClick={() => orderCode ? navigate(`/payment/${orderCode}`) : navigate('/orders')}>{orderCode ? 'Thử thanh toán lại' : 'Xem đơn hàng'}</Button>}
        />
    </div>;
};

export default VnPayReturnPage;
