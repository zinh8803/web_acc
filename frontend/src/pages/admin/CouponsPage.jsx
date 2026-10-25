import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Modal, Form, Input, InputNumber, Select, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import api from '../../services/api';
const AdminCouponsPage = () => {
    const [coupons, setCoupons] = useState([]);
    const [loading, setLoading] = useState(true);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [form] = Form.useForm();
    const fetchCoupons = async () => {
        try {
            const res = await api.get('/admin/coupons');
            if (res.data?.data)
                setCoupons(res.data.data);
        }
        catch (e) { }
        finally {
            setLoading(false);
        }
    };
    useEffect(() => {
        fetchCoupons();
    }, []);
    const handleCreate = async (values) => {
        try {
            await api.post('/admin/coupons', values);
            message.success('Tạo coupon thành công!');
            setIsModalOpen(false);
            form.resetFields();
            fetchCoupons();
        }
        catch (e) {
            message.error(e.response?.data?.message || 'Không thể tạo mã giảm giá.');
        }
    };
    const columns = [
        { title: 'Mã Code', dataIndex: 'code', key: 'code', render: (c) => _jsx(Tag, { color: "cyan", style: { fontWeight: 800 }, children: c }) },
        { title: 'Tên / Mô tả', dataIndex: 'description', key: 'description' },
        { title: 'Loại giảm', dataIndex: 'discount_type', key: 'discount_type', render: (t) => t === 'PERCENT' ? 'Phần trăm (%)' : 'Số tiền cố định' },
        { title: 'Giá trị', dataIndex: 'discount_value', key: 'discount_value', render: (v, r) => r.discount_type === 'PERCENT' ? `${v}%` : `${Number(v).toLocaleString('vi-VN')} đ` },
        { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: (s) => _jsx(Tag, { color: "green", children: s || 'ACTIVE' }) },
    ];
    return (_jsxs("div", { children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }, children: [_jsx("h1", { style: { color: '#fff', fontSize: '24px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }, children: "M\u00C3 GI\u1EA2M GI\u00C1 (COUPONS) \uD83C\uDFF7\uFE0F" }), _jsx(Button, { type: "primary", icon: _jsx(PlusOutlined, {}), onClick: () => setIsModalOpen(true), children: "T\u1EA1o M\u00E3 M\u1EDBi" })] }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: coupons, rowKey: "id", loading: loading }) }), _jsx(Modal, { title: _jsx("span", { style: { color: '#fff' }, children: "Th\u00EAm M\u00E3 Gi\u1EA3m Gi\u00E1 M\u1EDBi" }), open: isModalOpen, onCancel: () => setIsModalOpen(false), footer: null, children: _jsxs(Form, { form: form, layout: "vertical", onFinish: handleCreate, children: [_jsx(Form.Item, { name: "code", label: "M\u00E3 Code", rules: [{ required: true }], children: _jsx(Input, { placeholder: "CHAOBANMOI" }) }), _jsx(Form.Item, { name: "description", label: "M\u00F4 t\u1EA3", children: _jsx(Input, { placeholder: "Gi\u1EA3m 10% cho kh\u00E1ch h\u00E0ng m\u1EDBi" }) }), _jsx(Form.Item, { name: "discount_type", label: "Lo\u1EA1i gi\u1EA3m gi\u00E1", initialValue: "PERCENT", rules: [{ required: true }], children: _jsxs(Select, { children: [_jsx(Select.Option, { value: "PERCENT", children: "Ph\u1EA7n tr\u0103m (%)" }), _jsx(Select.Option, { value: "FIXED_AMOUNT", children: "S\u1ED1 ti\u1EC1n c\u1ED1 \u0111\u1ECBnh (\u0111)" })] }) }), _jsx(Form.Item, { name: "discount_value", label: "Gi\u00E1 tr\u1ECB gi\u1EA3m", rules: [{ required: true }], children: _jsx(InputNumber, { style: { width: '100%' }, min: 0 }) }), _jsx(Form.Item, { name: "min_order_amount", label: "\u0110\u01A1n h\u00E0ng t\u1ED1i thi\u1EC3u (\u0111)", children: _jsx(InputNumber, { style: { width: '100%' }, min: 0 }) }), _jsx(Button, { type: "primary", block: true, size: "large", htmlType: "submit", children: "L\u01B0u M\u00E3 Gi\u1EA3m Gi\u00E1" })] }) })] }));
};
export default AdminCouponsPage;
