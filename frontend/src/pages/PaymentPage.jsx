import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Button, Spin, Result, message } from 'antd';
import api from '../services/api';

const PaymentPage = () => {
    const { orderCode } = useParams();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [isPaid, setIsPaid] = useState(false);
    const returnResult = new URLSearchParams(window.location.search).get('vnp_result');

    const fetchPaymentInfo = async () => {
        try {
            const res = await api.get(`/orders/${orderCode}/payments`);
            if (res.data?.success) {
                // Tự động chuyển đến cổng thanh toán VNPay
                if (!returnResult) {
                    const payment = await api.post(`/orders/${orderCode}/payments/vnpay`);
                    const paymentUrl = payment.data?.data?.payment_url || payment.data?.payment_url;
                    if (!paymentUrl) {
                        throw new Error(payment.data?.message || 'Không thể tạo liên kết thanh toán VNPay.');
                    }
                    window.location.assign(paymentUrl);
                }
            }
        } catch (err) {
            message.error(err.response?.data?.message || err.message || 'Không thể tạo yêu cầu thanh toán VNPay.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (returnResult === 'success') {
            setIsPaid(true);
            setLoading(false);
            const timer = setTimeout(() => navigate(`/orders/${orderCode}`), 1500);
            return () => clearTimeout(timer);
        }
        if (orderCode) {
            fetchPaymentInfo();
            // Polling kiểm tra trạng thái đơn
            const timer = setInterval(async () => {
                try {
                    const res = await api.get(`/orders/${orderCode}`);
                    if (res.data?.success) {
                        const status = res.data.data.status;
                        if (status === 'completed' || status === 'paid' || status === 'COMPLETED' || status === 'PAID') {
                            setIsPaid(true);
                            clearInterval(timer);
                            message.success('Thanh toán thành công! Đang chuyển đến trang chi tiết tài khoản...');
                            setTimeout(() => {
                                navigate(`/orders/${orderCode}`);
                            }, 1500);
                        }
                    }
                } catch (e) {
                    // ignore
                }
            }, 4000);
            return () => clearInterval(timer);
        }
    }, [orderCode, returnResult, navigate]);

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: '120px 0' }}>
                <Spin size="large" />
                <div style={{ color: '#94a3b8', marginTop: '16px' }}>Đang kết nối cổng thanh toán VNPay...</div>
            </div>
        );
    }

    if (isPaid) {
        return (
            <div style={{ maxWidth: '600px', margin: '60px auto', padding: '0 20px' }}>
                <Result
                    status="success"
                    title={<span style={{ color: '#fff' }}>Thanh Toán Thành Công!</span>}
                    subTitle={<span style={{ color: '#94a3b8' }}>Đơn hàng {orderCode} đã hoàn tất. Bạn có thể xem thông tin tài khoản hoặc dịch vụ ngay bây giờ.</span>}
                    extra={[
                        <Button type="primary" key="order" onClick={() => navigate(`/orders/${orderCode}`)}>
                            Xem Đơn Hàng Ngay
                        </Button>,
                    ]}
                />
            </div>
        );
    }

    if (returnResult === 'fail') {
        return (
            <Result
                status="error"
                title="Thanh toán VNPay chưa thành công"
                subTitle="Giao dịch chưa được xác nhận hoặc đã bị hủy. Bạn có thể thử lại."
                extra={
                    <Button
                        type="primary"
                        onClick={async () => {
                            try {
                                const res = await api.post(`/orders/${orderCode}/payments/vnpay`);
                                window.location.assign(res.data.data.payment_url);
                            } catch (error) {
                                message.error(error.response?.data?.message || 'Không thể tạo giao dịch VNPay.');
                            }
                        }}
                    >
                        Thử thanh toán lại qua VNPay
                    </Button>
                }
            />
        );
    }

    return (
        <Result
            status="info"
            title="Đang chuyển hướng sang cổng thanh toán VNPay..."
            subTitle="Hệ thống đang mở cổng thanh toán bảo mật VNPay."
            extra={
                <Button type="primary" onClick={() => navigate('/orders')}>
                    Xem danh sách đơn hàng
                </Button>
            }
        />
    );
};

export default PaymentPage;
