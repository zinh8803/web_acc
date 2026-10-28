import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Button, Result, Spin, Space } from 'antd';
import api from '../services/api';
import { useCartStore } from '../stores/cartStore';

const VnPayReturnPage = () => {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const { clearCart } = useCartStore();
    const [checking, setChecking] = useState(true);
    const [paid, setPaid] = useState(false);
    const [orderCode, setOrderCode] = useState(searchParams.get('order_code') || '');

    const statusParam = searchParams.get('status');
    const responseCode = searchParams.get('vnp_ResponseCode');
    const messageParam = searchParams.get('message');

    useEffect(() => {
        const verifyPayment = async () => {
            // Nếu có status=fail hoặc responseCode != '00'
            if (statusParam === 'fail' || (responseCode && responseCode !== '00')) {
                setPaid(false);
                setChecking(false);
                return;
            }

            // Nếu status=success và có orderCode vừa được tạo từ backend callback
            if (orderCode) {
                try {
                    const response = await api.get(`/orders/${orderCode}`);
                    const order = response.data?.data;
                    setOrderCode(order?.order_code || orderCode);
                    const isSuccess = ['PAID', 'PROCESSING', 'COMPLETED'].includes(order?.status);
                    setPaid(isSuccess);
                    if (isSuccess) {
                        clearCart();
                    }
                } catch (err) {
                    // Nếu đã status=success từ VNPay checksum
                    if (statusParam === 'success') {
                        setPaid(true);
                        clearCart();
                    } else {
                        setPaid(false);
                    }
                } finally {
                    setChecking(false);
                }
                return;
            }

            // Không có mã đơn hàng và không thành công
            setPaid(false);
            setChecking(false);
        };

        verifyPayment();
    }, [orderCode, statusParam, responseCode, clearCart]);

    if (checking) {
        return (
            <div style={{ textAlign: 'center', padding: '120px 0' }}>
                <Spin size="large" />
                <p style={{ marginTop: '16px', color: '#94a3b8' }}>Đang xác thực kết quả thanh toán VNPay...</p>
            </div>
        );
    }

    return (
        <div style={{ maxWidth: 700, margin: '60px auto', padding: '0 20px' }}>
            <Result
                status={paid ? 'success' : 'error'}
                title={paid ? 'Thanh Toán VNPay Thành Công!' : 'Thanh Toán VNPay Không Thành Công'}
                subTitle={
                    paid
                        ? `Đơn hàng ${orderCode} đã được tạo và thanh toán thành công. Thông tin đăng nhập tài khoản đã sẵn sàng.`
                        : (messageParam || `Giao dịch đã bị huỷ hoặc thanh toán không thành công (Mã VNPay: ${responseCode || 'Huỷ'}). Đơn hàng chưa được tạo và bạn chưa bị trừ tiền.`)
                }
                extra={
                    paid ? (
                        <Button type="primary" size="large" onClick={() => navigate(`/orders/${orderCode}`)}>
                            Xem Tài Khoản & Đơn Hàng Ngay
                        </Button>
                    ) : (
                        <Space size="middle">
                            <Button type="primary" onClick={() => navigate('/checkout')}>
                                Thử Thanh Toán Lại
                            </Button>
                            <Button onClick={() => navigate('/cart')}>
                                Về Giỏ Hàng
                            </Button>
                        </Space>
                    )
                }
            />
        </div>
    );
};

export default VnPayReturnPage;
