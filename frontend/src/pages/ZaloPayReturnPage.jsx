import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Button, Result, Spin } from 'antd';
import api from '../services/api';

const ZaloPayReturnPage = () => {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const orderCode = searchParams.get('order_code') || '';
    const [status, setStatus] = useState('checking');

    useEffect(() => {
        if (!orderCode) {
            setStatus('failed');
            return undefined;
        }
        let attempts = 0;
        let timer;
        let active = true;
        const checkOrder = async () => {
            attempts += 1;
            try {
                const response = await api.get(`/orders/${orderCode}`);
                const orderStatus = response.data?.data?.status;
                if (['PAID', 'PROCESSING', 'COMPLETED'].includes(orderStatus)) {
                    if (active) setStatus('paid');
                    return;
                }
            } catch {
                if (attempts >= 10 && active) setStatus('failed');
            }
            if (attempts >= 10) {
                if (active) setStatus('pending');
                return;
            }
            timer = setTimeout(checkOrder, 3000);
        };
        checkOrder();
        return () => { active = false; clearTimeout(timer); };
    }, [orderCode]);

    if (status === 'checking') return <div style={{ textAlign: 'center', padding: '120px 0' }}><Spin size="large" /></div>;
    if (status === 'paid') return <div style={{ maxWidth: 700, margin: '60px auto', padding: '0 20px' }}><Result status="success" title="Thanh toán ZaloPay thành công" subTitle={`Đơn hàng ${orderCode} đã được xác nhận.`} extra={<Button type="primary" onClick={() => navigate(`/orders/${orderCode}`)}>Xem đơn hàng</Button>} /></div>;
    return <div style={{ maxWidth: 700, margin: '60px auto', padding: '0 20px' }}><Result status={status === 'pending' ? 'info' : 'error'} title={status === 'pending' ? 'Đang chờ ZaloPay xác nhận' : 'Chưa xác nhận được giao dịch'} subTitle={`Đơn hàng ${orderCode || 'không xác định'} chưa được hệ thống xác nhận thanh toán.`} extra={<Button onClick={() => orderCode ? navigate(`/payment/${orderCode}`) : navigate('/orders')}>{orderCode ? 'Thử thanh toán lại' : 'Xem đơn hàng'}</Button>} /></div>;
};

export default ZaloPayReturnPage;
