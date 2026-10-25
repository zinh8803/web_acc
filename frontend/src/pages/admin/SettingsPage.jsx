import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Form, Input, Button, message, Spin } from 'antd';
import api from '../../services/api';
const AdminSettingsPage = () => {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        const fetchSettings = async () => {
            try {
                const res = await api.get('/admin/settings');
                if (res.data?.data && Array.isArray(res.data.data)) {
                    const general = res.data.data.find((s) => s.key === 'general_settings');
                    if (general && general.value) {
                        const val = typeof general.value === 'string' ? JSON.parse(general.value) : general.value;
                        form.setFieldsValue(val);
                    }
                }
            }
            catch (e) {
                console.error('Failed to load settings', e);
            }
            finally {
                setLoading(false);
            }
        };
        fetchSettings();
    }, [form]);
    const onFinish = async (values) => {
        try {
            await api.put('/admin/settings', {
                settings: {
                    general_settings: values,
                },
            });
            message.success('Đã lưu cấu hình vào cơ sở dữ liệu thành công!');
        }
        catch (e) {
            message.error('Không thể lưu cấu hình.');
        }
    };
    return (_jsxs("div", { style: { maxWidth: '800px' }, children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', marginBottom: '24px' }, children: "C\u1EA4U H\u00CCNH H\u1EC6 TH\u1ED0NG & THANH TO\u00C1N (DB) \u2699\uFE0F" }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '32px', border: '1px solid rgba(255,255,255,0.08)' }, children: loading ? (_jsx("div", { style: { textAlign: 'center', padding: '40px' }, children: _jsx(Spin, {}) })) : (_jsxs(Form, { form: form, layout: "vertical", onFinish: onFinish, children: [_jsx(Form.Item, { name: "site_name", label: "T\u00EAn website", children: _jsx(Input, {}) }), _jsx(Form.Item, { name: "hotline", label: "Hotline CSKH", children: _jsx(Input, {}) }), _jsx(Form.Item, { name: "zalo", label: "S\u1ED1 Zalo h\u1ED7 tr\u1EE3", children: _jsx(Input, {}) }), _jsx(Form.Item, { name: "telegram", label: "Telegram h\u1ED7 tr\u1EE3", children: _jsx(Input, {}) }), _jsx(Button, { type: "primary", size: "large", htmlType: "submit", style: { fontWeight: 700, marginTop: '10px' }, children: "L\u01B0u V\u00E0o Database" })] })) })] }));
};
export default AdminSettingsPage;
