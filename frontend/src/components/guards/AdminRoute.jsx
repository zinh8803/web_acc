import { jsx as _jsx, Fragment as _Fragment } from "react/jsx-runtime";
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
const AdminRoute = ({ children }) => {
    const location = useLocation();
    const adminToken = localStorage.getItem('admin_token') || localStorage.getItem('admin_access_token');
    if (!adminToken) {
        return _jsx(Navigate, { to: "/login", state: { from: location.pathname }, replace: true });
    }
    return _jsx(_Fragment, { children: children });
};
export default AdminRoute;
