import React, { useEffect, useState } from 'react';
import { Form, Input, Button, message, Spin, Tabs, Alert } from 'antd';
import { SettingOutlined, SafetyCertificateOutlined, SaveOutlined } from '@ant-design/icons';
import api from '../../services/api';
import { useSettingsStore } from '../../stores/settingsStore';

const AdminSettingsPage = () => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        const fetchSettings = async () => {
            try {
                const res = await api.get('/admin/settings');
                const list = res.data?.data || res.data?.du_lieu;
                if (Array.isArray(list)) {
                    let combined = {};
                    const general = list.find((s) => s.key === 'general_settings');
                    if (general && general.value) {
                        const val = typeof general.value === 'string' ? JSON.parse(general.value) : general.value;
                        combined = { ...combined, ...val };
                    }
                    const policies = list.find((s) => s.key === 'policy_settings');
                    if (policies && policies.value) {
                        const val = typeof policies.value === 'string' ? JSON.parse(policies.value) : policies.value;
                        combined = { ...combined, ...val };
                    }
                    form.setFieldsValue(combined);
                }
            } catch (e) {
                console.error('Failed to load settings', e);
            } finally {
                setLoading(false);
            }
        };
        fetchSettings();
    }, [form]);

    const onFinish = async (values) => {
        setSubmitting(true);
        try {
            const generalValues = {
                site_name: values.site_name,
                hotline: values.hotline,
                zalo: values.zalo,
                telegram: values.telegram,
            };
            const policyValues = {
                terms_of_service: values.terms_of_service,
                warranty_terms: values.warranty_terms,
                privacy_policy: values.privacy_policy,
            };

            await api.put('/admin/settings', {
                settings: {
                    general_settings: generalValues,
                    policy_settings: policyValues,
                },
            });

            useSettingsStore.getState().setSettings({
                general_settings: generalValues,
                policy_settings: policyValues,
            });

            message.success('Đã lưu cấu hình và chính sách sử dụng thành công!');
        } catch (e) {
            message.error(e.response?.data?.message || 'Không thể lưu cấu hình.');
        } finally {
            setSubmitting(false);
        }
    };

    const tabItems = [
        {
            key: 'general',
            label: (
                <span>
                    <SettingOutlined /> Cấu Hình Chung & Liên Hệ
                </span>
            ),
            children: (
                <div>
                    <Form.Item
                        name="site_name"
                        label="Tên Website / Thương Hiệu (Hiển thị Header, Footer, Admin, Tiêu đề)"
                        rules={[{ required: true, message: 'Vui lòng nhập tên website / thương hiệu!' }]}
                    >
                        <Input placeholder="VD: GAMEHUB2003" size="large" />
                    </Form.Item>
                    <Form.Item name="hotline" label="Hotline CSKH">
                        <Input placeholder="VD: 0912.345.678" size="large" />
                    </Form.Item>
                    <Form.Item name="zalo" label="Số Zalo hỗ trợ">
                        <Input placeholder="VD: 0912.345.678" size="large" />
                    </Form.Item>
                    <Form.Item name="telegram" label="Telegram hỗ trợ">
                        <Input placeholder="VD: @cskh_gamehub" size="large" />
                    </Form.Item>
                </div>
            ),
        },
        {
            key: 'policies',
            label: (
                <span>
                    <SafetyCertificateOutlined /> Chính Sách Sử Dụng & Điều Khoản
                </span>
            ),
            children: (
                <div>
                    <Alert
                        type="info"
                        showIcon
                        message="Chính Sách & Điều Khoản Sử Dụng"
                        description="Nội dung bạn nhập tại đây sẽ được hiển thị công khai cho khách hàng khi truy cập trang Điều khoản & Chính sách sử dụng dịch vụ tại chân trang website."
                        style={{ marginBottom: '20px' }}
                    />
                    <Form.Item
                        name="terms_of_service"
                        label="Điều Khoản & Chính Sách Sử Dụng Dịch Vụ (Hiển thị tại trang /terms)"
                    >
                        <Input.TextArea
                            rows={8}
                            placeholder="Nhập nội dung quy định sử dụng, quyền và nghĩa vụ của người mua khi giao dịch tài khoản / dịch vụ tại website..."
                        />
                    </Form.Item>
                    <Form.Item
                        name="warranty_terms"
                        label="Quy Định Bảo Hành & Cam Kết Đổi Trả Chung"
                    >
                        <Input.TextArea
                            rows={6}
                            placeholder="Nhập quy định đổi tài khoản mới khi lỗi pass, lỗi thông tin, thời hạn xử lý khiếu nại..."
                        />
                    </Form.Item>
                    <Form.Item
                        name="privacy_policy"
                        label="Chính Sách Bảo Mật Thông Tin Khách Hàng"
                    >
                        <Input.TextArea
                            rows={5}
                            placeholder="Cam kết không chia sẻ dữ liệu cá nhân, mật khẩu game của khách hàng cho bên thứ ba..."
                        />
                    </Form.Item>
                </div>
            ),
        },
    ];

    return (
        <div style={{ maxWidth: '900px' }}>
            <h1
                style={{
                    color: '#fff',
                    fontSize: '24px',
                    fontWeight: 800,
                    fontFamily: 'Rajdhani',
                    marginBottom: '24px',
                }}
            >
                CẤU HÌNH HỆ THỐNG & CHÍNH SÁCH
            </h1>
            <div
                style={{
                    background: '#121324',
                    borderRadius: '14px',
                    padding: '32px',
                    border: '1px solid rgba(255,255,255,0.08)',
                }}
            >
                {loading ? (
                    <div style={{ textAlign: 'center', padding: '40px' }}>
                        <Spin />
                    </div>
                ) : (
                    <Form form={form} layout="vertical" onFinish={onFinish}>
                        <Tabs defaultActiveKey="general" items={tabItems} />
                        <Button
                            type="primary"
                            size="large"
                            htmlType="submit"
                            icon={<SaveOutlined />}
                            loading={submitting}
                            style={{ fontWeight: 700, marginTop: '20px' }}
                        >
                            Lưu Cấu Hình & Chính Sách
                        </Button>
                    </Form>
                )}
            </div>
        </div>
    );
};

export default AdminSettingsPage;
