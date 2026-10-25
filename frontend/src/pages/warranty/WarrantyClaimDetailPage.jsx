import { jsxs as _jsxs, jsx as _jsx } from "react/jsx-runtime";
import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
const WarrantyClaimDetailPage = () => {
    const { code } = useParams();
    return (_jsx("div", { style: { maxWidth: '800px', margin: '40px auto', padding: '0 20px' }, children: _jsxs("div", { style: { background: '#121324', borderRadius: '16px', padding: '32px', border: '1px solid rgba(255,255,255,0.08)' }, children: [_jsxs("h2", { style: { color: '#fff', marginBottom: '16px' }, children: ["Chi ti\u1EBFt khi\u1EBFu n\u1EA1i ", code] }), _jsx("p", { style: { color: '#94a3b8' }, children: "K\u1EF9 thu\u1EADt vi\u00EAn \u0111ang xem x\u00E9t v\u00E0 ph\u1EA3n h\u1ED3i trong th\u1EDDi gian s\u1EDBm nh\u1EA5t." }), _jsx(Link, { to: "/warranty-claims", children: _jsx(Button, { icon: _jsx(ArrowLeftOutlined, {}), style: { marginTop: '16px' }, children: "Quay l\u1EA1i danh s\u00E1ch" }) })] }) }));
};
export default WarrantyClaimDetailPage;
