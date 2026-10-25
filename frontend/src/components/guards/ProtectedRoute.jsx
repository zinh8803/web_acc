import { jsx as _jsx } from "react/jsx-runtime";
import React from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuthStore } from '../../stores/authStore';
const ProtectedRoute = () => {
    const { isAuthenticated } = useAuthStore();
    const location = useLocation();
    if (!isAuthenticated) {
        return _jsx(Navigate, { to: "/login", state: { from: location.pathname }, replace: true });
    }
    return _jsx(Outlet, {});
};
export default ProtectedRoute;
