import React, { useEffect, useState } from 'react';
import { Card, Typography, Tabs, Spin, Breadcrumb } from 'antd';
import { FileTextOutlined, SafetyCertificateOutlined, LockOutlined, HomeOutlined } from '@ant-design/icons';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { useSettingsStore } from '../stores/settingsStore';

const { Title, Paragraph } = Typography;

const TermsPage = () => {
    const { siteName } = useSettingsStore();
    const [loading, setLoading] = useState(true);
    const [policySettings, setPolicySettings] = useState(null);

    useEffect(() => {
        const fetchPolicies = async () => {
            try {
                const res = await api.get('/settings/public');
                const data = res.data?.data || res.data?.du_lieu || {};
                setPolicySettings(data.policy_settings || {});
            } catch (err) {
                console.error('Failed to load terms:', err);
            } finally {
                setLoading(false);
            }
        };
        fetchPolicies();
    }, []);

    const defaultTerms = `1. QUY ĐỊNH CHUNG
- Chúng tôi cung cấp các tài khoản game bản quyền và dịch vụ hỗ trợ game uy tín.
- Người mua có trách nhiệm tự bảo mật thông tin tài khoản sau khi nhận bàn giao (đổi mật khẩu, liên kết email/SĐT bảo mật cá nhân).
- Mọi hành vi cố ý gian lận, sử dụng hack cheat hoặc chia sẻ tài khoản cho bên thứ ba sẽ bị từ chối mọi hỗ trợ.

2. QUYỀN LỢI CỦA KHÁCH HÀNG
- Được nhận đầy đủ thông tin tài khoản hoặc dịch vụ đúng theo mô tả chi tiết tại sản phẩm.
- Được hỗ trợ kỹ thuật và bảo hành theo chính sách bảo hành quy định của từng sản phẩm.`;

    const defaultWarranty = `1. ĐIỀU KIỆN ÁP DỤNG BẢO HÀNH
- Tài khoản lỗi sai mật khẩu, sai thông tin ngay tại thời điểm nhận bàn giao.
- Tài khoản bị tranh chấp hoặc lỗi phát sinh từ phía người bán trong thời hạn bảo hành.

2. THỜI GIAN XỬ LÝ KHIẾU NẠI
- Tiếp nhận và xử lý yêu cầu khiếu nại bảo hành trong vòng 24 giờ kể từ khi khách hàng tạo yêu cầu tại mục Bảo Hành.
- Phương án: Đổi tài khoản tương đương hoặc hoàn tiền theo quy định.`;

    const defaultPrivacy = `1. BẢO MẬT DỮ LIỆU CÁ NHÂN
- Cam kết không tiết lộ email, số điện thoại hoặc lịch sử giao dịch của khách hàng cho bất kỳ bên thứ ba nào.
- Thông tin đăng nhập cung cấp khi đặt dịch vụ cày thuê được bảo mật và xóa bỏ ngay sau khi hoàn thành bàn giao.`;

    const items = [
        {
            key: 'terms',
            label: (
                <span>
                    <FileTextOutlined /> Điều Khoản & Chính Sách Sử Dụng
                </span>
            ),
            children: (
                <div style={{ color: '#cbd5e1', lineHeight: 1.8 }}>
                    <Title level={4} style={{ color: '#fff' }}>
                        Điều Khoản Sử Dụng Dịch Vụ
                    </Title>
                    <Paragraph style={{ color: '#cbd5e1', whiteSpace: 'pre-line', fontSize: '15px' }}>
                        {policySettings?.terms_of_service || defaultTerms}
                    </Paragraph>
                </div>
            ),
        },
        {
            key: 'warranty',
            label: (
                <span>
                    <SafetyCertificateOutlined /> Quy Định Bảo Hành & Hoàn Tiền
                </span>
            ),
            children: (
                <div style={{ color: '#cbd5e1', lineHeight: 1.8 }}>
                    <Title level={4} style={{ color: '#fff' }}>
                        Chính Sách Bảo Hành Chung
                    </Title>
                    <Paragraph style={{ color: '#cbd5e1', whiteSpace: 'pre-line', fontSize: '15px' }}>
                        {policySettings?.warranty_terms || defaultWarranty}
                    </Paragraph>
                </div>
            ),
        },
        {
            key: 'privacy',
            label: (
                <span>
                    <LockOutlined /> Chính Sách Bảo Mật
                </span>
            ),
            children: (
                <div style={{ color: '#cbd5e1', lineHeight: 1.8 }}>
                    <Title level={4} style={{ color: '#fff' }}>
                        Chính Sách Bảo Mật Thông Tin
                    </Title>
                    <Paragraph style={{ color: '#cbd5e1', whiteSpace: 'pre-line', fontSize: '15px' }}>
                        {policySettings?.privacy_policy || defaultPrivacy}
                    </Paragraph>
                </div>
            ),
        },
    ];

    return (
        <div style={{ maxWidth: '1000px', margin: '0 auto', padding: '24px 20px 60px' }}>
            <Breadcrumb
                style={{ marginBottom: '20px' }}
                items={[
                    { title: <Link to="/"><HomeOutlined /> Trang chủ</Link> },
                    { title: <span style={{ color: '#fff' }}>Chính sách & Điều khoản</span> },
                ]}
            />
            <div style={{ marginBottom: '24px' }}>
                <h1
                    style={{
                        color: '#fff',
                        fontSize: '28px',
                        fontWeight: 800,
                        fontFamily: 'Rajdhani',
                        margin: 0,
                    }}
                >
                    ĐIỀU KHOẢN VÀ CHÍNH SÁCH SỬ DỤNG
                </h1>
                <p style={{ color: '#94a3b8', fontSize: '14px', marginTop: '6px' }}>
                    Quy định minh bạch nhằm bảo đảm quyền lợi tối đa cho khách hàng khi giao dịch tại {siteName || 'hệ thống'}.
                </p>
            </div>

            <Card
                style={{
                    background: '#121324',
                    borderRadius: '16px',
                    borderColor: 'rgba(255,255,255,0.08)',
                    boxShadow: '0 10px 30px rgba(0,0,0,0.4)',
                }}
                bodyStyle={{ padding: '32px' }}
            >
                {loading ? (
                    <div style={{ textAlign: 'center', padding: '40px' }}>
                        <Spin size="large" />
                    </div>
                ) : (
                    <Tabs defaultActiveKey="terms" items={items} />
                )}
            </Card>
        </div>
    );
};

export default TermsPage;

