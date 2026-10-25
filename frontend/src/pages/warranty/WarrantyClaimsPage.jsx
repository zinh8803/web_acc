import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useEffect, useState } from 'react';
import { Table, Tag, Button, Modal, Form, Input, Select, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import api from '../../services/api';
const WarrantyClaimsPage = () => {
    const [claims, setClaims] = useState([]);
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [form] = Form.useForm();
    const fetchClaims = async () => {
        try {
            const res = await api.get('/warranty-claims');
            const list = res.data?.data?.data || res.data?.data || [];
            setClaims(Array.isArray(list) ? list : []);
        }
        catch (e) {
            console.error(e);
        }
        finally {
            setLoading(false);
        }
    };
    const fetchPaidOrders = async () => {
        try {
            const res = await api.get('/orders');
            const list = res.data?.data?.data || res.data?.data || [];
            const paid = list.filter((o) => ['completed', 'paid', 'COMPLETED', 'PAID'].includes(o.status));
            setOrders(paid);
        }
        catch (e) { }
    };
    useEffect(() => {
        fetchClaims();
        fetchPaidOrders();
    }, []);
    const handleCreateClaim = async (values) => {
        try {
            const res = await api.post('/warranty-claims', {
                order_item_id: values.order_item_id,
                reason: values.reason,
                description: values.description,
            });
            if (res.data?.success) {
                message.success('Đã gửi yêu cầu bảo hành thành công!');
                setIsModalOpen(false);
                form.resetFields();
                fetchClaims();
            }
        }
        catch (err) {
            message.error(err.response?.data?.message || 'Không thể gửi khiếu nại bảo hành.');
        }
    };
    const columns = [
        {
            title: 'Mã khiếu nại',
            dataIndex: 'claim_code',
            key: 'claim_code',
            render: (code) => _jsx(Tag, { color: "orange", style: { fontWeight: 700 }, children: code }),
        },
        {
            title: 'Lý do',
            dataIndex: 'reason',
            key: 'reason',
            render: (r) => _jsx("span", { style: { color: '#fff', fontWeight: 600 }, children: r }),
        },
        {
            title: 'Mô tả chi tiết',
            dataIndex: 'description',
            key: 'description',
            ellipsis: true,
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (st) => {
                switch (st?.toLowerCase()) {
                    case 'resolved':
                    case 'approved':
                        return _jsx(Tag, { color: "success", children: "\u0110\u00C3 X\u1EEC L\u00DD / \u0110\u1ED4I M\u1EDAI" });
                    case 'rejected':
                        return _jsx(Tag, { color: "error", children: "T\u1EEA CH\u1ED0I" });
                    default:
                        return _jsx(Tag, { color: "processing", children: "\u0110ANG X\u1EEC L\u00DD (SLA 24H)" });
                }
            },
        },
        {
            title: 'Ngày gửi',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (d) => new Date(d).toLocaleDateString('vi-VN'),
        },
    ];
    return (_jsxs("div", { style: { maxWidth: '1100px', margin: '0 auto', padding: '36px 20px 60px' }, children: [_jsxs("div", { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }, children: [_jsxs("div", { children: [_jsx("h1", { style: { color: '#fff', fontSize: '28px', fontWeight: 800, fontFamily: 'Rajdhani', margin: 0 }, children: "TRUNG T\u00C2M B\u1EA2O H\u00C0NH & KHI\u1EBEU N\u1EA0I \uD83D\uDEE1\uFE0F" }), _jsx("p", { style: { color: '#94a3b8', fontSize: '14px', marginTop: '4px' }, children: "Cam k\u1EBFt gi\u1EA3i quy\u1EBFt trong 24h. B\u1EA3o h\u00E0nh 1 \u0111\u1ED5i 1 ho\u1EB7c ho\u00E0n ti\u1EC1n n\u1EBFu acc b\u1ECB l\u1ED7i, sai pass, back nick." })] }), _jsx(Button, { type: "primary", icon: _jsx(PlusOutlined, {}), onClick: () => setIsModalOpen(true), style: { height: '42px', fontWeight: 700, borderRadius: '8px' }, children: "G\u1EEDi Y\u00EAu C\u1EA7u B\u1EA3o H\u00E0nh" })] }), _jsx("div", { style: { background: '#121324', borderRadius: '14px', padding: '20px', border: '1px solid rgba(255,255,255,0.08)' }, children: _jsx(Table, { columns: columns, dataSource: claims, rowKey: "id", loading: loading, pagination: { pageSize: 8 } }) }), _jsx(Modal, { title: _jsx("span", { style: { color: '#fff' }, children: "G\u1EEDi Y\u00EAu C\u1EA7u Khi\u1EBFu N\u1EA1i B\u1EA3o H\u00E0nh" }), open: isModalOpen, onCancel: () => setIsModalOpen(false), footer: null, children: _jsxs(Form, { form: form, layout: "vertical", onFinish: handleCreateClaim, children: [_jsx(Form.Item, { name: "order_item_id", label: "Ch\u1ECDn s\u1EA3n ph\u1EA9m trong \u0111\u01A1n \u0111\u00E3 mua", rules: [{ required: true, message: 'Vui lòng chọn sản phẩm cần bảo hành!' }], children: _jsx(Select, { placeholder: "Ch\u1ECDn s\u1EA3n ph\u1EA9m", children: orders.flatMap((o) => (o.items || []).map((i) => (_jsxs(Select.Option, { value: i.id, children: [o.order_code, " - ", i.product_name || 'Tài khoản'] }, i.id)))) }) }), _jsx(Form.Item, { name: "reason", label: "L\u00FD do b\u1EA3o h\u00E0nh", rules: [{ required: true, message: 'Vui lòng chọn lý do!' }], children: _jsxs(Select, { placeholder: "Ch\u1ECDn l\u00FD do", children: [_jsx(Select.Option, { value: "Sai m\u1EADt kh\u1EA9u ho\u1EB7c t\u00E0i kho\u1EA3n", children: "Sai m\u1EADt kh\u1EA9u ho\u1EB7c t\u00E0i kho\u1EA3n" }), _jsx(Select.Option, { value: "B\u1ECB ch\u1EE7 c\u0169 \u0111\u1ED5i th\u00F4ng tin (Back nick)", children: "B\u1ECB ch\u1EE7 c\u0169 \u0111\u1ED5i th\u00F4ng tin (Back nick)" }), _jsx(Select.Option, { value: "Thi\u1EBFu t\u01B0\u1EDBng/skin so v\u1EDBi m\u00F4 t\u1EA3", children: "Thi\u1EBFu t\u01B0\u1EDBng/skin so v\u1EDBi m\u00F4 t\u1EA3" }), _jsx(Select.Option, { value: "Kh\u00E1c", children: "L\u00FD do kh\u00E1c" })] }) }), _jsx(Form.Item, { name: "description", label: "M\u00F4 t\u1EA3 chi ti\u1EBFt s\u1EF1 c\u1ED1", rules: [{ required: true, message: 'Vui lòng mô tả chi tiết!' }], children: _jsx(Input.TextArea, { rows: 4, placeholder: "M\u00F4 t\u1EA3 c\u1EE5 th\u1EC3 th\u1EDDi gian g\u1EB7p l\u1ED7i v\u00E0 t\u00ECnh tr\u1EA1ng t\u00E0i kho\u1EA3n..." }) }), _jsx(Button, { type: "primary", block: true, htmlType: "submit", size: "large", style: { fontWeight: 700 }, children: "G\u1EEDi Y\u00EAu C\u1EA7u Cho \u0110\u1ED9i K\u1EF9 Thu\u1EADt" })] }) })] }));
};
export default WarrantyClaimsPage;
